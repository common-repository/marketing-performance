* Activate The Marketing Performance Plugin DDL
* @package    Marketing Performance
* @author     VyraSage
* @since      1.0.0
* @since      2.0.0 Added support for commissions based on click-through / order
* @license    GPL-3.0+
* @copyright  Copyright (c) 2019, VyraSage


<dbDelta>
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vsmp_attribution (
 cal_date date NOT NULL,
 delivery_event_id int(11) NOT NULL,
 attributed_conversions float NOT NULL,
 attributed_value float NOT NULL,
 KEY cal_date (cal_date)
);
</dbDelta>

<dbDelta>
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vsmp_channel_metric (
 cal_date date NOT NULL,
 metric_type varchar(16) NOT NULL,
 metric_detail varchar(128) NOT NULL,
 metric_count int(11) NOT NULL
);
</dbDelta>

<dbDelta>
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vsmp_conversion_log (
 vsmp_visitor_id varchar(32) NOT NULL,
 log_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 conversion_id varchar(32) NOT NULL,
 conversion_val float NOT NULL,
 UNIQUE KEY conversion_id (conversion_id),
 KEY vsmp_visitor_id (vsmp_visitor_id)
);
</dbDelta>

<dbDelta>
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vsmp_delivery_event_log (
 vsmp_visitor_id varchar(32) NOT NULL,
 delivery_event_id int(11) NOT NULL,
 log_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY vsmp_visitor_id (vsmp_visitor_id)
);
</dbDelta>

<dbDelta>
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vsmp_influence (
 conversion_id varchar(32) NOT NULL,
 delivery_event_id int(11) NOT NULL,
 path_seq smallint(6) NOT NULL,
 influence float NOT NULL,
 KEY conversion_id (conversion_id)
);
</dbDelta>

<dbDelta>
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vsmp_commission (
 conversion_id varchar(32) NOT NULL,
 conversion_date date NOT NULL,
 conversion_val float NOT NULL,
 delivery_event_id int(11) NOT NULL,
 commission_pct float NOT NULL,
 commission decimal(7,2) NOT NULL
);
</dbDelta>

<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_attribution;
</query>

<query>
CREATE PROCEDURE {$wpdb->prefix}vsmp_compute_attribution (IN `table_prefix` VARCHAR(16))
BEGIN

DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_influence;
DROP TABLE IF EXISTS tmp_adj_influence;
DROP TABLE IF EXISTS tmp_attribution;


CREATE TABLE tmp_date_range AS (
     SELECT DATE_SUB(CURRENT_DATE, INTERVAL 28 DAY) AS from_date, CURRENT_DATE AS to_date
);


SET @stmt1 = CONCAT(
    "CREATE TABLE tmp_conversion_log AS ("
    , " SELECT *"
    , " FROM ", table_prefix, "vsmp_conversion_log"
    , " WHERE DATE(log_time) BETWEEN"
    , " (SELECT from_date FROM tmp_date_range)"
    , " AND"
    , " (SELECT to_date FROM tmp_date_range)"
 	, ")"
);

    
PREPARE vsmp_sql FROM @stmt1;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt2 = CONCAT(
	"CREATE TABLE tmp_influence AS ("
	, " SELECT a.conversion_id, a.delivery_event_id, MAX(influence) AS influence"
	, " FROM ", table_prefix, "vsmp_influence a"
	, " WHERE a.conversion_id IN ("
	, " SELECT conversion_id"
	, " FROM tmp_conversion_log"
	, " )"
    , " GROUP BY a.conversion_id, a.delivery_event_id"
    , ")"
);

PREPARE vsmp_sql FROM @stmt2;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt3 = CONCAT(
	"CREATE TABLE tmp_adj_influence AS ("
	, " SELECT a.conversion_id, a.delivery_event_id,"
	, " (a.influence * (CASE WHEN sum_influence > 1 THEN 1 / sum_influence ELSE 1 END)) / 1 AS adj_influence"
	, " FROM tmp_influence a"
	, " JOIN ("
	, " SELECT conversion_id, SUM(influence) AS sum_influence"
	, " FROM tmp_influence"
	, " GROUP BY conversion_id"
	, " ) b ON b.conversion_id = a.conversion_id"
    , ")"
);

