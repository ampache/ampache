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

namespace Ampache\Module\Application\Song;

use Ampache\Config\AmpConfig;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowSongAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_song';

    public function __construct(
        private UiInterface $ui,
        private ModelFactoryInterface $modelFactory,
        private GuiFactoryInterface $guiFactory,
        private LoggerInterface $logger,
    ) {}

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
    ): ?ResponseInterface {
        $user     = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
        $catalogs = $user->catalogs['music'] ?? User::get_user_catalogs($user->id);
        $song     = $this->modelFactory->createSong((int) ($request->getQueryParams()['song_id'] ?? 0));
        $shown    = !$song->isNew() && in_array($song->catalog, $catalogs);

        if ($shown) {
            $webPath = AmpConfig::get_web_path();
            $license = $song->getLicense();
            PageMeta::set(
                [
                    $song->get_parent_fullname(),
                    $song->get_album_fullname(),
                    ($song->year > 0) ? $song->year : null,
                    $song->get_f_tags(),
                    $song->get_f_time(),
                    $license?->getName(),
                ],
                'music.song',
                (string) $song->get_fullname(),
                $webPath . '/song.php?action=show_song&song_id=' . $song->getId(),
                $webPath . '/image.php?object_id=' . $song->album . '&object_type=album&size=600x600',
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MusicRecording',
                    'name' => (string) $song->get_fullname(),
                    'url' => $webPath . '/song.php?action=show_song&song_id=' . $song->getId(),
                    'duration' => PageMeta::duration((int) $song->time),
                    'byArtist' => ['@type' => 'MusicGroup', 'name' => $song->get_parent_fullname()],
                    'inAlbum' => ['@type' => 'MusicAlbum', 'name' => $song->get_album_fullname()],
                    'license' => $license?->getExternalLink(),
                ])
            );
        }

        $this->ui->showHeader();

        if (!$shown) {
            $this->logger->warning(
                'Requested a song that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $this->ui->showBoxTop('', 'box box_song_details');

            echo $this->guiFactory->createSongViewAdapter($gatekeeper, $song)->render();

            $this->ui->showBoxBottom();
        }

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
