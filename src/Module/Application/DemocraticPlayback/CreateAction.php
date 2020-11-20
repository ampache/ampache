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

declare(strict_types=1);

namespace Ampache\Module\Application\DemocraticPlayback;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Model\Democratic;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';
    
    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
    }
    
    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Make sure they have access to this */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_DEMOCRATIC_PLAYBACK) === false) {
            Ui::access_denied();

            return null;
        }
        
        if (!Core::form_verify('create_democratic')) {
            Ui::access_denied();

            return null;
        }

        if (!Access::check('interface', 75)) {
            Ui::access_denied();

            return null;
        }

        $democratic = Democratic::get_current_playlist();

        // If we don't have anything currently create something
        if (!$democratic->id) {
            // Create the playlist
            Democratic::create($_POST);
            $democratic = Democratic::get_current_playlist();
        } else {
            if (!$democratic->update($_POST)) {
                show_confirmation(T_("There Was a Problem"),
                    T_("Cooldown out of range."),
                    AmpConfig::get('web_path') . "/democratic.php?action=manage");
            }
        }

        // Now check for additional things we might have to do
        if (Core::get_post('force_democratic') !== '') {
            Democratic::set_user_preferences();
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