PREPARE vsmp_sql FROM @stmt3;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt4 = CONCAT(
	"CREATE TABLE tmp_attribution AS ("
    , " SELECT DATE(b.log_time) AS cal_date, a.delivery_event_id,"
    , " SUM(a.adj_influence) AS attributed_conversions,"
    , " SUM(a.adj_influence * b.conversion_val) AS attributed_value"
    , " FROM tmp_adj_influence a"
	, " JOIN tmp_conversion_log b ON b.conversion_id = a.conversion_id"
 	, " GROUP BY 1, 2"
	, " )"
);

PREPARE vsmp_sql FROM @stmt4;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt5 = CONCAT(
	"DELETE FROM ", table_prefix, "vsmp_attribution"
	, " WHERE cal_date BETWEEN"
    , " (SELECT from_date FROM tmp_date_range)" 
    , " AND"
    , " (SELECT to_date FROM tmp_date_range)"	
);

PREPARE vsmp_sql FROM @stmt5;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt6 = CONCAT(
	"INSERT INTO ", table_prefix, "vsmp_attribution"
    , " (cal_date, delivery_event_id, attributed_conversions, attributed_value)"
    , " SELECT cal_date, delivery_event_id, attributed_conversions, attributed_value"
    , " FROM tmp_attribution"
);

PREPARE vsmp_sql FROM @stmt6;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt7 = CONCAT(
	"INSERT INTO ", table_prefix, "vsmp_attribution"
	, " (cal_date, attributed_conversions, attributed_value)"
	, " SELECT a.conversion_date,"
	, " a.conversion_count - IFNULL(b.attributed_conversions,0) AS unattributed_conversions,"
	, " a.conversion_value - IFNULL(b.attributed_value,0) AS unattributed_value"
	, " FROM ("
	, " SELECT date(log_time) AS conversion_date,"
    , " COUNT(*) AS conversion_count,"
    , " SUM(conversion_val) AS conversion_value"
	, " FROM tmp_conversion_log"
	, " GROUP BY 1"
	, " ) a"
	, " LEFT JOIN ("
	, " SELECT cal_date,"
    , " SUM(attributed_conversions) AS attributed_conversions,"
    , " SUM(attributed_value) AS attributed_value"
	, " FROM tmp_attribution"
	, " GROUP BY cal_date"
	, ") b ON b.cal_date = a.conversion_date"
    , " WHERE a.conversion_value - IFNULL(b.attributed_value,0) > .01"
);

PREPARE vsmp_sql FROM @stmt7;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_influence;
DROP TABLE IF EXISTS tmp_adj_influence;
DROP TABLE IF EXISTS tmp_attribution;

END
</query>

<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_influence;
</query>

<query>
CREATE PROCEDURE {$wpdb->prefix}vsmp_compute_influence (IN `table_prefix` VARCHAR(16))
BEGIN

DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_delivery_event;
DROP TABLE IF EXISTS tmp_channel_lag;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_delivery_event_log;
DROP TABLE IF EXISTS tmp_delivery_event_influence_01;
DROP TABLE IF EXISTS tmp_delivery_event_influence_02;


CREATE TABLE tmp_date_range AS (
     SELECT DATE_SUB(CURRENT_DATE, INTERVAL 28 DAY) AS from_date, CURRENT_DATE AS to_date
);

SET @stmt1 = CONCAT(
    "CREATE TABLE tmp_delivery_event AS ("
    , " SELECT a.ID AS delivery_event_Id,"
    , " CONVERT(b.meta_value, INTEGER) AS campaign_id,"
    , " CONVERT(c.meta_value, INTEGER) AS channel_Id"
    , " FROM ", table_prefix, "posts a"
    , " JOIN ", table_prefix, "postmeta b ON b.post_id = a.ID AND b.meta_key = 'campaign_id'"
 	, " JOIN ", table_prefix, "postmeta c ON c.post_id = a.ID AND c.meta_key = 'channel_id'"
    , " WHERE a.post_type = 'vsmp_delivery_event'"
    , " AND a.post_status = 'publish'"
    , ')'
);

