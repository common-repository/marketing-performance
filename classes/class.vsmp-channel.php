<?php

/*
 Class file for channel
  id = Channel Id
  title = Title of channel
	description = Textual description of channel
	lag_days = The maximum days of influence
	influence[] = Array of influence by day
	detection = How to find the associated delivery event

 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

	class vsmp_channel {
		var $id;
		var $title;
		var $description;
		var $lag_days;
		var $influence = [];
		var $detection;

		function loadById( $channel_id ) {
			$valid_id = $this->set_id( $channel_id );
			if ( ! $valid_id ) {
				return FALSE;
			}
			$post = get_post( $this->id );
			$this->title = $post->post_title;
			$this->description = $post->post_content;
			$custom_vars = get_post_custom( $channel_id );
			if ( metadata_exists( 'post', $channel_id, 'lag_days' ) ) {
				$this->lag_days = $custom_vars['lag_days'][0];
			}
			for ( $i = 0; $i <= $this->lag_days; $i++ ) { 
				if ( metadata_exists( 'post', $channel_id, 'influence_' . $i ) ) {
					$this->influence[$i] = $custom_vars['influence_' . $i][0];
				}
			}
			if ( metadata_exists( 'post', $channel_id, 'detection' ) ) {
				$this->detection = $custom_vars['detection'][0];
			}
			return TRUE;
		}

		function loadByJson( $json_string ) {
			$json = json_decode( $json_string );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$valid_id = $this->set_id( $json->id );
				$valid_title = $this->set_title( $json->title );
				$valid_description = $this->set_description( $json->description );
				$valid_lag_days = $this->set_lag_days( $json->lag_days );
				$valid_influence = $this->set_influence( $json->influence );
				$valid_detection = $this->set_detection( $json->detection );
				if ( $valid_id and $valid_title and $valid_description and $valid_lag_days and $valid_influence and $valid_detection ) {
					return TRUE;
				}
			}
			return FALSE;
		}
		
		/*
		 Id is optional.  If it's not set, the channel will be created.  
		 Otherwise, it must be a valid channel post type
		*/
		function set_id( $id ) {
			if ( ! isset( $id ) ) {
					return TRUE;
			}
			$valid = ( preg_match( '/^\d+$/' , $id ) );
			if ( $valid ) {
				$valid = ( get_post_type( $id ) == 'vsmp_channel' );
				if ( $valid ) {
					$this->id = sanitize_text_field( $id );
				}
			}
			return $valid;
		}		
		/*
		 Title is required. 
	  */				
		function set_title( $title ) {
			$valid = ( ! empty( $title ) );
			if ( $valid ) {
				$this->title = sanitize_text_field( $title );
			}
			return $valid;
		}
		/*
		 Description is required. 
	  */				
		function set_description( $description ) {
			$valid = ( ! empty( $description ) );
			if ( $valid ) {
				$this->description = sanitize_text_field( $description );
			}
			return $valid;
		}		
		/*
		 Lag days is required and must be an integer. 
	  */				
		function set_lag_days( $lag_days ) {
			$valid = ( preg_match( '/^\d+$/' , $lag_days ) === 1 );
			if ( $valid ) {
				$this->lag_days = sanitize_text_field( $lag_days );
			}
			return $valid;
		}
		/*
		 Influence is required and must be an array of intergers between 1 and 100. 
	  */			
		function set_influence( $influence ) {
			$valid = TRUE;
			foreach ( $influence as $day_influence ) {
				$day_valid = ( preg_match( '/^\d{1,3}$/' , $day_influence ) === 1 );
				if ( $day_valid == FALSE ) {
					$valid = FALSE;
				}
			}
			if ( $valid ) {
				$this->influence = $influence;
			}
			return $valid;
		}
		/*
		 Detection is required and must be either 'url' or 'srch_eng'. 
	  */			
		function set_detection( $detection ) {
			$valid = ( $detection == 'url' or $detection == 'srch_eng' );
			if ( $valid ) {
				$this->detection = sanitize_text_field( $detection );
			}
			return $valid;
		}

		function get_lag_days_html_input() {
			$html = get_lag_days_mask();
			if ( isset( $this->lag_days ) ) {
				$html = str_replace( 'value=""', 'value="' . $this->lag_days . '"', $html );
			}
			return $html;
		}
		function get_lag_days_html_view() {
			return $this->lag_days;
		}
		function get_influence_html_input() {
			$html = '';
			for ( $i = 0; $i <= $this->lag_days; $i++ ) {
				$influence_var = "influence_" . $i;
				$html = $html 
					. '<BR>Day ' . $i 
					. '<BR>&nbsp;&nbsp;';
				$html0 = get_influence_mask($i);
				if ( isset( $this->influence[$i] ) ) {
					$html0 = str_replace( 'value=""', 'value="' . $this->influence[$i] . '"', $html0 ); 
				}
				$html = $html . $html0;
			}
			return $html;
		}
		function get_influence_html_view( $day_no ) {
			$influence_var = "influence_" . $day_no;
			return $this->influence[$day_no];
		}
		function get_detection_html_input() {
			if ( ! isset( $this->detection ) ) {
				$this->detection = 'vsmp_evt';
			}
			$html = get_detection_mask();
			if ( $this->detection == 'srch_eng' ) {
				$html = str_replace( 'value="srch_eng"', 'value="srch_eng" checked', $html );
			} else {
				$html = str_replace( 'value="vsmp_evt"', 'value="vsmp_evt" checked', $html );
			}		
			return $html;
		}	
		function get_detection_html_view() {
			if ( $this->detection = 'srch_eng' ) {
				$html = 'Look for search engines in the referrer';
			} else {
				$html = 'Find vsmp_evt={nnn} in the query string';
			}
			return $html;
		}

		function save() {
			if ( ! isset( $this->id ) ) {
				$this->id = wp_insert_post(
					array (
					'post_type' => 'vsmp_channel',
					'post_title' => $this->title,
					'post_content' => $this->description,
					'post_status' => 'publish'
					)
				);
			} else {
				wp_update_post(
					array (
					'ID' => $this->id,
					'post_title' => $this->title,
					'post_content' => $this->description
					)
				);
			}
			$this->save_custom_fields();
		}

		function save_custom_fields() {
			update_post_meta( $this->id, "lag_days", $this->lag_days );
			if ( isset( $this->lag_days ) ) {
				for ( $i = 0; $i <= $this->lag_days; ++$i ) {
					update_post_meta($this->id, "influence_" . $i, $this->influence[$i]);
				}
			}
			update_post_meta( $this->id, "detection", $this->detection );
		}

	}

?>