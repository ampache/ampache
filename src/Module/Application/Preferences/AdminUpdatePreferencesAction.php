<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\PreferencesFromRequestUpdaterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class AdminUpdatePreferencesAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'admin_update_preferences';

    private PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater;

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private RequestParserInterface $requestParser;

    public function __construct(
        PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater,
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser
    ) {
        $this->preferencesFromRequestUpdater = $preferencesFromRequestUpdater;
        $this->responseFactory               = $responseFactory;
        $this->configContainer               = $configContainer;
        $this->requestParser                 = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false ||
            !$this->requestParser->verifyForm('update_preference')
        ) {
            throw new AccessDeniedException();
        }

        $this->preferencesFromRequestUpdater->update((int) Core::get_post('user_id'));

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/admin/users.php?action=show_preferences&user_id=%s',
                    $this->configContainer->getWebPath(),
                    scrub_out(Core::get_post('user_id'))
                )
            );
    }
}
