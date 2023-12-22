<?php

declare(strict_types=1);

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

namespace Ampache\Plugin;

/**
 * This class contains information about plugins
 */
final class PluginEnum
{
    public const LIST = [
        '7digital' => Ampache7digital::class,
        'amazon' => AmpacheAmazon::class,
        'bitly' => AmpacheBitly::class,
        'catalogfavorites' => AmpacheCatalogFavorites::class,
        'chartlyrics' => Ampachechartlyrics::class,
        'discogs' => AmpacheDiscogs::class,
        'facebook' => AmpacheFacebook::class,
        'flattr' => AmpacheFlattr::class,
        'flickr' => Ampacheflickr::class,
        'friendstimeline' => AmpacheFriendsTimeline::class,
        'googleanalytics' => AmpacheGoogleAnalytics::class,
        'googlemaps' => AmpacheGoogleMaps::class,
        'gravatar' => AmpacheGravatar::class,
        'headphones' => AmpacheHeadphones::class,
        'lastfm' => AmpacheLastfm::class,
        'libravatar' => AmpacheLibravatar::class,
        'librefm' => Ampachelibrefm::class,
        'listenbrainz' => Ampachelistenbrainz::class,
        'lyrist' => AmpacheLyristLyrics::class,
        'matomo' => AmpacheMatomo::class,
        'musicbrainz' => AmpacheMusicBrainz::class,
        'omdb' => AmpacheOmdb::class,
        'paypal' => AmpachePaypal::class,
        'piwik' => AmpachePiwik::class,
        'rssview' => AmpacheRSSView::class,
        'shouthome' => AmpacheShoutHome::class,
        'streambandwidth' => AmpacheStreamBandwidth::class,
        'streamhits' => AmpacheStreamHits::class,
        'streamtime' => AmpacheStreamTime::class,
        'theaudiodb' => AmpacheTheaudiodb::class,
        'tvdb' => AmpacheTvdb::class,
        'twitter' => AmpacheTwitter::class,
        'yourls' => AmpacheYourls::class,
        'personalfav_display' => AmpachePersonalFavorites::class,
        'ratingmatch' => AmpacheRatingMatch::class,
    ];
}
