<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
        return true;
    }

    /**
     * @param string $field
     * @param string $zoom
     */
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
            $end_date = (int) ($end_date);
        }
        if ($start_date == null) {
            $start_date = $end_date - 864000;
        } else {
            $start_date = (int) ($start_date);
        }

        $sql = "WHERE `object_count`.`date` >= " . $start_date . " AND `object_count`.`date` <= " . $end_date;
        if ($user > 0) {
            $user = (int) ($user);
            $sql .= " AND `object_count`.`user` = " . $user;
        }

        $object_id = (int) ($object_id);
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
            $end_date = (int) ($end_date);
        }
        if ($start_date == null) {
            $start_date = $end_date - 864000;
        } else {
            $start_date = (int) ($start_date);
        }

        $sql = "WHERE `" . $object_type . "`.`addition_time` >= " . $start_date . " AND `" . $object_type . "`.`addition_time` <= " . $end_date;
        if ($catalog > 0) {
            $catalog = (int) ($catalog);
            $sql .= " AND `" . $object_type . "`.`catalog` = " . $catalog;
        }

        $object_id = (int) ($object_id);
        if ($object_id) {
            $sql .= " AND `" . $object_type . "`.`id` = '" . $object_id . "'";
        }

        return $sql;
    }

    /**
     * @param string $fct
     */
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

    public function display_map($user = 0, $object_type = null, $object_id = 0, $start_date = null, $end_date = null, $zoom = 'day')
    {
        $pts = $this->get_geolocation_pts($user, $object_type, $object_id, $start_date, $end_date, $zoom);

        foreach (Plugin::get_plugins('display_map') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                if ($plugin->_plugin->display_map($pts)) {
                    break;
                }
            }
        }
    }

    public static function display_from_request()
    {
        $object_type = Core::get_request('object_type');
        $object_id   = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

        $libitem  = null;
        $owner_id = 0;
        if ($object_id) {
            if (Core::is_library_item($object_type)) {
                $libitem  = new $object_type($object_id);
                $owner_id = $libitem->get_user_owner();
            }
        }

        if (($owner_id <= 0 || $owner_id != Core::get_global('user')->id) && !Access::check('interface', '50')) {
            UI::access_denied();
        } else {
            $user_id      = Core::get_request('user_id');
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
