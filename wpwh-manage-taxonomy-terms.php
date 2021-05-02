<?php
/**
 * Plugin Name: WP Webhooks - Manage Taxonomy Terms
 * Plugin URI: https://ironikus.com/downloads/manage-taxonomy-terms/
 * Description: A WP Webhooks and WP Webhooks Pro extension for managing taxonomy terms
 * Version: 1.1.0
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

		private $wpmt_use_new_filter = null;

		public function __construct() {

			if( $this->wpwh_use_new_action_filter() ){
				add_filter( 'wpwhpro/webhooks/add_webhook_actions', array( $this, 'add_webhook_actions' ), 20, 4 );
			} else {
				add_action( 'wpwhpro/webhooks/add_webhooks_actions', array( $this, 'add_webhook_actions' ), 20, 3 );
			}
			add_filter( 'wpwhpro/webhooks/get_webhooks_actions', array( $this, 'add_webhook_actions_content' ), 20 );
		}

		/**
		 * ######################
		 * ###
		 * #### HELPERS
		 * ###
		 * ######################
		 */

		public function wpwh_use_new_action_filter(){

			if( $this->wpmt_use_new_filter !== null ){
				return $this->wpmt_use_new_filter;
			}

			$return = false;
			$version_current = '0';
			$version_needed = '0';
	
			if( defined( 'WPWHPRO_VERSION' ) ){
				$version_current = WPWHPRO_VERSION;
				$version_needed = '4.1.0';
			}
	
			if( defined( 'WPWH_VERSION' ) ){
				$version_current = WPWH_VERSION;
				$version_needed = '3.1.0';
			}
	
			if( version_compare( (string) $version_current, (string) $version_needed, '>=') ){
				$return = true;
			}

			$this->wpmt_use_new_filter = $return;

			return $return;
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
			$actions[] = $this->action_manage_term_meta_content();

			return $actions;
		}

		/*
		 * Add the callback function for a defined action
		 *
		 * We call the default get_active_webhooks function to grab
		 * all of the currently activated triggers.
		 *
		 * We always send three different properties with the defined wehook.
		 * @param $action - the defined action
		 * @param $webhook - The webhook itself
		 * @param $api_key - an api_key if defined
		 */
		public function add_webhook_actions( $response, $action, $webhook, $api_key = '' ){

			//Backwards compatibility prior 4.1.0 (wpwhpro) or 3.1.0 (wpwh)
			if( ! $this->wpwh_use_new_action_filter() ){
				$api_key = $webhook;
				$webhook = $action;
				$action = $response;

				$active_webhooks = WPWHPRO()->settings->get_active_webhooks();
				$available_actions = $active_webhooks['actions'];

				if( ! isset( $available_actions[ $action ] ) ){
					return $response;
				}
			}

			$return_data = null;

			switch( $action ){
				case 'set_terms':
					$return_data = $this->action_set_terms();
					break;
				case 'manage_term_meta':
					$return_data = $this->action_manage_term_meta();
					break;
			}

			//Make sure we only fire the response in case the old logic is used
			if( $return_data !== null && ! $this->wpwh_use_new_action_filter() ){
				WPWHPRO()->webhook->echo_response_data( $return_data );
				die();
			}

			if( $return_data !== null ){
				$response = $return_data;
			}
			
			return $response;
		}

		/**
		 * ######################
		 * ###
		 * #### ENDPOINT: set_terms
		 * ###
		 * ######################
		 */

		public function action_set_terms_content(){

			$translation_ident = "action-set_terms-content";

			$parameter = array(
				'object_id'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'The object to relate to. (Post ID)', 'action-set_terms-content' ) ),
				'taxonomy'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'The context in which to relate the term to the object. (Taxonomy slug)', 'action-set_terms-content' ) ),
				'terms'            => array( 'short_description' => WPWHPRO()->helpers->translate( 'The terms you want to set. Please see the description for more information.', 'action-set_terms-content' ) ),
				'append'            => array( 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes" in case you want to append the taxonomies. If set to no, all previous entries to the defined taxonomies will be deleted. Default "no"', 'action-set_terms-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-set_terms-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-set_terms-content' ) ),
				'data'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The taxonomy term ids on success or wp_error on failure.', 'action-set_terms-content' ) ),
				'msg'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-set_terms-content' ) ),
			);

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "To append taxonomy terms, simple separate them with a comma. You can either use a single term slug, single term id, or array of either term slugs or ids. Here is an example:", $translation_ident ); ?>
<pre>term-1,term-2,term-3</pre>
<?php echo WPWHPRO()->helpers->translate( "<strong>Important</strong>: Passing an empty value will remove all related terms.", $translation_ident ); ?>
		<?php
		$parameter['terms']['description'] = ob_get_clean();

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "Set this argument to <strong>yes</strong> if you to append the taxonomies. If the argument is set to <strong>no</strong>, all existing taxonomies on the given post (via the object_id argument) will be removed before the new ones are added. Default is <strong>no</strong>", $translation_ident ); ?>
		<?php
		$parameter['append']['description'] = ob_get_clean();

		ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>set_terms</strong> action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 5 );
