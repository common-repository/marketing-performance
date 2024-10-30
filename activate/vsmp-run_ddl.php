<?php
/*
 Run DDL scripts during Plugin Activation and De-activation
 
 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

function vsmp_run_ddl( $ddl_script ) {

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;

	$script = fopen( dirname( __FILE__ ) . '/' . $ddl_script, 'r' );
	$sql = '';
	
	while ( ! feof( $script ) ) {

		$line = fgets( $script );
		$line = str_replace( array("\r", "\n"), '', $line );
		$line = str_replace( '{$wpdb->prefix}', $wpdb->prefix, $line );

		if ( (strlen( $line ) == 0) ) {
		}
		elseif ( substr( ltrim( $line ), 1, 1 ) == '*' ) {
		}
		elseif ( $line == '<dbDelta>' ) {
			$sql = '';
		} 
		elseif ( $line == '</dbDelta>' ) {
			dbDelta( $sql );
			$sql = '';
		}
		elseif ( $line == '<query>' ) {
			$sql = '';
		}
		elseif ( $line == '</query>' ) {
			$wpdb->query( $sql );
			$sql = '';
		} else {
			$sql = $sql . ' ' . $line;
		}
	}
	fclose( $script );

}

?>