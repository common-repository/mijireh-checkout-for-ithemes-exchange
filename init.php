<?php
/**
 * @package Mijireh Checkout for iThemes Exchange
 */

/**
* init_mijireh function.
*
* @return void
*/
function init_mijireh() {

	if ( ! class_exists( 'Mijireh' ) ) {

    	require_once 'includes/Mijireh.php';
		
		$mijireh_settings  = it_exchange_get_option( 'addon_mijireh' );

    	Mijireh::$access_key = $mijireh_settings['mijireh-access-key'];

    }
	
}
	
/**
 * Outputs wizard settings for Mijireh
 *
 * @todo make this better, probably
 * @param object $form Current IT Form object
 * @return void
*/
function it_exchange_print_mijireh_wizard_settings( $form ) {
	$IT_Exchange_Mijireh_Checkout_Add_On = new IT_Exchange_Mijireh_Checkout_Add_On();
	$settings = it_exchange_get_option( 'addon_mijireh', true );
	$form_values = ITUtility::merge_defaults( ITForm::get_post_data(), $settings );
	$hide_if_js =  it_exchange_is_addon_enabled( 'mijireh' ) ? '' : 'hide-if-js';
	?>
	<div class="field mijireh-wizard <?php echo $hide_if_js; ?>">
	<?php if ( empty( $hide_if_js ) ) { ?>
        <input class="enable-mijireh" type="hidden" name="it-exchange-transaction-methods[]" value="mijireh" />
    <?php } ?>
	<?php $IT_Exchange_Mijireh_Checkout_Add_On->get_mijireh_payment_form_table( $form, $form_values ); ?>
	</div>
	<?php
}
add_action( 'it_exchange_print_mijireh_wizard_settings', 'it_exchange_print_mijireh_wizard_settings' );

/**
 * This is the function registered in the options array when it_exchange_register_addon was called for mijireh
 *
 * It tells Exchange where to find the settings page
 *
 * @return void
*/
function it_exchange_mijireh_settings_callback() {
	$IT_Exchange_Mijireh_Checkout_Add_On = new IT_Exchange_Mijireh_Checkout_Add_On();
	$IT_Exchange_Mijireh_Checkout_Add_On->print_settings_page();
}

/**
 * This is the function prints the payment form on the Wizard Settings screen
 *
 * @return void
*/
function mijireh_print_wizard_settings( $form ) {
	$IT_Exchange_Mijireh_Checkout_Add_On = new IT_Exchange_Mijireh_Checkout_Add_On();
	$settings = it_exchange_get_option( 'addon_mijireh', true );
	?>
	<div class="field mijireh-wizard hide-if-js">
	<?php $IT_Exchange_Mijireh_Checkout_Add_On->get_mijireh_payment_form_table( $form, $settings ); ?>
	</div>
	<?php
}

/**
 * Saves mijireh settings when the Wizard is saved
 *
 * @return void
*/
function it_exchange_save_mijireh_wizard_settings( $errors ) {
	if ( ! empty( $errors ) ){
		return $errors;
	}
	
	$IT_Exchange_Mijireh_Checkout_Add_On = new IT_Exchange_Mijireh_Checkout_Add_On();
	return $IT_Exchange_Mijireh_Checkout_Add_On->mijireh_save_wizard_settings();
}
add_action( 'it_exchange_save_mijireh_wizard_settings', 'it_exchange_save_mijireh_wizard_settings' );

/**
 * Default settings for mijireh
 *
 * @param array $values
 * @return array
*/
function it_exchange_mijireh_addon_default_settings( $values ) {
	$defaults = array(
		'mijireh-access-key' => '',
		'mijireh-purchase-button-label' => __( 'Pay with Mijireh', 'patsatech-exchange-mijireh' ),
	);
	$values = ITUtility::merge_defaults( $values, $defaults );
	return $values;
}
add_filter( 'it_storage_get_defaults_exchange_addon_mijireh', 'it_exchange_mijireh_addon_default_settings' );

