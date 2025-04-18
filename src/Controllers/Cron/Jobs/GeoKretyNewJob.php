<?php

use okapi\Facade;
use src\Controllers\Cron\Jobs\Job;
use src\Models\GeoKret\GeoKretyApi;
use src\Utils\Database\XDb;

/* * *************************************************************************
  ./util.sec/geokrety/geokrety.new.php
  --------------------
  date                 : 06.09.2011r
  copyright            : (C) 2011 Opencaching.pl
  author               : Kamil "Limak" Karczmarczyk
  contact              : kkarczmarczyk@gmail.com

  description          : It's the new version of geokrety.org synchronization
  for opencaching nodes. This code uses a dedicated method
  export_oc.php - see: https://geokrety.org/api.php for more
  information. The old method that is used in
  geokrety.class.php is deprecated.

 * ************************************************************************* */

class GeoKretyNewJob extends Job
{
    public function run()
    {
        // last synchro check
        $last_updated = XDb::xSimpleQueryValue(
            "SELECT value FROM sysconfig WHERE name='geokrety_lastupdate'",
            0
        );
        $modifiedsince = strtotime($last_updated);

        // new OC dedicated geokrety XML export
        $url = GeoKretyApi::GEOKRETY_URL . '/export_oc.php?modifiedsince=' . date('YmdHis', $modifiedsince - 1);

        $xmlString = file_get_contents($url);

        try {
            $gkxml = simplexml_load_string($xmlString);
        } catch (ErrorException $e) {
            $gkxml = false;
        }

        //    $gkxml=@simplexml_load_file($url);
        if ($gkxml === false) {
            return
                "\nGeokrety export error! Failed to load XML file [simplexml_load_file()]: " . $url
                . "\n" . $xmlString . "\n";
        }

        // read geokrety data
        foreach ($gkxml->geokret as $geokret) {
            // for safety
            $id = XDb::xEscape($geokret['id']);
            $name = XDb::xEscape($geokret->name);
            $dist = XDb::xEscape($geokret->distancetravelled);
            $state = XDb::xEscape($geokret->state ?? 0);
            $lat = XDb::xEscape($geokret->position['latitude'] ?? 0.0);
            $lon = XDb::xEscape($geokret->position['longitude'] ?? 0.0);

            // geokrety info update
            XDb::xSql(
                "INSERT INTO gk_item (`id`, `name`, `distancetravelled`, `latitude`, `longitude`, `stateid`)
                VALUES ('" . $id . "', '" . $name . "', '" . $dist . "', '" . $lat . "', '" . $lon . "','" . $state . "')
                ON DUPLICATE KEY UPDATE `name`='" . $name . "', `distancetravelled`='" . $dist . "',
                                        `latitude`='" . $lat . "', `longitude`='" . $lon . "',
                                        `stateid`='" . $state . "'"
            );

            // Notify OKAPI. https://github.com/opencaching/okapi/issues/179
            $rs = XDb::xSql(
                "SELECT distinct wp FROM gk_item_waypoint
                WHERE id='" . XDb::xEscape($id) . "'"
            );
            $cache_codes = [];

            while ($row = XDb::xFetchArray($rs)) {
                $cache_codes[] = $row[0];
            }
            Facade::schedule_geocache_check($cache_codes);

            // waypoints update
            XDb::xSql('DELETE FROM gk_item_waypoint WHERE id= ?', $id);

            foreach ($geokret->waypoints as $waypoint) {
                $wp = XDb::xEscape($waypoint->waypoint);

                if ($wp != '') {
                    XDb::xSql(
                        "INSERT INTO gk_item_waypoint (id, wp)
                        VALUES ('" . $id . "', '" . $wp . "')
                        ON DUPLICATE KEY UPDATE wp='" . $wp . "'"
                    );
                }
            }
        }

        // cleaning...

        // Notify OKAPI. https://github.com/opencaching/okapi/issues/179
        $rs = XDb::xSql('SELECT distinct wp FROM gk_item_waypoint WHERE id NOT IN (SELECT id FROM gk_item)');
        $cache_codes = [];

        while ($row = XDb::xFetchArray($rs)) {
            $cache_codes[] = $row[0];
        }

        Facade::schedule_geocache_check($cache_codes);

        XDb::xSql('DELETE FROM gk_item_waypoint WHERE id NOT IN (SELECT id FROM gk_item)');

        // last synchro update
        XDb::xSql(
            "UPDATE sysconfig SET value = '" . XDb::xEscape($gkxml['date']) . "'
            WHERE name='geokrety_lastupdate'"
        );
    }
}
