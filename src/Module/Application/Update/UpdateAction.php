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

namespace Ampache\Module\Application\Update;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Update\Exception\UpdateFailedException;
use Ampache\Module\System\Update\Exception\VersionNotUpdatableException;
use Ampache\Module\System\Update\UpdaterInterface;
use Ampache\Repository\Model\Preference;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\RFC\RFC7231;

final readonly class UpdateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'update';

    public function __construct(
        private TalFactoryInterface $talFactory,
        private GuiFactoryInterface $guiFactory,
        private ResponseFactoryInterface $responseFactory,
        private ConfigContainerInterface $configContainer,
        private StreamFactoryInterface $streamFactory,
        private UpdaterInterface $updater,
    ) {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) === 'sources') {
            if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false) {
                throw new AccessDeniedException();
            }

            set_time_limit(300);
            AutoUpdate::update_files();
            AutoUpdate::update_dependencies($this->configContainer);
            Preference::translate_db();

            return $this->responseFactory
                ->createResponse(RFC7231::FOUND)
                ->withHeader(
                    'Location',
                    $this->configContainer->getWebPath('/client')
                );
        } elseif ($this->updater->hasPendingUpdates()) {
            try {
                $this->updater->update();
            } catch (UpdateFailedException) {
                AmpError::add('general', T_('Update failed. Please check the logs for further information.'));
            } catch (VersionNotUpdatableException) {
                echo '<p class="database-update">Database version too old, please upgrade to <a href="https://github.com/ampache/ampache/releases/download/3.8.2/ampache-3.8.2_all.zip">Ampache-3.8.2</a> first</p>';
            }
        }

        $result = $this->talFactory->createTalView()
            ->setTemplate('update.xhtml')
            ->setContext(
                'UPDATE',
                $this->guiFactory->createUpdateViewAdapter()
            )
            ->render();

        return $this->responseFactory
            ->createResponse()
            ->withBody(
                $this->streamFactory->createStream($result)
            );
    }
}
