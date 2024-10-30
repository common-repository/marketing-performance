<?php
/*
 Functions file for The Marketing Performance Plugin
 
 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

/*
 Create global JavaScript variable for Ajax and normal posts
*/
function vsmp_url() {
	echo '<script type="text/JavaScript">';
	echo 'var vsmp_ajaxurl = "' . admin_url('admin-ajax.php') . '";';
	echo 'var vsmp_posturl = "' . admin_url('admin-post.php') . '";';
	echo "</script>";
}
add_action( 'admin_head', 'vsmp_url' );


/*
 Create a cookie with a unique visitor Id
*/
function vsmp_set_visitor_id() {
	if ( ! isset($_COOKIE['vsmp_visitor']) ) {

		$v1 = time();
		$v2 = $_SERVER['REMOTE_ADDR'];
		$v3 = rand( 1, 100 );
	
		$vsmp_visitor_id = hash( 'ripemd128', $v1 . $v2 . $v3 );

		$cookie_exp = mktime( 23, 59, 59, 12, 31, 2037 );
		
		setcookie( 'vsmp_visitor', $vsmp_visitor_id, strval( $cookie_exp ) , '/' );
	}
}

/*
 Get visitor Id from the cookie.  If no cookie, use IP Address and User Agent
 Note: A mobile device with cookies disabled will not have a static IP Address.  However using IP Address
 and User Agent is the best technique the author could find
*/
function vsmp_get_visitor_id() {
	
	$vsmp_visitor_id = NULL;

	if ( isset( $_COOKIE['vsmp_visitor'] ) ) {
		if ( preg_match( '/^[0-9a-f]{32}$/i' , $_COOKIE['vsmp_visitor'] ) === 1 ) {
			$vsmp_visitor_id = $_COOKIE['vsmp_visitor'];
		}
	}
	
	if ( ! isset( $vsmp_visitor_id ) ) {
		$v1 = $_SERVER['REMOTE_ADDR'];
		$v2 = $_SERVER['HTTP_USER_AGENT'];
		$vsmp_visitor_id = hash( 'ripemd128', $v1 . $v2 );		
	}
	
	return $vsmp_visitor_id;
		
}	


/*
 Helper function to build the select list of custom post types
 
 Parameters:
 
 $cpt_name
	(string) The custom post type being queried.  The object name will appear in the Id and Name attributes
	of the select list
 $id_name
    (string) The alias Id of the custom post type.  This will appear in the HTML and the post meta for any
	associated custom post type.  For example, the custom post type may be vsmp_campaign but the $id_name will
	be campaign_id.
 $order_by
	(string) The order that the select options appear.  Common values are ID and title.  See Wordpress Codex for
	more information.
 $order
	(string) Sort order: ASC = ascending or DESC = descending
 $sel_post_id (optional)
	(integer) Current selected Id to set in the select options.
 $base_post_id (optional)
	(integer) If $sel_post_id is not set, this will look up the Id to mark as selected from a related custom post type.
	Related custom post type stores the Id in post meta where the meta_key is the custom post type.  For example, a delivery 
	event is related to a campaign.  $cpt_name will contain 'vsmp_campaign' and $base_post_id will contain the Id of the 
	delivery event.  Code will retrieve the related campaign Id using that delivery event's post meta with key 'vsmp_campaign'.
 $include_id (optional)
    (boolean) Include the Id in the option text
 $submit (optional)
	(boolean) Adds an onchange form submit to the <SELECT> attributes 
*/
function vsmp_cpt_select( $cpt_name, $id_name, $order_by, $order, $sel_cpt_id=NULL, $include_id=FALSE, $submit=FALSE ) {

	$select = '<SELECT NAME="' . $id_name . '" ID="' . $id_name . '" required';
	if ( $submit == TRUE ) {
		$select = $select . ' onchange="this.form.submit()"';
	}
	$select = $select . '>';
	$select = $select . '<option disabled selected value>-- select an option -- </option>';
	
	$cpt_query = new WP_Query(
		array(
			'post_type' => $cpt_name,
			'orderby' => $order_by,
			'order' => $order,
			'posts_per_page' => -1
		)
	);
	if ( $cpt_query->have_posts() ) {
		while ( $cpt_query->have_posts() ) {
			$cpt_query->the_post();
			$cpt_id = get_the_ID();
			$cpt_desc = get_the_title();
			$select = $select . '<OPTION VALUE=' . $cpt_id;
			if ( isset( $sel_cpt_id ) && $sel_cpt_id == $cpt_id ) {
				$select = $select . ' SELECTED';
			}
			$select = $select . '>';
			if ( $include_id == TRUE ) {
				$select = $select . $cpt_id . ': ';
			}
			$select = $select . $cpt_desc; 
			$select = $select . '</OPTION>';
		}
	} else {
		$select = $select . 'No ' . $cpt_obj . 's found.';
	}
				
	$select = $select . '</SELECT>';
	
	return $select;

}


