<?php

/*
Plugin Name: Post Network
Description: Visualize the relationship between articles based on internal links.
Author: Naoto Onodera
Author URI: https://notondr.com/
Plugin URI: https://notondr.com/post-network/
Version: 1.4.1
Text Domain: post-network
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Include includes directory
 */

foreach ( glob( dirname( __FILE__ ) . '/includes/*.php' ) as $file ) {
	require_once $file;
}

/**
 * Enqueue css files
 */
function pn_theme_enqueue_styles() {
	wp_enqueue_style( 'visjs-style', plugins_url( '/css/vis.min.css', __FILE__ ) );
	wp_enqueue_style( 'pn-style', plugins_url( '/css/style.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'pn_theme_enqueue_styles' );

/**
 * Enqueue javascript files
 */
function pn_theme_enqueue_scripts() {
	wp_enqueue_script( 'visjs', plugins_url( '/js/vis-network.min.js', __FILE__ ) );
	wp_enqueue_script( 'pn', plugins_url( '/js/pn.js', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'pn_theme_enqueue_scripts' );



/**
 * activate
 */
function pn_activation() {
	if ( ! get_option( PostNetwork::pn_get_option_name() ) ) {
		PostNetwork::pn_option_init();
	}
}

register_activation_hook( __FILE__, 'pn_activation' );


/**
 * Load
 */

if ( is_admin() ) {
	new PostNetwork();
}
