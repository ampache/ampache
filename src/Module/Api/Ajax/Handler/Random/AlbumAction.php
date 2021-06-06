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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AlbumAction implements ActionInterface
{
    private SongRepositoryInterface $songRepository;

    private AlbumRepositoryInterface $albumRepository;

    public function __construct(
        SongRepositoryInterface $songRepository,
        AlbumRepositoryInterface $albumRepository
    ) {
        $this->songRepository  = $songRepository;
        $this->albumRepository = $albumRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results  = [];
        $album_id = $this->albumRepository->getRandom(
            Core::get_global('user')->id,
            null
        );

        if (empty($album_id)) {
            $results['rfc3514'] = '0x1';

            return $results;
        }

        $album = new Album($album_id[0]);
        // songs for all disks
        if (AmpConfig::get('album_group')) {
            $disc_ids = $album->get_group_disks_ids();
            $songs    = [];
            foreach ($disc_ids as $discid) {
                $disc     = new Album($discid);
                $allsongs = $this->songRepository->getByAlbum($disc->id);
                foreach ($allsongs as $songid) {
                    $songs[] = $songid;
                }
            }
        } else {
            // songs for just this disk
            $songs = $this->songRepository->getByAlbum($album->id);
        }
        foreach ($songs as $song_id) {
            Core::get_global('user')->playlist->add_object($song_id, 'song');
        }
        $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');

        return $results;
    }
}
