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

namespace Ampache\Module\User;

use Ampache\Module\User\Activity\TypeHandler\ActivityTypeHandlerMapper;
use Ampache\Module\User\Activity\TypeHandler\ActivityTypeHandlerMapperInterface;
use Ampache\Module\User\Activity\UserActivityPoster;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\User\Activity\UserActivityRenderer;
use Ampache\Module\User\Activity\UserActivityRendererInterface;
use Ampache\Module\User\Authorization\UserKeyGenerator;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Module\User\Following\UserFollowStateRenderer;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\User\Following\UserFollowToggler;
use Ampache\Module\User\Following\UserFollowTogglerInterface;
use Ampache\Module\User\PrivateMessage\PrivateMessageCreator;
use Ampache\Module\User\PrivateMessage\PrivateMessageCreatorInterface;
use Ampache\Module\User\Registration\RegistrationAgreementRenderer;
use Ampache\Module\User\Registration\RegistrationAgreementRendererInterface;
use Ampache\Module\User\Tracking\UserTracker;
use Ampache\Module\User\Tracking\UserTrackerInterface;

use function DI\autowire;

return [
    PasswordGeneratorInterface::class => autowire(PasswordGenerator::class),
    NewPasswordSenderInterface::class => autowire(NewPasswordSender::class),
    UserStateTogglerInterface::class => autowire(UserStateToggler::class),
    UserActivityRendererInterface::class => autowire(UserActivityRenderer::class),
    UserActivityPosterInterface::class => autowire(UserActivityPoster::class),
    ActivityTypeHandlerMapperInterface::class => autowire(ActivityTypeHandlerMapper::class),
    UserFollowTogglerInterface::class => autowire(UserFollowToggler::class),
    UserFollowStateRendererInterface::class => autowire(UserFollowStateRenderer::class),
    UserKeyGeneratorInterface::class => autowire(UserKeyGenerator::class),
    PrivateMessageCreatorInterface::class => autowire(PrivateMessageCreator::class),
    UserTrackerInterface::class => autowire(UserTracker::class),
    RegistrationAgreementRendererInterface::class => autowire(RegistrationAgreementRenderer::class),
];