/*
 Manage custom fields for campaign
 
 from_date (optional)
	(date) Starting date of campaign
 to_date (optional)
	(date) Ending date of campaign
 spend (optional)
	(number) Actual amount expended on the campaign (required for performance reporting)
 est_roas (optional)
	(number) Expected return on ad spend (required for performance reporting)
*/
function vsmp_campaign_custom_fields_input( $campaign_id ) {
	global $campaign;
	$campaign = new vsmp_campaign();
	$campaign->loadById( $campaign_id );
	add_meta_box(
		'from_date',
		'From Date',
		'vsmp_from_date_html_input',
		'vsmp_campaign',
		'normal',
		'low'
	);
	add_meta_box(
		'to_date',
		'To Date',
		'vsmp_to_date_html_input',
		'vsmp_campaign',
		'normal',
		'low'
	);
	add_meta_box(
		'spend',
		'Spend',
		'vsmp_spend_html_input',
		'vsmp_campaign',
		'normal',
		'low'
	);
	add_meta_box(
		'EstROAS',
		'Estimated Return on Ad Spend (ROAS)',
		'vsmp_est_roas_html_input',
		'vsmp_campaign',
		'normal',
		'low'
	);
}
function get_from_date_mask() {
	return '<input type=date id="from_date" name="from_date" value=""  style="width:180px;" required>';
}
function get_to_date_mask() {
	return '<input type=date id="to_date" name="to_date" value=""  style="width:180px;" required>';
}
function get_spend_mask() {
	return '<input type="number" min="0.00" step="0.01" max="10000" id="spend" name="spend" style="width:100px;" value="">';
}
function get_est_roas_mask() {
	return '<input type="number" min="0.00" step="0.1" max="100" id="est_roas" name="est_roas" style="width:100px;" value="">';
}
function vsmp_from_date_html_input() {
	global $campaign;
	echo $campaign->get_from_date_html_input();
}
function vsmp_to_date_html_input() {
	global $campaign;
	echo $campaign->get_to_date_html_input();
}
function vsmp_spend_html_input() {
	global $campaign;
	echo $campaign->get_spend_html_input();
}
function vsmp_est_roas_html_input() {
	global $campaign;
	echo $campaign->get_est_roas_html_input();
}


function vsmp_campaign_custom_fields_save( $campaign_id ) {
	global $campaign;
	$campaign = new vsmp_campaign();
	$campaign->loadById( $campaign_id );
	if ( isset($_POST["from_date"]) ) {
		$campaign->set_from_date( $_POST["from_date"] );
	}
	if ( isset($_POST["to_date"]) ) {
		$campaign->set_to_date( $_POST["to_date"] );
	}
	if ( isset($_POST["spend"]) ) {
		$campaign->set_spend( $_POST["spend"] );
	}
	if ( isset($_POST["est_roas"]) ) {
		$campaign->set_est_roas( $_POST["est_roas"] );		
	}
	$campaign->save_custom_fields();
}

function vsmp_campaign_custom_html_view( $campaign_id ) {		
	$campaign = new vsmp_campaign();
	$campaign->loadById( $campaign_id );
	return '<table>'
		. '<tr><td>From Date</td><td>' . $campaign->get_from_date_html_view() . '</td></tr>'
		. '<tr><td>To Date</td><td>' . $campaign->get_to_date_html_view() . '</td></tr>'
		. '<tr><td>Spend</td><td>' . $campaign->get_spend_html_view()  . '</td></tr>'
		.	'<tr><td>Estimated Return on Ad Spend</td><td>' . $campaign->get_est_roas_html_view() . '</td></tr>'
		. '</table>';
}


/*
 Manage custom fields for delivery events
  
 campaign_id
	(integer) Related campaign Id
 channel_id
	(integer) Related channel Id
 query_parm
	(string) Not stored in post meta.  It contains the parameter to add to the URL on clickthrough
*/
function vsmp_delivery_event_custom_fields_input( $delivery_event_id ) {
	global $delivery_event;
	$delivery_event = new vsmp_delivery_event();
	$delivery_event->loadById( $delivery_event_id );
	
	add_meta_box(
		'campaign_id',
		'Campaign',
		'vsmp_campaign_html',
		'vsmp_delivery_event',
		'normal',
		'high'
	);
	add_meta_box(
		'channel_id',
		'Channel',
		'vsmp_channel_html',
		'vsmp_delivery_event',
		'normal',
		'high'
	);
	add_meta_box(
		'commission',
		'Commission Schedule  (Optional, use when tracking commissions for affiliates)',
		'vsmp_commission_html',
		'vsmp_delivery_event',
		'normal',
		'low'
	);
}
function vsmp_campaign_html() {
	global $delivery_event;
	echo $delivery_event->get_campaign_id_html_input();
}
function vsmp_channel_html() {
	global $delivery_event;
	echo $delivery_event->get_channel_id_html_input();
}
function vsmp_commission_html() {
	global $delivery_event;
	echo $delivery_event->get_commission_html_input();
}