PREPARE vsmp_sql FROM @stmt1;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt2 = CONCAT(
    "CREATE TABLE tmp_channel_lag AS ("
    , " SELECT a.ID as channel_id, a.post_title AS channel,"
    , " CONVERT(SUBSTR(c.meta_key,11,1), INTEGER) AS day_no,"
    , " CONVERT(IFNULL(c.meta_value, '0'), INTEGER) AS influence"
 	, " FROM ", table_prefix, "posts a"
    , " JOIN ", table_prefix, "postmeta b ON b.post_id = a.ID AND b.meta_key = 'lag_days'"
    , " JOIN ", table_prefix, "postmeta c ON c.post_id = a.ID AND SUBSTR(c.meta_key,1,10) = 'influence_'"
 	, " WHERE a.post_type = 'vsmp_channel'"
    , " AND a.post_status = 'publish' "
    , " AND CONVERT(SUBSTR(c.meta_key,11,1), INTEGER) <= CONVERT(b.meta_value, INTEGER)"
    , ')'
);

PREPARE vsmp_sql FROM @stmt2;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt3 = CONCAT(
    "CREATE TABLE tmp_conversion_log AS ("
    , " SELECT *"
    , " FROM ", table_prefix, "vsmp_conversion_log"
    , " WHERE DATE(log_time) BETWEEN"
    , " (SELECT from_date FROM tmp_date_range)"
    , " AND"
    , " (SELECT to_date FROM tmp_date_range)"
 	, ")"
);
    
PREPARE vsmp_sql FROM @stmt3;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;

SET @stmt4 = CONCAT(
    "CREATE TABLE tmp_delivery_event_log AS ("
    , " SELECT *"
    , " FROM ", table_prefix, "vsmp_delivery_event_log"
    , " WHERE DATE(log_time) BETWEEN"
    , " (SELECT DATE_SUB(from_date, INTERVAL 14 DAY) FROM tmp_date_range)"
    , " AND"
    , " (SELECT to_date FROM tmp_date_range)"
	, ")"
);
    
PREPARE vsmp_sql FROM @stmt4;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


CREATE TABLE tmp_delivery_event_influence_01 AS (
 SELECT @row_seq := @row_seq + 1 as row_seq, a.conversion_id, a.channel_id, a.delivery_event_id, a.delivery_event_time, a.conversion_time, a.day_no
 FROM (
  SELECT a1.conversion_id, a3.channel_id, a2.delivery_event_id, a2.log_time AS delivery_event_time, a1.log_time AS conversion_time,
   FLOOR(TIMESTAMPDIFF(SECOND, a2.log_time, a1.log_time) / 86400) AS day_no
  FROM tmp_conversion_log a1
  JOIN tmp_delivery_event_log a2 on a2.vsmp_visitor_Id = a1.vsmp_visitor_Id
  JOIN tmp_delivery_event a3 ON a3.delivery_event_Id = a2.delivery_event_Id
  JOIN (
   SELECT channel_id, MAX(day_no) AS max_day_no
   FROM tmp_channel_lag
   GROUP BY channel_id
  ) a4 ON a4.channel_id = a3.channel_id
   WHERE a1.log_time >= a2.log_time - INTERVAL a4.max_day_no DAY
  ORDER BY a1.conversion_id, a2.log_time
 ) a
 CROSS JOIN (
  SELECT @row_seq := 0
 ) b
);

CREATE TABLE tmp_delivery_event_influence_02 AS (
 SELECT a.conversion_id, a.channel_id, a.delivery_event_Id, a.day_no, a.delivery_event_time, a.conversion_time, b.path_seq
 FROM tmp_delivery_event_influence_01 a  
 JOIN (
  SELECT b1.row_seq, COUNT(*) AS path_seq
  FROM tmp_delivery_event_influence_01 b1
  JOIN tmp_delivery_event_influence_01 b2 ON b2.conversion_id = b1.conversion_id AND b2.delivery_event_time <= b1.delivery_event_time
  GROUP BY b1.row_seq
 ) b ON b.row_seq = a.row_seq
);


