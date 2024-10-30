<?php
/*
 Display Attribution Report and Visualizations

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

<script type="text/javascript">

function initCharts() {
	var attrCanvas = document.getElementById( 'attrCanvas' );
	var attrLegend = document.getElementById( 'attrLegend' );
	
	var pathCanvas = document.getElementById( 'pathCanvas' );
	var starterCanvas = document.getElementById( 'starterCanvas' );
	var closerCanvas = document.getElementById( 'closerCanvas' );

	attribution = [];
	paths = [];
	starter = [];
	closer = [];
}
		
</script>

</head>


<body>

<div class="wrap">
<span id="vsmp_charts" style="display: none;">
<table class="wp-list-table fixed striped posts">
<tr>
<td align=center>
<canvas id="attrCanvas" width="300" height="300"></canvas>
<div id="attrLegend"></div>
</td>
<td align=center>
<table>
<tr>
<td colspan=2 align=center>
<canvas id="pathCanvas" width="500" height="180" style="border:1px solid #bbbbbb;"></canvas>
</td>
</tr>
<TR>
<TD align=center>
<canvas id="starterCanvas" width="250" height="100" style="border:1px solid #bbbbbb;"></canvas>
</TD>
<TD align=center>
<canvas id="closerCanvas" width="250" height="100" style="border:1px solid #bbbbbb;"></canvas>
</TD>
</TR>
</table>
</td>
</tr>
</table>
</span>
</div>

<div class="wrap">
<h1>Attribution Report</h1>
<FORM METHOD="POST">
<table class="form-table">

<?php
$valid_nonce = FALSE;
if ( isset( $_POST['vsmp-attribution_report_nonce'] ) ) {
	$valid_nonce = wp_verify_nonce( $_POST['vsmp-attribution_report_nonce'], 'vsmp-attribution_report' );
	if ( ! $valid_nonce ) {
		echo '<B><I>Security error on page.</I></B>';
	}
}
wp_nonce_field( 'vsmp-attribution_report', 'vsmp-attribution_report_nonce', FALSE, TRUE );
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
	mt_srand(12);
	$channel_colors = [];
	$color_scheme = array("#fedc3d", "#01ABAA", "#800000", "#0057ff", "#000075", "#f58231", "#aaffc3", "#ffd8b1", "#f032e6", "#ffe119", "#42d414", "#fffac8");

	$results = $wpdb->get_results(
		"SELECT a.delivery_event_id,"
		. " c.post_title AS delivery_event_desc,"
		. " CONVERT(d.meta_value, INTEGER) AS campaign_id,"
		. " f.post_title AS campaign_desc," 
		. " IFNULL(g.post_title, 'Unattributed') AS channel,"
		. " IFNULL(g.ID, 'unattr') AS channel_id,"
		. " a.attributed_conversions,"
		. " a.attributed_value,"
		. " 100 * a.attributed_value / b.attribution_total AS attributed_percent"
		. " FROM ("
		. " SELECT delivery_event_id,"
		. " SUM(attributed_conversions) AS attributed_conversions,"
		. " SUM(attributed_value) AS attributed_value"
		. " FROM {$wpdb->prefix}vsmp_attribution"
		. " WHERE cal_date BETWEEN " . $from_date . " AND " . $to_date
		. " GROUP BY delivery_event_id"
		. " ) a"
		. " CROSS JOIN ("
		. " SELECT SUM(attributed_value) AS attribution_total"
		. " FROM {$wpdb->prefix}vsmp_attribution"
		. " WHERE cal_date BETWEEN " . $from_date . " AND " . $to_date
		. " ) b"
		. " LEFT JOIN {$wpdb->prefix}posts c ON c.ID = a.delivery_event_id AND c.post_type = 'vsmp_delivery_event' AND c.post_status = 'publish'"
		. " LEFT JOIN {$wpdb->prefix}postmeta d ON d.post_id = c.ID AND d.meta_key = 'campaign_id'"
		. " LEFT JOIN {$wpdb->prefix}postmeta e ON e.post_id = c.ID AND e.meta_key = 'channel_id'"
		. " LEFT JOIN {$wpdb->prefix}posts f ON f.post_type = 'vsmp_campaign' AND f.ID = CONVERT(d.meta_value, INTEGER)"
		. " LEFT JOIN {$wpdb->prefix}posts g ON g.post_type = 'vsmp_channel' AND g.ID = CONVERT(e.meta_value, INTEGER)"
		. " ORDER BY g.post_title, f.post_title, a.delivery_event_id"
		);

	if ( count( $results ) == 0 ) {

		echo '<BR><I>No results found for your selected date range!</I>';

		} else {

		echo '<script>initCharts();</script>';

		echo "<table class='wp-list-table widefat fixed striped posts'>";
		echo "<thead><tr><th style='width:10px;'></th><th style='text-align:center;'>Channel</th><th style='text-align:center;'>Delivery Event</th>"
			. "<th style='text-align:center;'>Campaign</th><th style='text-align:center;width:100px;'>Conversions</th>"
			. "<th COLSPAN=2 style='text-align:center;width:180px;'>Attribution</th></thead>";
		echo "<tbody>";

		$sv_channel = NULL;
		$sv_channel_id = NULL;
		$table_string = '';
		$java_script_string = '<script>'
			. ' jQuery(document).ready(function(){';
		$java_script_toggle_string = '';
		$attributed_conversions = 0;
		$attributed_value = 0;
		$attributed_percent = 0;

		foreach ( $results as $result ) {
			$channel = $result->channel;
			$channel_id = $result->channel_id;
				
			if ( isset( $sv_channel_id ) && $channel_id != $sv_channel_id ) {
				
				if ( count( $color_scheme ) > 0 ) {
					$channel_colors[$sv_channel] = array_shift( $color_scheme );
				} else {
					$channel_colors[$sv_channel] = sprintf( "#%06x", mt_rand( 0, 16777215 ) );
			}
			
				echo "<tr>";
				if ($sv_channel_id != "unattr") {
					echo '<td><div class="dashicons dashicons-plus" ID="' . $sv_channel_id . '_toggle"></div></td>';	
				} else {
					echo '<td></td>';
				}
				echo "<td align=center>" . $sv_channel . "</td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td align=right>" . number_format( $attributed_conversions, 2 ) . "</td>";
				echo "<td align=right>$" . number_format( $attributed_value, 2 ) . "</td>";	
				echo "<td style='text-align:right'>" . number_format( $attributed_percent, 2 ) . "%</td>";	
				echo "</tr>";	

				if ( $sv_channel != "unattr" ) {	
					echo $table_string;

					$jQuery_ref = 'jQuery("#' . $sv_channel_id . '_toggle")'; 
					$java_script_string = $java_script_string
						. $jQuery_ref . '.click(function(){'
						. $java_script_toggle_string
						. ' if (' . $jQuery_ref . '.hasClass("dashicons dashicons-plus")) {'
						. ' ' . $jQuery_ref . '.removeClass();'
						. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-minus");'					
						. ' } else {'
						. ' ' . $jQuery_ref . '.removeClass();'
						. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-plus");'		
						. '}'
						. '});';
				}

				echo '<script>' 
					. 'n = attribution.length;'
					. 'attribution.push(new Array());'
					. 'attribution[n].push("' . $sv_channel . '");'
					. 'attribution[n].push(' . $attributed_value . ');'
					. 'attribution[n].push("' . $channel_colors[$sv_channel] . '");'
					. '</script>';

				$attributed_conversions = 0;
				$attributed_value = 0;
				$attributed_percent = 0;

				$table_string = '';
				$java_script_toggle_string = '';
			}
		
			$sv_channel = $channel;
			$sv_channel_id = $channel_id;
		
			if ( $channel_id != "unattr" ) {

				$table_string = $table_string 
					. '<tr ID="' . $channel_id . '_' . $result->delivery_event_id . '" style="display: none">'
					. '<td></td>'
					. "<td></td>"
					. "<td>" . $result->delivery_event_id . ' ' . $result->delivery_event_desc. "</td>"
					. "<td>" . $result->campaign_desc . "</td>"
					. "<td align=right>" . number_format($result->attributed_conversions,2) . "</td>"
					. "<td align=right>$" . number_format($result->attributed_value,2) . "</td>"
					. "<td align=right>" . number_format($result->attributed_percent,2) . "%</td>"
					. "</tr>";
				$java_script_toggle_string = $java_script_toggle_string
					. ' jQuery("#' . $channel_id . '_' . $result->delivery_event_id . '").toggle();';
			}
			
			$attributed_conversions += $result->attributed_conversions;
			$attributed_value += $result->attributed_value;
			$attributed_percent += $result->attributed_percent;		

		}	

		
		if ( isset( $sv_channel_id ) ) {
			
			if ( count( $color_scheme ) > 0 ) {
				$channel_colors[$sv_channel] = array_shift( $color_scheme );
			} else {
				$channel_colors[$sv_channel] = sprintf( "#%06x", mt_rand( 0, 16777215 ) );
			}

			echo "<tr>";
			if ( $sv_channel_id != "unattr" ) {
				echo '<td><div class="dashicons dashicons-plus" ID="' . $sv_channel_id . '_toggle"></div></td>';	
			} else {
				echo '<td></td>';
			}
			echo "<td align=center>" . $sv_channel . "</td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td align=right>" . number_format( $attributed_conversions, 2 ) . "</td>";
			echo "<td align=right>$" . number_format( $attributed_value, 2 ) . "</td>";	
			echo "<td align=right>" . number_format( $attributed_percent, 2 ) . "%</td>";	
			echo "</tr>";	

			if ( $sv_channel_id != "unattr" ) {		
				echo $table_string;

				$jQuery_ref = 'jQuery("#' . $sv_channel_id . '_toggle")'; 
				$java_script_string = $java_script_string
					. $jQuery_ref . '.click(function(){'
					. $java_script_toggle_string
					. ' if (' . $jQuery_ref . '.hasClass("dashicons dashicons-plus")) {'
					. ' ' . $jQuery_ref . '.removeClass();'
					. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-minus");'					
					. ' } else {'
					. ' ' . $jQuery_ref . '.removeClass();'
					. ' ' . $jQuery_ref . '.addClass("dashicons dashicons-plus");'		
					. '}'
					. '});';
			}

			echo '<script>' 
				. 'n = attribution.length;'
				. 'attribution.push(new Array());'
				. 'attribution[n].push("' . $sv_channel . '");'
				. 'attribution[n].push(' . $attributed_value . ');'
				. 'attribution[n].push("' . $channel_colors[$sv_channel] . '");'
				. '</script>';

		}

		echo "</tbody>";
		echo "</table>";


		$java_script_string = $java_script_string
			. '});'
			. '</script>';

		echo $java_script_string;

		echo '<script>'
			. 'var attrPiechart = new Piechart('
			. '{'
			. 'canvas:attrCanvas,'
			. 'data:attribution,'
			. 'legend:attrLegend'
			. '}'
			. ');'
			. 'attrPiechart.draw();'
			. '</script>';


		$results = $wpdb->get_results(
			"select metric_detail, SUM(metric_count) AS metric_count"
			. " from {$wpdb->prefix}vsmp_channel_metric"
			. " WHERE metric_type = 'channels'"
			. " AND cal_date BETWEEN " . $from_date . " AND " . $to_date
			. " GROUP BY metric_detail"
			. " ORDER BY 2 DESC"
			. " LIMIT 10"
			);

		foreach ( $results as $result ) {
			if ( isset($channel_colors[$result->metric_detail]) ) {
				$bar_chart_color = $channel_colors[$result->metric_detail];
			} else {
				$bar_chart_color = '#c5c5c5';
			}
			echo '<script>'
				. 'n = paths.length;'
				. 'paths.push(new Array());'
				. 'paths[n].push("' . $result->metric_detail . '");'
				. 'paths[n].push(' . $result->metric_count . ');'
				. 'paths[n].push("' . $bar_chart_color . '");'
				. '</script>';
		}


		echo '<script>'
			. 'var pathBarchart = new Barchart('
			. '{'
			. 'canvas:pathCanvas,'
			. 'title:"Top 10 Influencing Channel Combinations",' 
			. 'data:paths,'
			. '}'
			. ');'
			. 'pathBarchart.draw();'
			. '</script>';


		$results = $wpdb->get_results(
			"select metric_detail, SUM(metric_count) AS metric_count"
			. " from {$wpdb->prefix}vsmp_channel_metric"
			. " WHERE metric_type = 'starter'"
			. " AND cal_date BETWEEN " . $from_date . " AND " . $to_date
			. " GROUP BY metric_detail"
			. " ORDER BY 2 DESC"
			. " LIMIT 3"
		);

		echo '<script>';
		foreach ( $results as $result ) {
			echo 'n = starter.length;'
				. 'starter.push(new Array());'
				. 'starter[n].push("' . $result->metric_detail . '");'
				. 'starter[n].push(' . $result->metric_count . ');'
				. 'starter[n].push("' . $channel_colors[$result->metric_detail] . '");';
		}		

		echo 'var starterBarchart = new Barchart('
			. '{'
			. 'canvas:starterCanvas,'
			. 'title:"Top Starter Channels",'
			. 'data:starter,'
			. '}'
			. ');'
			. 'starterBarchart.draw();';
		echo '</script>';


		$results = $wpdb->get_results(
			"select metric_detail, SUM(metric_count) AS metric_count"
			. " from {$wpdb->prefix}vsmp_channel_metric"
			. " WHERE metric_type = 'closer'"
			. " AND cal_date BETWEEN " . $from_date . " AND " . $to_date
			. " GROUP BY metric_detail"
			. " ORDER BY 2 DESC"
			. " LIMIT 3"
			);

		echo '<script>';
		foreach ( $results as $result ) {
			echo 'n = closer.length;'
				. 'closer.push(new Array());'
				. 'closer[n].push("' . $result->metric_detail . '");'
				. 'closer[n].push(' . $result->metric_count . ');'
				. 'closer[n].push("' . $channel_colors[$result->metric_detail] . '");';
		}

		echo 'var closerBarchart = new Barchart('
			. '{'
			. 'canvas:closerCanvas,'
			. 'title:"Top Closer Channels",'
			. 'data:closer,'
			. '}'
			. ');'
			. 'closerBarchart.draw();';
		echo '</script>';


		echo '<script>'
			. ' jQuery(document).ready(function(){'
			. ' jQuery("#vsmp_charts").show()'
			. '})'
			. '</script>';
	}
}

?>
</div>
</body>