function my_custom_callback_function( $return_args, $object_id, $terms, $taxonomy, $append ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains all the data we send back to the webhook action caller. The data includes the following key: msg, success, data", $translation_ident ); ?>
    </li>
    <li>
        <strong>$object_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the post id of the post you want to assign the taxonomies to.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$terms</strong> (string)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the value of the <strong>terms</strong> argument that was set within the webhook call.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$taxonomy</strong> (string)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the taxonomy slug of the taxonomy you want to assign to the given post.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$append</strong> (bool)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains <strong>true</strong> if the <strong>append</strong> argument was set to <strong>yes</strong> and <strong>false</strong> if it was set to <strong>no</strong>.", $translation_ident ); ?>
    </li>
</ol>
		<?php
		$parameter['do_action']['description'] = ob_get_clean();

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

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to assign a whole taxonomy term to a post via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "This description is uniquely made for the <strong>set_terms</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand on how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>set_terms</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>set_terms</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the post id of the post you want to assign the taxonomy term to. You can do that by using the <strong>object_id</strong> argument.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "The last required argument is <strong>taxonomy</strong>. Please set it to the slug of the taxonomy you would like to assign.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the process of assigning a taxonomy to a post.", $translation_ident ); ?></li>
</ol>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'set_terms', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Connect whole taxonomy terms to a post.', 'action-set_terms-content' ),
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
				return $return_args;
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

			return $return_args;
		}

		/**
		 * ######################
		 * ###
		 * #### ENDPOINT: manage_term_meta
		 * ###
		 * ######################
		 */

		public function action_manage_term_meta_content(){

			$translation_ident = "action-manage_term_meta-description";

			$parameter = array(
				'taxonomy'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The slug of the taxonomy you want to update the items of.', 'action-manage_term_meta-content' ) ),
				'term_value'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The identifier of the term value. This can be the term id, name or slug. If you want to change the value type, use the get_term_by argument. Default: term id', 'action-manage_term_meta-content' ) ),
				'manage_meta_data'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( ' (String) A JSON formatted string containing all of the term meta values you want to create/update/delete. Please see the description for further details.', 'action-manage_term_meta-content' ) ),
				'get_term_by'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) An identifier on what term_value data you want to use to fetch the term. Default: term_id - Please see the description for further details.', 'action-manage_term_meta-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-manage_term_meta-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-manage_term_meta-content' ) ),
				'data'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The taxonomy term id on success or wp_error on failure, including other values from the request.', 'action-manage_term_meta-content' ) ),
				'msg'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-manage_term_meta-content' ) ),
			);

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "Since taxonomy term slugs are not unique outside of the taxonomy, it is required to set the taxonomy slug. Please note, that it must be the slug of the taxonomy and not the name or label.", $translation_ident ); ?>
		<?php
		$parameter['taxonomy']['description'] = ob_get_clean();

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "The term value contains either the term id, the term slug or the term name. Which value you set must be determined within the <strong>get_term_by</strong> argument. Default is the term id", $translation_ident ); ?>
		<?php
		$parameter['term_value']['description'] = ob_get_clean();

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "This argument determines the type for the <strong>term_value</strong> argument. Possible values are: <code>id</code> (term id), <code>slug</code>, or <code>name</code>. Default: id", $translation_ident ); ?>
		<?php
		$parameter['get_term_by']['description'] = ob_get_clean();

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "This argument integrates the full features of managing term related meta values.", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "<strong>Please note</strong>: This argument is very powerful and requires some good understanding of JSON. It is integrated with the commonly used functions for managing term meta within WordPress. You can find a list of all avaialble functions here: ", $translation_ident ); ?>
<ul>
    <li><strong>add_term_meta()</strong>: <a title="Go to WordPress" target="_blank" href="https://developer.wordpress.org/reference/functions/add_term_meta/">https://developer.wordpress.org/reference/functions/add_term_meta/</a></li>
    <li><strong>update_term_meta()</strong>: <a title="Go to WordPress" target="_blank" href="https://developer.wordpress.org/reference/functions/update_term_meta/">https://developer.wordpress.org/reference/functions/update_term_meta/</a></li>
    <li><strong>delete_term_meta()</strong>: <a title="Go to WordPress" target="_blank" href="https://developer.wordpress.org/reference/functions/delete_term_meta/">https://developer.wordpress.org/reference/functions/delete_term_meta/</a></li>
