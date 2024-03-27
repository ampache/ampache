<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
    public int $id = 0;
    public ?string $name;
    public ?string $category;
    public ?string $summary;
    public ?string $address;
    public ?string $email;
    public ?string $website;
    public ?int $user;
    public ?int $creation_date;
    public ?string $mbid; // MusicBrainz ID
    public ?string $country;
    public bool $active;

    /**
     * @var null|string $f_name
     */
    public $f_name;

    public ?string $link = null;

    /**
     * @var null|string $f_link
     */
    public $f_link;
    /**
     * @var array $artists
     */
    public $artists = array();
    /**
     * @var int $artists
     */
    public $artist_count;

    /**
     * __construct
     * @param int|null $label_id
     */
    public function __construct($label_id = 0)
    {
        if (!$label_id) {
            return;
        }
        $info = $this->get_info($label_id, static::DB_TABLENAME);
        if (empty($info)) {
            return;
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('label', $this->id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'label');
    }

    /**
     * @param bool $details
     */
    public function format($details = true): void
    {
        unset($details);
        $this->get_f_link();
        $this->get_artist_count();
    }

    /**
     * @return array
     */
    public function get_childrens(): array
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

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        return $this->summary ?? '';
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        if (!isset($this->f_name)) {
            $this->f_name = $this->name;
        }

        return $this->f_name;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = $web_path . '/labels.php?action=show&label=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($this->get_fullname());
        }

        return $this->f_link;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords(): array
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
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = array();
        if ($filter_type === null || $filter_type === 'song') {
            $songs = static::getSongRepository()->getByLabel((string)$this->name);
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => LibraryItemEnum::SONG,
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    /**
     * get_user_owner
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name): array
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
     * @return int|false
     */
    public function update(array $data)
    {
        // duplicate name check
        if (static::getLabelRepository()->lookup($data['name'], $this->id) !== 0) {
            return false;
        }

        $name     = $data['name'] ?? $this->name;
        $mbid     = $data['mbid'] ?? null;
        $category = $data['category'] ?? null;
        $summary  = $data['summary'] ?? null;
        $address  = $data['address'] ?? null;
        $country  = $data['country'] ?? null;
        $email    = $data['email'] ?? null;
        $website  = $data['website'] ?? null;
        $active   = isset($data['active']) ? (bool)$data['active'] : $this->active;

        $sql = "UPDATE `label` SET `name` = ?, `mbid` = ?, `category` = ?, `summary` = ?, `address` = ?, `country` = ?, `email` = ?, `website` = ?, `active` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $mbid, strtolower($category), $summary, $address, $country, $email, $website, $active, $this->id));

        return $this->id;
    }

    /**
     * helper
     */
    public static function helper(string $name): ?int
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
     * @return int|null
     */
    public static function create(array $data): ?int
    {
        if (static::getLabelRepository()->lookup($data['name']) !== 0) {
            return null;
        }

        $name          = $data['name'];
        $mbid          = $data['mbid'];
        $category      = $data['category'];
        $summary       = $data['summary'];
        $address       = $data['address'];
        $country       = $data['country'];
        $email         = $data['email'];
        $website       = $data['website'];
        $user          = $data['user'] ?? Core::get_global('user')?->getId();
        $active        = $data['active'];
        $creation_date = $data['creation_date'] ?? time();

        $sql = "INSERT INTO `label` (`name`, `mbid`, `category`, `summary`, `address`, `country`, `email`, `website`, `user`, `active`, `creation_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($name, $mbid, $category, $summary, $address, $country, $email, $website, $user, $active, $creation_date));

        $label_id = Dba::insert_id();
        if (!$label_id) {
            return null;
        }

        return (int)$label_id;
    }

    /**
     * get_artists
     * @return int[]
     */
    public function get_artists(): array
    {
        if (empty($this->artists)) {
            $sql        = "SELECT `artist` FROM `label_asso` WHERE `label` = ?";
            $db_results = Dba::read($sql, array($this->id));
            $results    = array();
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = (int)$row['artist'];
            }
            $this->artists = $results;
        }

        return $this->artists;
    }

    /**
     * get_artist_count
     */
    public function get_artist_count(): int
    {
        if (!isset($this->artist_count)) {
            $this->artist_count = count($this->get_artists());
        }

        return $this->artist_count;
    }

    /**
     * get_display
     * This returns a csv formatted version of the labels that we are given
     * @param array $labels
     * @param bool $link
     * @return string
     */
    public static function get_display($labels, $link = false): string
    {
        if (empty($labels)) {
            return '';
        }

        $web_path = AmpConfig::get('web_path');
        $results  = '';
        // Iterate through the labels, format them according to type and element id
        foreach ($labels as $label_id => $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/labels.php?action=show&label=' . $label_id . '" title="' . $value . '">';
            }
            $results .= $value;
            if ($link) {
                $results .= '</a>';
            }
            $results .= ', ';
        }

        $results = rtrim((string)$results, ', ');

        return $results;
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
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

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::LABEL;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