SET @stmt5 = CONCAT(
	"DELETE FROM ", table_prefix, "vsmp_influence"
 	, " WHERE conversion_id IN ("
  	, " SELECT DISTINCT conversion_id"
  	, " FROM tmp_delivery_event_influence_02"
 	, ")"
);

PREPARE vsmp_sql FROM @stmt5;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt6 = CONCAT(
    "INSERT INTO ", table_prefix, "vsmp_influence"
    , " (conversion_Id, delivery_event_id, path_seq, influence)"
    , " SELECT a.conversion_id, a.delivery_event_id, a.path_seq, b.influence / 100"
    , " FROM tmp_delivery_event_influence_02 a"
 	, " JOIN tmp_channel_lag b ON b.channel_id = a.channel_id AND b.day_no = a.day_no"
);

PREPARE vsmp_sql FROM @stmt6;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_delivery_event;
DROP TABLE IF EXISTS tmp_channel_lag;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_delivery_event_log;
DROP TABLE IF EXISTS tmp_delivery_event_influence_01;
DROP TABLE IF EXISTS tmp_delivery_event_influence_02;

END
</query>

<query>
DROP PROCEDURE IF EXISTS {$wpdb->prefix}vsmp_compute_path;
</query>

<query>
CREATE PROCEDURE {$wpdb->prefix}vsmp_compute_path (IN `table_prefix` VARCHAR(16))
BEGIN

DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_delivery_event;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_influence;
DROP TABLE IF EXISTS tmp_channel_metric_01;
DROP TABLE IF EXISTS tmp_channel_metric_02;
DROP TABLE IF EXISTS tmp_channel_metric_03;


CREATE TABLE tmp_date_range AS (
     SELECT DATE_SUB(CURRENT_DATE, INTERVAL 28 DAY) AS from_date, CURRENT_DATE AS to_date
);


SET @stmt1 = CONCAT(
  "CREATE TABLE tmp_delivery_event AS ("
    , " SELECT a.ID AS delivery_event_Id,"
    , " CONVERT(b.meta_value, INTEGER) AS campaign_id,"
    , " CONVERT(c.meta_value, INTEGER) AS channel_id,"
	, " d.post_title AS channel_title"
    , " FROM ", table_prefix, "posts a"
    , " JOIN ", table_prefix, "postmeta b ON b.post_id = a.ID AND b.meta_key = 'campaign_id'"
 	, " JOIN ", table_prefix, "postmeta c ON c.post_id = a.ID AND c.meta_key = 'channel_id'"
	, " JOIN ", table_prefix, "posts d ON d.ID = CONVERT(c.meta_value, INTEGER)"
    , " WHERE a.post_type = 'vsmp_delivery_event'"
    , " AND a.post_status = 'publish'"
    , ')'
);

PREPARE vsmp_sql FROM @stmt1;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt2 = CONCAT(
    "CREATE TABLE tmp_conversion_log AS ("
    , " SELECT conversion_id, DATE(log_time) AS cal_date"
    , " FROM ", table_prefix, "vsmp_conversion_log"
    , " WHERE DATE(log_time) BETWEEN"
    , " (SELECT from_date FROM tmp_date_range)"
    , " AND"
    , " (SELECT to_date FROM tmp_date_range)"
 	, ")"
);
    
PREPARE vsmp_sql FROM @stmt2;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt3 = CONCAT(
  "CREATE TABLE tmp_influence AS ("
    , " SELECT *"
    , " FROM ", table_prefix, "vsmp_influence"
    , " WHERE conversion_id IN ("
    , " SELECT conversion_id"
    , " FROM tmp_conversion_log"
    , ")"
    , ")"
);

PREPARE vsmp_sql FROM @stmt3;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt4 = CONCAT(
    "DELETE FROM ", table_prefix, "vsmp_channel_metric"
    , " WHERE cal_date BETWEEN"
    , " (SELECT from_date FROM tmp_date_range)"
    , " AND"
    , " (SELECT to_date FROM tmp_date_range)"
);

