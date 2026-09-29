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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;

/**
 * An ordered list of songs as a page someone else's site can frame.
 *
 * One song, an album or a playlist are the same thing here: a list. Deliberately free of the site's
 * stylesheets and scripts, because it is served into a stranger's page -- no session, no jQuery, no
 * navigation, only the art, the names and the streams.
 */
final class MediaEmbedView extends AbstractView
{
    public const int HEIGHT_LIST = 340;

    public const int HEIGHT_SINGLE = 128;
    /** The dimensions announced to the scrapers, and what the markup is built for */
    public const int WIDTH = 420;

    /** @var ?list<array{title: string, artist: string, time: string, url: string}> */
    private ?array $tracks = null;

    /**
     * @param list<Song> $songs in the order they should play
     */
    public function __construct(
        private readonly string $title,
        private readonly string $subtitle,
        private readonly string $artUrl,
        private readonly string $pageUrl,
        private readonly array $songs,
    ) {}

    /**
     * The height to announce for a list of this length, so the frame fits what it holds.
     */
    public static function heightFor(int $trackCount): int
    {
        return ($trackCount > 1) ? self::HEIGHT_LIST : self::HEIGHT_SINGLE;
    }

    /**
     * Whether a stranger's browser could play this at all.
     *
     * `Stream::get_base_url()` puts the listener's session id in the url when the instance requires one,
     * and that url is useless to anyone else and must not be published into someone else's page. So the
     * embed is only offered where streaming works without a session.
     */
    public static function isAvailable(): bool
    {
        return !AmpConfig::get('use_auth')
            || !AmpConfig::get('require_session');
    }

    public function getArtUrl(): string
    {
        return $this->artUrl;
    }

    public function getDocumentLanguage(): string
    {
        return str_replace('_', '-', (string) AmpConfig::get('lang', 'en_US'));
    }

    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * The playable songs, each with the stream url an anonymous listener gets.
     *
     * @return list<array{title: string, artist: string, time: string, url: string}>
     */
    public function getTracks(): array
    {
        if ($this->tracks === null) {
            $tracks = [];
            foreach ($this->songs as $song) {
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }

                $tracks[] = [
                    'title' => (string) $song->get_fullname(),
                    'artist' => (string) $song->get_parent_fullname(),
                    'time' => (string) $song->get_f_time(),
                    // `webplayer` because that is what this is: a browser playing it
                    'url' => $song->play_url('', 'webplayer', false, User::INTERNAL_SYSTEM_USER_ID),
                ];
            }

            $this->tracks = $tracks;
        }

        return $this->tracks;
    }

    /**
     * A single track needs no list to choose from, and says so to the template and to `heightFor()`.
     */
    public function isSingle(): bool
    {
        return count($this->getTracks()) < 2;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/media_embed.phtml');
    }
}
