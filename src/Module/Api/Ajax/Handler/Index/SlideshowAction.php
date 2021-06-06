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

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Util\SlideshowInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SlideshowAction implements ActionInterface
{
    private SlideshowInterface $slideshow;

    public function __construct(
        SlideshowInterface $slideshow
    ) {
        $this->slideshow = $slideshow;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        ob_start();
        $images = $this->slideshow->getCurrentSlideshow();
        if (count($images) > 0) {
            $fsname = 'fslider_' . time();
            echo "<div id='" . $fsname . "'>";
            foreach ($images as $image) {
                echo "<img src='" . $image['url'] . "' alt= '' onclick='update_action();' />";
            }
            echo "</div>";
            $results['fslider'] = ob_get_clean();
            ob_start();
            echo '<script>';
            echo "$('#" . $fsname . "').rhinoslider({
                    showTime: 15000,
                    effectTime: 2000,
                    randomOrder: true,
                    controlsPlayPause: false,
                    autoPlay: true,
                    showBullets: 'never',
                    showControls: 'always',
                    controlsMousewheel: false,
            });";
            echo "</script>";
        }
        $results['fslider_script'] = ob_get_clean();

        return $results;
    }
}
