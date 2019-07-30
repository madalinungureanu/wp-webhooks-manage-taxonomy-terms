<?php
/**
 * Plugin Name: WP Webhooks - Manage Taxonomy Terms
 * Plugin URI: https://ironikus.com/downloads/manage-taxonomy-terms/
 * Description: A WP Webhooks and WP Webhooks Pro extension for managing taxonomy terms
 * Version: 1.0.0
 * Author: Ironikus
 * Author URI: https://ironikus.com/
 * License: GPL2
 *
 * You should have received a copy of the GNU General Public License.
 * If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_Manage_Taxonomy_Terms' ) ){

	class WP_Webhooks_Manage_Taxonomy_Terms{

		public function __construct() {

			add_action( 'wpwhpro/webhooks/add_webhooks_actions', array( $this, 'add_webhook_actions' ), 20, 3 );
			add_filter( 'wpwhpro/webhooks/get_webhooks_actions', array( $this, 'add_webhook_actions_content' ), 20 );
		}

		/**
		 * ######################
		 * ###
		 * #### WEBHOOK ACTIONS
		 * ###
		 * ######################
		 */

		/*
		 * Register all available action webhooks here
		 *
		 * This function will add your webhook to our globally registered actions array
		 * You can add a webhook by just adding a new line item here.
		 */
		public function add_webhook_actions_content( $actions ){

			$actions[] = $this->action_set_terms_content();

			return $actions;
		}

		/*
		 * Add the callback function for a defined action
		 *
		 * We call the default get_active_webhooks function to grab
		 * all of the currently activated triggers.
		 *
		 * We always send three different properties with the defined wehook.
		 * @param $action - the defined action defined within the action_delete_user_content function
		 * @param $webhook - The webhook itself
		 * @param $api_key - an api_key if defined
		 */
		public function add_webhook_actions( $action, $webhook, $api_key ){

			$active_webhooks = WPWHPRO()->settings->get_active_webhooks();

			$available_actions = $active_webhooks['actions'];

			switch( $action ){
				case 'set_terms':
					if( isset( $available_actions['set_terms'] ) ){
						$this->action_set_terms();
					}
					break;
			}
		}

		public function action_set_terms_content(){

			$parameter = array(
				'object_id'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'The object to relate to. (Post ID)', 'action-set_terms-content' ) ),
				'terms'            => array( 'short_description' => WPWHPRO()->helpers->translate( 'The terms you want to set. Please see the description for more information.', 'action-set_terms-content' ) ),
				'taxonomy'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'The context in which to relate the term to the object. (Taxonomy slug)', 'action-set_terms-content' ) ),
				'append'            => array( 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes" in case you want to append the taxonomies. If set to false, all previous entries to the defined taxonomies will be deleted. Default "no"', 'action-set_terms-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-set_terms-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-set_terms-content' ) ),
				'data'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The taxonomy term ids on success or wp_error on failure.', 'action-set_terms-content' ) ),
				'msg'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-set_terms-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
	'success' => false,
	'msg' => '',
	'data' => '',
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to apply certain taxonomy terms to a post. You can connect multiple terms to a single taxonomy within one call.', 'action-set_terms-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'To append taxonomy terms, simple separate them with a comma. You can either use a single term slug, single term id, or array of either term slugs or ids. Passing an empty value will remove all related terms.', 'action-set_terms-content' ); ?></p>
				<pre>term-1,term-2,term-3</pre>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'set_terms', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Connect certain taxonomy terms to a post.', 'action-set_terms-content' ),
				'description'       => $description
			);

		}

		public function action_set_terms() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => '',
			);

			$object_id        = intval( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'object_id' ));
			$terms        = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'terms' );
			$taxonomy        = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'taxonomy' );
			$append        = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'append' ) == 'yes' ) ? true : false;

			$do_action      = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( empty( $object_id ) || empty( $taxonomy ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Object id and/or taxonomy not defined.", 'action-set_terms' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			$term_array = explode( ',', trim( $terms, ',' ) );
			if( empty( $term_array ) ){
				$term_array = array();
			}

			$term_taxonomy_ids = wp_set_object_terms( $object_id, $term_array, $taxonomy, $append );
 
			if ( ! is_wp_error( $term_taxonomy_ids ) ) {
				$return_args['success'] = true;
				$return_args['data'] = $term_taxonomy_ids;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Taxonomy terms were set successfully.", 'action-set_terms' );
			} else {
				$return_args['data'] = $term_taxonomy_ids;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error while setting taxonomy terms", 'action-set_terms' );
			}

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $object_id, $terms, $taxonomy, $append );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

	} // End class

	function wpwhpro_load_manage_taxonomy_terms(){
		new WP_Webhooks_Manage_Taxonomy_Terms();
	}

	// Make sure we load the extension after main plugin is loaded
	if( defined( 'WPWH_SETUP' ) || defined( 'WPWHPRO_SETUP' ) ){
		wpwhpro_load_manage_taxonomy_terms();
    } else {
		add_action( 'wpwhpro_plugin_loaded', 'wpwhpro_load_manage_taxonomy_terms' );
    }

	//Throw message in case WP Webhook is not active
	add_action( 'admin_notices', 'wpwh_manage_taxonomy_terms_active', 100 );
    function wpwh_manage_taxonomy_terms_active(){

        if( ! defined( 'WPWH_SETUP' ) && ! defined( 'WPWHPRO_SETUP' ) ){

                ob_start();
                ?>
                <div class="notice notice-warning">
                    <p><?php echo sprintf( '<strong>WP Webhooks - Manage Taxonomy Terms</strong> is active, but <strong>WP Webhooks</strong> or <strong>WP Webhooks Pro</strong> isn\'t. Please activate it to use the functionality for <strong>Contact Form 7</strong>. <a href="%s" target="_blank" rel="noopener">More Info</a>', 'https://de.wordpress.org/plugins/wp-webhooks/' ); ?></p>
                </div>
                <?php
                echo ob_get_clean();

        }

    }

}