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

namespace Ampache\Plugin;

use ReflectionClass;

/**
 * This class contains information about plugins
 */
final class PluginEnum
{
    public const array LIST = [
        'amazon' => AmpacheAmazon::class,
        'audiomuse' => AmpacheAudioMuse::class,
        'bitly' => AmpacheBitly::class,
        'bluesky' => AmpacheBluesky::class,
        'catalogfavorites' => AmpacheCatalogFavorites::class,
        'chartlyrics' => Ampachechartlyrics::class,
        'deezer' => AmpacheDeezer::class,
        'discogs' => AmpacheDiscogs::class,
        'facebook' => AmpacheFacebook::class,
        'flickr' => Ampacheflickr::class,
        'friendstimeline' => AmpacheFriendsTimeline::class,
        'googleanalytics' => AmpacheGoogleAnalytics::class,
        'googlemaps' => AmpacheGoogleMaps::class,
        'gravatar' => AmpacheGravatar::class,
        'headphones' => AmpacheHeadphones::class,
        'homedashboard' => AmpacheHomeDashboard::class,
        'itunes' => AmpacheItunes::class,
        'lastfm' => AmpacheLastfm::class,
        'libravatar' => AmpacheLibravatar::class,
        'librefm' => Ampachelibrefm::class,
        'listenbrainz' => Ampachelistenbrainz::class,
        'lrclib' => AmpacheLrcLib::class,
        'lyrist' => AmpacheLyristLyrics::class,
        'mastodon' => AmpacheMastodon::class,
        'matomo' => AmpacheMatomo::class,
        'musicbrainz' => AmpacheMusicBrainz::class,
        'paypal' => AmpachePaypal::class,
        'piwik' => AmpachePiwik::class,
        'rssview' => AmpacheRSSView::class,
        'shouthome' => AmpacheShoutHome::class,
        'streambandwidth' => AmpacheStreamBandwidth::class,
        'streamhits' => AmpacheStreamHits::class,
        'streamtime' => AmpacheStreamTime::class,
        'theaudiodb' => AmpacheTheaudiodb::class,
        'twitter' => AmpacheTwitter::class,
        'yourls' => AmpacheYourls::class,
        'personalfav_display' => AmpachePersonalFavorites::class,
        'ratingmatch' => AmpacheRatingMatch::class,
    ];

    /**
     * The plugin a stored name belongs to, whether that name is a key here or the one an operator reads.
     *
     * A preference files itself under the plugin's own `$name`, which carries spaces and dots and is not
     * always the key: twelve of them differ, so looking a plugin up by key alone silently finds nothing.
     *
     * @return class-string<AmpachePluginInterface>|null
     */
    public static function resolve(string $name): ?string
    {
        $key = strtolower($name);

        return self::LIST[$key] ?? self::byDisplayName()[$key] ?? null;
    }

    /**
     * Built from what each plugin declares rather than from a second list that would drift away from it.
     *
     * @return array<string, class-string<AmpachePluginInterface>>
     */
    private static function byDisplayName(): array
    {
        static $names = null;
        if ($names === null) {
            $names = [];
            foreach (self::LIST as $class) {
                $declared = new ReflectionClass($class)->getDefaultProperties()['name'] ?? null;
                if (is_string($declared) && $declared !== '') {
                    $names[strtolower($declared)] = $class;
                }
            }
        }

        return $names;
    }
}
