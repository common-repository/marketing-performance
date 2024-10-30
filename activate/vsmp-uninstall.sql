* De-Activate Marketing Performance Plugin DDL
 
* @package    Marketing Performance
* @author     VyraSage
* @since      1.0.0
* @since      2.0.0 Added support for commissions based on click-through / order
* @license    GPL-3.0+
* @copyright  Copyright (c) 2019, VyraSage


<query>
DROP TABLE IF EXISTS {$wpdb->prefix}vsmp_attribution;
</query>

<query>
DROP TABLE IF EXISTS {$wpdb->prefix}vsmp_channel_metric;
</query>

<query>
DROP TABLE IF EXISTS {$wpdb->prefix}vsmp_conversion_log;
</query>

<query>
DROP TABLE IF EXISTS {$wpdb->prefix}vsmp_delivery_event_log;
</query>

<query>
DROP TABLE IF EXISTS {$wpdb->prefix}vsmp_influence;
</query>

<query>
DROP TABLE IF EXISTS {$wpdb->prefix}vsmp_commission;
</query>


<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_attribution;
</query>

<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_influence;
</query>

<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_path;
</query>

<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_commission;
</query>