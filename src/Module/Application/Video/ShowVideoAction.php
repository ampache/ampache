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

namespace Ampache\Module\Application\Video;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Gui\Video\VideoView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowVideoAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_video';

    public function __construct(private UiInterface $ui) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $video = new Video((int) filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$video->isNew()) {
            $webPath = AmpConfig::get_web_path();
            $url     = $webPath . '/video.php?action=show_video&video_id=' . $video->getId();
            PageMeta::set(
                [
                    $video->get_f_time(),
                    (string) $video->get_f_resolution(),
                ],
                'video.other',
                (string) $video->get_fullname(),
                $url,
                $webPath . '/image.php?object_id=' . $video->getId() . '&object_type=video&size=600x600',
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'VideoObject',
                    'name' => (string) $video->get_fullname(),
                    'url' => $url,
                    'duration' => PageMeta::duration($video->time),
                    'thumbnailUrl' => $webPath . '/image.php?object_id=' . $video->getId() . '&object_type=video&size=600x600',
                    'uploadDate' => ($video->addition_time > 0) ? date('Y-m-d', $video->addition_time) : null,
                ])
            );
        }

        $this->ui->showHeader();

        $mayInteract = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);

        echo new VideoView(
            AmpConfig::get_web_path(),
            $video,
            ($video->file) ? $video->get_subtitles() : [],
            (string) ($_SESSION['iframe']['subtitle'] ?? ''),
            (bool) AmpConfig::get('encode_srt'),
            (bool) AmpConfig::get('directplay'),
            Stream_Playlist::check_autoplay_next(),
            Stream_Playlist::check_autoplay_append(),
            User::is_registered() && (bool) AmpConfig::get('ratings'),
            (bool) AmpConfig::get('show_played_times'),
            $mayInteract,
            (!AmpConfig::get('use_auth') || $mayInteract) && (bool) AmpConfig::get('sociable'),
            $mayInteract && (bool) AmpConfig::get('share'),
            Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER),
            Catalog::can_remove($video),
            (bool) AmpConfig::get('statistical_graphs')
        )->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
