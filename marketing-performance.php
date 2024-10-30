<?php
/*
 Plugin Name: Marketing Performance
 Plugin URI: http://vyrasage.com
 Description: Next generation attribution to visualize the complex multi-channel, multi-touch interactions that lead your customers to a conversion.
 Version: 2.0.0
 
 Author: VryaSage
 Author URI: http://vyrasage.com/about-us/
 Date: 12/05/2018
 
 The Marketing Performance Plugin is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by the Free Software 
 Foundation, either version 3 of the License, or any later version.
 
 The Marketing Performance Plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
 FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License along with WPForms. 
 If not, see <http://www.gnu.org/licenses/>.
 
 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/


/*
 Enqueue the jQuery script
*/
function vsmp_enqueue_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('vsmp-charts', plugins_url('scripts/vsmp-charts.js', __FILE__));

}
add_action('init', 'vsmp_enqueue_scripts');
function vsmp_enqueue_admin_scripts($page) {
	$screen = get_current_screen();
	if (($page == 'post-new.php' || $page == 'post.php')
		&& ($screen->post_type == 'vsmp_campaign' || $screen->post_type == 'vsmp_delivery_event' || $screen->post_type == 'vsmp_channel')) {
		wp_enqueue_script('vsmp-required', plugins_url('scripts/vsmp-required.js', __FILE__));
	}
}
add_action('admin_enqueue_scripts', 'vsmp_enqueue_admin_scripts');


/*
 Reference the vsmp-functions php file and the class files
*/
require_once(plugin_dir_path( __FILE__ ) . 'vsmp-functions.php');
require_once(plugin_dir_path( __FILE__ ) . 'classes/class.vsmp-campaign.php');
require_once(plugin_dir_path( __FILE__ ) . 'classes/class.vsmp-delivery_event.php');
require_once(plugin_dir_path( __FILE__ ) . 'classes/class.vsmp-channel.php');


/*
 Register the custom post types
 - vsmp_campaign
 - vsmp_delivery_event
 - vsmp_channel
*/
function vsmp_campaign() {
	register_post_type(
		'vsmp_campaign',
		array(
			'labels' => array(
				'name' => __('Campaigns'),
				'singular_name' => __('Campaign'),
				'add_new' => __('Create Campaign'),
				'add_new_item' => __('Create Campaign'),
				'edit_item' => __('Edit Campaign'),
				'new_item' => __('Create Campaign'),
				'view_item' => __('View Campaign'),
				'search_items' => __('Search Campaigns'),
				'not_found' => __('Campaign not found'),
				'not_found_in_trash' => __('Campaign not found in trash')
			),
			'public' => true,
			'has_archive' => true,
			'show_in_menu' => 'edit.php'
		)
	);
}
add_action('init', 'vsmp_campaign');


function vsmp_delivery_event() {
	register_post_type(
		'vsmp_delivery_event',
		array(
			'labels' => array(
				'name' => __('Delivery Events'),
				'singular_name' => __('Delivery Event'),
				'add_new' => __('Create Delivery Event'),
				'add_new_item' => __('Create Delivery Event'),
				'edit_item' => __('Edit Delivery Event'),
				'new_item' => __('Create Delivery Event'),
				'view_item' => __('View Delivery Event'),
				'search_items' => __('Search Delivery Events'),
				'not_found' => __('Delivery Event not found'),
				'not_found_in_trash' => __('Delivery Event not found in trash')
			),
			'public' => true,
			'has_archive' => true,
			'show_in_menu' => 'edit.php'
		)
	);
}
add_action('init', 'vsmp_delivery_event');


function vsmp_channel() {
	register_post_type(
		'vsmp_channel',
		array(
			'labels' => array(
				'name' => __('Channels'),
				'singular_name' => __('Channel'),
				'add_new' => __('Create Channel'),
				'add_new_item' => __('Create Channel'),
				'edit_item' => __('Edit Channel'),
				'new_item' => __('Create Channel'),
				'view_item' => __('View Channel'),
				'search_items' => __('Search Channels'),
				'not_found' => __('Channel not found'),
				'not_found_in_trash' => __('Channel not found in trash')
			),
			'public' => true,
			'has_archive' => true,
			'show_in_menu' => 'edit.php'
		)
	);
}
add_action('init', 'vsmp_channel');


/*
 Add custom fields
*/
function vsmp_custom_fields_input() {
	global $post;
	if ( $post->post_type == 'vsmp_campaign' ) {	
		vsmp_campaign_custom_fields_input($post->ID);		
	} elseif ( $post->post_type == 'vsmp_delivery_event' ) {		
		vsmp_delivery_event_custom_fields_input($post->ID);	
	} elseif ( $post->post_type == 'vsmp_channel' ) {
		vsmp_channel_custom_fields_input($post->ID);	
	} elseif ( $post->post_type == 'page' || $post->post_type == 'post' ) {
		vsmp_global_custom_fields_input();
	}
}
add_action('add_meta_boxes', 'vsmp_custom_fields_input');


/*
 Save custom fields
*/
function vsmp_custom_fields_save() {
	global $post;
	if (isset($post)) {
		if ( $post->post_type == 'vsmp_campaign' ) {	
			vsmp_campaign_custom_fields_save($post->ID);		
		} elseif ( $post->post_type == 'vsmp_delivery_event' ) {		
			vsmp_delivery_event_custom_fields_save($post->ID);	
		} elseif ( $post->post_type == 'vsmp_channel' ) {
			vsmp_channel_custom_fields_save($post->ID);	
		} elseif ( $post->post_type == 'page' || $post->post_type == 'post' ) {
			vsmp_global_custom_fields_save();
		}
	}
}
add_action('save_post', 'vsmp_custom_fields_save');


