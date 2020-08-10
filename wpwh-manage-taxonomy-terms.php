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
			$actions[] = $this->action_set_terms_meta_content();

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
				case 'set_terms_meta':
					if( isset( $available_actions['set_terms_meta'] ) ){
						$this->action_set_terms_meta();
					}
					break;
			}
		}

		/**
		 * ######################
		 * ###
		 * #### ENDPOINT: set_terms
		 * ###
		 * ######################
		 */

		public function action_set_terms_content(){

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
            <pre>
$return_args = array(
	'success' => false,
	'msg' => '',
	'data' => '',
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			$translation_ident = "action-set_terms-description";

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
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>
<h5><?php echo WPWHPRO()->helpers->translate( "terms", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "To append taxonomy terms, simple separate them with a comma. You can either use a single term slug, single term id, or array of either term slugs or ids. Here is an example:", $translation_ident ); ?>
<pre>term-1,term-2,term-3</pre>
<?php echo WPWHPRO()->helpers->translate( "<strong>Important</strong>: Passing an empty value will remove all related terms.", $translation_ident ); ?>
<br>
<hr>
<h5><?php echo WPWHPRO()->helpers->translate( "append", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Set this argument to <strong>yes</strong> if you to append the taxonomies. If the argument is set to <strong>no</strong>, all existing taxonomies on the given post (via the object_id argument) will be removed before the new ones are added. Default is <strong>no</strong>", $translation_ident ); ?>
<br>
<hr>
<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
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
        <?php echo WPWHPRO()->helpers->translate( "Contains the value of the <strong>terms</strong> argument that was set within the webbhook call.", $translation_ident ); ?>
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

		/**
		 * ######################
		 * ###
		 * #### ENDPOINT: set_terms_meta
		 * ###
		 * ######################
		 */

		public function action_set_terms_meta_content(){

			$parameter = array(
				'taxonomy'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(String) The slug of the taxonomy you want to update the items of.', 'action-set_terms_meta-content' ) ),
				'term_value'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( '(Mixed) The identifier of the term value. This can be the term id, name or slug. If you want to change the value type, use the get_term_by argument. Default: term id', 'action-set_terms_meta-content' ) ),
				'tax_meta'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( ' (String) A JSON formatted string containing all of the term meta values you want to create/update/delete. Please see the description for further details.', 'action-set_terms_meta-content' ) ),
				'get_term_by'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(String) An identifier on what term_value data you want to use to fetch the term. Default: term_id - Please see the description for further details.', 'action-set_terms_meta-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after WP Webhooks fires this webhook. More infos are in the description.', 'action-set_terms_meta-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-set_terms_meta-content' ) ),
				'data'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The taxonomy term ids on success or wp_error on failure.', 'action-set_terms_meta-content' ) ),
				'msg'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-set_terms_meta-content' ) ),
			);

			ob_start();
			?>
            <pre>{
    "success": true,
    "msg": "Taxonomy term meta was upated successfully.",
    "data": {
        "term_id": 92,
        "taxonomy": "download_category",
        "get_term_by": "slug",
        "term_value": "test",
        "tax_meta": "{\n  \"meta_key_1\": \"ironikus-delete\",\n  \"another_meta_key\": \"This is my second meta key!\",\n  \"third_meta_key\": \"ironikus-serialize{\\\"price\\\": \\\"100\\\"}\"\n}",
        "do_action": ""
    }
}
        </pre>
			<?php
			$returns_code = ob_get_clean();

			$translation_ident = "action-set_terms_meta-description";

			ob_start();
?>

<?php echo WPWHPRO()->helpers->translate( "This webhook action is used to update taxonomy term meta on a taxonomy term via a webhook call.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "This description is uniquely made for the <strong>set_terms_meta</strong> webhook action.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "In case you want to first understand on how to setup webhook actions in general, please check out the following manuals:", $translation_ident ); ?>
<br>
<a title="Go to ironikus.com/docs" target="_blank" href="https://ironikus.com/docs/article-categories/get-started/">https://ironikus.com/docs/article-categories/get-started/</a>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "How to use <strong>set_terms_meta</strong>", $translation_ident ); ?></h4>
<ol>
    <li><?php echo WPWHPRO()->helpers->translate( "The first argument you need to set within your webhook action request is the <strong>action</strong> argument. This argument is always required. Please set it to <strong>set_terms_meta</strong>.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "It is also required to set the <strong>taxonomy</strong> argument. This must contain the taxonomy slug.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Another argument that needs to be set is the <strong>term_value</strong> argument, which should contain either the term id, the term slug or the term name. Please see the <strong>Special Arguments list for further details.</strong>", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "Lastly, it is required to add the <strong>tax_meta</strong> argument, which must contain a JSON formatted string as stated below within the <strong>Special Arguments</strong> list.", $translation_ident ); ?></li>
    <li><?php echo WPWHPRO()->helpers->translate( "All the other arguments are optional and just extend the process of managing the taxonomy term meta.", $translation_ident ); ?></li>
</ol>
<br><br>
<h4><?php echo WPWHPRO()->helpers->translate( "Special Arguments", $translation_ident ); ?></h4>
<br>
<h5><?php echo WPWHPRO()->helpers->translate( "taxonomy", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "Since taxonomy term slugs are not unique outside of the taxonomy, it is required to set the taxonomy slug. Please note, that it must be the slug of the taxonomy and not the name or label.", $translation_ident ); ?>
<br>
<hr>
<h5><?php echo WPWHPRO()->helpers->translate( "term_value", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The term value contains either the term id, the term slug or the term name. Which value you set must be determined within the <strong>get_term_by</strong> argument. Default is the term id", $translation_ident ); ?>
<br>
<hr>
<h5><?php echo WPWHPRO()->helpers->translate( "get_term_by", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument determines the type for the <strong>term_value</strong> argument. Possible values are: <code>id</code> (term id), <code>slug</code>, or <code>name</code>. Default: id", $translation_ident ); ?>
<br>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "tax_meta", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "This argument is specifically designed to add/update or remove taxonomy term meta on your existing taxonomy term.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "To create/update or delete custom meta values, we offer you two different ways:", $translation_ident ); ?>
<ol>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "String method", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This method allows you to add/update or delete the taxonomy term meta using a simple string. To make it work, separate the meta key from the value using a comma (,). To separate multiple meta settings from each other, simply separate them with a semicolon (;). To remove a meta value, simply set as a value <strong>ironikus-delete</strong>", $translation_ident ); ?>
        <pre>meta_key_1,meta_value_1;my_second_key,ironikus-delete</pre>
        <?php echo WPWHPRO()->helpers->translate( "<strong>IMPORTANT:</strong> Please note that if you want to use values that contain commas or semicolons, the string method does not work. In this case, please use the JSON method.", $translation_ident ); ?>
    </li>
    <li>
    <strong><?php echo WPWHPRO()->helpers->translate( "JSON method", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This method allows you to add/update or remove the taxonomy term meta using a JSON formatted string. To make it work, add the meta key as the key and the meta value as the value. To delete a meta value, simply set the value to <strong>ironikus-delete</strong>. Here's an example on how this looks like:", $translation_ident ); ?>
        <pre>{
  "meta_key_1": "This is my meta value 1",
  "another_meta_key": "This is my second meta key!"
  "third_meta_key": "ironikus-delete"
}</pre>
    </li>
</ol>
<strong><?php echo WPWHPRO()->helpers->translate( "Advanced", $translation_ident ); ?></strong>: <?php echo WPWHPRO()->helpers->translate( "We also offer JSON to array/object serialization for single taxonomy term meta values. This means, you can turn JSON into a serialized array or object.", $translation_ident ); ?>
<br>
<?php echo WPWHPRO()->helpers->translate( "As an example: The following JSON <code>{\"price\": \"100\"}</code> will turn into <code>O:8:\"stdClass\":1:{s:5:\"price\";s:3:\"100\";}</code> with default serialization or into <code>a:1:{s:5:\"price\";s:3:\"100\";}</code> with array serialization.", $translation_ident ); ?>
<ol>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "Object serialization", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This method allows you to serialize a JSON to an object using the default json_decode() function of PHP.", $translation_ident ); ?>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "To serialize your JSON to an object, you need to add the following string in front of the escaped JSON within the value field of your single meta value of the meta_input argument: <code>ironikus-serialize</code>. Here's a full example:", $translation_ident ); ?>
        <pre>{
  "meta_key_1": "This is my meta value 1",
  "another_meta_key": "This is my second meta key!",
  "third_meta_key": "ironikus-serialize{\"price\": \"100\"}"
}</pre>
        <?php echo WPWHPRO()->helpers->translate( "This example will create three taxonomy term meta entries. The third entry has the meta key <strong>third_meta_key</strong> and a serialized meta value of <code>O:8:\"stdClass\":1:{s:5:\"price\";s:3:\"100\";}</code>. The string <code>ironikus-serialize</code> in front of the escaped JSON will tell our plugin to serialize the value. Please note that the JSON value, which you include within the original JSON string of the meta_input argument, needs to be escaped.", $translation_ident ); ?>
    </li>
    <li>
        <strong><?php echo WPWHPRO()->helpers->translate( "Array serialization", $translation_ident ); ?></strong>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "This method allows you to serialize a JSON to an array using the json_decode( \$json, true ) function of PHP.", $translation_ident ); ?>
        <br>
        <?php echo WPWHPRO()->helpers->translate( "To serialize your JSON to an array, you need to add the following string in front of the escaped JSON within the value field of your single meta value of the meta_input argument: <code>ironikus-serialize-array</code>. Here's a full example:", $translation_ident ); ?>
        <pre>{
  "meta_key_1": "This is my meta value 1",
  "another_meta_key": "This is my second meta key!",
  "third_meta_key": "ironikus-serialize-array{\"price\": \"100\"}"
}</pre>
        <?php echo WPWHPRO()->helpers->translate( "This example will create three taxonomy term meta entries. The third entry has the meta key <strong>third_meta_key</strong> and a serialized meta value of <code>a:1:{s:5:\"price\";s:3:\"100\";}</code>. The string <code>ironikus-serialize-array</code> in front of the escaped JSON will tell our plugin to serialize the value. Please note that the JSON value, which you include within the original JSON string of the meta_input argument, needs to be escaped.", $translation_ident ); ?>
    </li>
</ol>
<hr>

<h5><?php echo WPWHPRO()->helpers->translate( "do_action", $translation_ident ); ?></h5>
<?php echo WPWHPRO()->helpers->translate( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>set_terms_meta</strong> action was fired.", $translation_ident ); ?>
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
			$description = ob_get_clean();

			return array(
				'action'            => 'set_terms_meta', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Create, update and delete taxonomy term meta via a webbhook call.', 'action-set_terms_meta-content' ),
				'description'       => $description
			);

		}

		public function action_set_terms_meta() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => '',
			);

			$taxonomy = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'taxonomy' ); //mndtry
			$get_term_by = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'get_term_by' );
			$term_value = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'term_value' ); //mndtry
			$tax_meta = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'tax_meta' );

			$do_action      = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( empty( $term_value ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The term_value argument cannot be empty.", 'action-set_terms_meta' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			if( ! is_numeric( $term_value ) ){
				$term_obj = get_term_by( $get_term_by, $term_value, $taxonomy );
				if( empty( $term_obj ) ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( "We could not find any term for your given data.", 'action-set_terms_meta' );
					WPWHPRO()->webhook->echo_response_data( $return_args );
					die();
				}

				if( is_array( $term_obj ) ){
					$return_args['msg'] = WPWHPRO()->helpers->translate( "We found multiple entries for your given taxonomy term. Please specify the taxonomy argument.", 'action-set_terms_meta' );
					WPWHPRO()->webhook->echo_response_data( $return_args );
					die();
				}

				$term_id = $term_obj->term_id;
			} else {
				$term_id = $term_value;
			}

			if( ! WPWHPRO()->helpers->is_json( $tax_meta ) ){
				$return_args['msg'] = WPWHPRO()->helpers->translate( "The tax_meta argument does not contain a valid JSON.", 'action-set_terms_meta' );
				WPWHPRO()->webhook->echo_response_data( $return_args );
				die();
			}

			$tax_meta_data = json_decode( $tax_meta, true );
			$validated_meta = array();
			foreach( $tax_meta_data as $skey => $sval ){

				if( ! empty( $skey ) ){
					if( $sval == 'ironikus-delete' ){

						delete_term_meta( $term_id, $skey );

					} else {

						$ident = 'ironikus-serialize';
						if( substr( $sval , 0, strlen( $ident ) ) === $ident ){
							$serialized_value = trim( str_replace( $ident, '', $sval ),' ' );

							//Allow array validation
							$sa_ident = '-array';
							if( is_string( $serialized_value ) && substr( $serialized_value , 0, strlen( $sa_ident ) ) === $sa_ident ){
								$serialized_value = trim( str_replace( $sa_ident, '', $serialized_value ),' ' );

								if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
									$serialized_value = json_decode( $serialized_value, true );
								}
							} else {
								if( WPWHPRO()->helpers->is_json( $serialized_value ) ){
									$serialized_value = json_decode( $serialized_value );
								}
							}

							$validated_meta[ $skey ] = $serialized_value;

						} else {
							$validated_meta[ $skey ] = maybe_unserialize( $sval );
						}
					}
				}
			}

			foreach( $validated_meta as $meta_key => $meta_value ){
				update_term_meta( $term_id, $meta_key, $meta_value );
			}
 
			$return_args['success'] = true;
			$return_args['data'] = array(
				'term_id' => $term_id,
				'taxonomy' => $taxonomy,
				'get_term_by' => $get_term_by,
				'term_value' => $term_value,
				'tax_meta' => $tax_meta,
				'do_action' => $do_action,
			);
			$return_args['msg'] = WPWHPRO()->helpers->translate( "Taxonomy term meta was upated successfully.", 'action-set_terms_meta' );

			if( ! empty( $do_action ) ){
				do_action( $do_action, $term_id, $return_args );
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