/**
 * Returns the button for making the Mijireh faux payment button
 *
 * @param array $options
 * @return string HTML button
*/
function it_exchange_mijireh_addon_make_payment_button( $options ) {

	if ( 0 >= it_exchange_get_cart_total( false ) )
		return;
		
	$general_settings = it_exchange_get_option( 'settings_general' );
	$mijireh_settings  = it_exchange_get_option( 'addon_mijireh' );

	$payment_form = '';

	if ( $mijireh_email = $mijireh_settings['mijireh-access-key'] ) {
		
		$it_exchange_customer = it_exchange_get_current_customer();
		
		$payment_form .= '<form action="' . get_site_url() . '/?mijireh-form=1" method="post">';
		$payment_form .= '<input type="submit" class="it-exchange-mijireh-button" name="mijireh_purchase" value="' . $mijireh_settings['mijireh-purchase-button-label'] .'" />';
		$payment_form .= '</form>';
		
	}
	
	return $payment_form;
	
}
add_filter( 'it_exchange_get_mijireh_make_payment_button', 'it_exchange_mijireh_addon_make_payment_button', 10, 2 );

/**
 * Process the faux Mijireh form
 *
 * @param array $options
 * @return string HTML button
*/
function it_exchange_process_mijireh_form() {
	
	$mijireh_settings  = it_exchange_get_option( 'addon_mijireh' );
	
	if ( ! empty( $_REQUEST['mijireh_purchase'] ) ) {
		
		if ( $mijireh_email = $mijireh_settings['mijireh-access-key']  ) {
			
			$it_exchange_customer = it_exchange_get_current_customer();
			
			$temp_id = it_exchange_create_unique_hash();
			
			$transaction_object = it_exchange_generate_transaction_object();
			
			it_exchange_add_transient_transaction( 'mijireh', $temp_id, $it_exchange_customer->id, $transaction_object );
			
			wp_redirect( it_exchange_mijireh_addon_get_payment_url( $temp_id ) );
			
		} else {
		
			it_exchange_add_message( 'error', __( 'Error processing Mijireh form. Missing valid Mijireh account.', 'patsatech-exchange-mijireh' ) );
			wp_redirect( it_exchange_get_page_url( 'checkout' ) );
			
		}
	
	}
	
}
add_action( 'wp', 'it_exchange_process_mijireh_form' );

/**
 * Returns the button for making the Mijireh real payment button
 *
 * @param string $temp_id Temporary ID we reference late with IPN
 * @return string HTML button
*/
function it_exchange_mijireh_addon_get_payment_url( $temp_id ) {

	if ( 0 >= it_exchange_get_cart_total( false ) )
		return;
		
	$general_settings = it_exchange_get_option( 'settings_general' );
	$mijireh_settings = it_exchange_get_option( 'addon_mijireh' );

	$mijireh_payment_url = '';

	if ( $mijireh_email = $mijireh_settings['mijireh-access-key'] ) {
		
		$it_exchange_customer = it_exchange_get_current_customer();
		
		remove_filter( 'the_title', 'wptexturize' ); // remove this because it screws up the product titles in Mijireh
		
		init_mijireh();

		$mj_order = new Mijireh_Order();
		
		$mj_order->add_item( it_exchange_get_cart_description(), number_format( it_exchange_get_cart_total( false ), 2, '.', '' ), 1, '' );
		
		// set order name
		$mj_order->first_name 		= $it_exchange_customer->data->first_name;
		$mj_order->last_name 		= $it_exchange_customer->data->last_name;
		$mj_order->email 			= $it_exchange_customer->data->user_email;

		// set order totals
		$mj_order->total 			= number_format( it_exchange_get_cart_total( false ), 2, '.', '' );
		
		$mj_order->add_meta_data( 'order_id', $temp_id );
		
		// Set URL for mijireh payment notification
		$mj_order->return_url 		= add_query_arg( it_exchange_get_webhook( 'mijireh' ), '1', get_site_url() );

		// Identify PatSaTECH
		$mj_order->partner_id 		= 'patsatech';

		try {
			$mj_order->create();
			
			$mijireh_payment_url = $mj_order->checkout_url;
			
		} catch (Mijireh_Exception $e) {
		
			it_exchange_add_message( 'error', __('Gateway Error:', 'patsatech-exchange-mijireh' ) . $e->getMessage() );
			
			$mijireh_payment_url = it_exchange_get_page_url( 'checkout' );
		}
		
	} else {
	
		it_exchange_add_message( 'error', __( 'ERROR: Invalid Mijireh Setup' ) );
		
		$mijireh_payment_url = it_exchange_get_page_url( 'checkout' );
		
	}
	
	return $mijireh_payment_url;
	
}

