<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginLocationInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Core;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Repository\Model\User;
use CpChart;
use CpChart\Data;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Plugin;

class Graph
{
    public function __construct()
    {
        if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../../vendor/szymach/c-pchart/src/Chart/')) {
            return true;
        }
        debug_event(self::class, 'Access denied, statistical graph disabled.', 1);

        return false;
    }

    /**
     * @param string $field
     * @param string $zoom
     */
    protected function get_sql_date_format($field, $zoom): string
    {
        $dateformat = match ($zoom) {
            'hour' => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-%d %H:00:00')",
            'year' => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-01-01')",
            'month' => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-01')",
            default => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-%d')",
        };

        return "UNIX_TIMESTAMP(" . $dateformat . ")";
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @return string
     */
    protected function get_user_sql_where(
        $user_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null
    ): string {
        $start_date = (int)($start_date);
        $end_date   = (int)($end_date);
        if ($end_date == 0) {
            $end_date = time();
        }
        if ($start_date == 0) {
            $start_date = $end_date - 864000;
        }

        $sql = "WHERE `object_count`.`date` >= " . $start_date . " AND `object_count`.`date` <= " . $end_date;
        if ($user_id > 0) {
            $sql .= " AND `object_count`.`user` = " . $user_id;
        }

        if (InterfaceImplementationChecker::is_library_item((string)$object_type)) {
            $sql .= " AND `object_count`.`object_type` = '" . $object_type . "'";
            if ($object_id > 0) {
                $sql .= " AND `object_count`.`object_id` = '" . $object_id . "'";
            }
        }

        return $sql;
    }

    /**
     * @param string $object_type
     * @param int $object_id
     * @param int $catalog_id
     * @param int $start_date
     * @param int $end_date
     * @return string
     */
    protected function get_catalog_sql_where(
        $object_type = 'song',
        $object_id = 0,
        $catalog_id = 0,
        $start_date = null,
        $end_date = null
    ): string {
        $start_date = (int)($start_date);
        $end_date   = (int)($end_date);
        if ($end_date == 0) {
            $end_date = time();
        }
        if ($start_date == 0) {
            $start_date = $end_date - 864000;
        }

        $sql = "WHERE `" . $object_type . "`.`addition_time` >= " . $start_date . " AND `" . $object_type . "`.`addition_time` <= " . $end_date;
        if ($catalog_id > 0) {
            $sql .= " AND `" . $object_type . "`.`catalog` = " . $catalog_id;
        }

        $object_id = (int)$object_id;
        if ($object_id > 0) {
            $sql .= " AND `" . $object_type . "`.`id` = '" . $object_id . "'";
        }

        return $sql;
    }

    /**
     * @param string $fct
     * @param int $id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_all_type_pts(
        $fct,
        $id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        $type = $object_type;
        if ($object_type === null) {
            $type = 'song';
        }

        $song_values  = $this->$fct($id, $type, $object_id, $start_date, $end_date, $zoom);
        $video_values = [];
        if ($object_type === null && AmpConfig::get('allow_video')) {
            $video_values = $this->$fct($id, 'video', $object_id, $start_date, $end_date, $zoom);
        }

        $values = $song_values;
        foreach ($video_values as $date => $value) {
            if (array_key_exists($date, $values)) {
                $values[$date] += $value;
            } else {
                $values[$date] = $value;
            }
        }
        ksort($values, SORT_NUMERIC);

        return $values;
    }

    /**
     * @param string $fct
     * @param Data $MyData
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @param bool $show_total
     * @return array
     */
    protected function get_all_pts(
        $fct,
        Data $MyData,
        $user_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day',
        $show_total = true
    ): array {
        $values = $this->get_all_type_pts($fct, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
        foreach ($values as $date => $value) {
            if ($show_total) {
                $MyData->addPoints($value, "Total");
            }
            $MyData->addPoints($date, "TimeStamp");
        }

        return $values;
    }

    /**
     * get_user_all_pts
     * @param string $fct
     * @param Data $MyData
     * @param int $user_id
     * @param string|null $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     */
    protected function get_user_all_pts(
        $fct,
        Data $MyData,
        $user_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): void {
        $userRepository = $this->getUserRepository();

        $values = $this->get_all_pts($fct, $MyData, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
        $ustats = $userRepository->getStatistics();
        // Only display other users if the graph is not for a specific user and user count is small
        if ($user_id < 1 && $ustats['users'] < 10) {
            $userArray = $userRepository->getValidArray();
            foreach ($userArray as $userId => $userName) {
                $user_values = $this->get_all_type_pts(
                    $fct,
                    $userId,
                    $object_type,
                    $object_id,
                    $start_date,
                    $end_date,
                    $zoom
                );
                foreach ($values as $date => $value) {
                    if (array_key_exists($date, $user_values)) {
                        $value = $user_values[$date];
                    } else {
                        $value = 0;
                    }
                    $MyData->addPoints($value, $userName);
                }
            }
        }
    }

    /**
     * @param string $fct
     * @param Data $MyData
     * @param int $catalog_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     */
    protected function get_catalog_all_pts(
        $fct,
        Data $MyData,
        $catalog_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): void {
        $values = $this->get_all_pts($fct, $MyData, $catalog_id, $object_type, $object_id, $start_date, $end_date, $zoom, false);

        // Only display other users if the graph is not for a specific catalog
        if (!$catalog_id) {
            $catalogs = Catalog::get_all_catalogs();
            foreach ($catalogs as $catalog_id) {
                $catalog = Catalog::create_from_id($catalog_id);
                if ($catalog === null) {
                    break;
                }
                $catalog_values = $this->get_all_type_pts($fct, $catalog_id, $object_type, $object_id, $start_date, $end_date, $zoom);
                foreach ($values as $date => $value) {
                    if (array_key_exists($date, $catalog_values)) {
                        $value = $catalog_values[$date];
                    } else {
                        $value = 0;
                    }
                    $MyData->addPoints($value, (string)$catalog->name);
                }
            }
        }
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_user_hits_pts(
        $user_id = 0,
        $object_type = 'song',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        $dateformat = $this->get_sql_date_format("`object_count`.`date`", $zoom);
        $where      = $this->get_user_sql_where($user_id, $object_type, $object_id, $start_date, $end_date);
        $sql        = "SELECT " . $dateformat . " AS `zoom_date`, COUNT(`object_count`.`id`) AS `hits` FROM `object_count` " . $where . " GROUP BY " . $dateformat;
        $db_results = Dba::read($sql);

        $values = [];
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['hits'];
        }

        return $values;
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @param string $column
     * @return array
     */
    protected function get_user_object_count_pts(
        $user_id = 0,
        $object_type = 'song',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day',
        $column = 'size'
    ): array {
        $dateformat = $this->get_sql_date_format("`object_count`.`date`", $zoom);
        $where      = $this->get_user_sql_where($user_id, $object_type, $object_id, $start_date, $end_date);
        $sql        = "SELECT " . $dateformat . " AS `zoom_date`, SUM(`" . $object_type . "`.`" . $column . "`) AS `total` FROM `object_count` JOIN `" . $object_type . "` ON `" . $object_type . "`.`id` = `object_count`.`object_id` " . $where . " GROUP BY " . $dateformat;
        $db_results = Dba::read($sql);

        $values = [];
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['total'];
        }

        return $values;
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_user_bandwidth_pts(
        $user_id = 0,
        $object_type = 'song',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        return $this->get_user_object_count_pts($user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_user_time_pts(
        $user_id = 0,
        $object_type = 'song',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        return $this->get_user_object_count_pts($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, 'time');
    }

    /**
     * @param int $catalog_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_catalog_files_pts(
        $catalog_id = 0,
        $object_type = 'song',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        $start_date = $start_date ?? (($end_date ?? time()) - 864000);
        $dateformat = $this->get_sql_date_format("`" . $object_type . "`.`addition_time`", $zoom);
        $where      = $this->get_catalog_sql_where($object_type, $object_id, $catalog_id, $start_date, $end_date);
        $sql        = "SELECT " . $dateformat . " AS `zoom_date`, ((SELECT COUNT(`t2`.`id`) FROM `" . $object_type . "` `t2` WHERE `t2`.`addition_time` < `zoom_date`) + COUNT(`" . $object_type . "`.`id`)) AS `files` FROM `" . $object_type . "` " . $where . " GROUP BY " . $dateformat;
        $db_results = Dba::read($sql);

        $values = [];
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['files'];
        }

        return $values;
    }

    /**
     * @param int $catalog_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_catalog_size_pts(
        $catalog_id = 0,
        $object_type = 'song',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        $start_date = $start_date ?? (($end_date ?? time()) - 864000);
        $dateformat = $this->get_sql_date_format("`" . $object_type . "`.`addition_time`", $zoom);
        $where      = $this->get_catalog_sql_where($object_type, $object_id, $catalog_id, $start_date, $end_date);
        $sql        = ($object_type == 'album')
            ? "SELECT " . $dateformat . " AS `zoom_date`, ((SELECT SUM(`song`.`size`) AS `size` FROM `album` `t2` LEFT JOIN `song` ON `t2`.`id` = `song`.`id` WHERE `t2`.`addition_time` < `zoom_date`)) AS `storage` FROM `album` " . $where . " GROUP BY " . $dateformat
            : "SELECT " . $dateformat . " AS `zoom_date`, ((SELECT SUM(`t2`.`size`) FROM `" . $object_type . "` `t2` WHERE `t2`.`addition_time` < `zoom_date`) + SUM(`" . $object_type . "`.`size`)) AS `storage` FROM `" . $object_type . "` " . $where . " GROUP BY " . $dateformat;
        $db_results = Dba::read($sql);

        $values = [];
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['storage'];
        }

        return $values;
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @return array
     */
    protected function get_geolocation_pts(
        $user_id = 0,
        $object_type = '',
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day'
    ): array {
        $pts = [];

        $where = $this->get_user_sql_where($user_id, $object_type, $object_id, $start_date, $end_date);
        if ($object_type === '') {
            $where .= " AND `object_type` IN ('song', 'video')";
        }
        $sql        = "SELECT `geo_latitude`, `geo_longitude`, `geo_name`, MAX(`date`) AS `last_date`, COUNT(`id`) AS `hits` FROM `object_count` $where AND `geo_latitude` IS NOT NULL AND `geo_longitude` IS NOT NULL GROUP BY `geo_latitude`, `geo_longitude`, `geo_name` ORDER BY `last_date`, `geo_name` DESC";
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $pts[] = [
                'latitude' => $results['geo_latitude'],
                'longitude' => $results['geo_longitude'],
                'name' => $results['geo_name'],
                'last_date' => $results['last_date'],
                'hits' => $results['hits']
            ];
        }

        return $pts;
    }

    /**
     * @param string $title
     * @param Data $MyData
     * @param string $zoom
     * @param int $width
     * @param int $height
     */
    protected function render_graph($title, Data $MyData, $zoom, $width = 0, $height = 0): void
    {
        // Check graph size sanity
        $width = (int)$width;
        if ($width <= 50 || $width > 4096) {
            $width = 700;
        }
        $height = (int)$height;
        if ($height <= 60 || $height > 4096) {
            $height = 260;
        }

        $MyData->setSerieDescription("TimeStamp", "time");
        $MyData->setAbscissa("TimeStamp");
        switch ($zoom) {
            case 'hour':
                $MyData->setXAxisDisplay(AXIS_FORMAT_TIME, "H:00");
                break;
            case 'year':
                $MyData->setXAxisDisplay(AXIS_FORMAT_DATE, "Y");
                break;
            case 'month':
                $MyData->setXAxisDisplay(AXIS_FORMAT_DATE, "Y-m");
                break;
            case 'day':
                $MyData->setXAxisDisplay(AXIS_FORMAT_DATE, "Y-m-d");
                break;
        }

        /* Create the pChart object */
        $myPicture = new CpChart\Image($width, $height, $MyData);

        /* Turn of Antialiasing */
        $myPicture->Antialias = false;

        /* Draw a background */
        $Settings = ["R" => 90, "G" => 90, "B" => 90, "Dash" => 1, "DashR" => 120, "DashG" => 120, "DashB" => 120];
        $myPicture->drawFilledRectangle(0, 0, $width, $height, $Settings);

        /* Overlay with a gradient */
        $Settings = [
            "StartR" => 200,
            "StartG" => 200,
            "StartB" => 200,
            "EndR" => 50,
            "EndG" => 50,
            "EndB" => 50,
            "Alpha" => 50,
        ];
        $myPicture->drawGradientArea(0, 0, $width, $height, DIRECTION_VERTICAL, $Settings);
        $myPicture->drawGradientArea(0, 0, $width, $height, DIRECTION_HORIZONTAL, $Settings);

        /* Add a border to the picture */
        $myPicture->drawRectangle(0, 0, $width - 1, $height - 1, ["R" => 0, "G" => 0, "B" => 0]);

        /* Write the chart title */
        $myPicture->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
        $myPicture->drawText(150, 35, $title, ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);

        /* Set the default font */
        $myPicture->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);

        /* Define the chart area */
        $myPicture->setGraphArea(60, 40, $width - 20, $height - 50);

        /* Draw the scale */
        $scaleSettings = [
            "XMargin" => 10,
            "YMargin" => 10,
            "Floating" => true,
            "GridR" => 200,
            "GridG" => 200,
            "GridB" => 200,
            "RemoveSkippedAxis" => true,
            "DrawSubTicks" => false,
            "Mode" => SCALE_MODE_START0,
            "LabelRotation" => 45,
            "LabelingMethod" => LABELING_DIFFERENT,
        ];
        $myPicture->drawScale($scaleSettings);

        /* Turn on Antialiasing */
        $myPicture->Antialias = true;

        /* Draw the line chart */
        $myPicture->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $myPicture->drawLineChart();

        /* Write a label over the chart */
        $myPicture->writeLabel("Inbound", [720]);

        /* Write the chart legend */
        $myPicture->drawLegend(280, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

        header("Content-Disposition: filename=\"ampache-graph.png\"");
        /* Render the picture (choose the best way) */
        $myPicture->autoOutput();
    }

    /**
     * @param int $user_id
     * @param string|null $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @param int $width
     * @param int $height
     */
    public function render_user_hits(
        $user_id,
        $object_type,
        $object_id,
        $start_date = null,
        $end_date = null,
        $zoom = 'day',
        $width = 0,
        $height = 0
    ): void {
        $MyData = new Data();
        $this->get_user_all_pts(
            'get_user_hits_pts',
            $MyData,
            $user_id,
            $object_type,
            $object_id,
            $start_date,
            $end_date,
            $zoom
        );

        $MyData->setAxisName(0, "Hits");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_METRIC);

        $this->render_graph('Hits', $MyData, $zoom, $width, $height);
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @param int $width
     * @param int $height
     */
    public function render_user_bandwidth(
        $user_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day',
        $width = 0,
        $height = 0
    ): void {
        $MyData = new Data();
        $this->get_user_all_pts('get_user_bandwidth_pts', $MyData, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Bandwidth");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_TRAFFIC);

        $this->render_graph('Bandwidth', $MyData, $zoom, $width, $height);
    }

    /**
     * @param int $user_id
     * @param int $start_date
     * @param int $end_date
     */
    public function get_total_bandwidth($user_id = 0, $start_date = null, $end_date = null): int
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_bandwidth_pts', $user_id, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $date => $value) {
            $total += $value;
        }

        return $total;
    }

    /**
     * @param int $user_id
     * @param int $start_date
     * @param int $end_date
     */
    public function get_total_time($user_id = 0, $start_date = null, $end_date = null): int
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_time_pts', $user_id, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $date => $value) {
            $total += $value;
        }

        return $total;
    }

    /**
     * @param int $user_id
     * @param int $start_date
     * @param int $end_date
     */
    public function get_total_hits($user_id = 0, $start_date = null, $end_date = null): int
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_hits_pts', $user_id, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $date => $value) {
            $total += $value;
        }

        return $total;
    }

    /**
     * @param int $catalog_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @param int $width
     * @param int $height
     */
    public function render_catalog_files(
        $catalog_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day',
        $width = 0,
        $height = 0
    ): void {
        $MyData = new Data();
        $this->get_catalog_all_pts(
            'get_catalog_files_pts',
            $MyData,
            $catalog_id,
            $object_type,
            $object_id,
            $start_date,
            $end_date,
            $zoom
        );

        $MyData->setAxisName(0, "Files");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_METRIC);

        $this->render_graph('Files', $MyData, $zoom, $width, $height);
    }

    /**
     * @param int $catalog_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     * @param int $width
     * @param int $height
     */
    public function render_catalog_size(
        $catalog_id = 0,
        $object_type = null,
        $object_id = 0,
        $start_date = null,
        $end_date = null,
        $zoom = 'day',
        $width = 0,
        $height = 0
    ): void {
        $MyData = new Data();
        $this->get_catalog_all_pts(
            'get_catalog_size_pts',
            $MyData,
            $catalog_id,
            $object_type,
            $object_id,
            $start_date,
            $end_date,
            $zoom
        );

        $MyData->setAxisName(0, "Size");
        $MyData->setAxisUnit(0, "B");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_CUSTOM, "pGraph_Yformat_bytes");

        $this->render_graph('Size', $MyData, $zoom, $width, $height);
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param int $start_date
     * @param int $end_date
     * @param string $zoom
     */
    public function display_map(
        $user_id,
        $object_type,
        $object_id,
        $start_date,
        $end_date,
        $zoom,
    ): bool {
        $pts  = $this->get_geolocation_pts($user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            return false;
        }
        foreach (Plugin::get_plugins(PluginTypeEnum::GEO_MAP) as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->_plugin instanceof PluginLocationInterface && $plugin->load($user)) {
                if ($plugin->_plugin->display_map($pts)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @deprecated
     */
    private function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
