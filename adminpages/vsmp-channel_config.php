<?php
/*
 Configure Channels
 
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
<style>

.lag_wizard_overlay_style {
	display: none;
	position: fixed;
	z-index: 1;
	padding-top: 100px;
	left: 100px;
	top: 0;
	width: 100%;
	height: 100%;
	overflow: auto;
	background-color: rgb(0,0,0);
	background-color: rgba(0,0,0,0.4);
}
.lag_wizard_content_style {
	background-color: #fefefe;
	margin: auto;
	padding: 20px;
	border: 1px solid #888;
	width: 50%;
}

</style>

<script type="text/JavaScript">

	function channel_view( channel ) {
		jQuery( '#db_action' ).val( 'u' );
		jQuery( '#channel_id' ).val( channel.id );
		jQuery( '#channel_title' ).val( channel.title );
		jQuery( '#channel_desc' ).val( channel.description );
		jQuery( '#lag_days' ).val( channel.lag_days );
		jQuery( '#lag_days_value' ).text( channel.lag_days );
		for ( var i = 0; i <= channel.lag_days; i++ ) {
			var influence_var = 'influence_' + i;
			jQuery( '#' + influence_var ).val( channel.influence[i] );
		}
		influence_view();
		if ( ! channel.detection ) {
			channel.detection = 'vsmp_evt';
		}
		jQuery( '#' + channel.detection ).prop( 'checked', true );
		jQuery( '#channel_toggle' ).show();
		jQuery( '#channel_title' ).prop( 'readOnly', true );
		jQuery( '#channel_upsert' ).html( 'Update Channel' );
	}

	function influence_view() {
		for ( i = 0; i <= 9; i++ ) {
			if (i <= jQuery( '#lag_days' ).val()) {
				jQuery( '#day_' + i ).show();
				influence_text( i );
			} else {
				jQuery( 'influence_' + i ).val( '0' );
				jQuery( '#day_' + i ).hide();
			}
		}
	}
	function influence_text(i) {
		jQuery( '#influence_' + i + '_value' ).text( '(' + jQuery( '#influence_' + i ).val() + '%)' );
	}

	jQuery( document ).ready(
		function() {
			
			var nonce = <?PHP echo '"' . wp_create_nonce( 'vsmp-channel_config' ) . '"'?>;
			
			jQuery( '#channel_id' ).on( 'change',
				function() {
					if ( !! ( jQuery( '#channel_id' ).val() ) ) {
						var post_data = 'action=get_channel';
						post_data += '&vsmp_channel_id=' + jQuery( '#channel_id' ).val();

						jQuery.ajax({
							url: vsmp_ajaxurl, 
							type: 'POST',
							dataType: 'json',
							data: post_data,
							error: function( request, status, error ) {
								alert( status, error );
							},
							success: function( response ) {
								if ( typeof response.success != "undefined" && ! response.success ) {
									alert( response.data );
								} else {
									channel_view( response );
								}
							}
						})
					} else {
						jQuery( "#channel_toggle" ).hide();
					}
				}
			)

			jQuery( '#channel_new' ).click(
				function() {
					jQuery( '#db_action' ).val( 'a' );
					jQuery( '#channel_id' ).val( '' );
					jQuery( '#channel_title' ).val( '' );
					jQuery( '#channel_title' ).prop( 'readOnly', false );
					jQuery( '#channel_desc' ).val( '' );
					jQuery( '#lag_days' ).val( '0' );
					jQuery( '#lag_days_value' ).text( '0' );
					jQuery( '#influence_0' ).val( '0' );
					influence_view();
					jQuery( '#detection' ).val( 'vsmp_evt' );
					jQuery( '#channel_upsert' ).html( 'Add New Channel' );
					jQuery( '#channel_toggle' ).show();
				}
			)

			jQuery( '#channel_form' ).submit(
				function() {
					event.preventDefault();

					var influence = [];
					for ( i = 0; i <= jQuery( '#lag_days' ).val(); i++ ) {
						influence.push( jQuery( '#influence_' + i ).val() );
					}

					var channel = {
						id: jQuery( '#channel_id' ).val(),
						title: jQuery( '#channel_title' ).val(),
						description: jQuery( '#channel_desc' ).val(),
						lag_days: jQuery( '#lag_days' ).val(),
						influence: influence,
						detection: jQuery( 'input[name="detection"]:checked' ).val()
					}
					
					var post_data = 'action=upsert_channel'
						+ '&payload=' + encodeURIComponent( JSON.stringify( channel ) )
						+ '&vsmp-channel_config_nonce=' + nonce;

					jQuery.ajax({
						url: vsmp_ajaxurl, 
						type: 'POST',
						dataType: 'json',
						data: post_data,
						error: function( request, status, error ) {
							alert( status )
						},
						success: function( response ) {
							if ( typeof response.success != "undefined" && ! response.success ) {
								alert( response.data );
							} else {
								alert( 'Channel updated successfully' );
								window.location.reload( true );
								window.scrollTo( 0, 0 );
							}
						}
					})
				}
			);

			jQuery( '#lag_days' ).on( 'change',
				function() {
					influence_view();
				}
			);

			jQuery( '#influence_0' ).on( 'change', 
				function() {
					influence_text(0);
				}
			);
			jQuery( '#influence_1' ).on( 'change',
				function() {
					influence_text(1);
				}
			);
			jQuery( '#influence_2' ).on( 'change',
				function() {
					influence_text(2);
				}
			);
			jQuery( '#influence_3' ).on( 'change', 
				function() {
					influence_text(3);
				}
			);
			jQuery( '#influence_4' ).on( 'change', 
				function() {
					influence_text(4);
				}
			);
			jQuery( '#influence_5' ).on( 'change',
				function() {
					influence_text(5);
				}
			);
			jQuery( '#influence_6' ).on( 'change', 
				function() {
					influence_text(6);
				}
			);
			jQuery( '#influence_7' ).on( 'change',
				function() {
					influence_text(7);
				}
			);
			jQuery( '#influence_8' ).on( 'change', 
				function() {
					influence_text(8);
				}
			);
			jQuery( '#influence_9' ).on( 'change',
				function() {
					influence_text(9);
				}
			);


			jQuery( '#lag_wizard_button' ).click(
				function() {
					jQuery( '#lag_wizard_overlay' ).css( 'display', 'block' );
				}
			)
			jQuery( '#lag_wizard_close' ).click(
				function() {
					jQuery( '#lag_wizard_overlay' ).css( 'display', 'none' );
				}
			)	
			jQuery( '#lag_wizard_calculate' ).click(
				function() {
					var adj_factor = jQuery( 'input[name="brand_awareness"]:checked' ).val();
					var marketing_type = jQuery( 'input[name="marketing_type"]:checked' ).val();
					if (marketing_type === 'push') {
						jQuery( '#lag_days' ).val( '5' );
						for ( var i = 0; i <= 5; i++ ) {
							var influence = Math.floor( ((Math.log(i+1) * -50) + 100) / adj_factor );
							jQuery( '#influence_' + i ).val( influence );
						}
					} else if ( marketing_type === 'active' ) {
						jQuery( '#lag_days' ).val( '3' );
						for ( var i = 0; i <= 3; i++ ) {
							var influence = Math.floor( ((Math.log(i+1) * -66) + 100) / adj_factor );
							jQuery( '#influence_' + i ).val( influence );
						}
					} else if ( marketing_type == 'passive' ) {
						jQuery( '#lag_days' ).val( '1' );
						for ( var i = 0; i <= 1; i++ ) {
							var influence = Math.floor( ((Math.log(i+1) * -100) + 100) / adj_factor );
							jQuery( '#influence_' + i ).val( influence );
						}
					} else if ( marketing_type == 'assist' ) {
						jQuery( '#lag_days' ).val( '0' );
						jQuery( '#influence_0' ).val( '25' );
					}
					influence_view();
					jQuery( '#lag_wizard_overlay' ).css( 'display', 'none' );
				}
			)
		}
	)

</script>
</head>
<body><div class="wrap"><h1>Channels and Default Lags</h1>
Pick a channel
<?PHP
echo vsmp_cpt_select( 'vsmp_channel', 'channel_id', 'post_title', 'asc' );
?>
or <button id="channel_new" class="button button-small">add</button> a new one:

<span id="channel_toggle" style="display: none;">

<FORM ID="channel_form"> 

<INPUT TYPE="HIDDEN" ID="db_action">
<INPUT TYPE="HIDDEN" ID="channel_id">
<TABLE class="form-table">
<TR><TD><label>Channel Abbreviation</label></TD><TD><INPUT TYPE="TEXT" SIZE="12" ID="channel_title" required></TD></TR>
<TR><TD><label>Description</label></TD><TD><TEXTAREA ROWS="6" COLS="100" ID="channel_desc" NAME="channel_desc" required></TEXTAREA></TD></TR>
</TABLE>
<BR><BR>
<BUTTON class="button button-small" id="lag_wizard_button" type="button">Lag Wizard</BUTTON>
<BR><BR>

<TABLE class="form-table">
<TR><TD><label>Lag Days <p ID="lag_days_value"></p></label></TD><TD>
<?PHP
echo get_lag_days_mask();
?>
</TD></TR>
<TR><TD COLSPAN="2"><B><I>Influence</I></B></TD></TR>
<?php
for ( $i = 0; $i <= 9; ++$i ) {
	echo '<TR id="day_' . $i . '"><TD><label>Day ' . $i .  '<p ID="influence_' . $i . '_value"></p></label></TD>';
	echo '<TD>';
	echo get_influence_mask( $i );
	echo '</TD></TR>';
}
?>
<TR><TD><label>How to Detect This Channel</label></TD><TD>
<?php
echo get_detection_mask();
?>
</TD></TR>
<TR><TD COLSPAN="2"><button id="channel_upsert" class="button button-small">Update Channel</button></TD></TR>
</TABLE>

</FORM>


</span>

<div id="lag_wizard_overlay" class="lag_wizard_overlay_style">
<div class="lag_wizard_content_style">
<button id="lag_wizard_close" class="button button-small" style="float:right;">&times</button>

<p><i>How well known is your brand?</i></p>
<input type="radio" name="brand_awareness" value="1">Not too many have heard of us
<BR><input type="radio" name="brand_awareness" value="1.5">Known locally but limited nationally
<BR><input type="radio" name="brand_awareness" value="2">Nationally known and recognized
<BR><BR><p><i>Which option best describes this type of marketing?</i></p>
<input type="radio" name="marketing_type" value="push">Subscription based push marketing such as email or SMS
<BR><input type="radio" name="marketing_type" value="active">Customer initiated action like search
<BR><input type="radio" name="marketing_type" value="passive">Customer clicked through an ad or a link
<BR><input type="radio" name="marketing_type" value="assist">Provides assistance to the conversion
<BR><BR><button id="lag_wizard_calculate" class="button button-small">Calculate</button>

</div>
</div>

</div>
</body>