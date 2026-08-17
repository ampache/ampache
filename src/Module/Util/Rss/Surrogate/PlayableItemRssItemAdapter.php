<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Util\Rss\Surrogate;

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Art;
use Ampache\Module\Util\Rss\EnclosureResolver;
use Ampache\Module\Util\Rss\PodcastGuid;
use Ampache\Module\Util\Rss\RssUrl;
use Ampache\Repository\Model\container_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Generator;

/**
 * Abstraction layer for creating rss/podcasts from playable-items
 */
final readonly class PlayableItemRssItemAdapter implements RssItemInterface
{
    public function __construct(
        private LibraryItemLoaderInterface $libraryItemLoader,
        private ModelFactoryInterface $modelFactory,
        private library_item $playable,
        private ?User $user,
    ) {}

    /**
     * Returns the itunes author of the item
     */
    public function getAuthor(): string
    {
        $author = ($this->playable instanceof container_item)
            ? $this->playable->get_parent_fullname()
            : '';

        return ($author !== '')
            ? $author
            : $this->getTitle();
    }

    /**
     * Returns the itunes category of the item
     * https://www.rssboard.org/rss-validator/docs/error/InvalidItunesCategory.html
     */
    public function getCategory(): string
    {
        return 'Music';
    }

    /**
     * itunes:explicit, required by directories. Ampache has no per-item rating so it comes from the config
     */
    public function getExplicit(): string
    {
        return (AmpConfig::get('rss_explicit', false))
            ? 'true'
            : 'false';
    }

    /**
     * Returns the items image-url; Art falls back to the placeholder when the item has no art
     */
    public function getImageUrl(): string
    {
        $type = $this->playable->getMediaType()->value;

        // directories require 1400px artwork, so ask for the thumbnail that size unless upscaling is disabled
        $thumb = (AmpConfig::get('upscale_images', true))
            ? 700
            : null;

        return Art::url($this->playable->getId(), $type, null, $thumb)
            ?? Art::get_fallback_url($type, 'original');
    }

    /**
     * RSS channel language, a lowercase ISO 639 code with an optional region modifier (e.g. "en-us")
     * https://help.apple.com/itc/podcasts_connect/#/itcb54353390
     */
    public function getLanguage(): string
    {
        return strtolower(str_replace('_', '-', (string) AmpConfig::get('lang', 'en_US')));
    }

    /**
     * Returns a link to the item
     */
    public function getLink(): string
    {
        return $this->playable->get_link();
    }

    /**
     * Returns all media-items which are associated with the item
     *
     * @return Generator<array{
     *     title: string,
     *     guid: string,
     *     isPermaLink: string,
     *     link: string,
     *     description: string,
     *     length: string,
     *     author: null|string,
     *     pubDate: null|string,
     *     type: null|string,
     *     size: null|string,
     *     url: null|string,
     *     season: null|string,
     *     season_name: null|string,
     *     episode: null|string,
     *     image: string,
     *     explicit: string
     * }>
     */
    public function getMedias(): Generator
    {
        if (!$this->playable instanceof container_item) {
            return;
        }

        foreach ($this->playable->get_medias() as $media_info) {
            /** @var Song|Podcast_Episode|null $media */
            $media = $this->libraryItemLoader->load(
                $media_info['object_type'],
                $media_info['object_id'],
                [Song::class, Podcast_Episode::class]
            );

            if ($media === null) {
                continue;
            }

            $data = [
                'title' => (string) $media->get_fullname(),
                'guid' => (isset($media->mbid))
                    ? 'https://musicbrainz.org/recording/' . $media->mbid
                    : $media->get_link(),
                'isPermaLink' => 'true',
                'link' => $media->get_link(),
                'description' => ($media instanceof Song)
                    ? $media->get_fullname() . ' - ' . $media->get_album_fullname($media->album, true) . ' - ' . $media->get_parent_fullname()
                    : $media->get_description(),
                'length' => $media->get_f_time(),
                'author' => $media->get_parent_fullname(),
                'image' => $this->getMediaImageUrl($media),
                'explicit' => $this->getExplicit(),
                'pubDate' => null,
                'type' => null,
                'size' => null,
                'url' => null,
                'season' => null,
                'season_name' => null,
                'episode' => null,
            ];

            // Group songs by their album (podcast namespace season/episode)
            if ($media instanceof Song && $media->album > 0) {
                $data['season']      = (string) $media->album;
                $data['season_name'] = $media->get_album_fullname();
                if (($media->track ?? 0) > 0) {
                    $data['episode'] = (string) $media->track;
                }
            }

            if ($media->addition_time > 0) {
                $data['pubDate'] = date("r", $media->addition_time);
            }

            if ($media->mime) {
                [$stream_params, $data['type'], $data['size']] = EnclosureResolver::target($media);
                $data['url']                                   = EnclosureResolver::url($media, $this->user, $stream_params);
            }

            yield $data;
        }
    }

    /**
     * Returns the name of the owner
     */
    public function getOwnerName(): string
    {
        $user = $this->modelFactory->createUser(
            (int) $this->playable->get_user_owner()
        );

        return (string) $user->get_fullname();
    }

    /**
     * podcast:guid of this feed (UUIDv5 of its canonical token-less url)
     */
    public function getPodcastGuid(): string
    {
        // the query form identifies a feed whatever url shape it is served under
        $params = RssUrl::currentQueryParams();
        unset($params['rsstoken']);

        return PodcastGuid::fromFeedUrl(RssUrl::canonical($params));
    }

    /**
     * Returns a link to the feed url
     */
    public function getRssLink(): string
    {
        return RssUrl::published(RssUrl::currentQueryParams(), $this->getTitle());
    }

    /**
     * Apple sub-category, empty unless the admin picked one (Music Commentary, Music History, Music Interviews)
     */
    public function getSubCategory(): string
    {
        return (string) AmpConfig::get('rss_subcategory', '');
    }

    /**
     * Returns the items summary/description text
     */
    public function getSummary(): string
    {
        $summary = $this->playable->get_description();

        return ($summary !== '')
            ? $summary
            : sprintf(T_('%1$s on %2$s'), $this->getTitle(), (string) AmpConfig::get('site_title'));
    }

    /**
     * Returns the item title
     */
    public function getTitle(): string
    {
        return (string) $this->playable->get_fullname();
    }

    /**
     * Returns `true` if an item-owner is set
     */
    public function hasOwner(): bool
    {
        return ($this->playable->get_user_owner() ?? 0) > 0;
    }

    /**
     * Art of a single episode, its own if it has any, the feed art otherwise
     */
    private function getMediaImageUrl(Song|Podcast_Episode $media): string
    {
        $type = $media->getMediaType()->value;

        return ($media->has_art())
            ? (Art::url($media->getId(), $type, null, 700) ?? $this->getImageUrl())
            : $this->getImageUrl();
    }
}
