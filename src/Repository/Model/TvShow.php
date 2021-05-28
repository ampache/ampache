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

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\DataMigratorInterface;
use Ampache\Module\System\Dba;
use Ampache\Module\Tag\TagListUpdaterInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\TvShowEpisodeRepositoryInterface;
use Ampache\Repository\TvShowSeasonRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final class TvShow extends database_object implements TvShowInterface
{
    protected const DB_TABLENAME = 'tvshow';

    private TagListUpdaterInterface $tagListUpdater;

    public int $id;

    /** @var int[]|null */
    private ?array $seasonIds = null;

    /** @var null|array{episode_count?: int, catalog_id?: int} */
    private ?array $extra_info = null;

    /** @var array<string, mixed>|null */
    private ?array $dbData = null;

    public function __construct(
        TagListUpdaterInterface $tagListUpdater,
        int $id
    ) {
        $this->tagListUpdater = $tagListUpdater;
        $this->id             = $id;
    }

    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->get_info($this->getId());
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    public function getYear(): int
    {
        return (int) ($this->getDbData()['year'] ?? 0);
    }

    public function getSummary(): string
    {
        return $this->getDbData()['summary'] ?? '';
    }

    public function getPrefix(): string
    {
        return $this->getDbData()['prefix'] ?? '';
    }

    public function getName(): string
    {
        return $this->getDbData()['name'] ?? '';
    }

    public function getLinkFormatted(): string
    {
        return sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getLink(),
            $this->getNameFormatted(),
            $this->getNameFormatted()
        );
    }

    /**
     * garbage_collection
     *
     * This cleans out unused tv shows
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `tvshow` USING `tvshow` LEFT JOIN `tvshow_season` ON `tvshow_season`.`tvshow` = `tvshow`.`id` " . "WHERE `tvshow_season`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * gets the tv show seasons id list
     */
    public function get_seasons(): array
    {
        if ($this->seasonIds === null) {
            $this->seasonIds = $this->getTvShowSeasonRepository()->getSeasonIdsByTvShowId($this->getId());
        }

        return $this->seasonIds;
    }

    /**
     * gets all episodes for this tv show
     */
    public function get_episodes()
    {
        return $this->getTvShowEpisodeRepository()->getEpisodeIdsByTvShow($this->getId());
    }

    /**
     * _get_extra info
     * This returns the extra information for the tv show, this means totals etc
     * @return array
     */
    private function getExtraInfo()
    {
        if ($this->extra_info === null) {
            $sql              = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_season` LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `tvshow_season`.`tvshow` = ? GROUP BY `catalog_id`";
            $db_results       = Dba::read($sql, array($this->id));
            $this->extra_info = Dba::fetch_assoc($db_results);
        }

        return $this->extra_info;
    }

    public function getCatalogId(): int
    {
        return (int) ($this->getExtraInfo()['catalog_id'] ?? 0);
    }

    public function getEpisodeCount(): int
    {
        return (int) ($this->getExtraInfo()['episode_count'] ?? 0);
    }

    public function getNameFormatted(): string
    {
        return trim($this->getPrefix() . " " . $this->getName());
    }

    /**
     * format
     * this function takes the object and formats some values
     * @param boolean $details
     */
    public function format($details = true)
    {
    }

    public function getLink(): string
    {
        return AmpConfig::get('web_path') . '/tvshows.php?action=show&tvshow=' . $this->getId();
    }

    public function getTags(): array
    {
        return Tag::get_top_tags('tvshow', $this->id);
    }

    public function getTagsFormatted(): string
    {
        return Tag::get_display($this->getTags(), true, 'tvshow');
    }

    /**
     * get_keywords
     * @return array|mixed
     */
    public function get_keywords()
    {
        $keywords           = array();
        $keywords['tvshow'] = array(
            'important' => true,
            'label' => T_('TV Show'),
            'value' => $this->getNameFormatted()
        );
        $keywords['type'] = array(
            'important' => false,
            'label' => null,
            'value' => 'tvshow'
        );

        return $keywords;
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->getNameFormatted();
    }

    /**
     * @return array{object_type: string, object_id: int}|null
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
        return ['tvshow_season' => $this->get_seasons()];
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return [];
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = [];
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
     * @return integer[]
     */
    public function get_catalogs()
    {
        return [$this->getCatalogId()];
    }

    /**
     * @return mixed|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return mixed
     */
    public function get_description()
    {
        return $this->getSummary();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'tvshow') || $force) {
            echo Art::display('tvshow', $this->id, $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * check
     *
     * Checks for an existing tv show; if none exists, insert one.
     * @param string $name
     * @param $year
     * @param $tvshow_summary
     * @param boolean $readonly
     * @return integer|string|null
     */
    public static function check($name, $year, $tvshow_summary, $readonly = false)
    {
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
            return $tvshow_id;
        }

        if ($readonly) {
            return null;
        }

        $sql        = 'INSERT INTO `tvshow` (`name`, `prefix`, `year`, `summary`) VALUES(?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($name, $prefix, $year, $tvshow_summary));
        if (!$db_results) {
            return null;
        }

        return Dba::insert_id();
    }

    /**
     * update
     * This takes a key'd array of data and updates the current tv show
     * @param array $data
     * @return integer|string|null
     */
    public function update(array $data)
    {
        // Save our current ID
        $current_id = $this->id;
        $name       = isset($data['name']) ? $data['name'] : $this->getName();
        $year       = isset($data['year']) ? $data['year'] : $this->getYear();
        $summary    = isset($data['summary']) ? $data['summary'] : $this->getSummary();

        // Check if name is different than current name
        if ($this->getName() != $name || $this->getYear() != $year) {
            $tvshow_id = self::check($name, $year, true);

            $tvShowSeasonRepository = $this->getTvShowSeasonRepository();

            // If it's changed we need to update
            if ($tvshow_id != $this->id && $tvshow_id != null) {
                $seasons = $this->get_seasons();
                foreach ($seasons as $season_id) {
                    $tvShowSeasonRepository->setTvShow($tvshow_id, $season_id);
                }
                $current_id = $tvshow_id;

                static::getDataMigrator()->migrate('tvshow', $this->id, (int)$tvshow_id);
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

        $override_childs = false;
        if ($data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if ($data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->update_tags($data['edit_tags'], $override_childs, $add_to_childs, true);
        }

        return $current_id;
    } // update

    /**
     * update_tags
     *
     * Update tags of tv shows
     * @param string $tags_comma
     * @param boolean $override_childs
     * @param boolean $add_to_childs
     * @param boolean $force_update
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $force_update = false)
    {
        $this->tagListUpdater->update($tags_comma, 'tvshow', $this->id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $episodes = $this->get_episodes();
            foreach ($episodes as $ep_id) {
                $this->tagListUpdater->update($tags_comma, 'episode', $ep_id, $override_childs);
            }
        }
    }

    public function remove(): bool
    {
        $deleted    = true;
        $season_ids = $this->get_seasons();

        $modelFactory = $this->getModelFactory();

        foreach ($season_ids as $season_object) {
            $season  = $modelFactory->createTvShowSeason($season_object);
            $deleted = $season->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the season `' . (string) $season_object . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
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

    /**
     * @deprecated Inject by constructor
     */
    private static function getDataMigrator(): DataMigratorInterface
    {
        global $dic;

        return $dic->get(DataMigratorInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getModelFactory(): ModelFactoryInterface
    {
        global $dic;

        return $dic->get(ModelFactoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getTvShowSeasonRepository(): TvShowSeasonRepositoryInterface
    {
        global $dic;

        return $dic->get(TvShowSeasonRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getTvShowEpisodeRepository(): TvShowEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(TvShowEpisodeRepositoryInterface::class);
    }
}
