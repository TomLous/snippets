<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     02/12/14 10:29
 */

include_once("config.inc.php5");
include_once("AGOLHandler.php5");


$mysqli = connectToDB();

// Database + table settings
$kpi_actual_table = "gis_kpi_actual";
$kpi_target_table = "gis_kpi_target";
$kpi_pos_table = "gis_kpi_pos";
$kpi_baseline_table = "gis_kpi_baseline";

// Time setting
date_default_timezone_set("Europe/Amsterdam");


/*function updateKPI(&$mysqli, $dbase, $source_dbase, $kpi_actual_table)  {
	$public_dbase 		= _GetPublic($mysqli, $dbase);
	$kpi_fields			= array
							(
							1	=> "kpi_001_pos_total",					// KPI 001 - Total POS Amsterdam
							2	=> "kpi_002_pos_new",					// KPI 002 - New POS
							3	=> "kpi_003_pros_visited",				// KPI 003 - Prospects Visited
							4	=> "kpi_004_pros_progress",			// KPI 004 - Prospects in Progress
							5	=> "kpi_005_store_measurements",	// KPI 005 - Perfect Store Measurements
							6	=> "kpi_006_store_score_7_8",			// KPI 006 - Perfect Store Stores (7-8 scores)
							7   => "kpi_007_turnover",						// KPI 007 - Incremental Turnover
							8   => "kpi_008_turnover_index",				// KPI 008 - Incremental Turnover Index
							9   => "kpi_009_ola",							// KPI 009 - New Ola Impulse
							10	=> "kpi_010_benjerrys",					// KPI 010 - New Ben & Jerry's (dedicated)
							11	=> "kpi_011_cornetto",						// KPI 011 - My Cornetto
							12	=> "kpi_012_cartedor",					// KPI 012 - New Carte d'or
							13	=> "kpi_013_swirls",						// KPI 013 - New Swirls
							14	=> "kpi_014_pos_activiated",				// KPI 014 - Activated POS
							15	=> "kpi_015_cabinets",					// KPI 015 - New Cabinets placed
							);
	$kpi_date			= date('Y-m-d');
	$kpi_startdate		= (date('Y')-1) . '-11-01 00:00:00';
	$cid					= 1183;
	$current_year		= date('Y');
	$period				= 'P' . date('y');
	$period_1Y			= 'P' . (date('y')-1);
	$period_start		= $period . '01';
	$period_start_1Y	= $period_1Y . '01';
	$where_city			= "LIKE 'Amsterdam%'";
	$where_city_not	= "NOT LIKE '%Duivendrecht%'";

	$queries = array(
	// Delete current day and create new record
	"DELETE FROM `$dbase`.`$kpi_actual_table` WHERE `kpi_date` = '$kpi_date';",
	"INSERT INTO `$dbase`.`$kpi_actual_table` (`kpi_date`) VALUES ('$kpi_date');",

	// Turnover date
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IF(MAX(periode)>='P1501',MAX(periode),NULL) AS date_turnover FROM `$source_dbase`.`statistieken_extrapolatie`
		) AS kpi_date_turnover
	SET
		`$dbase`.`$kpi_actual_table`.`kpi_date_turnover` = date_turnover
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 001
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_001
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			((((((t2.`impbrand1_c` IN (14)) OR
			(t2.`prembrand1_c` IN (2) AND
			(t2.`impbrand1_c` IN (17) OR
			t2.`impbrand1_c` IS NULL))) OR
			(t2.`scoopbrand1_c` IN (3,4,14))) OR
			(t2.`softhsbrand1_c` IN (3,4,2))) OR
			(t2.`softmcbrand1_c` IN (2)))) AND
			t2.`status` = 1) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[1]` = kpi_001
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 002
	"UPDATE
		$dbase.$kpi_actual_table,
		(
	SELECT IFNULL(COUNT(*),0) AS kpi_002
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			((((((t2.`impbrand1_c` IN (14)) OR
			(t2.`prembrand1_c` IN (2) AND
			(t2.`impbrand1_c` IN (17) OR
			t2.`impbrand1_c` IS NULL))) OR
			(t2.`scoopbrand1_c` IN (3,4,14))) OR
			(t2.`softhsbrand1_c` IN (3,4,2))) OR
			(t2.`softmcbrand1_c` IN (2)))) AND
			t2.`pos_status` IN (1) AND
			t2.`datum` > '$kpi_startdate') AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[2]` = kpi_002
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 003
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_003
		FROM
			`$source_dbase`.`afspraken` t0,
			`$public_dbase`.`eindverbruiker` t1 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t2 ON t1.`klantnr` = t2.`klantnr`,
			`$source_dbase`.`campaign_det` t3
		WHERE
			(t0.`volgendedatum` >= '$kpi_startdate' AND
			t0.`afgehandeld` = 'ja' AND
			t0.`soortcode` IN ('Bezoek') AND
			t0.`rcode` IN ('Acquisitie') AND
			t1.`b_woonplaats` $where_city AND
			t1.`b_woonplaats` $where_city_not AND
			t3.`cid` IN ($cid)) AND
			t0.`klantnr` = t1.`klantnr` AND
			t0.`contactnr` = t3.`contactnr` AND
			t0.`sasu_contact_third` = t3.`contactnr` AND
			t3.`klantnr` = t1.`klantnr`
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[3]` = kpi_003
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 004
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_004
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2,
			`$source_dbase`.`campaign_det` t3
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			((((((((t2.`impbrand1_c` NOT IN (14) AND
			t2.`imp_ib` = '1') OR
			(t2.`prembrand1_c` NOT IN (2) AND
			t2.`prem_ib` = '1')) OR
			(t2.`scoopbrand1_c` NOT IN (3,4,14) AND
			t2.`scoop_ib` = '1')) OR
			(t2.`icofbrand1_c` NOT IN (1) AND
			t2.`icof_ib` = '1')) OR
			(t2.`softhsbrand1_c` NOT IN (3,4,2) AND
			t2.`sofths_ib` = '1')) OR
			(t2.`softmcbrand1_c` NOT IN (2) AND
			t2.`softmc_ib` = '1')) OR
			(t2.`softcsbrand1_c` NOT IN (2) AND
			t2.`softcs_ib` = '1'))) AND
			t2.`datum` > '$kpi_startdate' AND
			t2.`status` = 1 AND
			t3.`cid` IN ($cid)) AND
			t2.`klantnr` = t0.`klantnr` AND
			t3.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[4]` = kpi_004
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 005
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_005
		FROM
			`$public_dbase`.`eindverbruiker` t0,
			`$source_dbase`.`contactbedrijf` t1
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t1.`perfectstore` IN (1,0)) AND
			t1.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[5]` = kpi_005
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",


	// KPI 006
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_006
		FROM
			`$public_dbase`.`eindverbruiker` t0,
			`$source_dbase`.`contactbedrijf` t1
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t1.`perfectstore` IN (1)) AND
			t1.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[6]` = kpi_006
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 007
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT
			CASE
				WHEN ((IFNULL(turnover_current_year_sh,0)+IFNULL(turnover_current_year_ex,0))-(IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0)))>0
				THEN	CONCAT('+',CONCAT(ROUND(ROUND(((IFNULL(turnover_current_year_sh,0)+IFNULL(turnover_current_year_ex,0))-(IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0))), -3)/1000),'K'))
				WHEN ((IFNULL(turnover_current_year_sh,0)+IFNULL(turnover_current_year_ex,0))-(IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0)))<0
				THEN CONCAT(CONCAT(ROUND(ROUND(((IFNULL(turnover_current_year_sh,0)+IFNULL(turnover_current_year_ex,0))-(IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0))), -3)/1000),'K'))
				ELSE ''
			END AS kpi_007
		FROM
			(
			SELECT SUM(t1.`field_04`) AS turnover_current_year_sh
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` IS NULL) AND
				t1.`klantnr` = t0.`klantnr` AND
				t1.`gcode` = t2.`grossier_ijs` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS current_year_sh,

			(
			SELECT SUM(t1.`field_04`) AS turnover_previous_year_sh
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period_1Y', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start_1Y' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` IS NULL) AND
				t1.`klantnr` = t0.`klantnr` AND
				t1.`gcode` = t2.`grossier_ijs` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS previous_year_sh,

			(
			SELECT SUM(t1.`turnover`) AS turnover_current_year_ex
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken_extrapolatie` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` = 1) AND
				t1.`klantnr` = t0.`klantnr` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS current_year_ex,

			(
			SELECT SUM(t1.`turnover`) AS turnover_previous_year_ex
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken_extrapolatie` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period_1Y', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start_1Y' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` = 1) AND
				t1.`klantnr` = t0.`klantnr` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS previous_year_ex
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[7]` = kpi_007
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 008
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT
			CASE  WHEN (IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0)) = 0 THEN '' ELSE
				CONCAT(
						ROUND(
							(
								(
									(
										(IFNULL(turnover_current_year_sh,0)+IFNULL(turnover_current_year_ex,0)) -
										(IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0))
									)
									/
									(IFNULL(turnover_previous_year_sh,0)+IFNULL(turnover_previous_year_ex,0))
								)*100
							)+100
						,1)
				,'%')
			END AS kpi_008
		FROM
			(
			SELECT SUM(t1.`field_04`) AS turnover_current_year_sh
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` IS NULL) AND
				t1.`klantnr` = t0.`klantnr` AND
				t1.`gcode` = t2.`grossier_ijs` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS current_year_sh,

			(
			SELECT SUM(t1.`field_04`) AS turnover_previous_year_sh
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period_1Y', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start_1Y' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` IS NULL) AND
				t1.`klantnr` = t0.`klantnr` AND
				t1.`gcode` = t2.`grossier_ijs` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS previous_year_sh,

			(
			SELECT SUM(t1.`turnover`) AS turnover_current_year_ex
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken_extrapolatie` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` = 1) AND
				t1.`klantnr` = t0.`klantnr` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS current_year_ex,

			(
			SELECT SUM(t1.`turnover`) AS turnover_previous_year_ex
			FROM
				`$public_dbase`.`eindverbruiker` t0,
				`$source_dbase`.`statistieken_extrapolatie` t1,
				`$source_dbase`.`contactbedrijf` t2,
				(
					SELECT
						CONCAT('$period_1Y', IF(MAX(periode) <> '', RIGHT(MAX(periode),2), '00')) AS max_periode_extrapolatie
					FROM
						`$source_dbase`.`statistieken_extrapolatie`
					WHERE
						`year` = $current_year
				) AS t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`periode` BETWEEN '$period_start_1Y' AND t3.max_periode_extrapolatie AND
				t2.`toon_extrapol` = 1) AND
				t1.`klantnr` = t0.`klantnr` AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
			) AS previous_year_ex
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[8]` = kpi_008
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 009
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_009
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t2.`datum` > '$kpi_startdate' AND
			t2.`impbrand1_c` IN (14) AND
			t2.`impaction` IN (1)) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[9]` = kpi_009
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 010
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_010
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t2.`datum` > '$kpi_startdate' AND
			t2.`prembrand1_c` IN (2) AND
			t2.`premaction` IN (1) AND
			(t2.`impbrand1_c` IN (17) OR
			t2.`impbrand1_c` IS NULL)) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[10]` = kpi_010
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 011
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_011
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t2.`datum` > '$kpi_startdate' AND
			t2.`softmcbrand1_c` IN (2) AND
			t2.`softmcaction` IN (1)) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[11]` = kpi_011
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 012
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_012
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t2.`datum` > '$kpi_startdate' AND
			t2.`scoopbrand1_c` IN (3,4,14) AND
			t2.`scoopaction` IN (1)) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[12]` = kpi_012
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 013
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_013
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`od_form` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t2.`datum` > '$kpi_startdate' AND
			t2.`softhsbrand1_c` IN (3,4,2) AND
			t2.`softhsaction` IN (1)) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[13]` = kpi_013
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 014
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_014
		FROM
			`$public_dbase`.`eindverbruiker` t0,
			`$source_dbase`.`contactbedrijf` t1
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			t1.`locijsact` = '1') AND
			t1.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[14]` = kpi_014
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	// KPI 015
	"UPDATE
		$dbase.$kpi_actual_table,
		(
		SELECT IFNULL(COUNT(*),0) AS kpi_015
		FROM
			`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
			`$source_dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
			`$source_dbase`.`eq_request` t2
		WHERE
			((t0.`b_woonplaats` $where_city AND
			t0.`b_woonplaats` $where_city_not AND
			(t2.`datum` >= '2015-01-01 00:00:00' AND
			t2.`request_type` IN (2,1) AND
			t2.`req_status` IN ('confirmed_by_ldv') AND
			t2.`brand_id` NOT IN (401,611,700,701,612,402,408,410,297,295,290,300,197,607,204,294,203,202,299,195,288,201,205,206,207,208,209,210,293,292,291,298,289,606,617,999,114,116,609,403,405))) AND
			t2.`klantnr` = t0.`klantnr`) AND
			t0.status > 0
		) AS kpi
	SET
		`$dbase`.`$kpi_actual_table`.`$kpi_fields[15]` = kpi_015
	WHERE
		`$dbase`.`$kpi_actual_table`.`kpi_date` = '$kpi_date';
	",

	);

	// Execute all queries
	foreach ($queries as $q) {
		$mysqli->Query($q);
	}

	echo "Table $kpi_actual_table updated @ " .  date("Y-m-d h:i:s") . ".\n";
}


function publishTargetToAGOL(&$mysqli, $dbase, $kpi_target_table){
	// UTF 8
	$mysqli->Query("SET NAMES 'utf8' COLLATE 'utf8_swedish_ci'");

	// Init AGOL Handler
	$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_TARGET);

	$q = <<<SQL
				SELECT 
				DATE_FORMAT(kpi_date, '%m/%d/%Y') AS kpi_date,
				kpi_001_pos_total,
				kpi_002_pos_new,
				kpi_003_pros_visited,
				kpi_004_pros_progress,
				kpi_005_store_measurements,
				kpi_006_store_score_7_8,
				kpi_007_turnover,
				kpi_008_turnover_index,
				kpi_009_ola,
				kpi_010_benjerrys,
				kpi_011_cornetto,
				kpi_012_cartedor,
				kpi_013_swirls,
				kpi_014_pos_activiated,
				kpi_015_cabinets,
				latitude,
				longitude
			FROM `$dbase`.`$kpi_target_table`
			LIMIT 1
SQL;

	// Execute query
	if(!$mysqli->Query($q))
		return false;	
	
	// Geo settings
	$latitudeField = 'latitude';
	$longitudeField = 'longitude';
	$wkid = 4326; // Well known ID voor spatial reference
	
	// Remove existing GEO's
	$agolHandler->removeGEO();

	// Loop record
	while($mysqli->FetchRow($record, DB_ASSOC)) {
		$record = array_change_key_case($record);
 		$jsonData = array(
			"attributes" => $record,
			"geometry" => array(
				"x" => $record[$longitudeField],
				"y" => $record[$latitudeField],
				"spatialReference" => array("wkid" => $wkid)
			));

		$agolHandler->addPoint($jsonData);
	}
	
	echo "Target KPI published to ArcGis Online: '" . AGOL_SERVICE . "'.\n";
}


function updatePOS(&$mysqli, $dbase, $kpi_pos_table)  {
	$public_dbase 		= _GetPublic($mysqli, $dbase);
	$cid					= 1183;
	$where_city			= "LIKE 'Amsterdam%'";
	$where_city_not	= "NOT LIKE '%Duivendrecht%'";

	$queries = array(
	"TRUNCATE `$dbase`.`$kpi_pos_table`;",
	"INSERT IGNORE INTO `$dbase`.`$kpi_pos_table` (`klantnr`, `company_name`, `address`, `zip`, `city`, `phone`, `segment`, `activity`, `key_account`, `brand`, `commercial_status`, `latitude`, `longitude`)
		SELECT
			s.`klantnr` AS klantnr,
			e.naam AS company_name,
			CONCAT(e.`b_adres`, ' ', e.`b_huisnr`) AS address,
			e.`b_postcode` AS zip,
			e.`b_woonplaats` AS city,
			e.`telefoon` AS phone,
			IFNULL(c.`unilever_segment`,'Unknown') AS segment,
			k.`waarde_english` AS activity,
			IF(s.`key_account` = 1, 'Yes', 'No') AS key_account,
			CONCAT_WS(',', 
				IF(imp.`brand_dutch`= '-- geen --', NULL, imp.`brand_dutch`),
				IF(pre.`brand_dutch`= '-- geen --', NULL, pre.`brand_dutch`), 
				IF(sco.`brand_dutch`= '-- geen --', NULL, sco.`brand_dutch`), 
				IF(hs.`brand_dutch` = '-- geen --', NULL, hs.`brand_dutch`), 
				IF(mc.`brand_dutch` = '-- geen --', NULL, mc.`brand_dutch`)
			) AS brand,
			CASE
				WHEN sort = 1 THEN 'Customer - Existing'
				WHEN sort = 2 THEN 'Customer - New'
				WHEN sort = 3 THEN 'Prospect - Visited'
				WHEN sort = 4 THEN 'Prospect - In process'
				WHEN sort = 5 THEN 'Prospect - To do'												
			END AS commercial_status,
			e.`b_latitude` AS latitude,
			e.`b_longitude` AS longitude
		FROM
			(
			SELECT
				t0.`klantnr` AS klantnr,
				t0.`hoofdtype` AS hoofdtype,
				t1.`key_account` AS key_account,
				t2.`impbrand1_c`,
				t2.`prembrand1_c`,
				t2.`scoopbrand1_c`,
				t2.`softhsbrand1_c`,
				t2.`softmcbrand1_c`,
				'Customer - Existing' AS 'comm_status',
				1 AS sort
			FROM
				`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
				`$dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
				`$dbase`.`od_form` t2
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				((((((t2.`impbrand1_c` IN (14)) OR
				(t2.`prembrand1_c` IN (2) AND
				(t2.`impbrand1_c` IN (17) OR
				t2.`impbrand1_c` IS NULL))) OR
				(t2.`scoopbrand1_c` IN (3,4,14))) OR
				(t2.`softhsbrand1_c` IN (3,4,2))) OR
				(t2.`softmcbrand1_c` IN (2)))) AND
				t2.`status` = 1) AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0
				
			UNION
			SELECT 
				t0.`klantnr` AS klantnr,
				t0.`hoofdtype` AS hoofdtype,
				t1.`key_account` AS key_account,
				t2.`impbrand1_c`,
				t2.`prembrand1_c`,
				t2.`scoopbrand1_c`,
				t2.`softhsbrand1_c`,
				t2.`softmcbrand1_c`,
				'Customer - New' AS 'comm_status',
				2 AS sort
			FROM
				`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
				`$dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
				`$dbase`.`od_form` t2
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				((((((t2.`impbrand1_c` IN (14)) OR
				(t2.`prembrand1_c` IN (2) AND
				(t2.`impbrand1_c` IN (17) OR
				t2.`impbrand1_c` IS NULL))) OR
				(t2.`scoopbrand1_c` IN (3,4,14))) OR
				(t2.`softhsbrand1_c` IN (3,4,2))) OR
				(t2.`softmcbrand1_c` IN (2)))) AND
				t2.`pos_status` IN (1) AND
				t2.`datum` > '2014-11-01 00:00:00') AND
				t2.`klantnr` = t0.`klantnr`) AND
				t0.status > 0

			UNION

			SELECT
				t0.`klantnr` AS klantnr,
				t0.`hoofdtype` AS hoofdtype,
				t2.`key_account` AS key_account,
				t3.`impbrand1_c`,
				t3.`prembrand1_c`,
				t3.`scoopbrand1_c`,
				t3.`softhsbrand1_c`,
				t3.`softmcbrand1_c`,
				'Prospect - Visited' AS 'comm_status',
				3 AS sort
			FROM
				`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN 
				`$dbase`.`contactbedrijf` t2 ON t0.`klantnr` = t2.`klantnr` LEFT OUTER JOIN 
				`$dbase`.`od_form` t3 ON t2.`grossier_ijs` = t3.`softothlead_ws` AND
				t0.`klantnr` = t3.`klantnr` AND
				((((((t3.`impbrand1_c` IN (14)) OR
				(t3.`prembrand1_c` IN (2) AND
				(t3.`impbrand1_c` IN (17) OR
				t3.`impbrand1_c` IS NULL))) OR
				(t3.`scoopbrand1_c` IN (3,4,14))) OR
				(t3.`softhsbrand1_c` IN (3,4,2))) OR
				(t3.`softmcbrand1_c` IN (2)))) AND
				t3.`status` = 1, `$dbase`.`afspraken` t4,
				`$dbase`.`campaign_det` t1
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`cid` IN ($cid) AND
				t4.`volgendedatum` >= '2014-11-01' AND
				t4.`rcode` IN ('Acquisitie') AND
				t4.`soortcode` IN ('Bezoek') AND
				t4.`afgehandeld` = 'ja') AND
				t1.`klantnr` = t0.`klantnr` AND
				t1.`contactnr` = t4.`contactnr` AND
				t1.`contactnr` = t4.`sasu_contact_third` AND
				t4.`klantnr` = t0.`klantnr`) AND
				t0.status > 0

			UNION			

			SELECT
				t0.`klantnr` AS klantnr,
				t0.`hoofdtype` AS hoofdtype,
				t1.`key_account` AS key_account,
				t2.`impbrand1_c`,
				t2.`prembrand1_c`,
				t2.`scoopbrand1_c`,
				t2.`softhsbrand1_c`,
				t2.`softmcbrand1_c`,
				'Prospect - In process' AS 'comm_status',
				4 AS sort
			FROM
				`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
				`$dbase`.`contactbedrijf` t1 ON t0.`klantnr` = t1.`klantnr`,
				`$dbase`.`od_form` t2,
				`$dbase`.`campaign_det` t3
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				((((((((t2.`impbrand1_c` NOT IN (14) AND
				t2.`imp_ib` = '1') OR
				(t2.`prembrand1_c` NOT IN (2) AND
				t2.`prem_ib` = '1')) OR
				(t2.`scoopbrand1_c` NOT IN (3,4,14) AND
				t2.`scoop_ib` = '1')) OR
				(t2.`icofbrand1_c` NOT IN (1) AND
				t2.`icof_ib` = '1')) OR
				(t2.`softhsbrand1_c` NOT IN (3,4,2) AND
				t2.`sofths_ib` = '1')) OR
				(t2.`softmcbrand1_c` NOT IN (2) AND
				t2.`softmc_ib` = '1')) OR
				(t2.`softcsbrand1_c` NOT IN (2) AND
				t2.`softcs_ib` = '1'))) AND
				t2.`datum` > '2014-11-01 00:00:00' AND
				t2.`status` = 1 AND
				t3.`cid` IN ($cid)) AND
				t2.`klantnr` = t0.`klantnr` AND
				t3.`klantnr` = t0.`klantnr`) AND
				t0.status > 0

			UNION

			SELECT
				t0.`klantnr` AS klantnr,
				t0.`hoofdtype` AS hoofdtype,
				t2.`key_account` AS key_account,
				t3.`impbrand1_c`,
				t3.`prembrand1_c`,
				t3.`scoopbrand1_c`,
				t3.`softhsbrand1_c`,
				t3.`softmcbrand1_c`,
				'Prospect - To do' AS 'comm_status',
				5 AS sort			
			FROM
				`$public_dbase`.`eindverbruiker` t0 LEFT OUTER JOIN
				`$dbase`.`contactbedrijf` t2 ON t0.`klantnr` = t2.`klantnr` LEFT OUTER JOIN
				`$dbase`.`od_form` t3 ON t2.`grossier_ijs` = t3.`softothlead_ws` AND
				t0.`klantnr` = t3.`klantnr` AND
				((((((t3.`impbrand1_c` IN (14)) OR
				(t3.`prembrand1_c` IN (2) AND
				(t3.`impbrand1_c` IN (17) OR
				t3.`impbrand1_c` IS NULL))) OR
				(t3.`scoopbrand1_c` IN (3,4,14))) OR
				(t3.`softhsbrand1_c` IN (3,4,2))) OR
				(t3.`softmcbrand1_c` IN (2)))) AND
				t3.`status` = 1,
				`$dbase`.`campaign_det` t1
			WHERE
				((t0.`b_woonplaats` $where_city AND
				t0.`b_woonplaats` $where_city_not AND
				t1.`cid` IN ($cid)) AND
				t1.`klantnr` = t0.`klantnr`) AND
				t0.status > 0	
			) AS s INNER JOIN 
			`$public_dbase`.`eindverbruiker` e ON s.`klantnr` = e.`klantnr` LEFT JOIN
			`$dbase`.`ochannel_2015` c ON s.`hoofdtype` = c.`hoofdtype` LEFT JOIN
			`$public_dbase`.`kenmerken_taal` k ON k.`code` = CONCAT(s.`hoofdtype`,',') AND k.`tabel` = 'type' LEFT JOIN
			`$dbase`.`od_impbrand` imp ON imp.id = s.impbrand1_c LEFT JOIN
			`$dbase`.`od_prembrand` pre ON pre.id = s.prembrand1_c LEFT JOIN
			`$dbase`.`od_scoopbrand` sco ON sco.id = s.scoopbrand1_c LEFT JOIN
			`$dbase`.`od_softhsbrand` hs ON hs.id = s.softhsbrand1_c LEFT JOIN
			`$dbase`.`od_softmcbrand` mc ON mc.id = s.softmcbrand1_c
		WHERE
			e.`b_latitude` > 0 AND
			e.`b_latitude` IS NOT NULL AND
			e.`b_longitude` > 0 AND
			e.`b_longitude` IS NOT NULL
		GROUP BY klantnr HAVING MIN(sort)	
		ORDER BY zip, sort",
	);

	// Execute all queries
	foreach ($queries as $q) {
		$mysqli->Query($q);
	}

	echo "Table $kpi_pos_table updated @ " .  date("Y-m-d h:i:s") . ".\n";
}


function publishActualToAGOL(&$mysqli, $dbase, $kpi_actual_table){
	// UTF 8
	$mysqli->Query("SET NAMES 'utf8' COLLATE 'utf8_swedish_ci'");

	// Init AGOL Handler
	$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_ACTUAL);

	$q = <<<SQL
				SELECT 
				DATE_FORMAT(kpi_date, '%m/%d/%Y') AS kpi_date,
				DATE_FORMAT(kpi_date_turnover, '%m/%d/%Y') AS kpi_date_turnover,
				kpi_001_pos_total,
				kpi_002_pos_new,
				kpi_003_pros_visited,
				kpi_004_pros_progress,
				kpi_005_store_measurements,
				kpi_006_store_score_7_8,
				kpi_007_turnover,
				kpi_008_turnover_index,
				kpi_009_ola,
				kpi_010_benjerrys,
				kpi_011_cornetto,
				kpi_012_cartedor,
				kpi_013_swirls,
				kpi_014_pos_activiated,
				kpi_015_cabinets,
				latitude,
				longitude
			FROM `$dbase`.`$kpi_actual_table` 
			ORDER BY `kpi_date` DESC 
			LIMIT 1
SQL;

	// Execute query
	if(!$mysqli->Query($q))
		return false;	
	
	// Geo settings
	$latitudeField = 'latitude';
	$longitudeField = 'longitude';
	$wkid = 4326; // Well known ID voor spatial reference
	
	// Remove existing GEO's
	$agolHandler->removeGEO();

	// Loop record
	while($mysqli->FetchRow($record, DB_ASSOC)) {
		$record = array_change_key_case($record);
 		$jsonData = array(
			"attributes" => $record,
			"geometry" => array(
				"x" => $record[$longitudeField],
				"y" => $record[$latitudeField],
				"spatialReference" => array("wkid" => $wkid)
			));

		$agolHandler->addPoint($jsonData);
	}
	
	echo "Actual KPI published to ArcGis Online: '" . AGOL_SERVICE . "' @ " .  date("Y-m-d h:i:s") . ".\n";
}*/


