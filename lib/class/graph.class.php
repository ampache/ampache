<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class Graph
{
    public function __construct()
    {
        require_once AmpConfig::get('prefix') . '/modules/pChart/pData.class.php';
        require_once AmpConfig::get('prefix') . '/modules/pChart/pDraw.class.php';
        require_once AmpConfig::get('prefix') . '/modules/pChart/pImage.class.php';

        return true;
    }

    protected function get_sql_date_format($zoom)
    {
        switch ($zoom) {
            case 'hour':
                $df = "DATE_FORMAT(FROM_UNIXTIME(`object_count`.`date`), '%Y-%m-%d %H:00:00')";
                break;
            case 'year':
                $df = "DATE_FORMAT(FROM_UNIXTIME(`object_count`.`date`), '%Y-00-00')";
                break;
            case 'month':
                $df = "DATE_FORMAT(FROM_UNIXTIME(`object_count`.`date`), '%Y-%m-00')";
                break;
            case 'day':
            default:
                $df = "DATE_FORMAT(FROM_UNIXTIME(`object_count`.`date`), '%Y-%m-%d')";
                break;
        }

        return "UNIX_TIMESTAMP(" . $df . ")";
    }

    protected function get_sql_where($user = 0, $start_date = null, $end_date = null)
    {
        if ($end_date == null) {
            $end_date = time();
        } else {
            $end_date = intval($end_date);
        }
        if ($start_date == null) {
            $start_date = $end_date - 1123200;
        } else {
            $start_date = intval($start_date);
        }

        $sql = "WHERE `object_count`.`date` > " . $start_date . " AND `object_count`.`date` < " . $end_date;
        if ($user > 0) {
            $user = intval($user);
            $sql .= " AND `object_count`.`user` = " . $user;
        }
        return $sql;
    }

    protected function get_all_type_pts($fct, $user = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $song_values = $this->$fct($user, $start_date, $end_date, $zoom, 'song');
        if (AmpConfig::get('allow_video')) {
            $video_values = $this->$fct($user, $start_date, $end_date, $zoom, 'video');
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

    protected function get_all_pts($fct, pData $MyData, $user = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $values = $this->get_all_type_pts($fct, $user, $start_date, $end_date, $zoom);
        foreach ($values as $date => $value) {
            $MyData->addPoints($value, "Total");
            $MyData->addPoints($date, "TimeStamp");
        }

        $ustats = User::count();
        // Only display other users if the graph is not for a specific user and user count is small
        if (!$user && $ustats['users'] < 20) {
            $user_ids = User::get_valid_users();
            foreach ($user_ids as $id) {
                $u = new User($id);
                $user_values = $this->get_all_type_pts($fct, $id, $start_date, $end_date, $zoom);
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

    protected function get_user_hits_pts($user = 0, $start_date = null, $end_date = null, $zoom = 'day', $type = 'song')
    {
        $df = $this->get_sql_date_format($zoom);
        $where = $this->get_sql_where($user, $start_date, $end_date);
        $sql = "SELECT " . $df . " AS `zoom_date`, COUNT(`object_count`.`id`) AS `hits` FROM `object_count` " . $where .
                "  AND `object_count`.`object_type` = '" . $type . "'" .
                " GROUP BY " . $df;
        $db_results = Dba::read($sql);

        $values = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['hits'];
        }
        return $values;
    }

    protected function get_user_bandwidth_pts($user = 0, $start_date = null, $end_date = null, $zoom = 'day', $type = 'song')
    {
        $df = $this->get_sql_date_format($zoom);
        $where = $this->get_sql_where($user, $start_date, $end_date);
        $sql = "SELECT " . $df . " AS `zoom_date`, SUM(`" . $type . "`.`size`) AS `bandwith` FROM `object_count` " .
                " JOIN `" . $type . "` ON `" . $type . "`.`id` = `object_count`.`object_id` " . $where .
                "  AND `object_count`.`object_type` = '" . $type . "'" .
                " GROUP BY " . $df;
        $db_results = Dba::read($sql);

        $values = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $values[$results['zoom_date']] = $results['bandwith'];
        }
        return $values;
    }

    protected function render_graph($title, $MyData, $zoom)
    {
        $MyData->setSerieDescription("TimeStamp","time");
        $MyData->setAbscissa("TimeStamp");
        switch ($zoom) {
            case 'hour':
                $MyData->setXAxisDisplay(AXIS_FORMAT_TIME,"H:00");
                break;
            case 'year':
                $MyData->setXAxisDisplay(AXIS_FORMAT_DATE,"Y");
                break;
            case 'month':
                $MyData->setXAxisDisplay(AXIS_FORMAT_DATE,"Y-m");
                break;
            case 'day':
                $MyData->setXAxisDisplay(AXIS_FORMAT_DATE,"Y-m-d");
                break;
        }

        /* Create the pChart object */
        $myPicture = new pImage(700,230,$MyData);

        /* Turn of Antialiasing */
        $myPicture->Antialias = FALSE;

        /* Draw a background */
        $Settings = array("R"=>90, "G"=>90, "B"=>90, "Dash"=>1, "DashR"=>120, "DashG"=>120, "DashB"=>120);
        $myPicture->drawFilledRectangle(0,0,700,230,$Settings);

        /* Overlay with a gradient */
        $Settings = array("StartR"=>200, "StartG"=>200, "StartB"=>200, "EndR"=>50, "EndG"=>50, "EndB"=>50, "Alpha"=>50);
        $myPicture->drawGradientArea(0,0,700,230,DIRECTION_VERTICAL,$Settings);
        $myPicture->drawGradientArea(0,0,700,230,DIRECTION_HORIZONTAL,$Settings);

        /* Add a border to the picture */
        $myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));

        /* Write the chart title */
        $myPicture->setFontProperties(array("FontName"=>AmpConfig::get('prefix')."/modules/pChart/fonts/Forgotte.ttf","FontSize"=>11));
        $myPicture->drawText(150,35,$title,array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));

        /* Set the default font */
        $myPicture->setFontProperties(array("FontName"=>AmpConfig::get('prefix')."/modules/pChart/fonts/pf_arma_five.ttf","FontSize"=>6));

        /* Define the chart area */
        $myPicture->setGraphArea(60,40,680,200);

        /* Draw the scale */
        $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"RemoveSkippedAxis"=>TRUE,"DrawSubTicks"=>FALSE,"Mode"=>SCALE_MODE_START0,"LabelingMethod"=>LABELING_DIFFERENT);
        $myPicture->drawScale($scaleSettings);

        /* Turn on Antialiasing */
        $myPicture->Antialias = TRUE;

        /* Draw the line chart */
        $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
        $myPicture->drawLineChart();

        /* Write a label over the chart */
        $myPicture->writeLabel("Inbound",720);

        /* Write the chart legend */
        $myPicture->drawLegend(580,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

        /* Render the picture (choose the best way) */
        $myPicture->autoOutput();
    }

    public function render_user_hits($user = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        /* Create and populate the pData object */
        $MyData = new pData();

        $this->get_all_pts('get_user_hits_pts', $MyData, $user, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Hits");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_METRIC);

        $this->render_graph('Hits', $MyData, $zoom);
    }

    public function render_user_bandwidth($user = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        /* Create and populate the pData object */
        $MyData = new pData();

        $this->get_all_pts('get_user_bandwidth_pts', $MyData, $user, $start_date, $end_date, $zoom);

        $MyData->setAxisName(0, "Hits");
        $MyData->setAxisDisplay(0, AXIS_FORMAT_TRAFFIC);

        $this->render_graph('Bandwidth', $MyData, $zoom);
    }
}