function vsmp_delivery_event_custom_fields_save( $delivery_event_id ) {
	global $delivery_event;
	$delivery_event = new vsmp_delivery_event();
	$delivery_event->loadById( $delivery_event_id );
	if ( isset($_POST["campaign_id"]) ) {
		$delivery_event->set_campaign_id( $_POST["campaign_id"] );
	}
	if ( isset( $_POST["channel_id"]) ) {
		$delivery_event->set_channel_id( $_POST["channel_id"] );
	}
	if ( isset( $_POST["commission_days"]) ) {
		$delivery_event->set_commission_days( $_POST["commission_days"] );
	}
	if ( isset( $_POST["commission_pct"]) ) {
		$delivery_event->set_commission_pct( $_POST["commission_pct"] );
	}
	$delivery_event->save_custom_fields();
}

function vsmp_delivery_event_custom_html_view( $delivery_event_id ) {		
	$delivery_event = new vsmp_delivery_event();
	$delivery_event->loadById( $delivery_event_id );
	$html = '<table>';
	$html = $html . '<tr><td>Campaign</td><td>' . $delivery_event->get_campaign_id_html_view() . '</td></tr>';
	$html = $html . '<tr><td>Channel</td><td>' . $delivery_event->get_channel_id_html_view()[0] . '</td></tr>';
	$html .= '<tr><td>Commission Schedule</td><td>' . $delivery_event->get_commission_html_view() . '</td></tr>';
	$html = $html . '<tr><td>How to Detect This Delivery Event</td><td>';
	if ( $delivery_event->get_channel_id_html_view()[1] == 'srch_eng' ) {
		$html = $html . 'Look for search engines in the referrer.';
	} else {
		$html = $html . 'Find vsmp=' . $delivery_event_id . ' in the query string.';	
	}
	$html = $html . '</td></tr>';
	$html = $html . '</table>';
	return $html;
}

/*
 Manage custom fields for channel
  
 lag_days
	(integer) Number of days a click-through of a delivery event influences a conversion (0-9)
 influence_[0-9]
	(integer) Array of percent influence by day of a delivery event click-through.  Array size will match
	the lag days.
*/
function vsmp_channel_custom_fields_input( $channel_id ) {
	global $channel;
	$channel = new vsmp_channel();
	$channel->loadById( $channel_id );
	
	add_meta_box(
		'lag_days',
		'Lag Days',
		'vsmp_lag_days_html',
		'vsmp_channel',
		'normal',
		'high'
	);
	
	add_meta_box(
		'influence',
		'Influence Lag',
		'vsmp_influence_html',
		'vsmp_channel',
		'normal',
		'high'
	);
	add_meta_box(
		'detection',
		'How to Detect This Channel',
		'vsmp_detection_html',
		'vsmp_channel',
		'normal',
		'high'
	);
}

function get_lag_days_mask() {
	return '<input type=range id="lag_days" name="lag_days" min="0" step="1" max="9" value="" list="vsmp-tick0-9" required style="width: 300px;">'
		. '<datalist id="vsmp-tick0-9">'
		. '<option value="0" label="0">'
		. '<option value="1" label="1">'
		. '<option value="2" label="2">'
		. '<option value="3" label="3">'
		. '<option value="4" label="4">'
		. '<option value="5" label="5">'
		. '<option value="6" label="6">'
		. '<option value="7" label="7">'
		. '<option value="8" label="8">'
		. '<option value="9" label="9">'
		. '</datalist>';
}
function get_influence_mask( $day_no ) {
	$html = '<input type=range id="influence_' . $day_no . '" name="influence_'. $day_no . '" min="0" step="1" max="100" value="" list="vsmp-tick0-100" required style="width: 300px;">';
	if ( $day_no == 0 ) {
		$html = $html 
			. '<datalist id="vsmp-tick0-100">'
			. '<option value="0" label="0%">'
			. '<option value="20" label="20%">'
			. '<option value="40" label="40%">'
			. '<option value="60" label="60%">'
			. '<option value="80" label="80%">'
			. '<option value="100" label="100%">'
			. '</datalist>';
	}
	return $html;
}
function get_detection_mask() {
	$html = '<fieldset>';
	$html = $html . '<input type="radio" name="detection" id="url" value="url" checked="checked">';
	$html = $html . '<label for="query_string">Find vsmp={nnn} in the query string</label>';
	$html = $html . '<BR><input type="radio" name="detection" id="srch_eng" value="srch_eng">';
	$html = $html . '<label for="referrer">Look for search engines in the referrer</label>';
	$html = $html . '</fieldset>';
	return $html;
}

function vsmp_lag_days_html() {
	global $channel;
	echo $channel->get_lag_days_html_input();
}

function vsmp_influence_html() {
	global $channel;
	echo $channel->get_influence_html_input();
}

function vsmp_detection_html() {
	global $channel;
	echo $channel->get_detection_html_input();  
}

