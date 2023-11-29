<?php

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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

class TvShow extends database_object implements library_item
{
    protected const DB_TABLENAME = 'tvshow';

    /* Variables from DB */
    public int $id = 0;
    public ?string $name;
    public ?string $prefix;
    public ?string $summary;
    public ?int $year;

    public $catalog_id;
    public $tags;
    public $f_tags;
    public $episodes;
    public $seasons;
    public $f_name;

    public ?string $link = null;

    public $f_link;

    // Constructed vars
    private static $_mapcache = array();

    /**
     * TV Show
     * Takes the ID of the tv show and pulls the info from the db
     * @param int|null $show_id
     */
    public function __construct($show_id = 0)
    {
        if (!$show_id) {
            return;
        }
        $info = $this->get_info($show_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * garbage_collection
     *
     * This cleans out unused tv shows
     */
    public static function garbage_collection(): void
    {
        $sql = "DELETE FROM `tvshow` USING `tvshow` LEFT JOIN `tvshow_season` ON `tvshow_season`.`tvshow` = `tvshow`.`id` WHERE `tvshow_season`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * get_seasons
     * gets the tv show seasons
     */
    public function get_seasons()
    {
        $sql        = "SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? ORDER BY `season_number`";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_episodes
     * gets all episodes for this tv show
     */
    public function get_episodes()
    {
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT `tvshow_episode`.`id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` LEFT JOIN `tvshow_season` ON `tvshow_season`.`id` = `tvshow_episode`.`season` WHERE `tvshow_season`.`tvshow`='" . Dba::escape($this->id) . "' AND `catalog`.`enabled` = '1' "
            : "SELECT `tvshow_episode`.`id` FROM `tvshow_episode` LEFT JOIN `tvshow_season` ON `tvshow_season`.`id` = `tvshow_episode`.`season` WHERE `tvshow_season`.`tvshow`='" . Dba::escape($this->id) . "' ";
        $sql .= "ORDER BY `tvshow_season`.`season_number`, `tvshow_episode`.`episode_number`";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * _get_extra info
     * This returns the extra information for the tv show, this means totals etc
     * @return array
     */
    private function _get_extra_info()
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('tvshow_extra', $this->id)) {
            $row = parent::get_from_cache('tvshow_extra', $this->id);
        } else {
            $sql        = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` AS `catalog_id` FROM `tvshow_season` LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `tvshow_season`.`tvshow` = ? GROUP BY `catalog_id`";
            $db_results = Dba::read($sql, array($this->id));
            $row        = Dba::fetch_assoc($db_results);

            $sql                 = "SELECT COUNT(`tvshow_season`.`id`) AS `season_count` FROM `tvshow_season` WHERE `tvshow_season`.`tvshow` = ?";
            $db_results          = Dba::read($sql, array($this->id));
            $row2                = Dba::fetch_assoc($db_results);
            $row['season_count'] = $row2['season_count'];

            parent::add_to_cache('tvshow_extra', $this->id, $row);
        }

        /* Set Object Vars */
        $this->episodes   = $row['episode_count'];
        $this->seasons    = $row['season_count'];
        $this->catalog_id = $row['catalog_id'];

        return $row;
    }

    /**
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */
    public function format($details = true): void
    {
        $this->get_f_link();
        if ($details) {
            $this->_get_extra_info();
            $this->tags   = Tag::get_top_tags('tvshow', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'tvshow');
        }
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords           = array();
        $keywords['tvshow'] = array(
            'important' => true,
            'label' => T_('TV Show'),
            'value' => $this->get_fullname()
        );
        $keywords['type'] = array(
            'important' => false,
            'label' => null,
            'value' => 'tvshow'
        );

        return $keywords;
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_name)) {
            $this->f_name = trim(trim((string) $this->prefix) . ' ' . trim((string) $this->name));
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
            $this->link = $web_path . '/tvshows.php?action=show&tvshow=' . $this->id;
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
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . scrub_out($this->get_fullname()) . '">' . scrub_out($this->get_fullname()) . '</a>';
        }

        return $this->f_link;
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
     * @return array
     */
    public function get_childrens()
    {
        return array('tvshow_season' => $this->get_seasons());
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name)
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'video') {
            $episodes = $this->get_episodes();
            foreach ($episodes as $episode_id) {
                $medias[] = array(
                    'object_type' => 'video',
                    'object_id' => $episode_id
                );
            }
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return list<int>
     */
    public function get_catalogs()
    {
        return array($this->catalog_id);
    }

    /**
     * @return int|null
     */
    public function get_user_owner(): ?int
    {
        return null;
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
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'tvshow') || $force) {
            Art::display('tvshow', $this->id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * check
     *
     * Checks for an existing tv show; if none exists, insert one.
     * @param string $name
     * @param $year
     * @param $tvshow_summary
     * @param bool $readonly
     * @return int|null
     */
    public static function check($name, $year, $tvshow_summary, $readonly = false): ?int
    {
        // null because we don't have any unique id like mbid for now
        if (isset(self::$_mapcache[$name]['null'])) {
            return (int)self::$_mapcache[$name]['null'];
        }

        $tvshow_id  = 0;
        $exists     = false;
        $trimmed    = Catalog::trim_prefix(trim((string)$name));
        $name       = $trimmed['string'];
        $prefix     = $trimmed['prefix'];
        $sql        = 'SELECT `id` FROM `tvshow` WHERE `name` LIKE ? AND `year` = ?';
        $db_results = Dba::read($sql, array($name, $year));
        $id_array   = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $key            = 'null';
            $id_array[$key] = $row['id'];
        }

        if (count($id_array)) {
            $tvshow_id = array_shift($id_array);
            $exists    = true;
        }

        if ($exists && (int)$tvshow_id > 0) {
            self::$_mapcache[$name]['null'] = (int)$tvshow_id;

            return (int)$tvshow_id;
        }

        if ($readonly) {
            return null;
        }

        $sql        = 'INSERT INTO `tvshow` (`name`, `prefix`, `year`, `summary`) VALUES(?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($name, $prefix, $year, $tvshow_summary));
        if (!$db_results) {
            return null;
        }
        $tvshow_id = Dba::insert_id();
        if (!$tvshow_id) {
            return null;
        }

        self::$_mapcache[$name]['null'] = (int)$tvshow_id;

        return (int)$tvshow_id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current tv show
     * @param array $data
     */
    public function update(array $data): int
    {
        // Save our current ID
        $current_id = $this->id;
        $name       = $data['name'] ?? $this->name;
        $year       = $data['year'] ?? $this->year;
        $summary    = $data['summary'] ?? $this->summary;

        // Check if name is different than current name
        if ($this->name != $name || $this->year != $year) {
            $tvshow_id = self::check($name, $year, true);

            // If it's changed we need to update
            if ($tvshow_id !== null && $tvshow_id != $this->id) {
                $seasons = $this->get_seasons();
                foreach ($seasons as $season_id) {
                    TVShow_Season::update_tvshow($tvshow_id, $season_id);
                }
                $current_id = (int)$tvshow_id;
                Stats::migrate('tvshow', $this->id, $current_id, 0);
                Useractivity::migrate('tvshow', $this->id, $current_id);
                //Recommendation::migrate('tvshow', $this->id);
                Share::migrate('tvshow', $this->id, $current_id);
                $this->getShoutRepository()->migrate('tvshow', $this->id, $current_id);
                Tag::migrate('tvshow', $this->id, $current_id);
                Userflag::migrate('tvshow', $this->id, $current_id);
                Rating::migrate('tvshow', $this->id, $current_id);
                Art::duplicate('tvshow', $this->id, $current_id);
                if (!AmpConfig::get('cron_cache')) {
                    self::garbage_collection();
                }
            } // end if it changed
        }

        $trimmed = Catalog::trim_prefix(trim((string)$name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];

        $sql = 'UPDATE `tvshow` SET `name` = ?, `prefix` = ?, `year` = ?, `summary` = ? WHERE `id` = ?';
        Dba::write($sql, array($name, $prefix, $year, $summary, $current_id));

        $this->name    = $name;
        $this->prefix  = $prefix;
        $this->year    = $year;
        $this->summary = $summary;

        $override_childs = false;
        if (array_key_exists('overwrite_childs', $data) && $data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if (array_key_exists('add_to_childs', $data) && $data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->update_tags($data['edit_tags'], $override_childs, $add_to_childs, true);
        }

        return $current_id;
    }

    /**
     * update_tags
     *
     * Update tags of tv shows
     * @param string $tags_comma
     * @param bool $override_childs
     * @param bool $add_to_childs
     * @param bool $force_update
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $force_update = false)
    {
        Tag::update_tag_list($tags_comma, 'tvshow', $this->id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $episodes = $this->get_episodes();
            foreach ($episodes as $ep_id) {
                Tag::update_tag_list($tags_comma, 'episode', $ep_id, $override_childs);
            }
        }
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $deleted    = true;
        $season_ids = $this->get_seasons();
        foreach ($season_ids as $season_object) {
            $season  = new TVShow_Season($season_object);
            $deleted = $season->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the season `' . (string) $season_object . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow` WHERE `id` = ?";
            $deleted = (Dba::write($sql, array($this->id)) !== false);
            if ($deleted) {
                Art::garbage_collection('tvshow', $this->id);
                Userflag::garbage_collection('tvshow', $this->id);
                Rating::garbage_collection('tvshow', $this->id);
                $this->getShoutRepository()->collectGarbage('tvshow', $this->getId());
                $this->getUseractivityRepository()->collectGarbage('tvshow', $this->getId());
            }
        }

        return $deleted;
    }

    /**
     * @deprecated
     */
    private function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
