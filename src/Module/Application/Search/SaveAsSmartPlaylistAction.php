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

declare(strict_types=0);

namespace Ampache\Module\Application\Search;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Search;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SaveAsSmartPlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'save_as_smartplaylist';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->ui->showHeader();
        
        if (!Access::check('interface', 25)) {
            Ui::access_denied();

            $this->ui->showQueryStats();
            $this->ui->showFooter();
            
            return null;
        }
        $playlist = new Search();
        $playlist->parse_rules(Search::clean_request($_REQUEST));
        $playlist->save();
        
        show_confirmation(
            T_('No Problem'),
            /* HINT: playlist name */
            sprintf(
                T_('Your search has been saved as a Smart Playlist with the name %s'),
                $playlist->name
            ),
            sprintf(
                '%s/browse.php?action=smartplaylist', $this->configContainer->getWebPath()
            )
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
