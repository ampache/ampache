<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use PDOStatement;

/**
 * This is the class responsible for handling the Label object
 * it is related to the label table in the database.
 */
class Label extends database_object implements library_item
{
    protected const DB_TABLENAME = 'label';

    /* Variables from DB */

    /**
     * @var integer $id
     */
    public $id;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var string $mbid
     */
    public $mbid; // MusicBrainz ID
    /**
     * @var string $category
     */
    public $category;
    /**
     * @var string $address
     */
    public $address;
    /**
     * @var string $email
     */
    public $email;
    /**
     * @var string $website
     */
    public $website;
    /**
     * @var string $summary
     */
    public $summary;
    /**
     * @var string $country
     */
    public $country;
    /**
     * @var integer $active
     */
    public $active;
    /**
     * @var integer $user
     */
    public $user;

    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var integer $artists
     */
    public $artists;

    /**
     * __construct
     * @param $label_id
     */
    public function __construct($label_id)
    {
        $info = $this->get_info($label_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
        $this->active = (int)$this->active;

        return true;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'label') || $force) {
            Art::display('label', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
        unset($details);
        $this->f_name  = scrub_out($this->name);
        $this->link    = AmpConfig::get('web_path') . '/labels.php?action=show&label=' . scrub_out($this->id);
        $this->f_link  = "<a href=\"" . $this->link . "\" title=\"" . $this->f_name . "\">" . $this->f_name;
        $this->artists = count($this->get_artists());
    }

    /**
     * @return array|integer[]
     */
    public function get_catalogs()
    {
        return array();
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        $medias  = array();
        $artists = $this->get_artists();
        foreach ($artists as $artist_id) {
            $medias[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return array('artist' => $medias);
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords          = array();
        $keywords['label'] = array(
            'important' => true,
            'label' => T_('Label'),
            'value' => $this->f_name
        );

        return $keywords;
    }

    /**
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song') {
            $songs = static::getSongRepository()->getByLabel($this->name);
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => 'song',
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return integer
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
     * search_childrens
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "artist";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $artists                   = Search::run($search);

        $childrens = array();
        foreach ($artists as $artist_id) {
            $childrens[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return $childrens;
    }

    /**
     * update
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        if (static::getLabelRepository()->lookup($data['name'], $this->id) !== 0) {
            return false;
        }

        $name     = isset($data['name']) ? $data['name'] : $this->name;
        $mbid     = isset($data['mbid']) ? $data['mbid'] : $this->mbid;
        $category = isset($data['category']) ? $data['category'] : $this->category;
        $summary  = isset($data['summary']) ? $data['summary'] : $this->summary;
        $address  = isset($data['address']) ? $data['address'] : $this->address;
        $country  = isset($data['country']) ? $data['country'] : $this->country;
        $email    = isset($data['email']) ? $data['email'] : $this->email;
        $website  = isset($data['website']) ? $data['website'] : $this->website;
        $active   = isset($data['active']) ? (int)$data['active'] : $this->active;

        $sql = "UPDATE `label` SET `name` = ?, `mbid` = ?, `category` = ?, `summary` = ?, `address` = ?, `country` = ?, `email` = ?, `website` = ?, `active` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $mbid, strtolower($category), $summary, $address, $country, $email, $website, $active, $this->id));

        return $this->id;
    }

    /**
     * helper
     * @param string $name
     * @return string
     */
    public static function helper(string $name)
    {
        $label_data = array(
            'name' => $name,
            'mbid' => null,
            'category' => 'tag_generated',
            'summary' => null,
            'address' => null,
            'country' => null,
            'email' => null,
            'website' => null,
            'active' => 1,
            'user' => 0,
            'creation_date' => time()
        );

        return self::create($label_data);
    }

    /**
     * create
     * @param array $data
     * @return string
     */
    public static function create(array $data)
    {
        if (static::getLabelRepository()->lookup($data['name']) !== 0) {
            return false;
        }

        $name          = $data['name'];
        $mbid          = $data['mbid'];
        $category      = $data['category'];
        $summary       = $data['summary'];
        $address       = $data['address'];
        $country       = $data['country'];
        $email         = $data['email'];
        $website       = $data['website'];
        $user          = $data['user'] ?: Core::get_global('user')->id;
        $active        = $data['active'];
        $creation_date = $data['creation_date'] ?: time();

        $sql = "INSERT INTO `label` (`name`, `mbid`, `category`, `summary`, `address`, `country`, `email`, `website`, `user`, `active`, `creation_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($name, $mbid, $category, $summary, $address, $country, $email, $website, $user, $active, $creation_date));

        return Dba::insert_id();
    }

    /**
     * get_artists
     * @return integer[]
     */
    public function get_artists()
    {
        $sql        = "SELECT `artist` FROM `label_asso` WHERE `label` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist'];
        }

        return $results;
    }

    /**
     * get_display
     * This returns a csv formatted version of the labels that we are given
     * @param $labels
     * @param boolean $link
     * @return string
     */
    public static function get_display($labels, $link = false)
    {
        if (!is_array($labels)) {
            return '';
        }

        $results = '';

        // Iterate through the labels, format them according to type and element id
        foreach ($labels as $label_id => $value) {
            if ($link) {
                $results .= '<a href="' . AmpConfig::get('web_path') . '/labels.php?action=show&label=' . $label_id . '" title="' . $value . '">';
            }
            $results .= $value;
            if ($link) {
                $results .= '</a>';
            }
            $results .= ', ';
        }

        $results = rtrim((string)$results, ', ');

        return $results;
    } // get_display

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        if ($object_type == 'artist') {
            $sql    = "UPDATE `label_asso` SET `artist` = ? WHERE `artist` = ?";
            $params = array($new_object_id, $old_object_id);

            return Dba::write($sql, $params);
        }

        return false;
    }

    /**
     * garbage_collection
     *
     * This cleans out unused artists
     */
    public static function garbage_collection()
    {
        Dba::write('DELETE FROM `label_asso` WHERE `label_asso`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`)');
    }

    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