function publishPOSToAGOL(&$mysqli, $dbase, $kpi_pos_table){
	// UTF 8
	$mysqli->query("SET NAMES 'utf8' COLLATE 'utf8_swedish_ci'");

	// Init AGOL Handler
	$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_POS);
    $agolHandler->setDebug($_ENV['DEBUG']);

	$q = <<<SQL
				SELECT 
					`klantnr` AS 'id',
					`company_name`,
					`address`,
					`zip`,
					`city`,
					`phone`,
					`segment`,
					`activity`,
					`key_account`,
					`brand`,
					`commercial_status`,
					`latitude`,
					`longitude`
				FROM `$kpi_pos_table`
				LIMIT 10;
SQL;

    $resultSet = $mysqli->query($q);
	
	// Geo settings
	$latitudeField = 'latitude';
	$longitudeField = 'longitude';
	$wkid = 4326; // Well known ID voor spatial reference
	
	// Remove existing GEO's
	$agolHandler->removeGEO();

	// Loop record
    while ($record = $resultSet->fetch_array(MYSQLI_ASSOC)) {

        print_r($record);
		$record = array_change_key_case($record);
 		$jsonData = array(
			"attributes" => $record,
			"geometry" => array(
				"x" => $record[$longitudeField],
				"y" => $record[$latitudeField],
				"spatialReference" => array("wkid" => $wkid)
			));

		$agolHandler->addPoint($jsonData);
	}
	
	echo "POS published to ArcGis Online: '" . AGOL_SERVICE . "' @ " .  date("Y-m-d h:i:s") . ".\n";
}


