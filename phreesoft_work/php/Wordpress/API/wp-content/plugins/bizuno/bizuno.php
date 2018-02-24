<?php
/**
 * Plugin Name: Bizuno API
 * Plugin URI: http://www.phreesoft.com
 * Description: This app syncs data between bizuno books and your woocommerce cart.
 * Version: 1.0.0
 * Author: Kevin Premo
 * Author URI: http://www.phreesoft.com
 * Text Domain: phreesoft.com
 * Domain Path: Optional. Plugin's relative directory path to .mo files. Example: /locale/
 * License: paied
 */
if(!defined( 'ABSPATH' )) die( 'No script kiddies please!' );

// Set the module identity
if (!defined('BIZUNO_APP_TITLE')) define('BIZUNO_APP_TITLE', 'Bizuno'); // choices are Bizuno OR PhreeBooks

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	load_plugin_textdomain('your-unique-name', false, basename( dirname( __FILE__ ) ) . '/languages' );
} else {
	// log error / display error
	deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourselves
	wp_die( __('Did not find a WooCommerce, please activate this plugin to activate Bizuno API.', 'bizuno'), __('Need WooCommerce 2.2.X', 'bizuno'), array('back_link' => true));
	return;
}

/**
 * set default settings fields and post meta
 */
register_activation_hook( __FILE__ , 'bizunoapi_install' );
function bizunoapi_install() {
	global $wpdb;
	update_option('biz_active', true); //acivate
	update_option('biz_url', 'https://www.bizuno.com', false); //set default url
	//add to post meta to every order
		//transaction_id ?
		//transaction_mode ?
	$orders = $wpdb->get_results( "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_type` LIKE 'shop_order'", ARRAY_A);
 	foreach($orders as $order) {
		update_post_meta($order['ID'], '_biz_order_exported', 0);
		update_post_meta($order['ID'], '_biz_order_hint', '');
	}
}

/**
 * This deactives the plugin and the ablitly to upload and download.
 */
register_deactivation_hook( __FILE__ , 'bizunoapi_uninstall' );
function bizunoapi_uninstall() {
	global $wpdb;
	update_option('biz_active', false); //deacivate
}

/**
 * This removes all information related to this plugin
 */
register_uninstall_hook(__FILE__, 'bizunoapi_remove');
function bizunoapi_remove() {
		delete_option('biz_user');
		delete_option('biz_pw');
		delete_option('biz_url');
		delete_option('biz_comp');
		delete_option('biz_prefix');
		delete_option('biz_active');
		//multi site
		delete_site_option('biz_user');
		delete_site_option('biz_pw');
		delete_site_option('biz_url');
		delete_site_option('biz_comp');
		delete_site_option('biz_prefix');
		delete_site_option('biz_active');
		//add order fields
		$orders = $wpdb->get_results( "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_type` LIKE 'shop_order'", ARRAY_A);
		foreach($orders as $order) {
			delete_post_meta($order['ID'], 'biz_order_exported');
			delete_post_meta($order['ID'], 'biz_order_hint');
		}
}

/**
 * Add settings to the specific section we created before
 */
