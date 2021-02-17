<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Application\Artist;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Wanted\MissingArtistLookupInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowMissingAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_missing';

    private UiInterface $ui;

    private MissingArtistLookupInterface $missingArtistLookup;

    public function __construct(
        UiInterface $ui,
        MissingArtistLookupInterface $missingArtistLookup
    ) {
        $this->ui                  = $ui;
        $this->missingArtistLookup = $missingArtistLookup;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        set_time_limit(600);

        $this->ui->showHeader();
        $this->ui->show(
            'show_missing_artist.inc.php',
            [
                'wartist' => $this->missingArtistLookup->lookup(
                    $request->getQueryParams()['mbid'] ?? ''
                )
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
