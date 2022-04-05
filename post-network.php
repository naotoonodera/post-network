<?php

/*
Plugin Name: Post Network
Description: Visualize the relationship between articles based on internal links.
Author: Naoto Onodera
Author URI: https://notondr.com/
Plugin URI: https://notondr.com/post-network/
Version: 1.3.0
Text Domain: post-network
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include includes directiory
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
}
add_action( 'admin_enqueue_scripts', 'pn_theme_enqueue_scripts' );
