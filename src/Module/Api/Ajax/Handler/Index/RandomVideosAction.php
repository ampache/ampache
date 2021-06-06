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
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RandomVideosAction implements ActionInterface
{
    private VideoRepositoryInterface $videoRepository;

    public function __construct(
        VideoRepositoryInterface $videoRepository
    ) {
        $this->videoRepository = $videoRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];
        $moment  = (int) AmpConfig::get('of_the_moment');
        // filter album and video of the Moment instead of a hardcoded value
        if (!$moment > 0) {
            $moment = 6;
        }

        $videos = $this->videoRepository->getRandom($moment);
        if (count($videos) && is_array($videos)) {
            ob_start();
            require_once Ui::find_template('show_random_videos.inc.php');
            $results['random_video_selection'] = ob_get_clean();
        } else {
            $results['random_video_selection'] = '<!-- None found -->';
        }

        return $results;
    }
}
