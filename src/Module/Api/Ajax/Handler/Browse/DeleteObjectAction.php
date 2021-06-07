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

namespace Ampache\Module\Api\Ajax\Handler\Browse;

use Ampache\Module\System\Core;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteObjectAction extends AbstractBrowseAction
{
    private ModelFactoryInterface $modelFactory;

    private LiveStreamRepositoryInterface $liveStreamRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        LiveStreamRepositoryInterface $liveStreamRepository
    ) {
        parent::__construct($modelFactory);

        $this->modelFactory         = $modelFactory;
        $this->liveStreamRepository = $liveStreamRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        $browse = $this->getBrowse();

        switch ($_REQUEST['type']) {
            case 'playlist':
                // Check the perms we need to on this
                $playlist = new Playlist((int) Core::get_request('id'));
                if (!$playlist->has_access()) {
                    return $results;
                }

                // Delete it!
                $playlist->delete();
                $key = 'playlist_row_' . $playlist->id;
                break;
            case 'smartplaylist':
                $playlist = $this->modelFactory->createSearch((int) Core::get_request('id'));
                if (!$playlist->has_access()) {
                    return $results;
                }
                $playlist->delete();
                $key = 'smartplaylist_row_' . $playlist->id;
                break;
            case 'live_stream':
                if (!Core::get_global('user')->has_access('75')) {
                    return $results;
                }
                $liveStreamId = (int) Core::get_request('id');
                $this->liveStreamRepository->delete($liveStreamId);
                $key = 'live_stream_' . $liveStreamId;
                break;
            default:
                return $results;
        } // end switch on type

        $results[$key] = '';

        $browse->store();

        return $results;
    }
}
