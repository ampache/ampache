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
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Podcast\Exception\PodcastFeedLoadingException;
use Ampache\Module\Podcast\PodcastFeedLoaderInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;

class Podcast extends database_object implements library_item, PodcastInterface
{
    protected const DB_TABLENAME = 'podcast';

    /* Variables from DB */
    public $id;

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * Takes the ID of the podcast and pulls the info from the db
     * @param integer $podcast_id
     */
    public function __construct($podcast_id = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if (!$podcast_id) {
            return false;
        }

        /* Get the information from the db */
        $this->data = $this->get_info($podcast_id);

        $this->id = (int) $this->data['id'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function get_catalogs()
    {
        return [$this->getCatalog()];
    }

    /**
     * format
     * this function takes the object and reformats some values
     * @param boolean $details
     * @return boolean
     *
     * @deprecated Not in use
     */
    public function format($details = true)
    {
        return true;
    }

    public function getEpisodeCount(): int
    {
        $cache = static::getDatabaseObjectCache();
        // Try to find it in the cache and save ourselves the trouble
        $amount = $cache->retrieve('podcast_extra', $this->getId())['amount'] ?? null;
        if ($amount !== null) {
            return (int) $amount;
        } else {
            $amount = static::getPodcastEpisodeRepository()->getEpisodeCount($this);

            $cache->add(
                'podcast_extra',
                $this->getId(),
                ['amount' => $amount]
            );

            return $amount;
        }
    }

    public function getFeed(): string
    {
        return (string) ($this->data['feed'] ?? '');
    }

    public function getTitle(): string
    {
        return (string) ($this->data['title'] ?? '');
    }

    public function getWebsite(): string
    {
        return (string) ($this->data['website'] ?? '');
    }

    public function getDescription(): string
    {
        return (string) ($this->data['description'] ?? '');
    }

    public function getLanguage(): string
    {
        return (string) ($this->data['language'] ?? '');
    }

    public function getGenerator(): string
    {
        return (string) ($this->data['generator'] ?? '');
    }

    public function getCopyright(): string
    {
        return (string) ($this->data['copyright'] ?? '');
    }

    public function getLink(): string
    {
        return sprintf(
            '%s/podcast.php?action=show&podcast=%d',
            AmpConfig::get('web_path'),
            $this->getId()
        );
    }

    public function getLinkFormatted(): string
    {
        $title = $this->getTitleFormatted();

        return sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getLink(),
            $title,
            $title
        );
    }

    public function getCatalog(): int
    {
        return (int) ($this->data['catalog'] ?? 0);
    }

    public function getTitleFormatted(): string
    {
        return scrub_out($this->getTitle());
    }

    public function getDescriptionFormatted(): string
    {
        return scrub_out($this->getDescription());
    }

    public function getLanguageFormatted(): string
    {
        return scrub_out($this->getLanguage());
    }

    public function getCopyrightFormatted(): string
    {
        return scrub_out($this->getCopyright());
    }

    public function getGeneratorFormatted(): string
    {
        return scrub_out($this->getGenerator());
    }

    public function getWebsiteFormatted(): string
    {
        return scrub_out($this->getWebsite());
    }

    public function getLastSync(): int
    {
        return (int) ($this->data['lastsync'] ?? 0);
    }

    public function getLastSyncFormatted(): string
    {
        return get_datetime($this->getLastSync());
    }

    public function getLastBuildDate(): int
    {
        return (int) ($this->data['lastbuilddate'] ?? 0);
    }

    public function getLastBuildDateFormatted(): string
    {
        return get_datetime($this->getLastBuildDate());
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array(
            'important' => true,
            'label' => T_('Podcast'),
            'value' => $this->getTitleFormatted()
        );

        return $keywords;
    }

    /**
     * get_fullname
     *
     * @return string
     */
    public function get_fullname()
    {
        return $this->getTitleFormatted();
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return [
            'podcast_episode' => $this->getPodcastEpisodeRepository()->getEpisodeIds($this)
        ];
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
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'podcast_episode') {
            $episodes = $this->getPodcastEpisodeRepository()->getEpisodeIds($this);
            foreach ($episodes as $episode_id) {
                $medias[] = array(
                    'object_type' => 'podcast_episode',
                    'object_id' => $episode_id
                );
            }
        }

        return $medias;
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
     * get_description
     * @return string
     */
    public function get_description()
    {
        return $this->getDescriptionFormatted();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'podcast') || $force) {
            echo Art::display('podcast', $this->id, $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        $feed = $data['feed'] ?? $this->getFeed();

        try {
            $this->getPodcastFeedLoader()->load($feed);
        } catch (PodcastFeedLoadingException $e) {
            debug_event(self::class, 'Podcast update canceled, bad feed url.', 1);

            return $this->getId();
        }

        $title       = isset($data['title']) ? scrub_in($data['title']) : $this->getTitle();
        $website     = isset($data['website']) ? scrub_in($data['website']) : $this->getWebsite();
        $description = isset($data['description']) ? scrub_in($data['description']) : $this->getDescription();
        $generator   = isset($data['generator']) ? scrub_in($data['generator']) : $this->getGenerator();
        $copyright   = isset($data['copyright']) ? scrub_in($data['copyright']) : $this->getCopyright();

        $this->getPodastRepository()->update(
            $this->getId(),
            $feed,
            $title,
            $website,
            $description,
            $generator,
            $copyright
        );

        $this->feed        = $feed;
        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->generator   = $generator;
        $this->copyright   = $copyright;

        return $this->id;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeRepository(): PodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastFeedLoader(): PodcastFeedLoaderInterface
    {
        global $dic;

        return $dic->get(PodcastFeedLoaderInterface::class);
    }
}
