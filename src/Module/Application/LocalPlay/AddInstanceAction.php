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

declare(strict_types=0);

namespace Ampache\Module\Application\LocalPlay;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class AddInstanceAction extends AbstractLocalPlayAction
{
    public const REQUEST_KEY = 'add_instance';

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory,
        UiInterface $ui
    ) {
        parent::__construct($configContainer, $ui);
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
        $this->ui              = $ui;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper
    ): ?ResponseInterface {
        // This requires 75 or better!
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_LOCALPLAY, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        // Setup the object
        $localplay = new LocalPlay($this->configContainer->get(ConfigurationKeyEnum::LOCALPLAY_CONTROLLER));
        $localplay->add_instance($_POST);

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf('%s/localplay.php?action=show_instances', $this->configContainer->getWebPath())
            );
    }
}