/**
 * Adds the mijireh webhook to the global array of keys to listen for
 *
 * @param array $webhooks existing
 * @return array
*/
function it_exchange_mijireh_addon_register_webhook() {
	$key   = 'mijireh';
	$param = apply_filters( 'it_exchange_mijireh_webhook', 'it_exchange_mijireh' );
	it_exchange_register_webhook( $key, $param );
}
add_filter( 'init', 'it_exchange_mijireh_addon_register_webhook' );

/**
 * Processes webhooks for Mijireh Checkout
 *
 * @todo actually handle the exceptions
 *
 * @param array $request really just passing  $_REQUEST
 */
function it_exchange_mijireh_addon_process_webhook( $request ) {

	$general_settings = it_exchange_get_option( 'settings_general' );
	$settings = it_exchange_get_option( 'addon_mijireh' );
	
	if ( ! empty( $request['order_number'] ) ) {
		
		init_mijireh();

  		$mj_order = new Mijireh_Order( esc_attr( $request['order_number'] ) );
		
  		$order_id = $mj_order->get_meta_value( 'order_id' );
		
		if ( !empty( $order_id ) && $transient_data = it_exchange_get_transient_transaction( 'mijireh', $order_id ) ) {
			it_exchange_delete_transient_transaction( 'mijireh', $order_id  );
			$ite_transaction_id = it_exchange_add_transaction( 'mijireh', $mj_order->order_number, $mj_order->status, $transient_data['customer_id'], $transient_data['transaction_object'] );
			return $ite_transaction_id;
		}
		
		$url = it_exchange_get_transaction_confirmation_url( $ite_transaction_id );
		
		try {
				if( $mj_order->status == 'paid' ){
				
					// Clear the cart
					it_exchange_empty_shopping_cart();
				
					it_exchange_mijireh_addon_update_transaction_status( $mj_order->order_number, $mj_order->status );
					
					wp_redirect( $url ); exit;
					
				}
					
		} catch ( Exception $e ) {

			// What are we going to do here?

		}
		
	}
	
}
add_action( 'it_exchange_webhook_it_exchange_mijireh', 'it_exchange_mijireh_addon_process_webhook' );

/**
 * Gets iThemes Exchange's Transaction ID from Mijireh's Transaction ID
 *
 * @param integer $mijireh_id id of mijireh transaction
 * @return integer iTheme Exchange's Transaction ID
*/
function it_exchange_mijireh_addon_get_ite_transaction_id( $mijireh_id ) {
	$transactions = it_exchange_mijireh_addon_get_transaction_id( $mijireh_id );
	foreach( $transactions as $transaction ) { //really only one
		return $transaction->ID;
	}
}

/**
 * Grab a transaction from the mijireh transaction ID
 *
 * @param integer $mijireh_id id of mijireh transaction
 * @return transaction object
*/
function it_exchange_mijireh_addon_get_transaction_id( $mijireh_id ) {
	$args = array(
		'meta_key'    => '_it_exchange_transaction_method_id',
		'meta_value'  => $mijireh_id,
		'numberposts' => 1, //we should only have one, so limit to 1
	);
	return it_exchange_get_transactions( $args );
}

/**
 * Updates a mijirehs transaction status based on mijireh ID
 *
 * @param integer $mijireh_id id of mijireh transaction
 * @param string $new_status new status
 * @return void
*/
function it_exchange_mijireh_addon_update_transaction_status( $mijireh_id, $new_status ) {
	$transactions = it_exchange_mijireh_addon_get_transaction_id( $mijireh_id );
	foreach( $transactions as $transaction ) { //really only one
		$current_status = it_exchange_get_transaction_status( $transaction );
		if ( $new_status !== $current_status ){
			it_exchange_update_transaction_status( $transaction, $new_status );
		}
	}
}