</ul>
<br>
<?php echo WPWHPRO()->helpers->translate( "Down below you will find a complete JSON example that shows you how to use each of the functions above.", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "We also offer JSON to array/object serialization for single term meta values. This means, you can turn JSON into a serialized array or object.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "This argument accepts a JSON construct as an input. This construct contains each available function as a top-level key within the first layer and the assigned data respectively as a value. If you want to learn more about each line, please take a closer look at the bottom of the example.", $translation_ident ); ?>
<pre>{
   "add_term_meta":[
      {
        "meta_key": "first_custom_key",
        "meta_value": "Some custom value"
      },
      {
        "meta_key": "second_custom_key",
        "meta_value": { "some_array_key": "Some array Value" },
        "unique": true
      } 
    ],
   "update_term_meta":[
      {
        "meta_key": "first_custom_key",
        "meta_value": "Some custom value"
      },
      {
        "meta_key": "second_custom_key",
        "meta_value": "The new value",
        "prev_value": "The previous value"
      } 
    ],
   "delete_term_meta":[
      {
        "meta_key": "first_custom_key"
      },
      {
        "meta_key": "second_custom_key",
        "meta_value": "Target specific value"
      } 
    ]
}</pre>
<?php echo WPWHPRO()->helpers->translate( "Down below you will find a list that explains each of the top level keys.", $translation_ident ); ?>
<ol>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "add_term_meta", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This key refers to the <strong>add_term_meta()</strong> function of WordPress:", $translation_ident ); ?> <a title="Go to WordPress" target="_blank" href="https://developer.wordpress.org/reference/functions/add_term_meta/">https://developer.wordpress.org/reference/functions/add_term_meta/</a>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "In the example above, you will find two entries within the add_term_meta key. The first one shows the default behavior using only the meta key and the value. This causes the meta key to be created without checking upfront if it exists - that allows you to create the meta value multiple times.", $translation_ident ); ?>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "As seen in the second entry, you will find a third key called <strong>unique</strong> that allows you to check upfront if the meta key exists already. If it does, the meta entry is neither created, nor updated. Set the value to <strong>true</strong> to check against existing ones. Default: false", $translation_ident ); ?>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "If you look closely to the second entry again, the value included is not a string, but a JSON construct, which is considered as an array and will therefore be serialized. The given value will be saved to the database in the following format: <code>a:1:{s:14:\"some_array_key\";s:16:\"Some array Value\";}</code>", $translation_ident ); ?>
    </li>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "update_term_meta", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This key refers to the <strong>update_term_meta()</strong> function of WordPress:", $translation_ident ); ?> <a title="Go to WordPress" target="_blank" href="https://developer.wordpress.org/reference/functions/update_term_meta/">https://developer.wordpress.org/reference/functions/update_term_meta/</a>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "The example above shows you two entries for this function. The first one is the default set up thats used in most cases. Simply define the meta key and the meta value and the key will be updated if it does exist and if it does not exist, it will be created.", $translation_ident ); ?>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "The third argument, as seen in the second entry, allows you to check against a previous value before updating. That causes that the meta value will only be updated if the previous key fits to whats currently saved within the database. Default: ''", $translation_ident ); ?>
    </li>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "delete_term_meta", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This key refers to the <strong>delete_term_meta()</strong> function of WordPress:", $translation_ident ); ?> <a title="Go to WordPress" target="_blank" href="https://developer.wordpress.org/reference/functions/delete_term_meta/">https://developer.wordpress.org/reference/functions/delete_term_meta/</a>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Within the example above, you will see that only the meta key is required for deleting an entry. This will cause all meta keys on this term, with the same key, to be deleted.", $translation_ident ); ?>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "The second argument allows you to target only a specific meta key/value combination. This gets important if you want to target a specific meta key/value combination and not delete all available entries for the given term. Default: ''", $translation_ident ); ?>
    </li>
</ol>
<strong><?php echo WPWHPRO()->helpers->translate( "Some tipps:", $translation_ident ); ?></strong>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "You can include the value for this argument as a simple string to your webhook payload or you integrate it directly as JSON into your JSON payload (if you send a raw JSON response).", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Changing the order of the functions within the JSON causes the term meta to behave differently. If you, for example, add the <strong>delete_term_meta</strong> key before the <strong>update_term_meta</strong> key, the meta values will first be deleted and then added/updated.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "The webhook response contains a validted array that shows each initialized meta entry, as well as the response from its original WordPress function. This way you can see if the meta value was adjusted accordingly.", $translation_ident ); ?></li>
</ol>
		<?php
		$parameter['manage_meta_data']['description'] = ob_get_clean();

			ob_start();
		?>
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>manage_term_meta</strong> action was fired.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "You can use it to trigger further logic after the webhook action. Here's an example:", $translation_ident ); ?>
<br>
<br>
<?php echo WPWHPRO()->helpers->translate( "Let's assume you set for the <strong>do_action</strong> parameter <strong>fire_this_function</strong>. In this case, we will trigger an action with the hook name <strong>fire_this_function</strong>. Here's how the code would look in this case:", $translation_ident ); ?>
<pre>add_action( 'fire_this_function', 'my_custom_callback_function', 20, 2 );
function my_custom_callback_function( $term_id, $return_args ){
    //run your custom logic in here
}
</pre>
<?php echo WPWHPRO()->helpers->translate( "Here's an explanation to each of the variables that are sent over within the custom function.", $translation_ident ); ?>
<ol>
	<li>
        <strong>$term_id</strong> (integer)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains the taxonomy term id of the taxonomy term you assigned the taxonomies meta to.", $translation_ident ); ?>
    </li>
    <li>
        <strong>$return_args</strong> (array)
        <br>
        <?php echo WPWHPRO()->helpers->translate( "Contains all the data we send back to the webhook action caller. The data includes the following key: msg, success, data", $translation_ident ); ?>
    </li>
