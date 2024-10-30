<?php
/*
 Setup Campaigns

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

<script type="text/JavaScript">

	jQuery( document ).ready(
	
		function() {
		
			var nonce = <?PHP echo '"' . wp_create_nonce( 'vsmp-campaign_setup' ) . '"'?>;
			
			jQuery( '#campaign_id' ).on( 'change',
				function() {
					if ( !! ( jQuery( '#campaign_id' ).val() ) ) {
						jQuery( '#campaign_toggle' ).hide();
						jQuery( '#channel_toggle' ).show();
					} else {
						jQuery( '#channel_toggle' ).hide();
					}

				}
			)

			jQuery( '#channel_id' ).on( 'change',
				function() {
					jQuery( '#delivery_event_toggle' ).show();
				}
			)

			jQuery( '#campaign_show_button' ).click(
				function() {
					jQuery( '#campaign_title' ).val( '' );
					jQuery( '#campaign_desc' ).val( '' );
					jQuery( '#from_date' ).val( '' );
					jQuery( '#to_date' ).val( '' );
					jQuery( '#spend' ).val( '' );
					jQuery( '#est_roas' ).val( '' );
					jQuery( '#campaign_toggle' ).show();
					jQuery( '#channel_toggle' ).hide();
					jQuery( '#delivery_event_toggle' ).hide();
					jQuery( '#step_4' ).hide();
				}
			)
			
			jQuery( '#campaign_form' ).submit(
				function() {
					event.preventDefault();

					var campaign = {
						title: jQuery( '#campaign_title' ).val(),
						description: jQuery( '#campaign_desc' ).val(),
						from_date: jQuery( '#from_date' ).val(),
						to_date: jQuery( '#to_date' ).val(),
						spend: jQuery( '#spend' ).val(),
						est_roas: jQuery( '#est_roas' ).val()
					}

					var post_data = 'action=insert_campaign'
						+ '&payload=' + encodeURIComponent( JSON.stringify( campaign ) )
						+ '&vsmp-campaign_setup_nonce=' + nonce;
	
					jQuery.ajax({
						url: vsmp_ajaxurl, 
						type: 'POST',
						dataType: 'json',
						data: post_data,
						error: function( request, status, error ) {
							alert( status );
						},
						success: function( response ) {
							if ( typeof response.success != "undefined" && ! response.success ) {
								alert( response.data );
							} else {
								jQuery( '#campaign_toggle' ).hide(); 
								jQuery( '#campaign_select' ).html( response.html );
								jQuery( '#channel_toggle' ).show();
							}
						}
					})
				}
			)

			jQuery( '#delivery_event_form' ).submit(
				function() {
					event.preventDefault();

					var delivery_event = {
						title: jQuery( '#delivery_event_title' ).val(),
						description: jQuery( '#delivery_event_description' ).val(),
						campaign_id: jQuery( '#campaign_id' ).val(),
						channel_id: jQuery( '#channel_id' ).val(),
						commission_days: jQuery( '#commission_days' ).val(),
						commission_pct: jQuery( '#commission_pct' ).val()
					}

					var post_data = 'action=insert_delivery_event'
						+ '&payload=' + encodeURIComponent( JSON.stringify( delivery_event ) )
						+ '&vsmp-campaign_setup_nonce=' + nonce;

					jQuery.ajax({
						url: vsmp_ajaxurl, 
						type: 'post',
						dataType: 'json',
						data: post_data,
						error: function(request, status, error) {
							alert(status)
						},
						success: function(response) {
							if ( typeof response.success != "undefined" && ! response.success ) {
								alert( response.data );
							} else {
								jQuery( '#create_delivery_event' ).hide(); 
								jQuery( '#step_4' ).show(); 
								jQuery( '#query_string' ).show(); 
								jQuery( '#query_string' ).html( '<BR><BR>?vsmp=' + response.id )
							}
						}
					})
				}
			)

			jQuery( '#reload' ).click(
				function() {
					window.location.reload( true );
					window.scrollTo( 0, 0 );
				}
			)
			
		}
	);

</script>
</head>


<body><div class="wrap">
<h1>Create A New Marketing Delivery Event</h1>
Use this page to get the query string parameter to put on any link to your website that you want tracked in your marketing. You can also set up campaigns here to put your Delivery Events into.
<h3>Campaign</h3>
1. Pick a campaign

<span ID="campaign_select">
<?PHP
echo vsmp_cpt_select( 'vsmp_campaign', 'campaign_id', 'ID', 'desc' );
?>
</span>

 or <button id="campaign_show_button" class="button button-small">add</button> a new one:

<span id="campaign_toggle" style="display: none;">

<TABLE class="form-table">
<form id="campaign_form">
<TR><TD><label>Title</label></TD><TD><INPUT TYPE="TEXT" SIZE="100" ID="campaign_title" required></TD></TR>
<TR><TD><label>Description</label></TD><TD><TEXTAREA ROWS="6" COLS="50" ID="campaign_desc" required></TEXTAREA></TD></TR>
<TR><TD><label>From Date</label></TD><TD><?php echo get_from_date_mask(); ?></TD></TR>
<!--Non v5 alt date picker, will need a JS to parse from and to one single input, and will need to detect browser<TR><TD>From Date</TD><TD>
<fieldset style="display: none;" id="timestampdiv" class="hide-if-js">
	<legend class="screen-reader-text">Date and time</legend>
	<div class="timestamp-wrap"><label><span class="screen-reader-text">Month</span><select id="mm" name="mm">
			<option value="01" data-text="Jan">01-Jan</option>
			<option value="02" data-text="Feb">02-Feb</option>
			<option value="03" data-text="Mar">03-Mar</option>
			<option value="04" data-text="Apr">04-Apr</option>
			<option value="05" data-text="May">05-May</option>
			<option value="06" data-text="Jun">06-Jun</option>
			<option value="07" data-text="Jul">07-Jul</option>
			<option value="08" data-text="Aug" selected="selected">08-Aug</option>
			<option value="09" data-text="Sep">09-Sep</option>
			<option value="10" data-text="Oct">10-Oct</option>
			<option value="11" data-text="Nov">11-Nov</option>
			<option value="12" data-text="Dec">12-Dec</option>
</select></label> <label><span class="screen-reader-text">Day</span><input id="jj" name="jj" value="15" size="2" maxlength="2" autocomplete="off" type="text"></label>, <label><span class="screen-reader-text">Year</span><input id="aa" name="aa" value="2018" size="4" maxlength="4" autocomplete="off" type="text"></label> @ <label><span class="screen-reader-text">Hour</span><input id="hh" name="hh" value="23" size="2" maxlength="2" autocomplete="off" type="text"></label>:<label><span class="screen-reader-text">Minute</span><input id="mn" name="mn" value="32" size="2" maxlength="2" autocomplete="off" type="text"></label></div><input id="ss" name="ss" value="02" type="hidden">

<input id="hidden_mm" name="hidden_mm" value="08" type="hidden">
<input id="cur_mm" name="cur_mm" value="08" type="hidden">
<input id="hidden_jj" name="hidden_jj" value="15" type="hidden">
<input id="cur_jj" name="cur_jj" value="29" type="hidden">
<input id="hidden_aa" name="hidden_aa" value="2018" type="hidden">
<input id="cur_aa" name="cur_aa" value="2018" type="hidden">
<input id="hidden_hh" name="hidden_hh" value="23" type="hidden">
<input id="cur_hh" name="cur_hh" value="08" type="hidden">
<input id="hidden_mn" name="hidden_mn" value="32" type="hidden">
<input id="cur_mn" name="cur_mn" value="08" type="hidden">

<p>
<a href="#edit_timestamp" class="save-timestamp hide-if-no-js button">OK</a>
<a href="#edit_timestamp" class="cancel-timestamp hide-if-no-js button-cancel">Cancel</a>
</p>
	</fieldset>-->
<TR><TD><label>To Date</label></TD><TD><?php echo get_to_date_mask(); ?></TD></TR>
<TR><TD><label>Spend</label></TD><TD><?php echo get_spend_mask(); ?></TD></TR>
<TR><TD><label>Estimated Return (ROAS)</label></TD><TD><?php echo get_est_roas_mask(); ?></TD></TR>
<TR><TD COLSPAN=2 ALIGN=RIGHT><button class="button button-small">Create Campaign</button></TD></TR>
</form>
</TABLE>

</span>

<span id="channel_toggle" style="display: none;">
<BR><BR>
<h3>Channel</h3>
2. Pick a marketing channel:

<?PHP
echo vsmp_cpt_select( 'vsmp_channel', 'channel_id', 'post_title', 'asc' );
?>

</span>

<span id="delivery_event_toggle" style="display: none;">
<form id="delivery_event_form">
<BR><BR>
<h3>Event</h3>
3. Add a title and a description and optional commission schedule to this delivery event:
<TABLE>
<TR><TD>Title</TD><TD><INPUT TYPE=TEXT size="50" id="delivery_event_title" required></TD></TR>
<TR><TD>Description</TD><TD><TEXTAREA rows="6" cols="100" id="delivery_event_description" required></TEXTAREA></TD></TR>
<TR><TD>Commission Schedule</TD><TD>Days <INPUT TYPE=NUMBER ID='commission_days' MIN=0 MAX=99 STEP=1/>
	Percent <INPUT TYPE=NUMBER ID='commission_pct' MIN=0 MAX=100 STEP=.01/> %
	</TD></TR>
</TABLE>

<BR><BR><button ID="create_delivery_event" class="button button-small">Create Delivery Event</button>
</form>

<span>

<span id="step_4" style="display: none;">
<BR><BR>
<h3>Query String Parameter</h3>
4. Here's the query string parameter to add to your marketing links (don't worry about SEO):
<span id="query_string" style="display: none;">
</span>

<BR><BR><button id="reload" class="button button-small">Reset</button>
</span>
</div>
</body>