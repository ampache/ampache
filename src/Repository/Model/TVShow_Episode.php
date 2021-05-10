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

final class TVShow_Episode extends Video implements TvShowEpisodeInterface
{
    protected const DB_TABLENAME = 'tvshow_episode';

    private ?string $filename = null;

    private ?TVShow_Season $tvShowSeason = null;

    /** @var array<string, mixed>|null */
    private ?array $dbData = null;

    public function __construct(int $id)
    {
        parent::__construct($id);

        $this->id = $id;
    }

    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->get_info($this->id);
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

    public function getSummary(): string
    {
        return $this->getDbData()['summary'] ?? '';
    }

    public function getEpisodeNumber(): int
    {
        return (int) ($this->getDbData()['episode_number'] ?? 0);
    }

    public function getOriginalName(): string
    {
        return $this->getDbData()['original_name'] ?? '';
    }

    /**
     * garbage_collection
     *
     * This cleans out unused tv shows episodes
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `tvshow_episode` USING `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * insert
     * Insert a new tv show episode and related entities.
     * @param array $data
     * @param array $gtypes
     * @param array $options
     * @return integer
     */
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        if (empty($data['tvshow'])) {
            $data['tvshow'] = T_('Unknown');
        }
        $tags = $data['genre'];

        $tvshow = TvShow::check($data['tvshow'], $data['year'], $data['tvshow_summary']);
        if ($options['gather_art'] && $tvshow && $data['tvshow_art'] && !Art::has_db((int)$tvshow, 'tvshow')) {
            $art = new Art((int)$tvshow, 'tvshow');
            $art->insert_url($data['tvshow_art']);
        }
        $tvshow_season = TVShow_Season::check($tvshow, $data['tvshow_season']);
        if ($options['gather_art'] && $tvshow_season && $data['tvshow_season_art'] && !Art::has_db($tvshow_season,
                'tvshow_season')) {
            $art = new Art($tvshow_season, 'tvshow_season');
            $art->insert_url($data['tvshow_season_art']);
        }

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string)$tag);
                if (!empty($tag)) {
                    Tag::add('tvshow_season', (int) $tvshow_season, $tag, false);
                    Tag::add('tvshow', (int) $tvshow, $tag, false);
                }
            }
        }

        $sdata = $data;
        // Replace relation name with db ids
        $sdata['tvshow']                      = $tvshow;
        $sdata['tvshow_season']               = $tvshow_season;

        return self::create($sdata);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new tv show episode entry, it returns the record id
     * @param array $data
     * @return integer
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `tvshow_episode` (`id`, `original_name`, `season`, `episode_number`, `summary`) " . "VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array(
            $data['id'],
            $data['original_name'],
            $data['tvshow_season'],
            $data['tvshow_episode'],
            $data['summary']
        ));

        return $data['id'];
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a tv show episode entry
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        parent::update($data);

        $original_name  = isset($data['original_name']) ? $data['original_name'] : $this->getOriginalName();
        $tvshow_season  = isset($data['tvshow_season']) ? $data['tvshow_season'] : $this->getSeasonId();
        $tvshow_episode = isset($data['tvshow_episode']) ? $data['tvshow_episode'] : $this->getEpisodeNumber();
        $summary        = isset($data['summary']) ? $data['summary'] : $this->getSummary();

        $sql = "UPDATE `tvshow_episode` SET `original_name` = ?, `season` = ?, `episode_number` = ?, `summary` = ? WHERE `id` = ?";
        Dba::write($sql, array($original_name, $tvshow_season, $tvshow_episode, $summary, $this->id));


        return $this->id;
    }

    /**
     * format
     * this function takes the object and formats some values
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        parent::format($details);

        $this->f_title       = ($this->getOriginalName()?: $this->f_title);

        return true;
    }

    public function getFullTitle(): string
    {
        return $this->getFilename();
    }

    public function getSeasonId(): int
    {
        return (int) ($this->getDbData()['season'] ?? 0);
    }

    public function getLinkFormatted(): string
    {
        return '<a href="' . $this->link . '">' . $this->f_title . '</a>';
    }

    public function getTVShowSeason(): TVShow_Season
    {
        if ($this->tvShowSeason === null) {
            $this->tvShowSeason = static::getModelFactory()->createTvShowSeason($this->getSeasonId());
        }

        return $this->tvShowSeason;
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords           = parent::get_keywords();
        $keywords['tvshow'] = array(
            'important' => true,
            'label' => T_('TV Show'),
            'value' => $this->getTVShowSeason()->getTvShow()->f_name
        );
        $keywords['tvshow_season'] = array(
            'important' => false,
            'label' => T_('Season'),
            'value' => $this->getTVShowSeason()->getNameFormatted()
        );
        if ($this->getEpisodeNumber()) {
            $keywords['tvshow_episode'] = array(
                'important' => false,
                'label' => T_('Episode'),
                'value' => $this->getEpisodeNumber()
            );
        }
        $keywords['type'] = array(
            'important' => false,
            'label' => null,
            'value' => 'tvshow'
        );

        return $keywords;
    }

    /**
     * get_parent
     * @return array
     */
    public function get_parent()
    {
        return array('object_type' => 'tvshow_season', 'object_id' => $this->getSeasonId());
    }

    /**
     * get_release_item_art
     * @return array
     */
    public function get_release_item_art()
    {
        return array(
            'object_type' => 'tvshow_season',
            'object_id' => $this->getSeasonId()
        );
    }

    /**
     * @return string
     */
    public function get_description()
    {
        $summary = $this->getSummary();
        if (!empty($summary)) {
            return $summary;
        }

        return $this->getTVShowSeason()->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $episode_id = null;
        $type       = null;

        if (Art::has_db($this->id, 'video')) {
            $episode_id = $this->id;
            $type       = 'video';
        } else {
            if (Art::has_db($this->getSeasonId(), 'tvshow_season')) {
                $episode_id = $this->getSeasonId();
                $type       = 'tvshow_season';
            } else {
                $season = $this->getTVShowSeason();
                if (Art::has_db($season->getTvShowId(), 'tvshow') || $force) {
                    $episode_id = $season->getTvShowId();
                    $type       = 'tvshow';
                }
            }
        }

        if ($episode_id !== null && $type !== null) {
            echo Art::display($type, $episode_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * Remove the video from disk.
     */
    public function remove()
    {
        $deleted = parent::remove();
        if ($deleted) {
            $sql     = "DELETE FROM `tvshow_episode` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
        }

        return $deleted;
    }

    public function getFilename(): string
    {
        if ($this->filename === null) {
            $this->filename = $this->getTVShowSeason()->getTvShow()->f_name;
            if ($this->getEpisodeNumber()) {
                $this->filename .= ' - S' . sprintf('%02d', $this->getTVShowSeason()->getSeasonNumber()) . 'E' . sprintf('%02d', $this->getEpisodeNumber());
            }
            $this->filename .= ' - ' . $this->f_title;
        }

        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getModelFactory(): ModelFactoryInterface
    {
        global $dic;

        return $dic->get(ModelFactoryInterface::class);
    }
}