</ol>
		<?php
		$parameter['do_action']['description'] = ob_get_clean();

			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "Taxonomy term meta was upated successfully.",
    "data": {
        "term_id": "91",
        "taxonomy": "category",
        "get_term_by": false,
        "term_value": "91",
        "manage_meta_data": {
            "success": true,
            "msg": "The meta data was successfully executed.",
            "data": {
                "add_term_meta": [
                    {
                        "meta_key": "first_add_custom_key",
                        "meta_value": "Some custom value",
                        "unique": false,
                        "response": 26
                    },
                    {
                        "meta_key": "second_add_custom_key",
                        "meta_value": {
                            "some_array_key": "Some array Value"
                        },
                        "unique": true,
                        "response": 27
                    }
                ],
                "update_term_meta": [
                    {
                        "meta_key": "update_custom_key",
                        "meta_value": "Some custom value",
                        "prev_value": false,
                        "response": 28
                    },
                    {
                        "meta_key": "second_update_custom_key",
                        "meta_value": "The new value",
                        "prev_value": "The previous value",
                        "response": 29
                    }
                ]
            }
        },
        "do_action": ""
    }
}
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to update taxonomy term meta on a taxonomy term via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "This description is uniquely made for the <strong>manage_term_meta</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand on how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>manage_term_meta</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>manage_term_meta</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>taxonomy</strong> argument. This must contain the taxonomy slug.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Another argument that needs to be set is the <strong>term_value</strong> argument, which should contain either the term id, the term slug or the term name. Please see the <strong>Special Arguments list for further details.</strong>", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Lastly, it is required to add the <strong>manage_meta_data</strong> argument, which must contain a JSON formatted string as stated below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the process of managing the taxonomy term meta.", $translation_ident ); ?></li>
</ol>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'manage_term_meta', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Create, update and delete taxonomy term meta via a webhook call.', 'action-manage_term_meta-content' ),
				'description'       => $description
			);

		}

		public function action_manage_term_meta() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => '',
			);

			$taxonomy = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'taxonomy' ); //mndtry
			$get_term_by = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'get_term_by' );
			$term_value = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'term_value' ); //mndtry
			$manage_meta_data = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'manage_meta_data' );

			$do_action      = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( empty( $term_value ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The term_value argument cannot be empty.", 'action-manage_term_meta' );
				return $return_args;
			}

			if( ! is_numeric( $term_value ) ){
				$term_obj = get_term_by( $get_term_by, $term_value, $taxonomy );
				if( empty( $term_obj ) ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( "We could not find any term for your given data.", 'action-manage_term_meta' );
					return $return_args;
				}

				if( is_array( $term_obj ) ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( "We found multiple entries for your given taxonomy term. Please specify the taxonomy argument.", 'action-manage_term_meta' );
					return $return_args;
				}

				$term_id = $term_obj->term_id;
			} else {
				$term_id = $term_value;
			}
			
			$meta_response = $this->manage_term_meta_data( $term_id, $manage_meta_data );
 
			$return_args['success'] = true;
			$return_args['data'] = array(
				'term_id' => $term_id,
				'taxonomy' => $taxonomy,
				'get_term_by' => $get_term_by,
				'term_value' => $term_value,
				'manage_meta_data' => $meta_response,
				'do_action' => $do_action,
			);
			$return_args['msg'] = WPWHPRO()->helpers->translate( "Taxonomy term meta was upated successfully.", 'action-manage_term_meta' );

			if( ! empty( $do_action ) ){
				do_action( $do_action, $term_id, $return_args );
			}

			return $return_args;
		}

		public function manage_term_meta_data( $term_id, $term_meta_data ){
			$response = array(
				'success' => false,
				'msg' => '',
				'data' => array(),
			);
			
			if( ! empty( $term_meta_data ) ){
	
				if( WPWHPRO()->helpers->is_json( $term_meta_data ) ){
					$term_meta_data = json_decode( $term_meta_data, true );
				}
	
				if( is_array( $term_meta_data ) ){
					foreach( $term_meta_data as $function => $meta_data ){
						switch( $function ){
							case 'add_term_meta':
								if( ! isset( $response['data']['add_term_meta'] ) ){
									$response['data']['add_term_meta'] = array();
								}
	
								foreach( $meta_data as $add_row_key => $add_single_meta_data ){
									if( isset( $add_single_meta_data['meta_key'] ) && isset( $add_single_meta_data['meta_value'] ) ){
	
										$unique = false;
										if( isset( $add_single_meta_data['unique'] ) ){
											$unique = ( ! empty( $add_single_meta_data['unique'] ) ) ? true : false;
										}
	
										$add_response = add_term_meta( $term_id, $add_single_meta_data['meta_key'], $add_single_meta_data['meta_value'], $unique );
	
										$response['data']['add_term_meta'][] = array(
											'meta_key' => $add_single_meta_data['meta_key'],
											'meta_value' => $add_single_meta_data['meta_value'],
											'unique' => $unique,
											'response' => $add_response,
										);
									}
								}
							break;
							case 'update_term_meta':
								if( ! isset( $response['data']['update_term_meta'] ) ){
									$response['data']['update_term_meta'] = array();
								}
	
								foreach( $meta_data as $add_row_key => $update_single_meta_data ){
									if( isset( $update_single_meta_data['meta_key'] ) && isset( $update_single_meta_data['meta_value'] ) ){
	
										$prev_value = false;
										if( isset( $update_single_meta_data['prev_value'] ) ){
											$prev_value = $update_single_meta_data['prev_value'];
										}
	
										$update_response = update_term_meta( $term_id, $update_single_meta_data['meta_key'], $update_single_meta_data['meta_value'], $prev_value );
	
										$response['data']['update_term_meta'][] = array(
											'meta_key' => $update_single_meta_data['meta_key'],
											'meta_value' => $update_single_meta_data['meta_value'],
											'prev_value' => $prev_value,
											'response' => $update_response,
										);
									}
								}
							break;
							case 'delete_term_meta':
								if( ! isset( $response['data']['delete_term_meta'] ) ){
									$response['data']['delete_term_meta'] = array();
								}
	
								foreach( $meta_data as $add_row_key => $delete_single_meta_data ){
									if( isset( $delete_single_meta_data['meta_key'] ) ){
	
										$match_meta_value = '';
										if( isset( $delete_single_meta_data['meta_value'] ) ){
											$match_meta_value = $delete_single_meta_data['meta_value'];
										}
	
										$delete_response = delete_term_meta( $term_id, $delete_single_meta_data['meta_key'], $match_meta_value );
	
										$response['data']['delete_term_meta'][] = array(
											'meta_key' => $delete_single_meta_data['meta_key'],
											'meta_value' => $match_meta_value,
											'response' => $delete_response,
										);
									}
								}
							break;
						}
					}
	
					$response['success'] = true;
					$response['msg'] = WPWHPRO()->helpers->translate( 'The meta data was successfully executed.', 'manage-meta-data' );
				} else {
					$response['msg'] = WPWHPRO()->helpers->translate( 'Could not decode the meta data.', 'manage-meta-data' );
				}
			} else {
				$response['msg'] = WPWHPRO()->helpers->translate( 'No custom term meta given.', 'manage-meta-data' );
			}
	
			return $response;
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