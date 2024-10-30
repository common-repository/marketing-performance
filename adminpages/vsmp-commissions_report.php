<?php
/*
 Display Commission Report

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

<body>

<div class="wrap">
<h1>Commission Report</h1>
<FORM METHOD="POST">
<table class="form-table">

<?php
$valid_nonce = FALSE;
if ( isset( $_POST['vsmp-commissions_report_nonce'] ) ) {
	$valid_nonce = wp_verify_nonce( $_POST['vsmp-commissions_report_nonce'], 'vsmp-commissions_report' );
	if ( ! $valid_nonce ) {
		echo '<B><I>Security error on page.</I></B>';
	}
}
wp_nonce_field( 'vsmp-commissions_report', 'vsmp-commissions_report_nonce', FALSE, TRUE );
?>


<tr>
<td width=100>From Date<INPUT TYPE="DATE" ID="from_date" NAME="from_date" SIZE=10 VALUE="<?php echo $_POST["from_date"] ?>" style="width:180px;" required></td>
<td width=100>To Date<INPUT TYPE="DATE" ID="to_date" NAME="to_date" SIZE=10 VALUE="<?php echo $_POST["to_date"] ?>" style="width:180px;" required></td>
<td><INPUT TYPE=SUBMIT VALUE="Query" class="button button-small"></td>
</tr>
</table>
</FORM>


<?php

$from_date = NULL;
$to_date = NULL;

if ( isset( $_POST["from_date"] ) && isset( $_POST["to_date"] ) ) {

	if ( preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/' , $_POST["from_date"] ) === 1 ) {
		$from_date = "'" . $_POST["from_date"] . "'";		
	};
	if ( preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/' , $_POST["to_date"] ) === 1 ) {
		$to_date = "'" . $_POST["to_date"] . "'";		
	};

	if ( ! isset( $from_date ) || ! isset( $to_date )) {
		echo '<B><I>Invalid selection.</I></B>';
	}

}


if ( isset( $from_date ) && isset( $to_date ) && $valid_nonce ) {

	global $wpdb;

	$results = $wpdb->get_results(
		"SELECT a.delivery_event_id,"
		. " b.post_title AS delivery_event_desc,"
		. " a.conversion_date,"
		. " a.l1_order_count,"
		. " a.l1_commission" 
		. " FROM ("
		. " SELECT conversion_date,"
		. " delivery_event_id,"
		. " count(*) AS l1_order_count,"
		. " sum(commission) AS l1_commission"
		. " FROM {$wpdb->prefix}vsmp_commission"
		. " WHERE conversion_date BETWEEN " . $from_date . " AND " . $to_date
		. " GROUP BY delivery_event_id, conversion_date"
		. " ) a"
		. " JOIN {$wpdb->prefix}posts b ON b.id = a.delivery_event_id"
		. " ORDER BY b.post_title, a.conversion_date"
	);

	if ( count( $results ) == 0 ) {

		echo '<BR><I>No results found for your selected date range!</I>';

	} else {


		echo "<table class='wp-list-table widefat fixed striped posts'>";
		echo "<thead><tr><th style='width:10px;'></th><th style='text-align:center;'>Delivery Event</th>"
			. "<th style='text-align:center;'>Conversion Date</th><th style='text-align:center;width:100px;'>Order Count</th>"
			. "<th style='text-align:center;width:180px;'>Commissions</th></thead>";
		echo "<tbody>";

		$sv_delivery_event_id = NULL;
		$sv_delivery_event_desc = NULL;
		$java_script_string = '<script>'
			. ' jQuery(document).ready(function(){';
		$table_string = '';

		$l2_order_count = 0;
		$l2_commissions = 0;

		foreach ( $results as $result ) {
			$delivery_event_id = $result->delivery_event_id;

			if ( isset( $sv_delivery_event_id ) && $delivery_event_id != $sv_delivery_event_id ) {

				echo "<tr>";
				echo '<td><div class="dashicons dashicons-plus" ID="' . $sv_delivery_event_id . '-toggle"></div></td>';	
				echo "<td align=LEFT>" . $sv_delivery_event_desc . " (" . $sv_delivery_event_id . ")</td>";
				echo "<td></td>";
				echo "<td style='text-align:right'>" . $l2_order_count . "</td>";
				echo "<td style='text-align:right'>$" . number_format( $l2_commission, 2 ) . "</td>";
				echo "</tr>";

				echo $table_string;
				$jQuery_ref = 'jQuery("#' . $sv_delivery_event_id . '-toggle")'; 
				$java_script_string .= 
					$jQuery_ref . '.click(function(){'
					. ' jQuery("[id^=d' . $sv_delivery_event_id . ']").toggle();'
					. ' if (' . $jQuery_ref . '.hasClass("dashicons dashicons-plus")) {'
					. ' ' . $jQuery_ref . '.removeClass();'
					. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-minus");'
					. ' } else {'
					. ' ' . $jQuery_ref . '.removeClass();'
					. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-plus");'
					. '}'
					. '});';

				$l2_order_count = 0;
				$l2_commissions = 0;

				$table_string = '';
				$x = 0;
			}

			$sv_delivery_event_id = $delivery_event_id;
			$sv_delivery_event_desc = $result->delivery_event_desc;

			$x += 1;
			$table_string .=  
				'<tr ID="d' . $result->delivery_event_id . '-' . $x . '" style="display:none">'
				. '<td></td>'
				. '<td></td>'
				. "<td style='text-align:center'>" . $result->conversion_date . "</td>"
				. "<td style='text-align:right'>" . $result->l1_order_count . "</td>"
				. "<td style='text-align:right'>$" . number_format($result->l1_commission,2) . "</td>"
				. "</tr>";

			$l2_order_count += $result->l1_order_count;
			$l2_commission += $result->l1_commission;

		}


		if ( isset( $sv_delivery_event_id ) ) {

			echo "<tr>";
			echo '<td><div class="dashicons dashicons-plus" ID="' . $sv_delivery_event_id . '-toggle"></div></td>';	
			echo "<td align=LEFT>" . $sv_delivery_event_desc . " (" . $sv_delivery_event_id . ")</td>";
			echo "<td></td>";
			echo "<td style='text-align:right'>" . $l2_order_count . "</td>";
			echo "<td style='text-align:right'>$" . number_format( $l2_commission, 2 ) . "</td>";	
			echo "</tr>";

			echo $table_string;

			$jQuery_ref = 'jQuery("#' . $sv_delivery_event_id . '-toggle")'; 
			$java_script_string .=
				$jQuery_ref . '.click(function(){'
				. ' jQuery("[id^=d' . $sv_delivery_event_id . ']").toggle();'
				. ' if (' . $jQuery_ref . '.hasClass("dashicons dashicons-plus")) {'
				. ' ' . $jQuery_ref . '.removeClass();'
				. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-minus");'
				. ' } else {'
				. ' ' . $jQuery_ref . '.removeClass();'
				. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-plus");'
				. '}'
				. '});';

		}

		echo "</tbody>";
		echo "</table>";

		$java_script_string .= '});' . '</script>';
		echo $java_script_string;

	}

	echo '<BR><BR><a href=' 
		. plugins_url()
		. '/marketing-performance/adminpages/vsmp-commissions_download.php?from_date='
		. $from_date
		. '&to_date='
		. $to_date
		. '>Download</a>';

};




?>
</div>
</body>