<?php
/**
 * Plugin Name: Fidelo Snippet
 * Plugin URI: https://www.fidelo.com
 * Description: The plugin links the forms for Fidelo school and agency software
 * Version: 1.12
 * Author: thebingsoftware
 * Author URI: https://www.fidelo.com
 * License: GPLv2
*/

require_once(__DIR__.'/tc/class.wordpress.php');
require_once(__DIR__.'/tc/class.snippet.php');

// add hook for start output buffering
add_action('init', function() {
	ob_start();
});

// add hook for flush output buffering
add_action('wp_footer', function() {
	if(ob_get_level() > 0) {
    	ob_end_flush();
	}
});

// add hook for thebingsnippet tag
add_shortcode('thebingsnippet', 'Thebing_Wordpress::getContent');
add_shortcode('fidelo-snippet', 'Thebing_Wordpress::getContent');
 
/**
 * register our thebingsnippet_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'Thebing_Wordpress::thebingsnippet_settings_init');

/**
 * register our thebingsnippet_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'Thebing_Wordpress::thebingsnippet_options_page');

add_action( 'generate_rewrite_rules', 'Thebing_Wordpress::thebingsnippet_generate_rewrite_rules');

add_filter( 'query_vars', 'Thebing_Wordpress::thebingsnippet_query_vars' );

add_filter( 'document_title_parts', 'Thebing_Wordpress::thebingsnippet_change_page_title' );

add_filter( 'the_title', 'Thebing_Wordpress::thebingsnippet_the_title', 10, 2);

// Damit Yoast nicht den Titel überschreibt bei den Kursdetailseiten
add_filter( 'wpseo_title', 'Thebing_Wordpress::thebingsnippet_yoast_title' );
