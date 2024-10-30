=== Marketing Performance ===
Contributors: dwynkoop,ngwkoop
Donate link: http://www.vyrasage.com?vs_evt=1224
Tags: attribution,analytics,digital marketing
Requires at least: 4.9
Tested up to: 5.2.2
Stable tag: 2.0
Requires PHP: 7.0.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html


Get the real picture of marketing channels customers use to convert! Weighted paths across multiple channels, timing, channel characteristics, etc.

== Description ==
This WordPress plugin that lets you intelligently evaluate your marketing campaign effectiveness. It is based on weighted lags - the time from when customers click on a marketing channel to when they buy or convert - to assign likelihood of that marketing channel's influence on the convertion or purchase. It attributes a percentage of each conversion to each marketing channel the customer used, shows you the entire path of the customer, and which campaigns are performing well.

First, you should define all your digital marketing channels; affiliates, pay per click, SEO, email, social, etc.  You can then define the number of days that the channel can influence a conversion.  Of course, as time passes, the likelihood that an interaction influences a conversion is diminished in a lag curve.  For example, if a customer clicks through on an email and converts, that email is almost guaranteed to have influenced that conversion. 

Over time though, say a few days later,  if the customer comes back to your site and converts without a new click-through, the email probably had some influence but it's not as certain.  So we would weight the conversion after click-through much higher than the conversion a few days after click-through.

Each channel's lag curve will differ.  Some digital marketing channels assist the purchase such as affiliate coupons.  The may not be that influential since the customer's is already along the journey to conversion.  Some channels are subscription-based such as email or SMS.  People who subscribe are already aware of your brand and while the marketing contact is influential, there's an element of brand awareness that comes into play.  Of course, there is marketing where the customer is doing a search, finds your product, may or may not have ever heard of your company, but clicks through to find out more.  That path is highly influential.

Once each channel's lag curve is setup (there is a wizard to help), then you can setup campaigns and delivery events.  Campaigns are marketing programs that may or may not be tied to specific channels, e.g., \"father's day sale\").  Usually they have a budget and a fixed period of time.  Delivery events then are the channels used to deliver the marketing message for that campaign.  For our father's day sale, perhaps you will send a general email blast and then follow that up with some display ads and maybe even a followup targeted email.

Each delivery event is assigned a number.  Simply add that number to the click-through URL such as vs_evt={delivery event number}.  That is the trick to help the plugin track the responses to the marketing.  There is no opportunity on SEO to add any parameters to the click-through.  The plugin is smart enough to detect SEO from the referrers as long as your landing page is HTTPS.

Conversion can be tracking from WooCommerce or from a thank you page on your site.  Currently, thank yous served from Ajax are not supported but are on the list for a future enhancement.

Once we have click-throughs and conversions, there is a procedure which will interrogate the data and assign influence and attribution and present that information in reports.  It will also detect the path to conversion, that is, the marketing channel interactions that worked together in concert to influence the conversion.

See more on [MarketingPerformancePlugin.com](http://marketingperformanceplugin.com?vs_evt=1224).

== Installation ==
Install the plugin and activate it.  You can then setup some channels, campaigns, and delivery events.  There is a test data generator that will help you input some tests, run the computations, and see the results.  When you are satisfied, de-activate the plugin and all the click-throughs and conversions will be removed.  However channels, campaigns, and delivery events will be persisted.  If you want to purge that data, you will need to go to the posts and delete them manually.  Once you re-activate the plugin, the plugin will start capturing data again.  An automated compute step runs daily.

== Support ==
Besides using Wordpress.org plugin page, you can also email us directly from [MarketingPerformancePlugin.com](http://marketingperformanceplugin.com?vs_evt=1224).

== Frequently Asked Questions ==
= How is this different from Google Analytics? =

Most attribution is either first click, last click, or some type of weighting but most lack the ability to lag the weights over time.  The Marketing Performance Plugin allows you to control the weights of the influence over time by marketing channels.

= How do you know how much influence to assign to a click-through? =

You setup the lags for each channel.  There is a wizard to help you through the setup process. 

= Can I customize the lags? =

Absolutely.  There is a wizard which can help get you started but you can adjust the lags.  If you engage VyraSage in there services, we can help you determine the lags more precisely once you've captured a material volume of data.

= If I change the lags, can I rerun the computation from the beginning of time? =

The computation only goes back 4 weeks.  You can have a technical person modify the stored procedures or VyraSage can help you if you wish to recompute for a longer period. 

= How does it identify a marketing click-through? =

Make sure you add the vs_evt= into the URL as a parameter for channels other than SEO.  The number is the number assigned when you setup the delivery event.  You have to manually add that parameter to the links in the marketing URLs. 

= Is there a way to capture different types of SEO like brand vs. non-brand? =

Yes, this can be done.  But we will introduce that feature in a future release.

= What is unattributed =
Any conversion that is not influenced by any marketing being tracked will be placed into unattributed.  For any conversion where the likelihood of influence is less 100% so it can't be fully attributed to marketing, the residual conversion amount will be assigned to unattributed.

== Screenshots ==

1. Visualize the true value and performance of each of your marketing efforts.
2. Get a detail breakdown of attribution by marketing channel.
3. Understand the complex paths customers use before buying/converting.
4. Report on each marketing campaign -- did they meet the target Return on Ad Spend (ROAS).


== Changelog ==
Alpha release:  Winter 2018


== Upgrade Notice ==
= 1.0.0 =
Beta release
= 2.0.0 =
Added commission tracking