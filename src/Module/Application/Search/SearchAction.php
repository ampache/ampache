<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Search;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Search;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Wanted\MissingArtistFinderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SearchAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'search';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private MissingArtistFinderInterface $missingArtistFinder;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        MissingArtistFinderInterface $missingArtistFinder
    ) {
        $this->requestParser       = $requestParser;
        $this->ui                  = $ui;
        $this->modelFactory        = $modelFactory;
        $this->missingArtistFinder = $missingArtistFinder;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        // set the browse type BEFORE running the search (for the search bar)
        $searchType = $this->requestParser->getFromRequest('type');
        $rule_1     = $this->requestParser->getFromRequest('rule_1');
        if (empty($searchType)) {
            $searchType = in_array($rule_1, Search::VALID_TYPES)
                ? str_replace('_name', ' ', $rule_1)
                : 'song';
            // set the search type when you don't set one.
            $_REQUEST['type'] = $searchType;
            if ($searchType != 'song') {
                $_REQUEST['rule_1'] = 'title';
            }
        }

        if ($rule_1 != 'missing_artist') {
            $browse = $this->modelFactory->createBrowse();
            require_once Ui::find_template('show_form_search.inc.php');
            require_once Ui::find_template('show_search_options.inc.php');
            $results = Search::run($_REQUEST);
            $browse->set_type($searchType);
            $browse->show_objects($results);
            $browse->store();
        } else {
            $wartists = $this->missingArtistFinder->find($this->requestParser->getFromRequest('rule_1_input'));
            require_once Ui::find_template('show_missing_artists.inc.php');

            printf(
                '<a href="http://musicbrainz.org/search?query=%s&type=artist&method=indexed" target="_blank">%s</a><br />',
                rawurlencode($this->requestParser->getFromRequest('rule_1_input')),
                T_('View on MusicBrainz')
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
