<?php

/*
 * Plugin Name: Mijireh Checkout for iThemes Exchange
 * Plugin URI: http://www.patsatech.com
 * Description: Mijireh Checkout Plugin for accepting payments on your iThemes Exchange Store.
 * Author: PatSaTECH
 * Version: 1.0.0
 * Author URI: http://www.patsatech.com
 * Contributors: patsatech
 * Text Domain: patsatech-exchange-mijireh
 * Domain Path: /lang
*/

// Registers the gateway files / information to be read by iThemes Exchange Plugin.
function it_exchange_register_mijireh_addon() {

	$add_mijireh = array(
		'name'              => __( 'Mijireh Checkout', 'patsatech-exchange-mijireh' ),
		'description'       => __( 'Mijireh Checkout helps you to keep your checkout process seamless to your customers while securely handling the collecting and transmitting of the credit card data for you.', 'patsatech-exchange-mijireh' ),
		'author'            => 'PatSaTECH',
		'author_url'        => 'http://www.patsatech.com',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/images/mijireh50px.png'),
		'wizard-icon'       => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/images/wizard-mijireh.png'),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'transaction-methods',
		'settings-callback' => 'it_exchange_mijireh_settings_callback',
	);
	
	it_exchange_register_addon( 'mijireh', $add_mijireh );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_mijireh_addon' );

?>