function vsmp_channel_custom_fields_save( $channel_id ) {
	global $campaign;
	$channel = new vsmp_channel();
	$channel->loadById( $channel_id );
	if ( isset($_POST["lag_days"]) ) {
		if ( $channel->set_lag_days( $_POST["lag_days"] ) ) {
			$influence = [];
			for ( $i = 0; $i <= $channel->lag_days; $i++ ) {
				if ( isset( $_POST["influence_" . $i] ) ) {
					$influence[$i] = $_POST["influence_" . $i];
					$channel->set_influence( $influence );
				}
			}	
		}
	}
	if ( isset($_POST["detection"]) ) {
		$channel->set_detection( $_POST["detection"] );
	}
	$channel->save_custom_fields();
}

function vsmp_channel_custom_html_view( $channel_id ) {
	$channel = new vsmp_channel();
	$channel->loadById( $channel_id );
	$html = '<table>'
		. '<tr><td>Description</td><td>' . $channel->description . '</td></tr>'
		. '<tr><td>Lag Days</td><td>' . $channel->get_lag_days_html_view() . '</td></tr>'
		. '<tr><td>Lag</td><td><canvas id="influenceCanvas" width="200" height="200"></canvas></td></tr>';
			
	$html = $html . '<script>';
	$html = $html .  'var influence = [];';
	for ( $i = 0; $i <= $channel->lag_days; $i++ ) {
		$html = $html .  'influence.push(' . $channel->influence[$i] . ');';
	}
	
	$line_chart_color = sprintf( "#%06x", mt_rand(0,16777215) );		

	$html = $html .  'var influenceCanvas = document.getElementById("influenceCanvas");';
	$html = $html .  'var influenceLinechart = new Linechart('
		. '{'
		. 'canvas:influenceCanvas,'
		. 'title:"Lag",' 
		. 'data:influence,'
		. 'color:"' . $line_chart_color . '",'
		. '}'
		. ');';
	$html = $html . 'influenceLinechart.draw();';
	$html = $html . '</script>';
	
	$html = $html . '<tr><td>How to Detect This Channel</td><td>' . $channel->get_detection_html_view() . '</td></tr>';
	$html = $html . '</table>';
	return $html;
}


