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
    LoginApplication::class => autowire(LoginApplication::class),
    LogoutApplication::class => autowire(LogoutApplication::class),
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
];
