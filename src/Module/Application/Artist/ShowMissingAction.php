<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Artist;

use Ampache\Module\Util\VaInfo;
use Ampache\Module\Wanted\MissingArtistRetrieverInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowMissingAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_missing';

    private UiInterface $ui;

    private MissingArtistRetrieverInterface $missingArtistRetriever;

    public function __construct(
        UiInterface $ui,
        MissingArtistRetrieverInterface $missingArtistRetriever
    ) {
        $this->ui                     = $ui;
        $this->missingArtistRetriever = $missingArtistRetriever;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $musicBrainzId = VaInfo::parse_mbid($_REQUEST['mbid'] ?? '');

        if ($musicBrainzId === null) {
            $wartist = [];
        } else {
            $wartist = $this->missingArtistRetriever->retrieve($musicBrainzId);
        }

        $this->ui->show(
            'show_missing_artist.inc.php',
            ['wartist' => $wartist]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
