<?php
/*
 Overview of Plugin
 
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


<body><div class="wrap">

<?php
	$admin_url = admin_url() . 'admin.php?page=';
?>

<h1>Welcome to The Marketing Performance Plugin</h1>
<h3>The WordPress plugin that lets you intelligently evaluate your marketing campaign effectiveness.</h3>
Each time you create marketing activity, you should

<ol><li>Set up a <a href="
<?php 
	echo $admin_url . 'vsmp_campaign_setup';
?>
">Delivery Event</a> (i.e. "mother's day first email"),</li>

<li>Organize Events into <a href="
<?php 
	echo $admin_url . 'vsmp_campaign_setup';
?>
">Campaigns</a>
("mother's day campaign") and <a href="
<?php 
	echo $admin_url . 'vsmp_channel_config';
?>
">Channels</a> ("email", "SEO", etc.) to see reports on results of each!</li>

<li>Except for SEO, use the query string parameter (a little tag you place on every link in that Event) 
to see related conversions (mysite.com/mypage?vsmp_evt=1234).
For SEO, the system will automatically check for the most common search engines.</li></ol>

Do these for every marketing activity big or small, and soon you will have a complete picture of your marketing effectiveness
in the <a href="
<?php 
	echo $admin_url . 'vsmp_attribution_report';
?>
">Attribution Report</a> and <a href="
<?php 
	echo $admin_url . 'vsmp_campaign_performance_report';
?>
">Campaign Performance Report</a>.
</div>

<div class="wrap"><h1>Secret Sauce</h1>
<h3>Default Lags</h3>
Most marketing analytics will treat all of your customer's activities the same. They use techniques like
first click, last click, proportional attribution, and such to assign attribution.  To get a more realistic picture 
of your marketing performance, the Marketing Performance Plugin uses time based "Lags" for each marketing delivery event
you create.  When a customer clicks-through your marketing link and then converts in that visit or even a later visit, 
some of that conversion is likely to have been influenced by that click-through.  You can configure the amount of influence
based on the type of marketing and the number of days between the click-through and the conversion.  Attribution is then
calculated from the marketing influences of that conversion.
<br/><br/>For example, if a customer clicks your link and converts that day, you can assume that Delivery Event was highly
influential to that conversion. However, if they don't purchase until 6 days after the click, should you really consider
that Delivery Event to be as influential? What if they click-through 3 other Delivery Events during those 6 days?
<br/><br/>To solve these problems, this plugin will assign influence of any conversion to EACH marketing Delivery Event,
Channel, and Campaign that the customer interacts with, based on the "Lag".  If there is no click-through or the conversion
is beyond the lag, then the no marketing will be considered influential to that conversion.  Based on all conversion and their 
corresponding influences, attribution will then be calculated.  Any uninfluenced conversion will be bucketed into unattributed.
If the influences are less than 100%, the residual amount will be also bucketed into unattributed.  If the influences are greater 
than 100%, the influences are indexed to 100%.
<br/><br/>You can set some KPI (Key Performance Indicators) that indicate which of your Delivery Events and Campaigns
performed to expectations by calculating return on ad spend (ROAS) taking the attribution divided by the ad spend.  Within this
plugin, you can specify the expected ROAS.  The reports will then show which campaigns met the expected performance by highlighted
them in green (exceeded expectation), yellow (came within 95% of expectation), or red.

<br/><br/>Visit the <a href="
<?php 
	echo $admin_url . 'vsmp_channel_config';
?>
">Channels</a> page to configure how long and how much influence a Delivery Event click-through should be assigned to a conversion.
If you are unsure of the lag you should assign, use the handy Lag Wizard.  It will ask a couple of questions and assign a
lag for you.  You can always configure the lag as you learn more about your particular marketing.

</div>

</body>