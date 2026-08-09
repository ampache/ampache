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

namespace Ampache\Module\Application\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\CreatePlaylistFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Playlist;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Creates an empty playlist owned by the current user
 */
final readonly class CreateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'create';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user = $gatekeeper->getUser();
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
            || $user === null
            || !$this->requestParser->verifyForm('add_playlist')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $name = $this->requestParser->getFromRequest('name');
        if ($name === '') {
            AmpError::add('name', T_('Name is required'));
        }

        if (AmpError::occurred()) {
            echo $this->createFormView()->render();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // `existing: false` so a name already in use is reported rather than silently handing back that list
        $playlistId = Playlist::create(
            $name,
            ($this->requestParser->getFromRequest('type') === 'public') ? 'public' : 'private',
            $user->getId(),
            false
        );

        if ($playlistId === null) {
            AmpError::add('name', T_('That name already exists'));
            echo $this->createFormView()->render();
        } else {
            $this->ui->showConfirmation(
                T_('Playlist created'),
                $name,
                sprintf('%s/playlist.php?action=show&playlist_id=%d', $this->configContainer->getWebPath('/client'), $playlistId)
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    private function createFormView(): CreatePlaylistFormView
    {
        return new CreatePlaylistFormView(
            $this->configContainer->getWebPath(),
            $this->requestParser->getFromRequest('name'),
            $this->requestParser->getFromRequest('type') ?: 'private'
        );
    }
}
