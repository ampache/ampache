<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Application\Song;

use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowSongAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_song';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private GuiFactoryInterface $guiFactory;

    private TalFactoryInterface $talFactory;

    private LoggerInterface $logger;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        GuiFactoryInterface $guiFactory,
        TalFactoryInterface $talFactory,
        LoggerInterface $logger
    ) {
        $this->ui           = $ui;
        $this->modelFactory = $modelFactory;
        $this->guiFactory   = $guiFactory;
        $this->talFactory   = $talFactory;
        $this->logger       = $logger;
    }

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper
    ): ?ResponseInterface {
        $this->ui->showHeader();

        $song = $this->modelFactory->createSong((int)($request->getQueryParams()['song_id'] ?? 0));
        $song->format();
        $song->fill_ext_info();

        if (!$song->id) {
            $this->logger->warning(
                'Requested a song that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $this->ui->showBoxTop(
                scrub_out($song->get_fullname()),
                'box box_song_details'
            );

            echo $this->talFactory
                ->createTalView()
                ->setTemplate('song.xhtml')
                ->setContext('SONG', $this->guiFactory->createSongViewAdapter($gatekeeper, $song))
                ->render();

            $this->ui->showBoxBottom();
        }
        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