PREPARE vsmp_sql FROM @stmt4;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt5 = CONCAT(
    "CREATE TABLE tmp_channel_metric_01 AS ("
	, " SELECT b.cal_date,"
    , " TRIM(a.metric_detail) AS metric_detail,"
    , " a.channel_count,"
    , " COUNT(*) AS metric_count"
    , " FROM ("
	, " SELECT a1.conversion_id,"
    , " GROUP_CONCAT(a1.channel_title ORDER BY a1.channel_title SEPARATOR ';') AS metric_detail,"
    , " COUNT(*) AS channel_count"
	, " FROM ("
	, " SELECT DISTINCT a1a.conversion_id, a1b.channel_title"
	, " FROM tmp_influence a1a"
	, " JOIN tmp_delivery_event a1b ON a1b.delivery_event_id = a1a.delivery_event_id"
    , " ) a1"
	, " GROUP BY a1.conversion_id"
    , " ) a"
    , " JOIN tmp_conversion_log b ON b.conversion_id = a.conversion_id"
    , " GROUP BY 1, 2"
    , ")"
);

PREPARE vsmp_sql FROM @stmt5;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt6 = CONCAT(
    "INSERT INTO ", table_prefix, "vsmp_channel_metric"
	, " (cal_date, metric_type, metric_detail, metric_count)"
    , " SELECT cal_date, 'channels', metric_detail, metric_count"
    , " FROM tmp_channel_metric_01"
);

PREPARE vsmp_sql FROM @stmt6;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt7 = CONCAT(
    "CREATE TABLE tmp_channel_metric_02 AS ("
	, " SELECT b.cal_date, c.channel_title, COUNT(*) AS metric_count"
    , " FROM wp_vsmp_influence a"
    , " JOIN tmp_conversion_log b ON b.conversion_id = a.conversion_id"
    , " JOIN tmp_delivery_event c ON c.delivery_event_Id = a.delivery_event_id"
    , " WHERE a.path_seq = 1"
    , " GROUP BY 1, 2"
    , " )"
);

PREPARE vsmp_sql FROM @stmt7;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt8 = CONCAT(
    "INSERT INTO ", table_prefix, "vsmp_channel_metric"
	, " (cal_date, metric_type, metric_detail, metric_count)"
    , " SELECT cal_date, 'starter', channel_title, metric_count"
    , " FROM tmp_channel_metric_02"
);

PREPARE vsmp_sql FROM @stmt8;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt9 = CONCAT(
    "CREATE TABLE tmp_channel_metric_03 AS ("
    , " SELECT b.cal_date, c.channel_title, COUNT(*) AS metric_count"
    , " FROM tmp_influence a" 
    , " JOIN tmp_conversion_log b ON b.conversion_id = a.conversion_id"
    , " JOIN tmp_delivery_event c ON c.delivery_event_Id = a.delivery_event_id"
    , " JOIN ("
    , " SELECT conversion_id, MAX(path_seq) AS max_path_seq"
    , " FROM tmp_influence"
    , " GROUP BY conversion_id"
    , " ) d ON d.conversion_id = a.conversion_id AND d.max_path_seq = a.path_seq" 
    , " GROUP BY 1, 2"
    , ")"
);

PREPARE vsmp_sql FROM @stmt9;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt10 = CONCAT(
    "INSERT INTO ", table_prefix, "vsmp_channel_metric"
	, " (cal_date, metric_type, metric_detail, metric_count)"
    , " SELECT cal_date, 'closer', channel_title, metric_count"
    , " FROM tmp_channel_metric_03"
);

PREPARE vsmp_sql FROM @stmt10;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_delivery_event;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_influence;
DROP TABLE IF EXISTS tmp_channel_metric_01;
DROP TABLE IF EXISTS tmp_channel_metric_02;
DROP TABLE IF EXISTS tmp_channel_metric_03;

END
</query>

<query>
CREATE PROCEDURE {$wpdb->prefix}vsmp_compute_commission (IN `table_prefix` VARCHAR(16))
BEGIN

CREATE OR REPLACE TABLE tmp_date_range AS (
 SELECT DATE_SUB(CURRENT_DATE, INTERVAL 28 DAY) AS from_date, CURRENT_DATE AS to_date
);

