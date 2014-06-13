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

class License
{
    public $id;
    public $name;
    public $description;
    public $external_link;

    public $f_link;

    /**
     * Constructor
     * This pulls the license information from the database and returns
     * a constructed object
     */
    public function __construct($id)
    {
        // Load the data from the database
        $this->_get_info($id);

        return true;

    } // Constructor

    /**
     * _get_info
     * does the db call, reads from the license table
     */
    private function _get_info($id)
    {
        $sql = "SELECT * FROM `license` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($id));

        $data = Dba::fetch_assoc($db_results);

        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }

        return true;

    } // _get_info

    /**
     * create
     * This takes a key'd array of data as input and inserts a new license entry, it returns the auto_inc id
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `license` (`name`,`description`,`external_link`) " .
            "VALUES (? , ?, ?)";
        Dba::write($sql, array($data['name'], $data['description'], $data['external_link']));
        $insert_id = Dba::insert_id();

        return $insert_id;

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a license entry
     */
    public static function update($data)
    {
        $sql = "UPDATE `license` SET `name` = ?, `description` = ?, `external_link` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['name'], $data['description'], $data['external_link'], $data['license_id']));

        return true;

    } // create

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format()
    {
        $this->f_link = ($this->external_link) ? '<a href="' . $this->external_link . '">' . $this->name . '</a>' : $this->name;
        return true;

    } //format

    /**
     * delete
     * this function deletes a specific license entry
     */

    public static function delete($license_id)
    {
        $sql = "DELETE FROM `license` WHERE `id` = ?";
        Dba::write($sql, array($license_id));

    } // delete

    /**
     * get_licenses
     * Returns a list of licenses accessible by the current user.
     */
    public static function get_licenses()
    {
        $sql = 'SELECT `id` from `license` ORDER BY `name`';
        $db_results = Dba::read($sql);

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_licenses

} // License class
