<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Application;

use Ampache\Application\Admin\AccessApplication;
use Ampache\Application\Admin\CatalogApplication;
use Ampache\Application\Admin\DuplicatesApplication;
use Ampache\Application\Admin\ExportApplication;
use Ampache\Application\Admin\IndexApplication as AdminIndexApplication;
use Ampache\Application\Admin\LicenseApplication;
use Ampache\Application\Admin\MailApplication;
use Ampache\Application\Admin\ModulesApplication;
use Ampache\Application\Admin\ShoutApplication as AdminShoutApplication;
use Ampache\Application\Admin\SystemApplication;
use Ampache\Application\Admin\UsersApplication;
use Ampache\Application\Api\Ajax\AjaxApplication;
use Ampache\Application\Api\Ajax\Handler\BrowseAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\CatalogAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\DefaultAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\DemocraticPlaybackAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\IndexAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\LocalPlayAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\PlayerAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\PlaylistAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\PodcastAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\RandomAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\SearchAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\SongAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\StatsAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\StreamAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\TagAjaxHandler;
use Ampache\Application\Api\Ajax\Handler\UserAjaxHandler;
use Ampache\Application\Api\DaapApplication;
use Ampache\Application\Api\EditApplication;
use Ampache\Application\Api\JsonApplication;
use Ampache\Application\Api\RefreshReorderedApplication;
use Ampache\Application\Api\SseApplication;
use Ampache\Application\Api\SubsonicApplication;
use Ampache\Application\Api\Upnp\CmControlReplyApplication;
use Ampache\Application\Api\Upnp\ControlReplyApplication;
use Ampache\Application\Api\Upnp\EventReplyApplication;
use Ampache\Application\Api\Upnp\MediaServiceDescriptionApplication;
use Ampache\Application\Api\Upnp\PlayStatusApplication;
use Ampache\Application\Api\Upnp\UpnpApplication;
use Ampache\Application\Api\WebDavApplication;
use Ampache\Application\Api\XmlApplication;
use Ampache\Application\Playback\ChannelApplication as PlaybackChannelApplication;
use Ampache\Application\Playback\PlayApplication;
use function DI\autowire;

/**
 * Provides the definition of all services
 */