/**
 * Adds a refund to post_meta for a mijireh transaction
 *
*/
function it_exchange_mijireh_addon_add_refund_to_transaction( $mijireh_id, $refund ) {
	$transactions = it_exchange_mijireh_addon_get_transaction_id( $mijireh_id );
	foreach( $transactions as $transaction ) { //really only one
		it_exchange_add_refund_to_transaction( $transaction, number_format( abs( $refund ), '2', '.', '' ) );
	}

}

/**
 * Gets the interpretted transaction status from valid mijireh transaction statuses
 *
 * @param string $status the string of the mijireh transaction
 * @return string translaction transaction status
*/
function it_exchange_mijireh_addon_transaction_status_label( $status ) {

	switch ( strtolower( $status ) ) {

		case 'paid':
			return __( 'Paid', 'patsatech-exchange-mijireh' );
			break;
		default:
			return __( 'Unknown', 'patsatech-exchange-mijireh' );
	}

}
add_filter( 'it_exchange_transaction_status_label_mijireh', 'it_exchange_mijireh_addon_transaction_status_label' );

/**
 * Returns a boolean. Is this transaction a status that warrants delivery of any products attached to it?
 *
 * @param boolean $cleared passed in through WP filter. Ignored here.
 * @param object $transaction
 * @return boolean
*/
function it_exchange_mijireh_transaction_is_cleared_for_delivery( $cleared, $transaction ) { 
    $valid_stati = array( 'paid' );
    return in_array( strtolower( it_exchange_get_transaction_status( $transaction ) ), $valid_stati );
}
add_filter( 'it_exchange_mijireh_transaction_is_cleared_for_delivery', 'it_exchange_mijireh_transaction_is_cleared_for_delivery', 10, 2 );

/**
 * Class for Mijireh Checkout
*/
class IT_Exchange_Mijireh_Checkout_Add_On {

	/**
	 * @var boolean $_is_admin true or false
	*/
	var $_is_admin;

	/**
	 * @var string $_current_page Current $_GET['page'] value
	*/
	var $_current_page;

	/**
	 * @var string $_current_add_on Current $_GET['add-on-settings'] value
	*/
	var $_current_add_on;

	/**
	 * @var string $status_message will be displayed if not empty
	*/
	var $status_message;

	/**
	 * @var string $error_message will be displayed if not empty
	*/
	var $error_message;

