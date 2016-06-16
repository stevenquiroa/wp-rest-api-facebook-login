<?php
/**
 * @package WP REST API - Facebook Login
 */
/*
Plugin Name: WP REST API - Facebook Login
Plugin URI: https://github.com/stevenquiroa/wp-rest-api-facebook-login
Description: 
Version: 0.0.1
Author: Steven Quiroa
Author URI: http://quiroa.me/
License: GPLv2 or later
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'WP_REST_API_FB_LOGIN_VERSION', '0.0.1' );
define( 'WP_REST_API_FB_LOGIN_MINIMUM_WP_VERSION', '4.5.2' );
define( 'WP_REST_API_FB_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_REST_API_FB_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// define( 'WP_REST_API_FB_LOGINDELETE_LIMIT', 100000 );

register_activation_hook( __FILE__, array( 'WP Template Email', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'WP Template Email', 'plugin_deactivation' ) );


function wp_rest_api_fb_login_load() {
	//Load Libraries
	require_once WP_REST_API_FB_LOGIN_PLUGIN_DIR . 'libs/facebook-php-sdk/autoload.php';
	require_once WP_REST_API_FB_LOGIN_PLUGIN_DIR . 'libs/php-jwt-v3/autoload.php';

	//Load Controllers
	require_once WP_REST_API_FB_LOGIN_PLUGIN_DIR . 'controllers/Controller.php';
	require_once WP_REST_API_FB_LOGIN_PLUGIN_DIR . 'controllers/AuthController.php';
}
add_action( 'init', 'wp_rest_api_fb_login_load' );