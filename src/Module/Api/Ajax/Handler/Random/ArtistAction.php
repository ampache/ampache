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

namespace Ampache\Module\Api\Ajax\Handler\Random;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ArtistAction implements ActionInterface
{
    private SongRepositoryInterface $songRepository;

    public function __construct(
        SongRepositoryInterface $songRepository
    ) {
        $this->songRepository = $songRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results   = [];
        $artist_id = Random::artist();

        if (!$artist_id) {
            $results['rfc3514'] = '0x1';

            return $results;
        }

        $songs  = $this->songRepository->getByArtist($artist_id);
        foreach ($songs as $song_id) {
            Core::get_global('user')->playlist->add_object($song_id, 'song');
        }
        $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');

        return $results;
    }
}
