<?php
/*
Plugin Name: Influenet
Plugin URI: https://influenet.com/
Description: <a href="https://influenet/register/">Sign up for an Influenet API key</a>, and Go to your <a href="admin.php?page=influenet_options">Influenet configuration</a> page, and save your API key.
Version: 1.0.0
Author: influenet
Author URI: https://influenet.com/
*/

if ('influenet.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die (__('Please do not access this file directly. Thanks!', INFLUENET_PLUGIN_NAME_));

class influenet_class
{
	function __construct()
	{
		
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		$this->load_dependencies();

		$this->define_rest_hooks();
		
	}
	
	static function activation()
	{
		
		add_option( 'influenet_api_key', '');
		add_option( 'influenet_author', '1');

	}
	
	static function deactivation()
	{
		
		delete_option( 'influenet_api_key' );
		delete_option( 'influenet_author_id' );

	}

	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'influenet/inc/class-influenet-rest.php';

	}
	
	function admin_menu()
	{

		add_menu_page( __('Influenet Options', INFLUENET_PLUGIN_NAME_), 'Influenet', 'manage_options', 'influenet_options', array( &$this, 'admin_options' ), get_option('siteurl') . '/wp-content/plugins/' . basename( dirname( __FILE__ ) ) . '/images/icon16.png' );

	}

	function admin_options()
	{

		include 'inc/influenet-admin.php';

	}

	private function define_rest_hooks() {
	    
	    $plugin_rest = new Influenet_Rest( INFLUENET_PLUGIN_NAME_ );
	    
	    add_action( 'rest_api_init', array( $plugin_rest, 'register_routes' ), 10, 1 );

	    add_filter( 'determine_current_user', array( $plugin_rest, 'determine_current_user' ), 10, 1 );
	    add_filter( 'rest_authentication_errors', array( $plugin_rest, 'rest_authentication_errors' ), 10, 1 );
	    
	    add_action( 'init', array( $plugin_rest, 'register_endpoints' ), 10, 1 );
	    add_action( 'template_redirect', array( $plugin_rest, 'intercept_request' ), 10, 1 );

	}
}


define('INFLUENET_PLUGIN_NAME_', 'influenet');
define( 'INFLUENET_PLUGIN_PATH_', plugins_url( '', __FILE__ ) );
load_plugin_textdomain( INFLUENET_PLUGIN_NAME_, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

$influenet_class = new influenet_class();

?>