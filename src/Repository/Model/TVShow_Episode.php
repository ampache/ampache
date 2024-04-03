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

class TVShow_Episode extends Video
{
    protected const DB_TABLENAME = 'tvshow_episode';

    public ?string $original_name = null;

    public int $season;

    public int $episode_number;

    public ?string $summary = null;

    public $f_link;

    public $f_season;

    public $f_season_link;

    public $f_tvshow;

    public $f_tvshow_link;

    /**
     * Constructor
     * This pulls the tv show episode information from the database and returns
     * a constructed object
     * @param int|null $episode_id
     */
    public function __construct($episode_id = 0)
    {
        if (!$episode_id) {
            return;
        }

        parent::__construct($episode_id);

        $info = $this->get_info($episode_id, static::DB_TABLENAME);
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
     * garbage_collection
     *
     * This cleans out unused tv shows episodes
     */
    public static function garbage_collection(): void
    {
        $sql = "DELETE FROM `tvshow_episode` USING `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * insert
     * Insert a new tv show episode and related entities.
     */
    public static function insert(array $data, ?array $gtypes = [], ?array $options = []): int
    {
        if (empty($data['tvshow'])) {
            $data['tvshow'] = T_('Unknown');
        }

        $tags = $data['genre'];

        $tvshow = TvShow::check($data['tvshow'], $data['year'], $data['tvshow_summary']);
        if ($options !== null && $options !== [] && $options['gather_art'] && $tvshow && $data['tvshow_art'] && !Art::has_db((int)$tvshow, 'tvshow')) {
            $art = new Art((int)$tvshow, 'tvshow');
            $art->insert_url($data['tvshow_art']);
        }

        $tvshow_season = TVShow_Season::check($tvshow, $data['tvshow_season']);
        if ($options !== null && $options !== [] && $options['gather_art'] && $tvshow_season && $data['tvshow_season_art'] && !Art::has_db($tvshow_season, 'tvshow_season')) {
            $art = new Art($tvshow_season, 'tvshow_season');
            $art->insert_url($data['tvshow_season_art']);
        }

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string)$tag);
                if ($tag !== '' && $tag !== '0') {
                    Tag::add('tvshow_season', (int) $tvshow_season, $tag, false);
                    Tag::add('tvshow', (int) $tvshow, $tag, false);
                }
            }
        }

        $sdata = $data;
        // Replace relation name with db ids
        $sdata['tvshow']        = $tvshow;
        $sdata['tvshow_season'] = $tvshow_season;

        return self::create($sdata);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new tv show episode entry, it returns the record id
     * @param array $data
     */
    public static function create($data): int
    {
        $sql = "INSERT INTO `tvshow_episode` (`id`, `original_name`, `season`, `episode_number`, `summary`) VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, [$data['id'], $data['original_name'], $data['tvshow_season'], $data['tvshow_episode'], $data['summary']]);

        return $data['id'];
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a tv show episode entry
     */
    public function update(array $data): int
    {
        parent::update($data);

        $original_name  = $data['original_name'] ?? $this->original_name;
        $tvshow_season  = $data['tvshow_season'] ?? $this->season;
        $tvshow_episode = $data['tvshow_episode'] ?? $this->episode_number;
        $summary        = $data['summary'] ?? null;

        $sql = "UPDATE `tvshow_episode` SET `original_name` = ?, `season` = ?, `episode_number` = ?, `summary` = ? WHERE `id` = ?";
        Dba::write($sql, [$original_name, $tvshow_season, $tvshow_episode, $summary, $this->id]);

        $this->original_name  = $original_name;
        $this->season         = $tvshow_season;
        $this->episode_number = $tvshow_episode;
        $this->summary        = $summary;

        return $this->id;
    }

    /**
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */
    public function format($details = true): void
    {
        parent::format($details);

        $season = new TVShow_Season($this->season);
        $season->format($details);

        $this->f_name        = ($this->original_name ?? $this->f_name);
        $this->f_link        = '<a href="' . $this->get_link() . '">' . scrub_out($this->f_name) . '</a>';
        $this->f_season      = $season->f_name;
        $this->f_season_link = $season->f_link;
        $this->f_tvshow      = $season->f_tvshow;
        $this->f_tvshow_link = $season->f_tvshow_link;
    }

    public function getFileName(): string
    {
        $season = new TVShow_Season($this->season);
        $season->format();

        $value = $season->f_tvshow;
        if ($this->episode_number !== 0) {
            $value .= ' - S' . sprintf('%02d', $season->season_number) . 'E' . sprintf('%02d', $this->episode_number);
        }

        return sprintf('%s - %s', $value, $this->original_name ?? '');
    }

    /**
     * Get item keywords for metadata searches.
     */
    public function get_keywords(): array
    {
        $keywords                  = parent::get_keywords();
        $keywords['tvshow']        = ['important' => true, 'label' => T_('TV Show'), 'value' => $this->f_tvshow];
        $keywords['tvshow_season'] = ['important' => false, 'label' => T_('Season'), 'value' => $this->f_season];
        if ($this->episode_number !== 0) {
            $keywords['tvshow_episode'] = ['important' => false, 'label' => T_('Episode'), 'value' => $this->episode_number];
        }

        $keywords['type'] = ['important' => false, 'label' => null, 'value' => 'tvshow'];

        return $keywords;
    }

    /**
     * @return array{object_type: LibraryItemEnum, object_id: int}
     */
    public function get_parent(): ?array
    {
        return [
            'object_type' => LibraryItemEnum::TV_SHOW_SEASON,
            'object_id' => $this->season
        ];
    }

    /**
     * get_release_item_art
     */
    public function get_release_item_art(): array
    {
        return [
            'object_type' => 'tvshow_season',
            'object_id' => $this->season
        ];
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        if (isset($this->summary) && ($this->summary !== null && $this->summary !== '' && $this->summary !== '0')) {
            return $this->summary;
        }

        $season = new TVShow_Season($this->season);

        return $season->get_description();
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        $episode_id = null;
        $type       = null;

        if (Art::has_db($this->id, 'video')) {
            $episode_id = $this->id;
            $type       = 'video';
        } elseif (Art::has_db($this->season, 'tvshow_season')) {
            $episode_id = $this->season;
            $type       = 'tvshow_season';
        } else {
            $season = new TVShow_Season($this->season);
            if (Art::has_db($season->tvshow, 'tvshow') || $force) {
                $episode_id = $season->tvshow;
                $type       = 'tvshow';
            }
        }

        if ($episode_id !== null && $type !== null) {
            Art::display($type, $episode_id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $deleted = parent::remove();
        if ($deleted) {
            $sql     = "DELETE FROM `tvshow_episode` WHERE `id` = ?";
            $deleted = (Dba::write($sql, [$this->id]) !== false);
        }

        return $deleted;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::TV_SHOW_EPISODE;
    }
}
