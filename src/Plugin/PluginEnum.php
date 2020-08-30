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

declare(strict_types=1);

namespace Ampache\Plugin;

use Ampache\Plugin\Ampache7digital;
use Ampache\Plugin\AmpacheAmazon;
use Ampache\Plugin\AmpacheBitly;
use Ampache\Plugin\AmpacheCatalogFavorites;
use Ampache\Plugin\Ampachechartlyrics;
use Ampache\Plugin\AmpacheDiscogs;
use Ampache\Plugin\AmpacheFacebook;
use Ampache\Plugin\AmpacheFlattr;
use Ampache\Plugin\Ampacheflickr;
use Ampache\Plugin\AmpacheFriendsTimeline;
use Ampache\Plugin\AmpacheGoogleAnalytics;
use Ampache\Plugin\AmpacheGoogleMaps;
use Ampache\Plugin\AmpacheGravatar;
use Ampache\Plugin\AmpacheHeadphones;
use Ampache\Plugin\AmpacheLastfm;
use Ampache\Plugin\AmpacheLibravatar;
use Ampache\Plugin\Ampachelibrefm;
use Ampache\Plugin\Ampachelistenbrainz;
use Ampache\Plugin\Ampachelyricwiki;
use Ampache\Plugin\AmpacheMatomo;
use Ampache\Plugin\AmpacheMusicBrainz;
use Ampache\Plugin\AmpacheOmdb;
use Ampache\Plugin\AmpachePaypal;
use Ampache\Plugin\AmpachePiwik;
use Ampache\Plugin\AmpacheRSSView;
use Ampache\Plugin\AmpacheShoutHome;
use Ampache\Plugin\AmpacheStreamBandwidth;
use Ampache\Plugin\AmpacheStreamHits;
use Ampache\Plugin\AmpacheStreamTime;
use Ampache\Plugin\AmpacheTheaudiodb;
use Ampache\Plugin\AmpacheTmdb;
use Ampache\Plugin\AmpacheTvdb;
use Ampache\Plugin\AmpacheTwitter;
use Ampache\Plugin\AmpacheYourls;

/**
 * This class contains informations about plugins
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
        'lyricwiki' => Ampachelyricwiki::class,
        'matomo' => AmpacheMatomo::class,
        'musicbrains' => AmpacheMusicBrainz::class,
        'omdb' => AmpacheOmdb::class,
        'paypal' => AmpachePaypal::class,
        'piwik' => AmpachePiwik::class,
        'rssview' => AmpacheRSSView::class,
        'shouthome' => AmpacheShoutHome::class,
        'streambandwith' => AmpacheStreamBandwidth::class,
        'streamhits' => AmpacheStreamHits::class,
        'streamtime' => AmpacheStreamTime::class,
        'theaudiodb' => AmpacheTheaudiodb::class,
        'tmdb' => AmpacheTmdb::class,
        'tvdb' => AmpacheTvdb::class,
        'twitter' => AmpacheTwitter::class,
        'yourls' => AmpacheYourls::class,
    ];
}
