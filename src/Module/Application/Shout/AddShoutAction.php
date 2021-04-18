<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Application\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutCreatorInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class AddShoutAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_shout';

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private ShoutCreatorInterface $shoutCreator;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        ShoutCreatorInterface $shoutCreator
    ) {
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
        $this->shoutCreator    = $shoutCreator;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Must be at least a user to do this
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false ||
            !Core::form_verify('add_shout') ||
            !InterfaceImplementationChecker::is_library_item(Core::get_post('object_type'))
        ) {
            throw new AccessDeniedException();
        }

        // Remove unauthorized defined values from here
        if (filter_has_var(INPUT_POST, 'user')) {
            unset($_POST['user']);
        }
        if (filter_has_var(INPUT_POST, 'date')) {
            unset($_POST['date']);
        }

        $this->shoutCreator->create(
            $gatekeeper->getUser(),
            $_POST
        );

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/shout.php?action=show_add_shout&type=%s&id=%d',
                    $this->configContainer->getWebPath(),
                    $_POST['object_type'],
                    (int) ($_POST['object_id'])
                )
            );
    }
}
