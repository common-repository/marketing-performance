<?php

/*
 Class file for delivery events
  id = Delivery Event (Post) Id
  title = Title of delivery event
  description = Textual description of delivery event
  campaign_id = Campaign that delivery event belongs to
  channel_id = Channel leveraged by this delivery event
  commission_days = Number of days Commission is Eligible
  commission_pct = Commission Percent

 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

	class vsmp_delivery_event {
		var $id;
		var $title;
		var $description;
		var $campaign_id;
		var $channel_id;
		var $commission_days;
		var $commission_pct;

		function loadById( $delivery_event_id ) {
			$post = get_post( $delivery_event_id );
			$this->id = $post->ID;
			$this->title = $post->post_title;
			$this->description = $post->post_content;
			$custom_vars = get_post_custom($delivery_event_id);
			if ( metadata_exists( 'post', $delivery_event_id, 'campaign_id' ) ) {
				$this->campaign_id = $custom_vars['campaign_id'][0];
			}
			if ( metadata_exists( 'post', $delivery_event_id, 'channel_id' ) ) {
				$this->channel_id = $custom_vars['channel_id'][0];
			}
			if ( metadata_exists( 'post', $delivery_event_id, 'commission_days' ) ) {
				$this->commission_days = $custom_vars['commission_days'][0];
			}
			if ( metadata_exists( 'post', $delivery_event_id, 'commission_pct' ) ) {
				$this->commission_pct = $custom_vars['commission_pct'][0];
			}
		}

		function loadByJson( $json_string ) {
			$json = json_decode( $json_string );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$valid_id = $this->set_id( $json->id );
				$valid_title = $this->set_title( $json->title );
				$valid_description = $this->set_description( $json->description ); 
				$valid_campaign_id = $this->set_campaign_id( $json->campaign_id );
				$valid_channel_id = $this->set_channel_id( $json->channel_id );
				$valid_commission_days = $this->set_commission_days( $json->commission_days );
				$valid_commission_pct = $this->set_commission_pct( $json->commission_pct );
				if ( $valid_id and $valid_title and $valid_description and $valid_campaign_id and $valid_channel_id 
					and $valid_commission_days and $valid_commission_pct ) {
					return TRUE;
				}
			}
			return FALSE;
		}
		
		/*
		 Id is optional.  If it's not set, the delivery event will be created.  
		 Otherwise, it must be a valid delivery event post type
		*/
		function set_id( $id ) {
			if ( ! isset( $id ) ) {
					return TRUE;
			}
			$valid = ( preg_match( '/^\d+$/' , $id ) );
			if ( $valid ) {
				$valid = ( get_post_type( $id ) == 'vsmp_delivery_event' );
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
		 Campaign Id is required and must be a valid campaign post.
		*/
		function set_campaign_id( $campaign_id ) {
			$valid = ( preg_match( '/^\d+$/' , $campaign_id ) );
			if ( $valid ) {
				$valid = ( get_post_type( $campaign_id ) == 'vsmp_campaign' );
				if ( $valid ) {
					$this->campaign_id = sanitize_text_field( $campaign_id );
				}
			}
			return $valid;
		}
		/*
		 Channel Id is required and must be a valid channel post.
		*/
		function set_channel_id( $channel_id ) {
			$valid = ( preg_match( '/^\d+$/' , $channel_id ) === 1 );
			if ( $valid ) {
				$valid = ( get_post_type( $channel_id ) == 'vsmp_channel' );
				if ( $valid ) {
					$this->channel_id = $channel_id;
				}
			}	
			return $valid;
		}
		/*
		 Commission Days is optional but if entered, must be a valid number between 0 and 99.
		*/
		function set_commission_days( $commission_days ) {
			if ( empty( $commission_days ) ) {
				return TRUE;
			}
			$valid = ( preg_match( '/^\d{1,2}$/' , $commission_days ) === 1 );
			if ( $valid ) {
				$this->commission_days = $commission_days;
			}
			return $valid;
		}
		/*
		 Commission Percent should be omitted if commission days are empty. 
		 Otherwise must be a valid number between 0 and 100 with up to 3 decimals.
		*/
		function set_commission_pct( $commission_pct ) {
			if ( ! empty( $this->commission_days ) && empty( $commission_pct ) ) {
				return FALSE;
			}
			if ( empty( $this->commission_days ) && ! empty( $commission_pct ) ) {
				return FALSE;
			}
			$valid = ( preg_match( '/^\d{0,3}(.\d{0,2})?$/' , $commission_pct ) === 1 );
			if ( $valid ) {
				$this->commission_pct = $commission_pct;
			}
			return $valid;
		}

		function get_campaign_id_html_input() {
			return vsmp_cpt_select( 'vsmp_campaign', 'campaign_id', 'ID', 'desc', $this->campaign_id );
		}
		function get_campaign_id_html_view() {
			$campaign = get_post( $this->campaign_id );
			return $campaign->post_title;
		}
		function get_channel_id_html_input() {
			return vsmp_cpt_select( 'vsmp_channel', 'channel_id', 'post_title', 'asc', $this->channel_id );
		}
		function get_channel_id_html_view() {
			$channel = get_post( $this->channel_id );
			$custom_vars = get_post_meta( $this->channel_id, 'detection' );
			return [$channel->post_title, $custom_vars[0]];
		}
		function get_commission_html_input() {
			$html = 'Days <INPUT TYPE=NUMBER ID=\'commission_days\' NAME=\'commission_days\' MIN=0 MAX=99 STEP=1'
				. ' VALUE=\'' . $this->commission_days . '\''
				. '/>'
				. ' Percent <INPUT TYPE=NUMBER ID=\'commission_pct\' NAME=\'commission_pct\' MIN=0 MAX=100 STEP=.01'
				. ' VALUE=\'' . $this->commission_pct . '\''
				. '/> %';
			return $html;
		}
		function get_commission_html_view() {
			$html = 'Days ' . $this->commission_days . ' Percent ' . $this->commission_pct . '%';
			return $html;
		}

		function save() {

			if ( ! isset( $this->id ) ) {
				$this->id = wp_insert_post(
					array (
					'post_type' => 'vsmp_delivery_event',
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
			update_post_meta( $this->id, "campaign_id", $this->campaign_id );
			update_post_meta( $this->id, "channel_id", $this->channel_id );
			update_post_meta( $this->id, "commission_days", $this->commission_days );
			update_post_meta( $this->id, "commission_pct", $this->commission_pct );
		}

	}

?>