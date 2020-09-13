<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

class License
{
    /**
     * @var integer $id
     */
    public $id;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var string $description
     */
    public $description;
    /**
     * @var string $external_link
     */
    public $external_link;

    /**
     * @var string $f_link
     */
    public $f_link;

    /**
     * Constructor
     * This pulls the license information from the database and returns
     * a constructed object
     * @param integer $license_id
     */
    public function __construct($license_id)
    {
        // Load the data from the database
        $this->has_info($license_id);

        return true;
    } // Constructor

    /**
     * has_info
     * does the db call, reads from the license table
     * @param integer $license_id
     * @return boolean
     */
    private function has_info($license_id)
    {
        $sql        = "SELECT * FROM `license` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($license_id));

        $data = Dba::fetch_assoc($db_results);

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // has_info

    /**
     * create
     * This takes a key'd array of data as input and inserts a new license entry, it returns the auto_inc id
     * @param array $data
     * @return integer
     */
    public static function create(array $data)
    {
        $sql = "INSERT INTO `license` (`name`, `description`, `external_link`) " .
            "VALUES (? , ?, ?)";
        Dba::write($sql, array($data['name'], $data['description'], $data['external_link']));
        $insert_id = Dba::insert_id();

        return (int) $insert_id;
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a license entry
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        $sql = "UPDATE `license` SET `name` = ?, `description` = ?, `external_link` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['name'], $data['description'], $data['external_link'], $this->id));

        return $this->id;
    } // create

    /**
     * format
     * this function takes the object and reformats some values
     */
    public function format()
    {
        $this->f_link = ($this->external_link) ? '<a href="' . $this->external_link . '">' . $this->name . '</a>' : $this->name;
    } // format

    /**
     * delete
     * this function deletes a specific license entry
     * @param integer $license_id
     */
    public static function delete($license_id)
    {
        $sql = "DELETE FROM `license` WHERE `id` = ?";
        Dba::write($sql, array($license_id));
    } // delete

    /**
     * get_licenses
     * Returns a list of licenses accessible by the current user.
     * @return integer[]
     */
    public static function get_licenses()
    {
        $sql        = 'SELECT `id` from `license` ORDER BY `name`';
        $db_results = Dba::read($sql);

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_licenses

    /**
     * get_license_songs
     * Returns a list of song ID's attached to a license ID.
     *
     * @param integer $license_id
     * @return integer[]
     */
    public static function get_license_songs($license_id)
    {
        $sql        = 'SELECT `id` from `song` WHERE `song`.`license` = ?';
        $db_results = Dba::read($sql, array($license_id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_license_songs

    /**
     * lookup
     * Returns a license matched by name or create one if missing
     * @param string $value
     * @return integer
     */
    public static function lookup($value)
    {
        // lookup the license by name
        $sql        = 'SELECT `id` from `license` WHERE `name` = ?';
        $db_results = Dba::read($sql, array($value));

        while ($row = Dba::fetch_assoc($db_results)) {
            return $row['id'];
        }
        // lookup the license by external_link
        $sql        = 'SELECT `id` from `license` WHERE `external_link` = ?';
        $db_results = Dba::read($sql, array($value));

        while ($row = Dba::fetch_assoc($db_results)) {
            return $row['id'];
        }
        // create one if missing
        $license = array(
            'name' => $value,
            'description' => null,
            'external_link' => null
        );

        return self::create($license);
    } // get_licenses
} // end license.class
