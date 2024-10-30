<?php
/*
 Display Campaign Performance Report

 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! current_user_can( 'edit_posts' ) ) {
	exit; // Must be able to edit posts
}
?> 


<head>
</head>

<body>
<div class="wrap">
<h1>Campaign Performance Report</h1>

<?PHP
global $wpdb;

echo '<FORM METHOD="POST">';

$campaign_id = NULL;

if ( isset( $_POST['campaign_id'] ) ) {
	$valid = ( preg_match( '/^\d+$/' , $_POST["campaign_id"] ) === 1);
	if ( $valid ) {
		$campaign_id = $_POST["campaign_id"];
	}
	if ( ! isset( $campaign_id ) ) {
		echo '<B><I>Invalid selection.</I></B>';
	}
}

$valid_nonce = FALSE;
if ( isset( $_POST['vsmp-campaign_performance_report_nonce'] ) ) {
	$valid_nonce = wp_verify_nonce( $_POST['vsmp-campaign_performance_report_nonce'], 'vsmp-campaign_performance_report' );
	if ( ! $valid_nonce ) {
		echo '<BR><BR><B><I>Security error on page.</I></B><BR><BR>';
	}
}

wp_nonce_field( 'vsmp-campaign_performance_report', 'vsmp-campaign_performance_report_nonce', FALSE, TRUE );

echo 'Campaign&nbsp;';
echo vsmp_cpt_select( "vsmp_campaign", 'campaign_id', 'ID', 'desc', $campaign_id, NULL, TRUE );
echo '</FORM>';


if ( ! is_null( $campaign_id ) and $valid_nonce ) {
	echo '<table class="wp-list-table widefat fixed striped posts">';
	$campaign = new vsmp_campaign();
	$campaign->loadById( $campaign_id );
	echo '<tr><td>Description</td><td>' . $campaign->description . '</td></tr>';
	echo '<tr><td>From Date</td><td>' . $campaign->get_from_date_html_view() . '</td></tr>';
	echo '<tr><td>To Date</td><td>' . $campaign->get_to_date_html_view() . '</td></tr>';
	echo '<tr><td>Spend</td><td>' . $campaign->get_spend_html_view() . '</td></tr>';	
	echo '<tr><td>Estimated Return (ROAS)</td><td>' . $campaign->get_est_roas_html_view() . '</td></tr>';
	echo '<tr><td>Actual Return</td><td><span id="act_return"></span></td></tr>';
	echo '</TABLE>';

	echo '<table class="wp-list-table widefat fixed striped posts">';
	echo '<thead><tr><th style="text-align:center;width:200px">Delivery Event</th><th style="text-align:center;width:100px;">Channel</th><th style="text-align:center;width:100px;">Attributed<BR>Conversions</th><th style="text-align:center;width:100px;">Attributed<BR>Value</th></tr></thead>';

	$results = $wpdb->get_results(
		"SELECT b.ID AS delivery_event_id, b.post_title AS delivery_event_desc, d.post_title AS channel,"
		. " IFNULL(e.attributed_conversions, 0) AS attributed_conversions,"
        . "	IFNULL(e.attributed_value, 0) AS attributed_value"
		. " FROM ("
		. " SELECT post_id" 
		. " FROM {$wpdb->prefix}postmeta"
		. " WHERE meta_key = 'campaign_id' AND meta_value = '" . $campaign_id . "'"
		. " ) a" 
		. " JOIN {$wpdb->prefix}posts b ON b.ID = a.post_id AND b.post_status = 'publish'"
		. " JOIN {$wpdb->prefix}postmeta c ON c.post_id = b.ID AND c.meta_key = 'channel_id'"
		. " JOIN {$wpdb->prefix}posts d ON d.ID = CONVERT(c.meta_value, INTEGER)"
		. " LEFT JOIN ("
		. " SELECT delivery_event_id, SUM(attributed_conversions) AS attributed_conversions,"
		. " SUM(attributed_value) AS attributed_value"
		. " FROM {$wpdb->prefix}vsmp_attribution"
		. " GROUP BY 1"
		. " ) e ON e.delivery_event_id = b.ID"
	);
	$tot_attributed_conversions = 0;
	$tot_attributed_value = 0;
	foreach ( $results as $result ) {
		echo '<tr>';
		echo '<td>' . $result->delivery_event_id . ' ' . $result->delivery_event_desc . '</td>';
		echo '<td align=center>' . $result->channel . '</td>';
		echo '<td align=right>' . number_format($result->attributed_conversions,2) . '</td>';
		echo '<td align=right>$' . number_format($result->attributed_value,2) . '</td>';
		echo '</tr>';
		$tot_attributed_conversions += $result->attributed_conversions;
		$tot_attributed_value += $result->attributed_value;
	}
	echo '<tr><td colspan=2><i>Total</i></td><td align=right>' . number_format( $tot_attributed_conversions, 2 ) 
		. '</td><td align=right>$' . number_format( $tot_attributed_value, 2 ) . '</td></tr>';

	if ( $campaign->spend > 0 ) {
		$act_return = number_format( $tot_attributed_value / $campaign->spend, 2 );
		echo '<script>';
		echo 'jQuery("#act_return").html("<B>' . $act_return . '</B>")';
		echo '</script>';
		echo '<script>';
		if ( $campaign->est_roas != NULL ) {
			if ( $act_return >= $campaign->est_roas ) {
				echo 'jQuery("#act_return").css("color", "green");';
			} elseif ( $act_return >= $campaign->est_roas * .95 ) {
				echo 'jQuery("#act_return").css("color", "yellow");';
			} else {
				echo 'jQuery("#act_return").css("color", "red");';
			}
		}
		echo '</script>';
	}
	echo '</table>';

}

?>
</div>

</body>