/* 
 Log click through on a delivery event
*/
function vsmp_log_delivery_event( $vsmp_visitor_id, $delivery_event_id ) {
	
	$valid_vsmp_visitor_id = ( ! empty( $vsmp_visitor_id ) );	
	$valid_delivery_event_id = ( preg_match( '/^\d+$/' , $delivery_event_id ) === 1 );
	if ( ! $valid_vsmp_visitor_id or ! valid_delivery_event_id ) {
		return 'baddata';
	}
	
	if ( $vsmp_visitor_id == '*COOKIE' ) {
		$vsmp_visitor_id = vsmp_get_visitor_id();
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'vsmp_delivery_event_log';
	
	$wpdb->insert(
		$table_name,
		array(
			'vsmp_visitor_id' => $vsmp_visitor_id,
			'delivery_event_id' => $delivery_event_id
		)
	);
	
	return 'ok';
	
}	

/*
 Log a conversion
*/
function vsmp_log_conversion($vsmp_visitor_id, $conversion_id, $conversion_value) {

	$valid_vsmp_visitor_id = ( ! empty( $vsmp_visitor_id ) );	
	$valid_conversion_id = ( ! empty( $conversion_id ) );
	$valid_conversion_value = ( preg_match( '/^\d+(\.\d+)?$/' , $conversion_value ) === 1 );
	if ( ! $valid_vsmp_visitor_id or ! valid_conversion_id or ! valid_conversion_value ) {
		return 'baddata';
	}

	if ( $vsmp_visitor_id == '*COOKIE' ) {
		$vsmp_visitor_id = vsmp_get_visitor_id();
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'vsmp_conversion_log';
	
	$result = $wpdb->get_row( "SELECT COUNT(*) AS conversion_count FROM {$wpdb->prefix}vsmp_conversion_log WHERE conversion_id = '" . addslashes( $conversion_id ) . "'" );
	
	if ( $result->conversion_count > 0 ) {
		return "dupid";
	}
	
	$wpdb->insert(
		$table_name,
		array(
			'vsmp_visitor_id' => $vsmp_visitor_id,
			'conversion_id' => $conversion_id,
			'conversion_val' => $conversion_value
		)
	);
	
	return "ok";
	
}

function wp_compute() {
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare( "CALL {$wpdb->prefix}vsmp_compute_influence('{$wpdb->prefix}')", array() )
	);
	$wpdb->query(
		$wpdb->prepare( "CALL {$wpdb->prefix}vsmp_compute_attribution('{$wpdb->prefix}')",  array() )
	);
	$wpdb->query(
		$wpdb->prepare( "CALL {$wpdb->prefix}vsmp_compute_path('{$wpdb->prefix}')",  array() )
	);
	$wpdb->query(
		$wpdb->prepare( "CALL {$wpdb->prefix}vsmp_compute_commission('{$wpdb->prefix}')",  array() )
	);
	echo 'Compute step has been run.  It will be run again in 24 hours.';
}


/*
 Get a channel object using Ajax
 (action=get_channel&vsmp_channel_id={custom post type Id})
*/
function wp_ajax_get_channel() {
	$channel_id = sanitize_text_field( $_POST['vsmp_channel_id'] );
	if ( ! is_numeric ( $channel_id ) ) {
		wp_send_json_error('Channel could not be retrieved.');
	} else {
		$channel = new vsmp_channel();
		$json_result = $channel->loadById( $channel_id );
		if ( $json_result ) {
			echo json_encode( $channel );
		} else {
			wp_send_json_error('Channel could not be retrieved.');
		}
	}
	die();
}
add_action( 'wp_ajax_get_channel', 'wp_ajax_get_channel' );


/*
 Insert/Update a channel using Ajax
 (action=upsert_channel&payload={json representation of a channel object})
*/
function wp_ajax_upsert_channel() {
	
	$payload = $_POST['payload'];
	$payload = stripslashes( urldecode( $payload ) );
	$vsmp_channel_config_nonce = ( $_POST['vsmp-channel_config_nonce'] );
	$valid_nonce = wp_verify_nonce( $vsmp_channel_config_nonce, 'vsmp-channel_config' );
	if ( $valid_nonce === FALSE ) {	
		wp_send_json_error( 'Not authorized.' );
	} else {	
		$channel = new vsmp_channel();
		$json_result = $channel->loadByJson( $payload );
		if ( $json_result ) {
			$channel->save();
			echo '{"id":"' . $channel->id . '", "success":"true" }';
		} else {
			wp_send_json_error( 'Channel could not be created or changed due to bad data.' );
		}
	}
	die();
}
add_action( 'wp_ajax_upsert_channel', 'wp_ajax_upsert_channel' );


/*
 Insert a campaign using Ajax
 (action=insert_campaign&payload={json representation of a campaign object}
*/
function wp_ajax_insert_campaign() {

	$payload = $_POST['payload'];
	$payload = stripslashes( urldecode( $payload ) );
	$vsmp_campaign_setup_nonce = ( $_POST['vsmp-campaign_setup_nonce'] );
	$valid_nonce = wp_verify_nonce( $vsmp_campaign_setup_nonce, 'vsmp-campaign_setup' );
	if ( $valid_nonce === FALSE ) {	
		wp_send_json_error( 'Not authorized.' );
	} else {
		$campaign = new vsmp_campaign();
		$json_result = $campaign->loadByJson( $payload );
		if ( $json_result ) {
			$campaign->save();
			echo '{"html":' . json_encode( vsmp_cpt_select( 'vsmp_campaign', 'campaign_id', 'ID', 'desc', $campaign->id ) ) . ' }';
		} else {
			wp_send_json_error(	'Campaign could not be created due to bad data.' );
		}
	}
	die();
}
add_action( 'wp_ajax_insert_campaign', 'wp_ajax_insert_campaign' );


/*
 Insert a delivery event using Ajax
 (action=insert_delivery_event&payload={json representation of a delivery event object}
*/
function wp_ajax_insert_delivery_event() {
	
	$payload = $_POST['payload'];
	$payload = stripslashes( urldecode( $payload ) );
	$vsmp_campaign_setup_nonce = ( $_POST['vsmp-campaign_setup_nonce'] );
	$valid_nonce = wp_verify_nonce( $vsmp_campaign_setup_nonce, 'vsmp-campaign_setup' );
	if ( $valid_nonce === FALSE ) {	
		wp_send_json_error( 'Not authorized.' );
	} else {
		$delivery_event = new vsmp_delivery_event();
		$json_result = $delivery_event->loadByJson( $payload );
		if ( $json_result ) {
			$delivery_event->save();
			echo '{"id":"' . $delivery_event->id . '"}';
		} else {	
			wp_send_json_error( 'Delivery event could not be created due to bad data.' );
		}
	}
	die();
}
add_action( 'wp_ajax_insert_delivery_event', 'wp_ajax_insert_delivery_event' );


/*
 Insert a delivery event click-through using Ajax
 (action=insert_delivery_event_click_through&vsmp_visitor_id={visitor Id -- leave null to pull from cookie}&delivery_event={Id of delivery event})
*/
function wp_ajax_insert_click_thru() {
	$vsmp_visitor_id = stripslashes( urldecode( sanitize_text_field( $_POST['vsmp_visitor_id'] ) ) );
	$delivery_event_id = sanitize_text_field( $_POST['delivery_event_id'] );
	$vsmp_test_data_generator_nonce = ( $_POST['vsmp-test_data_generator_nonce'] );
	$valid_nonce = wp_verify_nonce( $vsmp_test_data_generator_nonce, 'vsmp-test_data_generator' );
	if ( $valid_nonce === FALSE ) {
		$rtn_code = 'nonce';
	} else {
		$rtn_code = vsmp_log_delivery_event( $vsmp_visitor_id, $delivery_event_id );
	}		
	echo '{"result":"' . $rtn_code . '"}';
	die();
}
add_action( 'wp_ajax_insert_click_thru', 'wp_ajax_insert_click_thru' );


/*
 Insert a conversion using Ajax
 (action=insert_conversion&vsmp_visitor_id={visitor Id -- leave null to pull from cookie}&conversion_id={Conversion Id}&conversion_val={Conversion Value})
*/
function wp_ajax_insert_conversion() {
	$vsmp_visitor_id = stripslashes( urldecode( sanitize_text_field( $_POST['vsmp_visitor_id'] ) ) );
	$conversion_id = stripslashes( urldecode( sanitize_text_field( $_POST['conversion_id'] ) ) );
	$conversion_value = sanitize_text_field( $_POST['conversion_val'] );
	$vsmp_test_data_generator_nonce = ( $_POST['vsmp-test_data_generator_nonce'] );
	$valid_nonce = wp_verify_nonce( $vsmp_test_data_generator_nonce, 'vsmp-test_data_generator' );
	if ( $valid_nonce === FALSE ) {
		$rtn_code = 'nonce';
	} else {
		$rtn_code = vsmp_log_conversion( $vsmp_visitor_id, $conversion_id, $conversion_value );
	}
	echo '{"result":"' . $rtn_code . '"}';
	die();
}
add_action( 'wp_ajax_insert_conversion', 'wp_ajax_insert_conversion' );


function vsmp_woocommerce_conversion( $conversion_id ) {
	$vsmp_visitor_id = vsmp_get_visitor_id();
	$conversion = wc_get_order( $conversion_id );
	$conversion_value = $conversion->get_total();
	vsmp_log_conversion( $vsmp_visitor_id, $conversion_id, $conversion_value );
}
add_action( 'woocommerce_thankyou', 'vsmp_woocommerce_conversion' );
	

/*
 Compute results using Ajax
 (action=compute)
*/
function wp_ajax_compute() {
	wp_compute();
}
add_action( 'wp_ajax_compute', 'wp_ajax_compute' );


/*
 Reset the campaign setup page
 (action=campaign_setup)
*/
function wp_ajax_reset_campaign_setup() {
	vsmp_campaign_setup_link();
}
add_action( 'wp_ajax_reset_campaign_setup', 'wp_ajax_reset_campaign_setup' );



/*
 For development only
function vsmp_debug($debug_string) {

	global $wpdb;
	$table_name = 'tmp_debug';
	
	$wpdb->insert(
		$table_name,
		array(
			'debug_string' => $debug_string
		)
	);
}
*/ 



/*
 Add global tags to track conversion
*/
function vsmp_global_custom_fields_input() {
	global $post;
	add_meta_box(
		'is_conversion',
		'Track Conversion on this Page/Post (Conversions other than WooCommerce)',
		'vsmp_is_conversion_input',
		['page', 'post'],
		'normal',
		'low'
	);
}

function vsmp_is_conversion_input() {
	global $post;
	$custom_vars = get_post_custom($post->ID);
	if ( metadata_exists('post', $post->ID, 'is_conversion') ) {
		$is_conversion = $custom_vars['is_conversion'][0];
	} else {
		$is_conversion = 'N';
	}
	echo '<table>';
	echo '<tr><td>Is this page a conversion</td>' 
		. '<td><SELECT ID="is_conversion" NAME="is_conversion">';
	echo '<OPTION VALUE="Y"';
	if ( $is_conversion == 'Y' ) {
		echo ' SELECTED';
	}	
	echo '>Yes</OPTION>';
	echo '<OPTION VALUE="N"';
	if ( $is_conversion == 'N' ) {
		echo ' SELECTED';
	}		
	echo '>No</OPTION>';
	echo '</SELECT></td></tr>';
	
	echo 


	$conversion_id_data_source_type = '';
	$conversion_id_data_source = '';
	$conversion_value_data_source_type = '';
	$conversion_value_data_source = '';
		
	if ( metadata_exists( 'post', $post->ID, 'conversion_id_data_source_type' ) ) {
		$conversion_id_data_source_type = $custom_vars['conversion_id_data_source_type'][0];
	}
	if ( metadata_exists( 'post', $post->ID, 'conversion_id_data_source' ) ) {
		$conversion_id_data_source = $custom_vars['conversion_id_data_source'][0];
	}

// For now, set the conversion Id to auto generate and the conversion value to 1	
	echo '<INPUT TYPE=HIDDEN ID="conversion_id_data_source_type" NAME="conversion_id_data_source_type" VALUE="';
	if ( $conversion_id_data_source_type != "" ) {
		echo $conversion_id_data_source_type;
	} else {
		echo 'AUTO';
	}	
	echo '">';
	echo '<INPUT TYPE=HIDDEN ID="conversion_id_data_source" NAME="conversion_id_data_source" VALUE="';
	if ( $conversion_id_data_source != "" ) {
		echo $conversion_id_data_source;
	}	
	echo '">';
	
/* Future enhancements:
	echo '<tr><td>Conversion Id</td><td>';
	echo 'Source&nbsp;<SELECT ID="conversion_id_data_source_type" NAME="conversion_id_data_source_type">';
	echo '<OPTION VALUE="AUTO"';
	if ($conversion_id_data_source_type == 'AUTO') {
		echo ' SELECTED';
	}
	echo '>Auto Generate</OPTION>';
	echo '<OPTION VALUE="FORM"';
	if ($conversion_id_data_source_type == 'FORM') {
		echo ' SELECTED';
	}
	echo '>Form</OPTION>';
	echo '<OPTION VALUE="URL"';
	if ($conversion_id_data_source_type == 'URL') {
		echo ' SELECTED';
	}
	echo '>URL Parameter</OPTION>';
	echo '<OPTION VALUE="VAR"';
	if ($conversion_id_data_source_type == 'VAR') {
		echo ' SELECTED';
	}
	echo '>Variable on Page</OPTION>';	
	echo '</SELECT>';
	echo '&nbsp;&nbsp;';
	echo 'Value&nbsp;' ;
	echo '<INPUT ID="conversion_id_data_source" NAME="conversion_id_data_source" VALUE="' . $conversion_id_data_source . '">';
	echo '</td></tr>';
*/	

	if ( metadata_exists( 'post', $post->ID, 'conversion_value_data_source_type' ) ) {
		$conversion_value_data_source_type = $custom_vars['conversion_value_data_source_type'][0];
	}
	if ( metadata_exists( 'post', $post->ID, 'conversion_value_data_source' ) ) {
		$conversion_value_data_source = $custom_vars['conversion_value_data_source'][0];
	}
	
	echo '<INPUT TYPE=HIDDEN ID="conversion_value_data_source_type" NAME="conversion_value_data_source_type" VALUE="';
	if ( $conversion_value_data_source_type != "" ) {
		echo $conversion_value_data_source_type;
	} else {
		echo 'CONST';
	}	
	echo '">';
	echo '<INPUT TYPE=HIDDEN ID="conversion_value_data_source" NAME="conversion_value_data_source" VALUE="';
	if ( $conversion_value_data_source != "" ) {
		echo $conversion_value_data_source;
	} else {
		echo '1';
	}	
	echo '">';
	
/* Future enhancements:
	echo '<tr><td>Conversion Value</td><td>';
	echo 'Source&nbsp;<SELECT ID="conversion_value_data_source_type" NAME="conversion_value_data_source_type">';
	echo '<OPTION VALUE="CONST"';
	if ($conversion_value_data_source_type == 'CONST') {
		echo ' SELECTED';
	}
	echo '>Constant</OPTION>';
	echo '<OPTION VALUE="FORM"';
	if ($conversion_value_data_source_type == 'FORM') {
		echo ' SELECTED';
	}
	echo '>Form</OPTION>';
	echo '<OPTION VALUE="URL"';
	if ($conversion_value_data_source_type == 'URL') {
		echo ' SELECTED';
	}
	echo '>URL Parameter</OPTION>';
	echo '<OPTION VALUE="VAR"';
	if ($conversion_value_data_source_type == 'VAR') {
		echo ' SELECTED';
	}
	echo '>Variable on Page</OPTION>';	
	echo '</SELECT>';
	echo '&nbsp;&nbsp;';
	echo 'Value&nbsp;' ;
	echo '<INPUT ID="conversion_value_data_source" NAME="conversion_value_data_source" VALUE="' . $conversion_value_data_source . '">';
	echo '</td></tr>';
*/

	echo '</table>';
}

function vsmp_global_custom_fields_save() {
	global $post;
	
	if ( isset( $_POST["is_conversion"] ) ) {
		$is_conversion = sanitize_text_field( $_POST["is_conversion"] );
		update_post_meta( $post->ID, "is_conversion", $is_conversion );
		if ( $is_conversion == "N" ) {
			delete_post_meta($post->ID, "conversion_id_data_source_type");	
			delete_post_meta($post->ID, "conversion_id_data_source");	
			delete_post_meta($post->ID, "conversion_value_data_source_type");	
			delete_post_meta($post->ID, "conversion_value_data_source");					
		} else {
			if ( isset( $_POST["conversion_id_data_source_type"] ) ) {
				$conversion_id_data_source_type = sanitize_text_field( $_POST["conversion_id_data_source_type"] );
				if ( $conversion_id_data_source_type != '' ) {
					update_post_meta( $post->ID, "conversion_id_data_source_type", $conversion_id_data_source_type );	
				} else {
					delete_post_meta( $post->ID, "conversion_id_data_source_type" );
				}
			}
			if ( isset($_POST["conversion_id_data_source"]) ) {
				$conversion_id_data_source = sanitize_text_field( $_POST["conversion_id_data_source"] );
				if ( $conversion_id_data_source != '' ) {
					update_post_meta( $post->ID, "conversion_id_data_source", $conversion_id_data_source );
				} else {
					delete_post_meta( $post->ID, "conversion_id_data_source" );
				}
			}
			if ( isset( $_POST["conversion_value_data_source_type"] ) ) {
				$conversion_value_data_source_type = sanitize_text_field( $_POST["conversion_value_data_source_type"] );
				if ( $conversion_value_data_source_type != '' ) {		
					update_post_meta($post->ID, "conversion_value_data_source_type", $conversion_value_data_source_type);	
				} else {
					delete_post_meta($post->ID, "conversion_value_data_source_type");			
				}
			}
			if ( isset( $_POST["conversion_value_data_source"] ) ) {
				$conversion_value_data_source = sanitize_text_field( $_POST["conversion_value_data_source"] );
				if ( $conversion_value_data_source != '' ) {				
					update_post_meta( $post->ID, "conversion_value_data_source", $conversion_value_data_source );
				} else {
					delete_post_meta( $post->ID, "conversion_value_data_source" );
				}
			}						
		}
	}

}

function vsmp_data_capture() {

	/*
	Setup the visitor cookie
	*/
	$vsmp_visitor_id = vsmp_get_visitor_id();
	
	global $wpdb;

	$search = preg_match('/^https?:\/\/.{1,10}\.(google|bing|yahoo|baidu|aol|excite)\.com.*$/', wp_get_referer());
	if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
		$bot = preg_match('/bot|crawl|slurp|spider|mediapartners/', $_SERVER['HTTP_USER_AGENT']);
	} else {
		$bot = 0;
	}

	/*
	Parse the URL query string for the vsmp parameter
	*/	
	if ( isset($_GET['vsmp']) ) {
		$valid = ( preg_match( '/^\d+$/' , $_GET['vsmp'] ) === 1 );
		if ( $valid ) {
			$vsmp = $_GET['vsmp'];
		} 

	/*
	If not found in the URL, check to see if the referrer is a search engine
	*/
	} elseif ( $search == 1 and $bot == 0 ) {
		$vsmp = $wpdb->get_var(
			"SELECT a.ID"
			. " FROM {$wpdb->prefix}posts a"
			. " JOIN {$wpdb->prefix}postmeta b ON b.post_id = a.ID AND b.meta_key = 'channel_id'"
			. " JOIN {$wpdb->prefix}postmeta c on c.post_id = CONVERT(b.meta_value, INTEGER) AND c.meta_key = 'detection' AND c.meta_value = 'srch_eng'"
			. " WHERE a.post_type = 'vsmp_delivery_event'"
			. "	ORDER BY a.ID DESC"
			. " LIMIT 1"
		);
	}
	
	if ( isset( $vsmp ) ) {
		vsmp_log_delivery_event($vsmp_visitor_id, $vsmp);			
	}

	/* 
	 Check to see if the page is marked as a conversion
	*/
	global $post;

	if ( isset( $post->ID ) ) {
		$custom_vars = get_post_custom( $post->ID );
		
		if ( metadata_exists('post', $post->ID, 'is_conversion') ) {
			$is_conversion = $custom_vars['is_conversion'][0];
			
			if ( metadata_exists('post', $post->ID, 'conversion_id_data_source_type') ) {
				$conversion_id_data_source_type = $custom_vars['conversion_id_data_source_type'][0];
			}
			if ( metadata_exists('post', $post->ID, 'conversion_id_data_source') ) {
				$conversion_id_data_source = $custom_vars['conversion_id_data_source'][0];
			}
			if ( metadata_exists('post', $post->ID, 'conversion_value_data_source_type') ) {
				$conversion_value_data_source_type = $custom_vars['conversion_value_data_source_type'][0];
			}
			if ( metadata_exists('post', $post->ID, 'conversion_value_data_source') ) {
				$conversion_value_data_source = $custom_vars['conversion_value_data_source'][0];
			}
	
			if ( $is_conversion == 'Y' ) {
		
				if ( $conversion_id_data_source_type == "FORM" && isset($_POST[$conversion_id_data_source]) ) {
					$conversion_id = sanitize_text_field( $_POST[$conversion_id_data_source] );		
				}	elseif ( $conversion_id_data_source_type == "URL" && isset($_GET[$conversion_id_data_source]) ) {
					$conversion_id = sanitize_text_field( $_GET[$conversion_id_data_source] );
				}	elseif ( $conversion_id_data_source_type == "VAR" && isset($$conversion_id_data_source) ) {
					$conversion_id = $$conversion_id_data_source;
				} else {
					$date = get_the_date('Ymd');
					$conversion_id = $_SERVER['REMOTE_ADDR'] . '_' . $date;
				}
		
				if ( $conversion_id_data_source_type == "FORM" && isset($_POST[$conversion_value_data_source]) ) {
					$conversion_value = $_POST[$conversion_value_data_source];		
				} elseif ( $conversion_value_data_source_type == "URL" && isset($_GET[$conversion_value_data_source]) ) {
					$conversion_value = $_GET[$conversion_value_data_source];
				}	elseif ( $conversion_value_data_source_type == "VAR" && isset($$conversion_value_data_source) ) {		
					$conversion_id = $$conversion_value_data_source;
				} elseif ( $conversion_value_data_source_type = 'CONST' ) {
					$conversion_value = $conversion_value_data_source;
				} else {
					$conversion_value = "1";
				}
		
				vsmp_log_conversion('*COOKIE', $conversion_id, $conversion_value);
		
			}
		}
	}
	
}

?>