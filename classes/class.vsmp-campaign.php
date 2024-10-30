<?php

/*
 Class file for campaigns
  id = Post Id
  title = Title of campaign
	description = Textual description of campaign
  from_date = Campaign begin date
	to_date = Campaign end date
	spend = Actual spend on the campaign_id
	est_roas = Estimated or expected return on ad spend

 @package    The Marketing Performance Plugin
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

	class vsmp_campaign {
		var $id;
		var $title;
		var $description;
		var $from_date;
		var $to_date;
		var $spend;
		var $est_roas;

		function loadById( $campaign_id ) {
			$post = get_post( $campaign_id );
			$this->id = $post->ID;
			$this->title = $post->post_title;
			$this->description = $post->post_content;
			$custom_vars = get_post_custom( $campaign_id );
			if ( metadata_exists( 'post', $campaign_id, 'from_date' ) ) {
				$this->from_date = $custom_vars['from_date'][0];
			}
			if ( metadata_exists( 'post', $campaign_id, 'to_date' ) ) {
				$this->to_date = $custom_vars['to_date'][0];
			}
			if ( metadata_exists( 'post', $campaign_id, 'spend' ) ) {
				$this->spend = $custom_vars['spend'][0];
			}
			if ( metadata_exists( 'post', $campaign_id, 'est_roas' ) ) {
				$this->est_roas = $custom_vars['est_roas'][0];
			}
		}

		function loadByJson( $json_string ) {
			$json = json_decode( $json_string );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$valid_id =	$this->set_id( $json->id );
				$valid_title = $this->set_title( $json->title );
				$valid_description = $this->set_description( $json->description );
				$valid_from_date = $this->set_from_date( $json->from_date );
				$valid_to_date = $this->set_to_date( $json->to_date );
				$valid_spend = $this->set_spend( $json->spend );
				$valid_est_roas = $this->set_est_roas( $json->est_roas );
				if ( $valid_id and $valid_title and $valid_description and $valid_from_date and $valid_to_date and $valid_spend and $valid_est_roas ) {
					return TRUE;
				}
			}
			return FALSE;
		}
		
		/*
		 Id is optional.  If it's not set, the campaign will be created.  
		 Otherwise, it must be a valid campaign post type
		*/
		function set_id( $id ) {
			if ( ! isset( $id ) ) {
					return TRUE;
			}
			$valid = ( preg_match( '/^\d+$/' , $id ) === 1 );
			if ( $valid ) {
				$valid = ( get_post_type( $id ) == 'vsmp_campaign' );
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
		 From Date is required and must be a valid date formatted yyyy-mm-dd. 
	  */
		function set_from_date( $from_date ) {
			$valid = ( preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/' , $from_date ) === 1 );
			if ( $valid ) {
				$this->from_date = sanitize_text_field( $from_date );
			}
			return $valid;
		}
		/*
		 To Date is required and must be a valid date formatted yyyy-mm-dd. 
	  */
		function set_to_date( $to_date ) {
			$valid = ( preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/' , $to_date ) === 1 );
			if ( $valid ) {
				$this->to_date = sanitize_text_field( $to_date );
			}
			return $valid;
		}
		/*
		 Spend is optional and must be a decimal value. 
	  */
		function set_spend( $spend ) {
			if ( empty( $spend ) ) {
				return TRUE;
			}
			$valid = ( preg_match( '/^\d+(\.\d+)?$/' , $spend ) === 1 );
			if ( $valid ) {
				$this->spend = sanitize_text_field( $spend );
			}
			return $valid;
		}
		/*
		 Estimated Return on Ad Spend is optional and must be a decimal value. 
	  */
		function set_est_roas( $est_roas ) {
			if ( empty( $est_roas ) ) {
				return TRUE;
			}
			$valid = ( preg_match( '/^\d+(\.\d+)?$/' , $est_roas ) === 1 );
			if ( $valid )	{
				$this->est_roas = sanitize_text_field( $est_roas );
			}
			return $valid;
		}

		
		function get_from_date_html_input() {
			$html = get_from_date_mask();
			if ( isset( $this->from_date ) ) {
				$html = str_replace( 'value=""', 'value="' . $this->from_date . '"', $html );
			}
			return $html;
		}
		function get_from_date_html_view() {
			$date = date_create( $this->from_date );
			return date_format( $date, 'm/d/Y' );
		}
		function get_to_date_html_input() {
			$html = get_to_date_mask();
			if ( isset( $this->to_date ) ) {
				$html = str_replace( 'value=""', 'value="' . $this->to_date . '"', $html );
			}
			return $html;
		}
		function get_to_date_html_view() {
			$date = date_create( $this->to_date );
			return date_format( $date, 'm/d/Y' );
		}
		function get_spend_html_input() {
			$html = get_spend_mask();
			if ( isset( $this->from_date ) ) {
				$html = str_replace( 'value=""', 'value="' . $this->spend . '"', $html );
			}
			return $html;
		}
		function get_spend_html_view() {
			if ( isset( $this->spend ) && $this->spend != "" && is_numeric( $this->spend ) ) {
				$html = '$' . number_format( $this->spend, 2 );
			} else {
				$html = '<I>Spend has not been set.</I>';			
			}
			return $html;
		}
		function get_est_roas_html_input() {
			$html = get_est_roas_mask();
			if ( isset( $this->est_roas ) ) {
				$html = str_replace( 'value=""', 'value="' . $this->est_roas . '"', $html );
			}
			return $html;
		}
		function get_est_roas_html_view() {
			if ( isset( $this->est_roas ) && $this->est_roas != "" && is_numeric( $this->est_roas ) ) {
				$html = number_format( $this->est_roas, 2 );
			} else {
				$html = '<I>Estimated Return on Ad Spend has not been set.</I>';
			}
			return $html;
		}

		function save() {

			if ( ! isset( $this->id ) ) {
				$this->id = wp_insert_post(
					array (
					'post_type' => 'vsmp_campaign',
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
			update_post_meta( $this->id, "from_date", $this->from_date );
			update_post_meta( $this->id, "to_date", $this->to_date );
			update_post_meta( $this->id, "spend", $this->spend );
			update_post_meta( $this->id, "est_roas", $this->est_roas );
		}

	}

?>