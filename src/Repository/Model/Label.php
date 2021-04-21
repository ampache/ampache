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

        return true;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'label') || $force) {
            echo Art::display('label', $this->id, $this->get_fullname(), $thumb, $this->link);
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
        $this->artists = count(static::getLabelRepository()->getArtists($this->getId()));
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
        return [
            'artist' => array_map(
                static function (int $artistId): array {
                    return [
                        'object_type' => 'artist',
                        'object_id' => $artistId
                    ];
                },
                static::getLabelRepository()->getArtists($this->getId())
            )
        ];
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
     * @return array|mixed
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
        $category = isset($data['category']) ? $data['category'] : $this->category;
        $summary  = isset($data['summary']) ? $data['summary'] : $this->summary;
        $address  = isset($data['address']) ? $data['address'] : $this->address;
        $email    = isset($data['email']) ? $data['email'] : $this->email;
        $website  = isset($data['website']) ? $data['website'] : $this->website;

        $sql = "UPDATE `label` SET `name` = ?, `category` = ?, `summary` = ?, `address` = ?, `email` = ?, `website` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $category, $summary, $address, $email, $website, $this->id));

        $this->name     = $name;
        $this->category = $category;
        $this->summary  = $summary;
        $this->address  = $address;
        $this->email    = $email;
        $this->website  = $website;

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
            'category' => 'tag_generated',
            'summary' => null,
            'address' => null,
            'email' => null,
            'website' => null,
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
        $category      = $data['category'];
        $summary       = $data['summary'];
        $address       = $data['address'];
        $email         = $data['email'];
        $website       = $data['website'];
        $user          = $data['user'] ?: Core::get_global('user')->id;
        $creation_date = $data['creation_date'] ?: time();

        $sql = "INSERT INTO `label` (`name`, `category`, `summary`, `address`, `email`, `website`, `user`, `creation_date`) " . "VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($name, $category, $summary, $address, $email, $website, $user, $creation_date));

        return Dba::insert_id();
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

        return rtrim((string)$results, ', ');
    } // get_display

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
