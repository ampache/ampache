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

use Ampache\Config\AmpConfig;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Gui\Playlist\PlaylistPageView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private UiInterface $ui,
        private LoggerInterface $logger,
        private ModelFactoryInterface $modelFactory,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlist = $this->modelFactory->createPlaylist(
            (int) ($_REQUEST['playlist_id'] ?? 0)
        );
        if (!$playlist->isNew() && ($playlist->has_collaborate() || $playlist->type !== 'private')) {
            $webPath = AmpConfig::get_web_path();
            $count   = (int) $playlist->last_count;
            $url     = $webPath . '/playlist.php?action=show_playlist&playlist_id=' . $playlist->id;
            PageMeta::set(
                [
                    (string) $playlist->username,
                    ($count > 0) ? sprintf(nT_('%d song', '%d songs', $count), $count) : null,
                    $playlist->get_f_time(),
                ],
                'music.playlist',
                (string) $playlist->name,
                $url,
                $webPath . '/image.php?object_id=' . $playlist->id . '&object_type=playlist&size=600x600',
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MusicPlaylist',
                    'name' => (string) $playlist->name,
                    'url' => $url,
                    'numTracks' => $count,
                ])
            );
        }

        $this->ui->showHeader();

        if ($playlist->isNew() || (!$playlist->has_collaborate() && $playlist->type === 'private')) {
            $this->logger->warning(
                'Requested a playlist that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $object_ids = $playlist->get_items();
            echo new PlaylistPageView(
                $playlist,
                $object_ids,
                $this->zipHandler,
                $this->browseFactory,
                AmpConfig::get_web_path('/client')
            )->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
