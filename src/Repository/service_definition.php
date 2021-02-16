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

namespace Ampache\Repository;

use function DI\autowire;

return [
    AccessRepositoryInterface::class => autowire(AccessRepository::class),
    AlbumRepositoryInterface::class => autowire(AlbumRepository::class),
    SongRepositoryInterface::class => autowire(SongRepository::class),
    LabelRepositoryInterface::class => autowire(LabelRepository::class),
    ArtistRepositoryInterface::class => autowire(ArtistRepository::class),
    LicenseRepositoryInterface::class => autowire(LicenseRepository::class),
    LiveStreamRepositoryInterface::class => autowire(LiveStreamRepository::class),
    ShoutRepositoryInterface::class => autowire(ShoutRepository::class),
    UserRepositoryInterface::class => autowire(UserRepository::class),
    UserActivityRepositoryInterface::class => autowire(UserActivityRepository::class),
    WantedRepositoryInterface::class => autowire(WantedRepository::class),
    IpHistoryRepositoryInterface::class => autowire(IpHistoryRepository::class),
    UserFollowerRepositoryInterface::class => autowire(UserFollowerRepository::class),
    BookmarkRepositoryInterface::class => autowire(BookmarkRepository::class),
    PrivateMessageRepositoryInterface::class => autowire(PrivateMessageRepository::class),
    VideoRepositoryInterface::class => autowire(VideoRepository::class),
    Model\ModelFactoryInterface::class => autowire(Model\ModelFactory::class),
];