	/**
	 * Class constructor
	 *
	 * Sets up the class.
	 * @return void
	*/
	function IT_Exchange_Mijireh_Checkout_Add_On() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'mijireh' == $this->_current_add_on ) {
			$this->save_settings();
		}

	}

	function print_settings_page() {
		$settings = it_exchange_get_option( 'addon_mijireh', true );
		$form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_mijireh', 'it-exchange-add-on-mijireh-settings' ),
			'enctype' => apply_filters( 'it_exchange_add_on_mijireh_settings_form_enctype', false ),
			'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=mijireh',
		);
		$form = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-mijireh' ) );

		if ( ! empty ( $this->status_message ) ){
			ITUtility::show_status_message( $this->status_message );
		}
		if ( ! empty( $this->error_message ) ){
			ITUtility::show_error_message( $this->error_message );
		}

		?>
		<div class="wrap">
			<?php screen_icon( 'it-exchange' ); ?>
			<h2><?php _e( 'Mijireh Settings', 'patsatech-exchange-mijireh' ); ?></h2>

			<?php do_action( 'it_exchange_mijireh_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

			<?php $form->start_form( $form_options, 'it-exchange-mijireh-settings' ); ?>
				<?php do_action( 'it_exchange_mijireh_settings_form_top' ); ?>
				<?php $this->get_mijireh_payment_form_table( $form, $form_values ); ?>
				<?php do_action( 'it_exchange_mijireh_settings_form_bottom' ); ?>
				<p class="submit">
					<?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'patsatech-exchange-mijireh' ), 'class' => 'button button-primary button-large' ) ); ?>
				</p>
			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_mijireh_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
		<?php
	}
	
	/**
	 *
	 * @todo verify video link
	 *
	 */
	function get_mijireh_payment_form_table( $form, $settings = array() ) {

		$general_settings = it_exchange_get_option( 'settings_general' );

		if ( ! empty( $_GET['page'] ) && 'it-exchange-setup' == $_GET['page'] ) { ?>
			<h3><?php _e( 'Mijireh Checkout', 'patsatech-exchange-mijireh' ); ?></h3>
		<?php }

		if ( !empty( $settings ) ){
			foreach ( $settings as $key => $var ){
				$form->set_option( $key, $var );
			}
		}

		?>
		<div class="it-exchange-addon-settings it-exchange-mijireh-addon-settings">
            <p>
				<?php _e( 'Mijireh Checkout helps you to keep your checkout process seamless to your customers while securely handling the collecting and transmitting of the credit card data for you. To get Mijireh set up for use with Exchange, you\'ll need to add the following information from your Mijireh account.', 'patsatech-exchange-mijireh' ); ?><br />
			</p>
			<p><?php _e( 'Don\'t have a Mijireh account yet?', 'patsatech-exchange-mijireh' ); ?> <a href="http://www.mijireh.com" target="_blank"><?php _e( 'Go set one up here', 'patsatech-exchange-mijireh' ); ?></a>.</p>
            <h4><?php _e( 'What is your Mijireh Access Key?', 'patsatech-exchange-mijireh' ); ?></h4>
			<p>
				<label for="mijireh-access-key"><?php _e( 'Mijireh Access Key', 'patsatech-exchange-mijireh' ); ?> <span class="tip" title="<?php _e( 'This is required to take payments from customer.', 'patsatech-exchange-mijireh' ); ?>">i</span></label>
				<?php $form->add_text_box( 'mijireh-access-key' ); ?>
			</p>
			<p>
				<label for="mijireh-purchase-button-label"><?php _e( 'Purchase Button Label', 'patsatech-exchange-mijireh' ); ?> <span class="tip" title="<?php _e( 'This is the text inside the button your customers will press to purchase with Mijireh', 'patsatech-exchange-mijireh' ); ?>">i</span></label>
				<?php $form->add_text_box( 'mijireh-purchase-button-label' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @return void
	*/
	function save_settings() {
		$defaults = it_exchange_get_option( 'addon_mijireh' );
		$new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-mijireh-settings' ) ) {
			$this->error_message = __( 'Error. Please try again', 'patsatech-exchange-mijireh' );
			return;
		}

		$errors = apply_filters( 'it_exchange_add_on_mijireh_validate_settings', $this->get_form_errors( $new_values ), $new_values );
		if ( ! $errors && it_exchange_save_option( 'addon_mijireh', $new_values ) ) {
			ITUtility::show_status_message( __( 'Settings saved.', 'patsatech-exchange-mijireh' ) );
		} else if ( $errors ) {
			$errors = implode( '<br />', $errors );
			$this->error_message = $errors;
		} else {
			$this->status_message = __( 'Settings not saved.', 'patsatech-exchange-mijireh' );
		}
		
		do_action( 'it_exchange_save_add_on_settings_mijireh' );

	}

	function mijireh_save_wizard_settings() {
	
		if ( empty( $_REQUEST['it_exchange_settings-wizard-submitted'] ) ){
			return;
		}

		$mijireh_settings = array();

		$fields = array(
			'mijireh-access-key',
			'mijireh-purchase-button-label',
		);
		$default_wizard_mijireh_settings = apply_filters( 'default_wizard_mijireh_settings', $fields );
		
		foreach( $default_wizard_mijireh_settings as $var ) {

			if ( isset( $_REQUEST['it_exchange_settings-' . $var] ) ) {
				$mijireh_settings[$var] = $_REQUEST['it_exchange_settings-' . $var];
			}

		}

		$settings = wp_parse_args( $mijireh_settings, it_exchange_get_option( 'addon_mijireh' ) );
		
		if ( $error_msg = $this->get_form_errors( $settings ) ) {

			return $error_msg;

		} else {
			it_exchange_save_option( 'addon_mijireh', $settings );
			$this->status_message = __( 'Settings Saved.', 'patsatech-exchange-mijireh' );
		}
		
		return;

	}

	/**
	 * Validates for values
	 *
	 * Returns string of errors if anything is invalid
	 *
	 * @return void
	*/
	function get_form_errors( $values ) {

		$errors = array();
		
		if ( empty( $values['mijireh-access-key'] ) ){
			$errors[] = __( 'Please enter your Mijireh Access Key', 'patsatech-exchange-mijireh' );
		}

		return $errors;
	}
}