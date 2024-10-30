<?php
/*
 Generate Test Data

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

			var nonce = <?PHP echo '"' . wp_create_nonce( 'vsmp-test_data_generator' ) . '"'?>;
		
			jQuery( '#sim_click_through' ).on( 'submit',
				function() {
					event.preventDefault()
					var vsmp_visitor_id = jQuery( '#delivery_event_visitor_id' ).val()
					var delivery_event_id = jQuery( '#delivery_event_id' ).val()
					jQuery.ajax({
						url: vsmp_ajaxurl,
						type: 'POST',
						timeout: 5000,
						dataType: 'json',
						data: 'action=insert_click_thru&vsmp_visitor_id='	+ encodeURIComponent( vsmp_visitor_id )
							+ '&delivery_event_id=' +  delivery_event_id
							+ '&vsmp-test_data_generator_nonce=' + nonce,
						error: function( request, status, error ) {
							alert( status + ' ' + error )
						},
						success: function(response) {
							if ( response.result === 'baddata' ) {
								alert('Bad or missing data.');		
							} else if ( response.result === 'nonce' ) {
								alert( ' Not authorized. ' );								
							} else {
								alert( 'Click-Through logged' )
								window.location.reload( true );
								window.scrollTo( 0, 0 );
							}
						}
					})
				}
			)
			
			jQuery( '#sim_conversion' ).on( 'submit',
				function() {
					event.preventDefault()
					var vsmp_visitor_id = jQuery( '#conversion_visitor_id' ).val();
					var conversion_id = jQuery( '#conversion_id' ).val();
					var conversion_val = jQuery( '#conversion_val' ).val();
					jQuery.ajax({
						url: vsmp_ajaxurl,
						type: 'post',
						dataType: 'json',
						data: 'action=insert_conversion&vsmp_visitor_id=' + encodeURIComponent( vsmp_visitor_id ) 
							+ '&conversion_id=' + encodeURIComponent( conversion_id ) 
							+ '&conversion_val=' + conversion_val
							+ '&vsmp-test_data_generator_nonce=' + nonce,
						error: function( request, status, error ) {
							alert( error )
						},
						success: function( response ) {
							if ( response.result === 'dupid' ) {
								alert( 'Conversion Id already exists.' );
							} else if ( response.result === 'baddata' ) {
								alert( 'Bad or missing data.' );
							} else if ( response.result === 'nonce' ) {
								alert( ' Not authorized. ' );
							} else {
								alert( 'Conversion logged' );
								window.location.reload( true );
								window.scrollTo( 0, 0 );
							}
						}
					})
				}
			)
			
			jQuery( '#compute' ).click(
				function() {
					event.preventDefault()
					jQuery.ajax({
						url: vsmp_ajaxurl,
						type: 'post',
						dataType: 'html',
						data: "action=compute",
						error: function( request, status, error ) {
							alert( status )
						},
						success: function(response) {
							alert( 'Compute Completed' )
							window.location.reload(true);
							window.scrollTo( 0, 0 );
						}
					})
				}
			)
		}
	)


</script>

</head>


<body>

<span id="test_data_generator_nonce" style="display: none;"><?PHP echo wp_create_nonce('test_data_generator');?></span>

<div class="wrap">
<datalist id="visitors">
<option value="*COOKIE">
<option value="Andrew">
<option value="Bethany">
<option value="Chris">
<option value="Dana">
<option value="Edward">
<option value="Francis">
<option value="Gerald">
<option value="Haley">
<option value="Ivan">
<option value="Jesse">
<option value="Kyle">
</datalist>

<BR><BR>
<H1>Test Data Generator</H1>
<BR>

Use the page to test some basic activity.  Make sure you have some Campaigns and Delivery Events setup. You can then simulate click-through from any of the Delivery Events. 
And then to see what happens, key in a simulated conversion for that same Visitor Id.  Give it an Id and a value such as order number 123 for $99.95.  The Id must be unique.  
Any duplication Id's will be ignored since the plug in doesn't want to over-attribute.  After you have simulated 
a conversion, click the Compute Attribution button and you can view the results on the report pages.  
<BR><BR>
Once you see how it works and are comfortable, deactivate the plug in and reactivate it again.  Those steps will clear all the click-throughs and conversions captured
by the plug in.  Be careful because it will clear any live activity as well.  Deactivation will not clear any Campaigns, Delivery Events, or Channels.

<BR><BR><BR>
<li><B>Simulate Click-Through</B>
<FORM NAME='sim_click_through' ID='sim_click_through'>
<TABLE class="form-table">
<TR>
<TD WIDTH=200>Click-Through Visitor Id</TD><TD><INPUT TYPE=TEXT ID="delivery_event_visitor_id" NAME="delivery_event_visitor_id" LIST="visitors" style="width:500px;" required></TD>
</TR>
<TR>
<TD WIDTH=200>Delivery Event</TD>
<TD>
<?PHP
echo vsmp_cpt_select("vsmp_delivery_event", 'delivery_event_id', 'ID', 'desc', NULL, TRUE);
?>
</TD>
</TR>
<TR>
<TD></TD><TD><INPUT TYPE=SUBMIT class="button button-small"></TD>
</TR>
</TABLE>
</FORM>
</li>

<li><B>Simulate Conversion</B>
<FORM NAME='sim_conversion' ID='sim_conversion'>
<TABLE class="form-table">
<TR>
<TD WIDTH=200>Conversion Visitor Id</TD><TD><INPUT TYPE=TEXT ID="conversion_visitor_id" NAME="conversion_visitor_id" LIST="visitors" style="width:500px;" required></TD>
</TR>
<TR>
<TD WIDTH=200>Conversion Id</TD><TD><INPUT TYPE=TEXT ID="conversion_id" NAME="conversion_id" style="width:500px;" required></TD>
</TR>
<TR>
<TD WIDTH=200>Conversion Value</TD><TD><INPUT TYPE=NUMBER ID="conversion_val" NAME="conversion_val" min=".01" step=".01" style="width:120px;" required></TD>
</TR>
<TR>
<TD></TD><TD><INPUT TYPE=SUBMIT class="button button-small"></TD>
</TR>
</TABLE>
</FORM>
</li>

<BUTTON NAME='compute' ID='compute' class="button button-small">Compute</BUTTON><BR>(this may take a minute)

</ul>
</div>
</body>