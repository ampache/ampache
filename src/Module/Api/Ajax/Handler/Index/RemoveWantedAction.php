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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Index;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\WantedRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RemoveWantedAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    private WantedRepositoryInterface $wantedRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        WantedRepositoryInterface $wantedRepository
    ) {
        $this->modelFactory     = $modelFactory;
        $this->wantedRepository = $wantedRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        if (AmpConfig::get('wanted') && isset($_REQUEST['mbid'])) {
            $mbid = $_REQUEST['mbid'];

            $userId = Core::get_global('user')->has_access('75') ? null : Core::get_global('user')->id;
            $walbum = $this->modelFactory->createWanted($this->wantedRepository->getByMusicbrainzId($mbid));

            $this->wantedRepository->deleteByMusicbrainzId($mbid, $userId);
            ob_start();
            $walbum->accepted = false;
            $walbum->id       = 0;
            $walbum->show_action_buttons();
            $results['wanted_action_' . $mbid] = ob_get_clean();
        }

        return $results;
    }
}