add_filter( 'woocommerce_get_settings_account', 'bizunoapi_all_setting', 10, 2 );
function bizunoapi_all_setting( $settings, $current_section ) {
		//Check the current section is what we want
		$settings[] = array( 'name' => __( 'Bizuno API Settings', 'bizuno' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure Bizuno API', 'bizuno' ), 'id' => 'bizunoapi' );

		$settings[] = array(
			'name'     => __( BIZUNO_APP_TITLE.' Login Username', 'bizuno' ),
			'desc_tip' => __( 'This is the bizuno login e-mail.', 'bizuno' ),
			'id'       => 'biz_user',
			'type'     => 'text',
			//'css'      => 'min-width:300px;',
			'desc'     => __( 'We suggest making a new user in bizuno for downloading orders, this is to track downloading orders to your system.', 'bizuno' )
		);
		$settings[] = array(
			'name'     => __( BIZUNO_APP_TITLE.' Login Password', 'bizuno' ),
			'desc_tip' => __( 'This is the bizuno login password.', 'bizuno' ),
			'id'       => 'biz_pw',
			'type'     => 'password',
			'desc'     => __( 'We suggest making a strong password.', 'bizuno' )
		);
		$settings[] = array(
			'name'     => __( 'Download PhreeBook Url', 'bizuno' ),
			'desc_tip' => __( 'Leave blank for bizuno users', 'bizuno' ),
			'id'       => 'biz_url',
			'type'     => 'text',
			'desc'     => __( 'example : https://{your phreebooks domain}.com', 'bizuno' )
		);
		$settings[] = array(
			'name'     => __( 'Download Company', 'bizuno' ),
			'desc_tip' => __( 'Leave blank for one company', 'bizuno' ),
			'id'       => 'biz_comp',
			'type'     => 'text',
			'desc'     => __( '', 'bizuno' )
		);
		$settings[] = array(
			'name'     => __( BIZUNO_APP_TITLE.' Order Prefix', 'bizuno' ),
			'desc_tip' => __( 'Leave blank for no prefix', 'bizuno' ),
			'id'       => 'biz_prefix',
			'type'     => 'text',
			'desc'     => __( '', 'bizuno' )
		);

		$settings[] = array( 'type' => 'sectionend', 'id' => 'bizunoapi' );

	return $settings;
}

/**
 * Adds download button
 * filter located at woonommerce/includes/admin/class-wc-admin-post-types.php (626)
 */
add_filter( 'woocommerce_admin_order_actions', 'bizunoapi_order_download_action', 10, 2);
function bizunoapi_order_download_action($actions, $the_order) {
	//get download status
	$active = get_option('biz_active', false );
	$downloaded = get_post_meta( $the_order->id, '_biz_order_exported', true );
	if( $downloaded == '0' && $active == 1 ) {
		$actions['download'] = array(
			'url'		=> admin_url( 'admin-ajax.php?action=bizunoapi_order_download&order_id=' . $the_order->id ),
			'name'		=> __( 'download', 'bizunoapi' ),
			'action'	=> "download"
		);
	}
	return $actions;
}

/**
 * Downloads order
 */
add_action('wp_ajax_bizunoapi_order_download','bizunoapi_download_order', 10);
function bizunoapi_download_order() {
	$active = get_option('biz_active', false);
	if($active == '') wp_die();
	require_once(dirname(__FILE__).'/bizunoAPI.php');
	$ctl = new bizunoAPI();
	$data = false;
	$data = apply_filters( "bizunoapi_data", $data, (int)$_GET['order_id'] );
	$result = $ctl->processSend('downloadOrder', (int)$_GET['order_id'], $data );
	//get responce
	$error = $warning = $success = "";
	if (isset($result) && is_array($result)) foreach ($result as $key => $value) {
		switch ($key) {
			case 'error':   foreach ($value as $msg) $error   .= $msg['text']."\n"; break;
			case 'caution':
			case 'warning': foreach ($value as $msg) $warning .= $msg['text']."\n"; break;
			case 'success': foreach ($value as $msg) $success .= $msg['text']."\n"; break;
		}
	} else {
		$error .= "Unexpected response from the recipient: ".print_r($result, true);
	}
	//add message to top of screen
	if($error) {
		//$error = substr($error,0,512) //default 512 char limit
		wp_safe_redirect( admin_url( 'edit.php?post_type=shop_order&bizunoapi_level=error&bizunoapi_message=' . urlencode($error) ) );
	} else if($warning) {
		//remove download button
		update_post_meta((int)$_GET['order_id'], '_biz_order_exported', 1);
		//$warning = substr($warning,0,512) //default 512 char limit
		wp_safe_redirect( admin_url( 'edit.php?post_type=shop_order&bizunoapi_level=warning&bizunoapi_message=' . urlencode($warning) ) );
	} else if($success) {
		//remove download button
		update_post_meta((int)$_GET['order_id'], '_biz_order_exported', 1);
		//$success = substr($success,0,512) //default 512 char limit
		wp_safe_redirect( admin_url( 'edit.php?post_type=shop_order&bizunoapi_level=success&bizunoapi_message=' . urlencode($success) ) );
	} else {
		wp_safe_redirect( admin_url( 'edit.php?post_type=shop_order&bizunoapi_level=error&bizunoapi_message=' . urlencode('No Responce.') ) );
	}
	die();
}

/**
 * Display message if there is one to display
 */
add_action( 'admin_notices', 'bizunoapi_alert' );
function bizunoapi_alert() {
	if ( isset( $_GET['bizunoapi_message'] ) && isset( $_GET['bizunoapi_level'] ) ) {
		$message = urldecode($_GET['bizunoapi_message']);
		$level = $_GET['bizunoapi_level'];
		switch($level) {
			case 'error': $class = "error"; break;
			case 'warning': $class = "update-nag"; break;
			case 'success': $class = "updated"; break;
			default: $class = "error"; break;
		}
		echo"<div class=\"$class\"> <p>$message</p></div>";
	}
}

/**
 * Adds bizuno meta to order (last call before completed)
 */
add_filter( 'woocommerce_payment_successful_result', 'bizunoapi_add_meta', 10, 2);
add_filter( 'woocommerce_checkout_no_payment_needed_redirect', 'bizunoapi_add_meta', 10, 2);
function bizunoapi_add_meta($result, $order_id) {
		if(is_int($order_id)) update_post_meta($order_id, '_biz_order_exported', 0);
		else {
			$id = $order_id->id;
			update_post_meta($id, '_biz_order_exported', 0);
		}
		//update_post_meta($order_id, '_biz_order_hint', '');
		return $result;
}
?>