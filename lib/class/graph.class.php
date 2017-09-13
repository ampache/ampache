<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Graph
{
    public function __construct()
    {
        require_once AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/Data.php';
        require_once AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/Draw.php';
        require_once AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/Image.php';

        return true;
    }
    
    protected function get_sql_date_format($field, $zoom)
    {
        switch ($zoom) {
            case 'hour':
                $df = "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-%d %H:00:00')";
                break;
            case 'year':
                $df = "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-01-01')";
                break;
            case 'month':
                $df = "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-01')";
                break;
            case 'day':
            default:
                $df = "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-%d')";
                break;
        }

        return "UNIX_TIMESTAMP(" . $df . ")";
    }

    protected function get_user_sql_where($user = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null)
    {
        if ($end_date == null) {
            $end_date = time();
        } else {
            $end_date = intval($end_date);
        }
        if ($start_date == null) {
            $start_date = $end_date - 864000;
        } else {
            $start_date = intval($start_date);
        }

        $sql = "WHERE `object_count`.`date` >= " . $start_date . " AND `object_count`.`date` <= " . $end_date;
        if ($user > 0) {
            $user = intval($user);
            $sql .= " AND `object_count`.`user` = " . $user;
        }

        $object_id = intval($object_id);
        if (Core::is_library_item($object_type)) {
            $sql .= " AND `object_count`.`object_type` = '" . $object_type . "'";
            if ($object_id) {
                $sql .= " AND `object_count`.`object_id` = '" . $object_id . "'";
            }
        }

        return $sql;
    }

    protected function get_catalog_sql_where($object_type = 'song', $object_id = 0, $catalog = 0, $start_date = null, $end_date = null)
    {
        if ($end_date == null) {
            $end_date = time();
        } else {
            $end_date = intval($end_date);
        }
        if ($start_date == null) {
            $start_date = $end_date - 864000;
        } else {
            $start_date = intval($start_date);
        }

        $sql = "WHERE `" . $object_type . "`.`addition_time` >= " . $start_date . " AND `" . $object_type . "`.`addition_time` <= " . $end_date;
        if ($catalog > 0) {
            $catalog = intval($catalog);
            $sql .= " AND `" . $object_type . "`.`catalog` = " . $catalog;
        }

        $object_id = intval($object_id);
        if ($object_id) {
            $sql .= " AND `" . $object_type . "`.`id` = '" . $object_id . "'";
        }

        return $sql;
    }

    protected function get_all_type_pts($fct, $id = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        if ($object_type == null) {
            $type = 'song';
        } else {
            $type = $object_type;
        }
        $song_values = $this->$fct($id, $type, $object_id, $start_date, $end_date, $zoom);
        if ($object_type == null && AmpConfig::get('allow_video')) {
            $video_values = $this->$fct($id, 'video', $object_id, $start_date, $end_date, $zoom);
        } else {
            $video_values = array();
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

    protected function get_all_pts($fct, CpChart\Chart\Data $MyData, $id = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day', $show_total = true)
    {
        $values = $this->get_all_type_pts($fct, $id, $object_type, $object_id, $start_date, $end_date, $zoom);
        foreach ($values as $date => $value) {
            if ($show_total) {
                $MyData->addPoints($value, "Total");
            }
            $MyData->addPoints($date, "TimeStamp");
        }

        return $values;
    }

    protected function get_user_all_pts($fct, CpChart\Chart\Data $MyData, $user = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $values = $this->get_all_pts($fct, $MyData, $user, $object_type, $object_id, $start_date, $end_date, $zoom);

        $ustats = User::count();
        // Only display other users if the graph is not for a specific user and user count is small
        if (!$user && $ustats['users'] < 10) {
            $user_ids = User::get_valid_users();
            foreach ($user_ids as $user_id) {
                $u           = new User($user_id);
                $user_values = $this->get_all_type_pts($fct, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
                foreach ($values as $date => $value) {
                    if (array_key_exists($date, $user_values)) {
                        $value = $user_values[$date];
                    } else {
                        $value = 0;
                    }
                    $MyData->addPoints($value, $u->username);
                }
            }
        }
    }

    protected function get_catalog_all_pts($fct, CpChart\Chart\Data $MyData, $catalog = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $values = $this->get_all_pts($fct, $MyData, $catalog, $object_type, $object_id, $start_date, $end_date, $zoom, false);

        // Only display other users if the graph is not for a specific catalog
        if (!$catalog) {
            $catalog_ids = Catalog::get_catalogs();
            foreach ($catalog_ids as $catalog_id) {
                $c              = Catalog::create_from_id($catalog_id);
                $catalog_values = $this->get_all_type_pts($fct, $catalog_id, $object_type, $object_id, $start_date, $end_date, $zoom);
                $pv             = 0;
                foreach ($values as $date => $value) {
                    if (array_key_exists($date, $catalog_values)) {
                        $value = $catalog_values[$date];
                        $pv    = $value;
                    } else {
                        $value = $pv;
                    }
                    $MyData->addPoints($value, $c->name);
                }
            }
        }
    }

    protected function get_user_hits_pts($user = 0, $object_type = 'song', $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $df    = $this->get_sql_date_format("`object_count`.`date`", $zoom);
        $where = $this->get_user_sql_where($user, $object_type, $object_id, $start_date, $end_date);
        $sql   = "SELECT " . $df . " AS `zoom_date`, COUNT(`object_count`.`id`) AS `hits` FROM `object_count` " . $where .
                " GROUP BY " . $df;
        $db_results = Dba::read($sql);

        $values = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['hits'];
        }

        return $values;
    }

    protected function get_user_object_count_pts($user = 0, $object_type = 'song', $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day', $column = 'size')
    {
        $df    = $this->get_sql_date_format("`object_count`.`date`", $zoom);
        $where = $this->get_user_sql_where($user, $object_type, $object_id, $start_date, $end_date);
        $sql   = "SELECT " . $df . " AS `zoom_date`, SUM(`" . $object_type . "`.`" . $column . "`) AS `total` FROM `object_count` " .
                " JOIN `" . $object_type . "` ON `" . $object_type . "`.`id` = `object_count`.`object_id` " . $where .
                " GROUP BY " . $df;
        $db_results = Dba::read($sql);

        $values = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['total'];
        }

        return $values;
    }

    protected function get_user_bandwidth_pts($user = 0, $object_type = 'song', $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        return $this->get_user_object_count_pts($user, $object_type, $object_id, $start_date, $end_date, $zoom, 'size');
    }

    protected function get_user_time_pts($user = 0, $object_type = 'song', $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        return $this->get_user_object_count_pts($user, $object_type, $object_id, $start_date, $end_date, $zoom, 'time');
    }

    protected function get_catalog_files_pts($catalog = 0, $object_type = 'song', $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $start_date = $start_date ?: ($end_date ?: time()) - 864000;
        $df         = $this->get_sql_date_format("`" . $object_type . "`.`addition_time`", $zoom);
        $where      = $this->get_catalog_sql_where($object_type, $object_id, $catalog, $start_date, $end_date);
        $sql        = "SELECT " . $df . " AS `zoom_date`,  ((SELECT COUNT(`t2`.`id`) FROM `" . $object_type . "` `t2` WHERE `t2`.`addition_time` < `zoom_date`) + COUNT(`" . $object_type . "`.`id`)) AS `files` FROM `" . $object_type . "` " . $where .
                " GROUP BY " . $df;
        $db_results = Dba::read($sql);

        $values = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['files'];
        }

        return $values;
    }

    protected function get_catalog_size_pts($catalog = 0, $object_type = 'song', $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $start_date = $start_date ?: ($end_date ?: time()) - 864000;
        $df         = $this->get_sql_date_format("`" . $object_type . "`.`addition_time`", $zoom);
        $where      = $this->get_catalog_sql_where($object_type, $object_id, $catalog, $start_date, $end_date);
        $sql        = "SELECT " . $df . " AS `zoom_date`,  ((SELECT SUM(`t2`.`size`) FROM `" . $object_type . "` `t2` WHERE `t2`.`addition_time` < `zoom_date`) + SUM(`" . $object_type . "`.`size`)) AS `storage` FROM `" . $object_type . "` " . $where .
                " GROUP BY " . $df;
        $db_results = Dba::read($sql);

        $values = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['storage'];
        }

        return $values;
    }

    protected function get_geolocation_pts($user = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $pts = array();

        $where = $this->get_user_sql_where($user, $object_type, $object_id, $start_date, $end_date);
        if ($object_type == null) {
            $where .= " AND `object_type` IN ('song', 'video')";
        }
        $sql = "SELECT `geo_latitude`, `geo_longitude`, `geo_name`, MAX(`date`) AS `last_date`, COUNT(`id`) AS `hits` FROM `object_count` " .
                $where . " AND `geo_latitude` IS NOT NULL AND `geo_longitude` IS NOT NULL " .
                "GROUP BY `geo_latitude`, `geo_longitude` ORDER BY `last_date` DESC";
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $pts[] = array(
                'latitude' => $results['geo_latitude'],
                'longitude' => $results['geo_longitude'],
                'name' => $results['geo_name'],
                'last_date' => $results['last_date'],
                'hits' => $results['hits']
            );
        }

        return $pts;
    }

    protected function render_graph($title, CpChart\Chart\Data $MyData, $zoom, $width = 0, $height = 0)
    {
        // Check graph size sanity
        $width = intval($width);
        if ($width <= 50 || $width > 4096) {
            $width = 700;
        }
        $height = intval($height);
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
        $myPicture = new CpChart\Chart\Image($width, $height, $MyData);

        /* Turn of Antialiasing */
        $myPicture->Antialias = false;

        /* Draw a background */
        $Settings = array("R" => 90, "G" => 90, "B" => 90, "Dash" => 1, "DashR" => 120, "DashG" => 120, "DashB" => 120);
        $myPicture->drawFilledRectangle(0, 0, $width, $height, $Settings);

        /* Overlay with a gradient */
        $Settings = array("StartR" => 200, "StartG" => 200, "StartB" => 200, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 50);
        $myPicture->drawGradientArea(0, 0, $width, $height, DIRECTION_VERTICAL, $Settings);
        $myPicture->drawGradientArea(0, 0, $width, $height, DIRECTION_HORIZONTAL, $Settings);

        /* Add a border to the picture */
        $myPicture->drawRectangle(0, 0, $width - 1, $height - 1, array("R" => 0, "G" => 0, "B" => 0));

        $font_path = AmpConfig::get('prefix') . "/lib/vendor/szymach/c-pchart/src/Resources/fonts";
        /* Write the chart title */
        $myPicture->setFontProperties(array("FontName" => $font_path . "/Forgotte.ttf", "FontSize" => 11));
        $myPicture->drawText(150, 35, $title, array("FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE));

        /* Set the default font */
        $myPicture->setFontProperties(array("FontName" => $font_path . "/pf_arma_five.ttf", "FontSize" => 6));

        /* Define the chart area */
        $myPicture->setGraphArea(60, 40, $width - 20, $height - 50);

        /* Draw the scale */
        $scaleSettings = array("XMargin" => 10,"YMargin" => 10,"Floating" => true,"GridR" => 200,"GridG" => 200,"GridB" => 200,"RemoveSkippedAxis" => true,"DrawSubTicks" => false,"Mode" => SCALE_MODE_START0,"LabelRotation" => 45,"LabelingMethod" => LABELING_DIFFERENT);
        $myPicture->drawScale($scaleSettings);

        /* Turn on Antialiasing */
        $myPicture->Antialias = true;

        /* Draw the line chart */
        $myPicture->setShadow(true, array("X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));
        $myPicture->drawLineChart();

        /* Write a label over the chart */
        $myPicture->writeLabel("Inbound", 720);

        /* Write the chart legend */
        $myPicture->drawLegend(280, 20, array("Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL));

        header("Content-Disposition: filename=\"ampache-graph.png\"");
        /* Render the picture (choose the best way) */
        $myPicture->autoOutput();
    }

    public function render_user_hits($user = 0, $object_type, $object_id, $start_date = null, $end_date = null, $zoom = 'day', $width = 0, $height = 0)
    {
        $MyData = new CpChart\Chart\Data();
        $this->get_user_all_pts('get_user_hits_pts', $MyData, $user, $object_type, $object_id, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Hits");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_METRIC);

        $this->render_graph('Hits', $MyData, $zoom, $width, $height);
    }

    public function render_user_bandwidth($user = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day', $width = 0, $height = 0)
    {
        $MyData = new CpChart\Chart\Data();
        $this->get_user_all_pts('get_user_bandwidth_pts', $MyData, $user, $object_type, $object_id, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Bandwidth");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_TRAFFIC);

        $this->render_graph('Bandwidth', $MyData, $zoom, $width, $height);
    }

    public function get_total_bandwidth($user = 0, $start_date = null, $end_date = null)
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_bandwidth_pts', $user, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $date => $value) {
            $total += $value;
        }

        return $total;
    }

    public function get_total_time($user = 0, $start_date = null, $end_date = null)
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_time_pts', $user, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $date => $value) {
            $total += $value;
        }

        return $total;
    }

    public function get_total_hits($user = 0, $start_date = null, $end_date = null)
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_hits_pts', $user, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $date => $value) {
            $total += $value;
        }

        return $total;
    }

    public function render_catalog_files($catalog = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day', $width = 0, $height = 0)
    {
        $MyData = new CpChart\Chart\Data();
        $this->get_catalog_all_pts('get_catalog_files_pts', $MyData, $catalog, $object_type, $object_id, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Files");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_METRIC);

        $this->render_graph('Files', $MyData, $zoom, $width, $height);
    }

    public function render_catalog_size($catalog = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day', $width = 0, $height = 0)
    {
        $MyData = new CpChart\Chart\Data();
        $this->get_catalog_all_pts('get_catalog_size_pts', $MyData, $catalog, $object_type, $object_id, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Size");
        $MyData->setAxisUnit(0, "B");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_CUSTOM, "pGraph_Yformat_bytes");

        $this->render_graph('Size', $MyData, $zoom, $width, $height);
    }

    public function display_map($user = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $pts = $this->get_geolocation_pts($user, $object_type, $object_id, $start_date, $end_date, $zoom);

        foreach (Plugin::get_plugins('display_map') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load($GLOBALS['user'])) {
                if ($plugin->_plugin->display_map($pts)) {
                    break;
                }
            }
        }
    }

    public static function display_from_request()
    {
        $object_type = $_REQUEST['object_type'];
        $object_id   = $_REQUEST['object_id'];
        
        $libitem  = null;
        $owner_id = 0;
        if ($object_id) {
            if (Core::is_library_item($object_type)) {
                $libitem  = new $object_type($object_id);
                $owner_id = $libitem->get_user_owner();
            }
        }
        
        if (($owner_id <= 0 || $owner_id != $GLOBALS['user']->id) && !Access::check('interface', '50')) {
            UI::access_denied();
        } else {
            $user_id      = $_REQUEST['user_id'];
            $end_date     = $_REQUEST['end_date'] ? strtotime($_REQUEST['end_date']) : time();
            $f_end_date   = date("Y-m-d H:i", $end_date);
            $start_date   = $_REQUEST['start_date'] ? strtotime($_REQUEST['start_date']) : ($end_date - 864000);
            $f_start_date = date("Y-m-d H:i", $start_date);
            $zoom         = $_REQUEST['zoom'] ?: 'day';

            $gtypes   = array();
            $gtypes[] = 'user_hits';
            if ($object_type == null || $object_type == 'song' || $object_type == 'video') {
                $gtypes[] = 'user_bandwidth';
            }
            if (!$user_id && !$object_id) {
                $gtypes[] = 'catalog_files';
                $gtypes[] = 'catalog_size';
            }

            $blink = '';
            if ($libitem !== null) {
                $libitem->format();
                if (isset($libitem->f_link)) {
                    $blink = $libitem->f_link;
                }
            } else {
                if ($user_id) {
                    $u = new User($user_id);
                    $u->format();
                    $blink = $u->f_link;
                }
            }

            require_once AmpConfig::get('prefix') . UI::find_template('show_graphs.inc.php');
        }
    }
}

// Need to create a function to pass to pGraph objects
function pGraph_Yformat_bytes($value)
{
    return UI::format_bytes($value);
}