function append_post_meta($content) {
	global $post;
	if ( is_single() ) {
		if ( $post->post_type == 'vsmp_campaign' ) {	
			$content = vsmp_campaign_custom_html_view($post->ID);		
		} elseif ( $post->post_type == 'vsmp_delivery_event' ) {		
			$content = vsmp_delivery_event_custom_html_view($post->ID);
		} elseif ( $post->post_type == 'vsmp_channel' ) {
			$content = vsmp_channel_custom_html_view($post->ID);
		}
	}
	return $content;
}
add_filter('the_content', 'append_post_meta');


/*
 Set up menu items:
 - Campaign Setup guides the user through setting up the campaign and delivery events
 - Channel Setup guides the user through setting up channels
 - Test Data Generator allows testing of the basic functions of the plugin
 - Attribution Report allows the user to query by date the attribution and most common marketing paths
 - Campaign Performance Report shows the user whether their campaign has met the estimate goal
*/
function vsmp_marketing_menu() {
	add_menu_page(
		'Marketing Performance',
		'Marketing Performance',
		'manage_options',
		'vsmp_marketing_performance',
		'vsmp_marketing_performance_link',
		 'dashicons-chart-pie',
		66
	);
	add_submenu_page(
		'vsmp_marketing_performance',
		'Campaigns & Events',
		'Campaigns & Events',
		'manage_options',
		'vsmp_campaign_setup',
		'vsmp_campaign_setup_link'
	);
	add_submenu_page(
		'vsmp_marketing_performance',
		'Channels',
		'Channels',
		'manage_options',
		'vsmp_channel_config',
		'vsmp_channel_config_link'
	);
	add_submenu_page(
		'vsmp_marketing_performance',
		'Test Data Generator',
		'Test Data Generator',
		'manage_options',
		'vsmp_test_data_generator',
		'vsmp_test_data_generator_link'
	);
	add_submenu_page(
		'vsmp_marketing_performance',
		'Attribution Report',
		'Attribution Report',
		'manage_options',
		'vsmp_attribution_report',
		'vsmp_attribution_report_link'
	);
	add_submenu_page(
		'vsmp_marketing_performance',
		'Campaign Performance Report',
		'Campaign Performance Report',
		'manage_options',
		'vsmp_campaign_performance_report',
		'vsmp_campaign_performance_report_link'
	);
	add_submenu_page(
		'vsmp_marketing_performance',
		'Commissions Report',
		'Commissions Report',
		'manage_options',
		'vsmp_commissions_report',
		'vsmp_commissions_report_link'
	);
};
add_action('admin_menu', 'vsmp_marketing_menu');

function vsmp_marketing_performance_link() {
	require_once dirname(__FILE__) . '/adminpages/vsmp-marketing_performance.php';
}
function vsmp_channel_config_link() {
	require_once dirname(__FILE__) . '/adminpages/vsmp-channel_config.php';
}
function vsmp_campaign_setup_link() {
	require_once dirname(__FILE__) . '/adminpages/vsmp-campaign_setup.php';
}
function vsmp_test_data_generator_link() {
	require_once dirname(__FILE__) . '/adminpages/vsmp-test_data_generator.php';
}
function vsmp_attribution_report_link() {
	require_once dirname( __FILE__ ) . '/adminpages/vsmp-attribution_report.php';
}
function vsmp_campaign_performance_report_link() {
	require_once dirname( __FILE__ ) . '/adminpages/vsmp-campaign_performance_report.php';
}
function vsmp_commissions_report_link() {
	require_once dirname( __FILE__ ) . '/adminpages/vsmp-commissions_report.php';
}


/*
 Capture click through and conversion data
*/
add_action( 'init', 'vsmp_set_visitor_Id' );
add_action( 'wp_head', 'vsmp_data_capture' );

/*
 Register the table and procedures for when the plugin is activated /deactivated
*/
function vsmp_attribution_activate() {
	require_once dirname( __FILE__ ) . '/activate/vsmp-run_ddl.php';
	vsmp_run_ddl( 'vsmp-install.sql' );
}
register_activation_hook( __FILE__, 'vsmp_attribution_activate' );
function vsmp_attribution_deactivate() {
	require_once dirname( __FILE__ ) . '/activate/vsmp-run_ddl.php';
	vsmp_run_ddl( 'vsmp-uninstall.sql' );
}
register_deactivation_hook( __FILE__, 'vsmp_attribution_deactivate' );


/*
 Setup cron job to compute attribution daily (8:00 in the morning GMT)
*/
function wp_register_compute() {
	if ( ! wp_next_scheduled( 'wp_compute' ) ) {
		$compute_time = new DateTime();
		$compute_time->setTime( 8, 0, 0 );
		$compute_time_string = $compute_time->format( 'Y-m-d H:i:s' );
		$compute_time_unix = strtotime( $compute_time_string );
		wp_schedule_event( $compute_time_unix, 'daily', 'wp_compute' );
	}
}
add_action( 'init', 'wp_register_compute' );
add_action( 'wp_compute', 'wp_compute' );
	
?>