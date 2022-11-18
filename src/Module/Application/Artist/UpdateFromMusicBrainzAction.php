<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Artist;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateFromMusicBrainzAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'update_from_musicbrainz';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $artistId = (int) ($request->getQueryParams()['artist'] ?? 0);
        $user     = (!empty(Core::get_global('user')))
            ? Core::get_global('user')
            : new User(-1);
        // load up musicbrainz or cause an error
        $plugin = new Plugin('musicbrainz');
        if (!$plugin->load($user)) {
            throw new AccessDeniedException('Unable to load musicbrainz plugin');
        }
        if (!$plugin->_plugin->overwrite_name) {
            throw new AccessDeniedException(T_('Enable') . ': ' . T_('Overwrite Artist names that match an mbid'));
        }
        $artist = new Artist($artistId);
        $plugin->_plugin->get_external_metadata($artist, 'artist');

        $this->ui->showHeader();

        $this->ui->showContinue(
            T_('No Problem'),
            T_('Artist information updated using MusicBrainz'),
            $this->configContainer->get('web_path') . "/artists.php?action=show&artist=" . $artistId
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
