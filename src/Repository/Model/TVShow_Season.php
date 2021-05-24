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
use Ampache\Module\Video\VideoLoaderInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\TvShowSeasonRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final class TVShow_Season extends database_object implements TvShowSeasonInterface
{
    protected const DB_TABLENAME = 'tvshow_season';

    public int $id;

    private ShoutRepositoryInterface $shoutRepository;

    private UserActivityRepositoryInterface $userActivityRepository;

    private TvShowSeasonRepositoryInterface $tvShowSeasonRepository;

    private ModelFactoryInterface $modelFactory;

    private VideoLoaderInterface $videoLoader;

    private ?TvShow $tvShow = null;

    /** @var array<string, mixed>|null */
    private ?array $dbData = null;

    /** @var null|array{episode_count?: int, catalog_id?: int} */
    private ?array $extra_info = null;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository,
        UserActivityRepositoryInterface $userActivityRepository,
        TvShowSeasonRepositoryInterface $tvShowRepository,
        ModelFactoryInterface $modelFactory,
        VideoLoaderInterface $videoLoader,
        int $id
    ) {
        $this->shoutRepository        = $shoutRepository;
        $this->userActivityRepository = $userActivityRepository;
        $this->tvShowSeasonRepository = $tvShowRepository;
        $this->modelFactory           = $modelFactory;
        $this->videoLoader            = $videoLoader;
        $this->id                     = $id;
    }

    /**
     * @return array<string, mixed>
     */
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

    public function getTvShowId(): int
    {
        return (int) ($this->getDbData()['tvshow'] ?? 0);
    }

    public function getSeasonNumber(): int
    {
        return (int) ($this->getDbData()['season_number'] ?? 0);
    }

    /**
     * gets all episodes for this tv show season
     * @return int[]
     */
    public function getEpisodeIds(): array
    {
        return $this->tvShowSeasonRepository->getEpisodeIds($this->getId());
    }

    /**
     * format
     * this function takes the object and formats some values
     * @param boolean $details
     */
    public function format($details = true)
    {
    }

    /**
     * @return array{episode_count?: int, catalog_id?: int}
     */
    public function getExtraInfo(): array
    {
        if ($this->extra_info === null) {
            $this->extra_info = $this->tvShowSeasonRepository->getExtraInfo($this->getId());
        }

        return $this->extra_info;
    }

    public function getEpisodeCount(): int
    {
        return (int) ($this->getExtraInfo()['episode_count'] ?? 0);
    }

    public function getCatalogId(): int
    {
        return (int) ($this->getExtraInfo()['catalog_id'] ?? 0);
    }

    public function getLink(): string
    {
        return AmpConfig::get('web_path') . '/tvshow_seasons.php?action=show&season=' . $this->getId();
    }

    public function getLinkFormatted(): string
    {
        return sprintf(
            '<a href="%s" title="%s - %s">%s</a>',
            $this->getLink(),
            $this->getTvShow()->getNameFormatted(),
            $this->getNameFormatted(),
            $this->getNameFormatted()
        );
    }

    public function getNameFormatted(): string
    {
        return sprintf(
            '%s %d',
            T_('Season'),
            $this->getSeasonNumber()
        );
    }

    public function getTvShow(): TvShow
    {
        if ($this->tvShow === null) {
            $this->tvShow = $this->modelFactory->createTvShow($this->getTvShowId());
        }

        return $this->tvShow;
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
            'value' => $this->getTvShow()->getNameFormatted()
        );
        $keywords['tvshow_season'] = array(
            'important' => false,
            'label' => T_('Season'),
            'value' => $this->getSeasonNumber()
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
        return ['object_type' => 'tvshow', 'object_id' => $this->getTvShowId()];
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return ['tvshow_episode' => $this->getEpisodeIds()];
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * get_medias
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'video') {
            $episodes = $this->getEpisodeIds();
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
        return $this->getTvShow()->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $tvshow_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'tvshow_season')) {
            $tvshow_id = $this->id;
            $type      = 'tvshow_season';
        } else {
            if (Art::has_db($this->getTvShowId(), 'tvshow') || $force) {
                $tvshow_id = $this->getTvShowId();
                $type      = 'tvshow';
            }
        }

        if ($tvshow_id !== null && $type !== null) {
            echo Art::display($type, $tvshow_id, $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * check
     *
     * Checks for an existing tv show season; if none exists, insert one.
     */
    public static function check(int $tvshow, int $season_number): ?int
    {
        $tvShowSeasonRepository = static::getTvShowSeasonRepository();

        $seasonId = $tvShowSeasonRepository->findByTvShowAndSeasonNumber(
            $tvshow, $season_number
        );

        if ($seasonId !== null) {
            return $seasonId;
        }

        return $tvShowSeasonRepository->addSeason(
            $tvshow,
            $season_number
        );
    }

    /**
     * This takes a key'd array of data and updates the current tv show
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        $this->tvShowSeasonRepository->update(
            (int) $data['tvshow'],
            (int) $data['season_number'],
            $this->getId()
        );

        return $this->id;
    }

    public function remove(): bool
    {
        $deleted = true;
        $videos  = $this->getEpisodeIds();
        foreach ($videos as $video_id) {
            $video   = $this->videoLoader->load($video_id);
            $deleted = $video->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the video `' . $video_id . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $this->tvShowSeasonRepository->delete(
                $this->getId()
            );
            Art::garbage_collection('tvshow_season', $this->id);
            Userflag::garbage_collection('tvshow_season', $this->id);
            Rating::garbage_collection('tvshow_season', $this->id);
            $this->shoutRepository->collectGarbage('tvshow_season', $this->getId());
            $this->userActivityRepository->collectGarbage('tvshow_season', $this->getId());
        }

        return $deleted;
    }

    private static function getTvShowSeasonRepository(): TvShowSeasonRepositoryInterface
    {
        global $dic;

        return $dic->get(TvShowSeasonRepositoryInterface::class);
    }
}
