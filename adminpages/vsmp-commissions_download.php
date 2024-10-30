<?PHP
/*
 Download Commissions
 
 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename=commissions.csv' );

define( 'SHORTINIT', true );
require_once('../../../../wp-load.php');

$cfd = fopen( 'php://output' , 'w' );

fputcsv($cfd, array('Delivery Event Id', 'Delivery Event Desc', 'Order Id', 'Order Date', 'Gross Sales'
	, 'Commission %', 'Commission' ));

fputcsv($cfd, array('From Date', $_GET["from_date"]));
fputcsv($cfd, array('To Date', $_GET["to_date"]));


if ( isset( $_GET["from_date"] ) && isset( $_GET["to_date"] ) ) {

	if ( preg_match( "/^'\d{4}-\d{1,2}-\d{1,2}'$/" , $_GET["from_date"] ) === 1 ) {
		$from_date = $_GET["from_date"];
	};
	if ( preg_match( "/^'\d{4}-\d{1,2}-\d{1,2}'$/" , $_GET["to_date"] ) === 1 ) {
		$to_date = $_GET["to_date"];
	};

	if ( ! isset( $from_date ) || ! isset( $to_date )) {
		fputcsv($cfd, array('Invalid selection'));
	};

}


if ( isset( $from_date ) && isset( $to_date ) ) {

	global $wpdb;

	$results = $wpdb->get_results(
		"SELECT a.delivery_event_id,"
		. " b.post_title AS delivery_event_desc,"
		. " a.conversion_id,"
		. " a.conversion_date,"
		. " a.conversion_val,"
		. " a.commission_pct,"
		. " a.commission" 
		. " FROM {$wpdb->prefix}vsmp_commission a"
		. " JOIN {$wpdb->prefix}posts b ON b.id = a.delivery_event_id"
		. " WHERE conversion_date BETWEEN " . $from_date . " AND " . $to_date
		. " ORDER BY b.post_title, a.conversion_id"
	);

	if ( count( $results ) == 0 ) {

		fputcsv($cfd, array('No results found'));

	} else {

		foreach ( $results as $result ) {

			fputcsv($cfd, array($result->delivery_event_id, $result->delivery_event_desc,
				$result->conversion_id, $result->conversion_date, $result->conversion_val,
				$result->commission_pct, $result->commission));

		}

	}

}

fclose( $cfd );

?>