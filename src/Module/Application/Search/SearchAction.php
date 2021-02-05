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

use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Search;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Wanted\MissingArtistFinderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SearchAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'search';
    
    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private MissingArtistFinderInterface $missingArtistFinder;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        MissingArtistFinderInterface $missingArtistFinder
    ) {
        $this->ui                  = $ui;
        $this->modelFactory        = $modelFactory;
        $this->missingArtistFinder = $missingArtistFinder;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();
        
        if (Core::get_request('rule_1') != 'missing_artist') {
            $browse = $this->modelFactory->createBrowse();
            require_once Ui::find_template('show_search_form.inc.php');
            require_once  Ui::find_template('show_search_options.inc.php');
            $results = Search::run($_REQUEST);
            $browse->set_type(Core::get_request('type'));
            $browse->show_objects($results);
            $browse->store();
        } else {
            $wartists = $this->missingArtistFinder->find($_REQUEST['rule_1_input']);
            require_once Ui::find_template('show_missing_artists.inc.php');
            
            printf(
                '<a href="http://musicbrainz.org/search?query=%s&type=artist&method=indexed" target="_blank">%s</a><br />',
                rawurlencode($_REQUEST['rule_1_input']),
                T_('View on MusicBrainz')
            );
        }
        
        $this->ui->showQueryStats();
        $this->ui->showFooter();
        
        return null;
    }
}
