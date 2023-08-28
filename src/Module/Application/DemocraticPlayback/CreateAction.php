<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Module\Application\DemocraticPlayback;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Democratic;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Make sure they have access to this */
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_DEMOCRATIC_PLAYBACK) === false ||
            !Core::form_verify('create_democratic') ||
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false
        ) {
            throw new AccessDeniedException();
        }

        $democratic = Democratic::get_current_playlist();

        // If we don't have anything currently create something
        if (!$democratic->id) {
            // Create the playlist
            Democratic::create($_POST);
            Democratic::get_current_playlist();
        } elseif (!$democratic->update($_POST)) {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_("Cooldown out of range."),
                AmpConfig::get('web_path') . "/democratic.php?action=manage"
            );
        }

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/democratic.php?action=show',
                    $this->configContainer->getWebPath()
                )
            );
    }
}