/*function publishBaselineToAGOL(&$mysqli, $dbase, $kpi_baseline_table){
	// UTF 8
	$mysqli->Query("SET NAMES 'utf8' COLLATE 'utf8_swedish_ci'");

	// Init AGOL Handler
	$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_BASELINE);

	$q = <<<SQL
				SELECT 
					`klantnr` AS 'id',
					`company_name`,
					`address`,
					`zip`,
					`city`,
					`phone`,
					`segment`,
					`activity`,
					`key_account`,
					`brand`,
					`commercial_status`,
					`latitude`,
					`longitude`
				FROM `$dbase`.`$kpi_baseline_table`
				ORDER BY FIELD(`commercial_status`, 'Customer - Existing', 'Customer - New', 'Prospect - Visited', 'Prospect - In process', 'Prospect - To do') DESC, `zip`;
SQL;

	// Execute query
	if(!$mysqli->Query($q))
		return false;	
	
	// Geo settings
	$latitudeField = 'latitude';
	$longitudeField = 'longitude';
	$wkid = 4326; // Well known ID voor spatial reference
	
	// Remove existing GEO's
	$agolHandler->removeGEO();

	// Loop record
	while($mysqli->FetchRow($record, DB_ASSOC)) {
		$record = array_change_key_case($record);
 		$jsonData = array(
			"attributes" => $record,
			"geometry" => array(
				"x" => $record[$longitudeField],
				"y" => $record[$latitudeField],
				"spatialReference" => array("wkid" => $wkid)
			));

		$agolHandler->addPoint($jsonData);
	}
	
	echo "Baseline published to ArcGis Online: '" . AGOL_SERVICE . "' @ " .  date("Y-m-d h:i:s") . ".\n";
}*/

// KPI 
//	updateKPI($mysqli, $dbase, $source_dbase, $kpi_actual_table);
//	publishActualToAGOL($mysqli, $dbase, $kpi_actual_table);

// POS
//	updatePOS($mysqli, $dbase, $kpi_pos_table) ;
	publishPOSToAGOL($mysqli, $dbase, $kpi_pos_table) ;
	
// Optional
//	publishTargetToAGOL($mysqli, $dbase, $kpi_target_table);
//	publishBaselineToAGOL($mysqli, $dbase, $kpi_baseline_table) ;