return [
    AlbumApplication::class => autowire(AlbumApplication::class),
    ArtApplication::class => autowire(ArtApplication::class),
    ArtistApplication::class => autowire(ArtistApplication::class),
    BroadcastApplication::class => autowire(BroadcastApplication::class),
    BrowseApplication::class => autowire(BrowseApplication::class),
    ChannelApplication::class => autowire(ChannelApplication::class),
    CookieDisclaimerApplication::class => autowire(CookieDisclaimerApplication::class),
    DemocraticPlaybackApplication::class => autowire(DemocraticPlaybackApplication::class),
    ImageApplication::class => autowire(ImageApplication::class),
    IndexApplication::class => autowire(IndexApplication::class),
    InstallationApplication::class => autowire(InstallationApplication::class),
    LabelApplication::class => autowire(LabelApplication::class),
    LocalPlayApplication::class => autowire(LocalPlayApplication::class),
    LoginApplication::class => autowire(LoginApplication::class),
    LogoutApplication::class => autowire(LogoutApplication::class),
    LostPasswordApplication::class => autowire(LostPasswordApplication::class),
    MashupApplication::class => autowire(MashupApplication::class),
    NowPlayingApplication::class => autowire(NowPlayingApplication::class),
    PhpInfoApplication::class => autowire(PhpInfoApplication::class),
    PlaylistApplication::class => autowire(PlaylistApplication::class),
    PodcastApplication::class => autowire(PodcastApplication::class),
    PodcastEpisodeApplication::class => autowire(PodcastEpisodeApplication::class),
    PreferencesApplication::class => autowire(PreferencesApplication::class),
    PrivateMessageApplication::class => autowire(PrivateMessageApplication::class),
    RadioApplication::class => autowire(RadioApplication::class),
    RandomApplication::class => autowire(RandomApplication::class),
    RegisterApplication::class => autowire(RegisterApplication::class),
    RssApplication::class => autowire(RssApplication::class),
    SearchApplication::class => autowire(SearchApplication::class),
    SearchDataApplication::class => autowire(SearchDataApplication::class),
    ShareApplication::class => autowire(ShareApplication::class),
    ShoutApplication::class => autowire(ShoutApplication::class),
    ShowGetApplication::class => autowire(ShowGetApplication::class),
    SmartPlaylistApplication::class => autowire(SmartPlaylistApplication::class),
    SongApplication::class => autowire(SongApplication::class),
    StatisticGraphApplication::class => autowire(StatisticGraphApplication::class),
    StatsApplication::class => autowire(StatsApplication::class),
    StreamApplication::class => autowire(StreamApplication::class),
    TestApplication::class => autowire(TestApplication::class),
    TvShowApplication::class => autowire(TvShowApplication::class),
    TvShowSeasonApplication::class => autowire(TvShowSeasonApplication::class),
    UpdateApplication::class => autowire(UpdateApplication::class),
    UploadApplication::class => autowire(UploadApplication::class),
    UtilityApplication::class => autowire(UtilityApplication::class),
    VideoApplication::class => autowire(VideoApplication::class),
    WaveformApplication::class => autowire(WaveformApplication::class),
    WebPlayerApplication::class => autowire(WebPlayerApplication::class),
    WebPlayerEmbeddedApplication::class => autowire(WebPlayerEmbeddedApplication::class),
    AdminIndexApplication::class => autowire(AdminIndexApplication::class),
    AccessApplication::class => autowire(AccessApplication::class),
    CatalogApplication::class => autowire(CatalogApplication::class),
    DuplicatesApplication::class => autowire(DuplicatesApplication::class),
    ExportApplication::class => autowire(ExportApplication::class),
    LicenseApplication::class => autowire(LicenseApplication::class),
    MailApplication::class => autowire(MailApplication::class),
    ModulesApplication::class => autowire(ModulesApplication::class),
    AdminShoutApplication::class => autowire(AdminShoutApplication::class),
    SystemApplication::class => autowire(SystemApplication::class),
    UsersApplication::class => autowire(UsersApplication::class),
    PlaybackChannelApplication::class => autowire(PlaybackChannelApplication::class),
    DaapApplication::class => autowire(DaapApplication::class),
    PlayApplication::class => autowire(PlayApplication::class),
    SubsonicApplication::class => autowire(SubsonicApplication::class),
    WebDavApplication::class => autowire(WebDavApplication::class),
    UpnpApplication::class => autowire(UpnpApplication::class),
    PlayStatusApplication::class => autowire(PlayStatusApplication::class),
    CmControlReplyApplication::class => autowire(CmControlReplyApplication::class),
    ControlReplyApplication::class => autowire(ControlReplyApplication::class),
    EventReplyApplication::class => autowire(EventReplyApplication::class),
    MediaServiceDescriptionApplication::class => autowire(MediaServiceDescriptionApplication::class),
    XmlApplication::class => autowire(XmlApplication::class),
    SseApplication::class => autowire(SseApplication::class),
    JsonApplication::class => autowire(JsonApplication::class),
    EditApplication::class => autowire(EditApplication::class),
    RefreshReorderedApplication::class => autowire(RefreshReorderedApplication::class),
    AjaxApplication::class => autowire(AjaxApplication::class),
    BrowseAjaxHandler::class => autowire(BrowseAjaxHandler::class),
    DefaultAjaxHandler::class => autowire(DefaultAjaxHandler::class),
    CatalogAjaxHandler::class => autowire(CatalogAjaxHandler::class),
    DemocraticPlaybackAjaxHandler::class => autowire(DemocraticPlaybackAjaxHandler::class),
    IndexAjaxHandler::class => autowire(IndexAjaxHandler::class),
    LocalPlayAjaxHandler::class => autowire(LocalPlayAjaxHandler::class),
    PlayerAjaxHandler::class => autowire(PlayerAjaxHandler::class),
    PlaylistAjaxHandler::class => autowire(PlaylistAjaxHandler::class),
    PodcastAjaxHandler::class => autowire(PodcastAjaxHandler::class),
    RandomAjaxHandler::class => autowire(RandomAjaxHandler::class),
    SearchAjaxHandler::class => autowire(SearchAjaxHandler::class),
    SongAjaxHandler::class => autowire(SongAjaxHandler::class),
    StatsAjaxHandler::class => autowire(StatsAjaxHandler::class),
    StreamAjaxHandler::class => autowire(StreamAjaxHandler::class),
    TagAjaxHandler::class => autowire(TagAjaxHandler::class),
    UserAjaxHandler::class => autowire(UserAjaxHandler::class),
    BatchApplication::class => autowire(BatchApplication::class),
];