SET @stmt1 = CONCAT(
 "CREATE OR REPLACE TABLE tmp_conversion_log AS" 
 , " SELECT *"
 , " FROM ", table_prefix, "vsmp_conversion_log" 
 , " WHERE DATE(log_time) BETWEEN"   
 , " (SELECT from_date FROM tmp_date_range)" 
 , " AND"  
 , " (SELECT to_date FROM tmp_date_range)" 
);
PREPARE vsmp_sql FROM @stmt1;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;

SET @stmt2 = CONCAT(
 "CREATE OR REPLACE TABLE tmp_delivery_event AS "
 , " SELECT a.ID AS delivery_event_Id,"   
 , " CONVERT(b.meta_value, INTEGER) AS campaign_id," 
 , " CONVERT(c.meta_value, INTEGER) AS channel_Id,"
 , " CONVERT(d.meta_value, INTEGER) AS commission_days,"
 , " CAST(e.meta_value AS DECIMAL(5,2)) AS commission_pct" 
 , " FROM ", table_prefix, "posts a"  
 , " JOIN ", table_prefix, "postmeta b ON b.post_id = a.ID AND b.meta_key = 'campaign_id'" 
 , " JOIN ", table_prefix, "postmeta c ON c.post_id = a.ID AND c.meta_key = 'channel_id'"
 , " JOIN ", table_prefix, "postmeta d ON d.post_id = a.ID AND d.meta_key = 'commission_days' AND d.meta_key IS NOT NULL"
 , " JOIN ", table_prefix, "postmeta e ON e.post_id = a.ID AND e.meta_key = 'commission_pct' AND e.meta_key IS NOT NULL"
 , " WHERE a.post_type = 'vsmp_delivery_event'"  
 , " AND a.post_status = 'publish'"
);

PREPARE vsmp_sql FROM @stmt2;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;

SET @stmt3 = CONCAT(
 "CREATE OR REPLACE TABLE tmp_delivery_event_log AS"    
 , " SELECT *"   
 , " FROM ", table_prefix, "vsmp_delivery_event_log" 
 , " WHERE DATE(log_time) BETWEEN"
 , " (SELECT DATE_SUB(from_date, INTERVAL max_commission_days DAY)"
 , " FROM tmp_date_range f1"
 , " CROSS JOIN ("
 , " SELECT MAX(commission_days) as max_commission_days"
 , " FROM tmp_delivery_event"
 , " ) f2"
 , " )" 
 , " AND"   
 , " (SELECT to_date FROM tmp_date_range)"
 , " AND delivery_event_id IN"
 , " (SELECT delivery_event_id FROM tmp_delivery_event)"
);
PREPARE vsmp_sql FROM @stmt3;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;

SET @stmt4 = CONCAT(
 "DELETE FROM ", table_prefix, "vsmp_commission"
 , " WHERE conversion_id IN ("
 , " SELECT conversion_id"
 , " FROM tmp_conversion_log"
 , " )"
);
PREPARE vsmp_sql FROM @stmt4;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


SET @stmt5 = CONCAT(
 "INSERT INTO ", table_prefix, "vsmp_commission"
 , " (conversion_id, conversion_date, conversion_val, delivery_event_id, commission_pct, commission)"
 , " SELECT DISTINCT a.conversion_id, DATE(a.log_time), a.conversion_val, b.delivery_event_id, c.commission_pct,"
 , " a.conversion_val * c.commission_pct / 100"
 , " FROM tmp_conversion_log a"
 , " JOiN tmp_delivery_event_log b on b.vsmp_visitor_id = a.vsmp_visitor_id"
 , " JOIN tmp_delivery_event c on c.delivery_event_Id = b.delivery_event_id"
 , " WHERE b.log_time BETWEEN DATE_SUB(a.log_time, INTERVAL c.commission_days DAY) AND a.log_time"
);
PREPARE vsmp_sql FROM @stmt5;
EXECUTE vsmp_sql;
DEALLOCATE PREPARE vsmp_sql;


DROP TABLE IF EXISTS tmp_date_range;
DROP TABLE IF EXISTS tmp_conversion_log;
DROP TABLE IF EXISTS tmp_delivery_event;
DROP TABLE IF EXISTS tmp_delivery_event_log;

END
</query>