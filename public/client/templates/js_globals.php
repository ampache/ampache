<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Repository\Model\Preference;

global $dic;
$web_path         = AmpConfig::get_web_path('/client');
$ajaxUriRetriever = $dic->get(AjaxUriRetrieverInterface::class);
?>

<script>
    // Using the following workaround to set global variable available from any javascript script.

    // AmpConfig values
    var jsAmpConfigAjaxLoad = "<?php echo make_bool(AmpConfig::get('ajax_load')); ?>";
    var jsAmpConfigCookieSecure = "<?php echo make_bool(AmpConfig::get('cookie_secure')); ?>";
    var jsAmpConfigGeolocation = "<?php echo make_bool(AmpConfig::get('geolocation')); ?>";
    var jsAmpConfigLibitemContextmenu = "<?php echo make_bool(AmpConfig::get('libitem_contextmenu')); ?>";
    var jsAmpConfigPlayType = "<?php echo AmpConfig::get('play_type'); ?>";
    var jsAmpConfigSlideshowTime = "<?php echo make_bool(AmpConfig::get('slideshow_time')); ?>";
    var jsAmpConfigSidebarHideSwitcher = "<?php echo make_bool(AmpConfig::get('sidebar_hide_switcher', false)); ?>";

    // Preferences
    var jsPrefExistsFlickrApiKey = "<?php echo Preference::exists('flickr_api_key'); ?>";

    // Misc
    var jsAjaxUrl = "<?php echo $ajaxUriRetriever->getAjaxUri(); ?>";
    var jsWebPath = "<?php echo $web_path; ?>";
    var jsAjaxServer = "<?php echo $ajaxUriRetriever->getAjaxServerUri(); ?>";
    var jsSiteTitle = "<?php echo addslashes(AmpConfig::get('site_title', '')); ?>";
    var jsCookieString = jsAmpConfigCookieSecure ? "expires: 30, path: '/', secure: true, samesite: 'Strict'" : "expires: 30, path: '/', samesite: 'Strict'";
    var jsBasketCount = 0; // updated in rightbar.inc.php after ajax load

    // Strings
    var jsHomeTitle = "<?php echo addslashes(T_('Home')); ?>";
    var jsUploadTitle = "<?php echo addslashes(T_('Upload')); ?>";
    var jsLocalplayTitle = "<?php echo addslashes(T_('Localplay')); ?>";
    var jsRandomTitle = "<?php echo addslashes(T_('Random Play')); ?>";
    var jsPlaylistTitle = "<?php echo addslashes(T_('Playlist')); ?>";
    var jsSmartPlaylistTitle = "<?php echo addslashes(T_('Smart Playlist')); ?>";
    var jsSearchTitle = "<?php echo addslashes(T_('Search')); ?>";
    var jsPreferencesTitle = "<?php echo addslashes(T_('Preferences')); ?>";
    var jsAdminCatalogTitle = "<?php echo addslashes(T_('Catalogs')); ?>";
    var jsAdminUserTitle = "<?php echo addslashes(T_('User Tools')); ?>";
    var jsAdminMailTitle = "<?php echo addslashes(T_('E-mail Users')); ?>";
    var jsAdminManageAccessTitle = "<?php echo addslashes(T_('Access Control')); ?>";
    var jsAdminPreferencesTitle = "<?php echo addslashes(T_('Server Config')); ?>";
    var jsAdminManageModulesTitle = "<?php echo addslashes(T_('Modules')); ?>";
    var jsAdminLicenseTitle = "<?php echo addslashes(T_('Media Licenses')); ?>";
    var jsAdminFilterTitle = "<?php echo addslashes(T_('Catalog Filters')); ?>";
    var jsBrowseMusicTitle = "<?php echo addslashes(T_('Browse')); ?>";
    var jsAlbumTitle = "<?php echo addslashes(T_('Album')); ?>";
    var jsArtistTitle = "<?php echo addslashes(T_('Artist')); ?>";
    var jsStatisticsTitle = "<?php echo addslashes(T_('Statistics')); ?>";
    var jsSongTitle = "<?php echo addslashes(T_('Song')); ?>";
    var jsDemocraticTitle = "<?php echo addslashes(T_('Democratic')); ?>";
    var jsLabelsTitle = "<?php echo addslashes(T_('Labels')); ?>";
    var jsDashboardTitle = "<?php echo addslashes(T_('Dashboards')); ?>";
    var jsPodcastTitle = "<?php echo addslashes(T_('Podcast')); ?>";
    var jsPodcastEpisodeTitle = "<?php echo addslashes(T_('Podcast Episode')); ?>";
    var jsRadioTitle = "<?php echo addslashes(T_('Radio Stations')); ?>";
    var jsVideoTitle = "<?php echo addslashes(T_('Video')); ?>";
    var jsSaveTitle = "<?php echo addslashes(T_('Save')); ?>";
    var jsCancelTitle = "<?php echo addslashes(T_('Cancel')); ?>";
    var jsPlay = "<?php echo addslashes(T_('Play')); ?>";
    var jsPlayNext = "<?php echo addslashes(T_('Play next')); ?>";
    var jsPlayLast = "<?php echo addslashes(T_('Play last')); ?>";
    var jsAddTmpPlaylist = "<?php echo addslashes(T_('Add to Temporary Playlist')); ?>";
    var jsAddPlaylist = "<?php echo addslashes(T_('Add to playlist')); ?>";
</script>
