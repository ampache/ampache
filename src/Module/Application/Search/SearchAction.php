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

namespace Ampache\Module\Application\Search;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Search\SearchFormView;
use Ampache\Gui\Search\SearchOptionsView;
use Ampache\Gui\Wanted\MissingArtistsView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Module\Wanted\MissingArtistFinderInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SearchAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'search';

    public function __construct(
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
        private BrowseFactoryInterface $browseFactory,
        private MissingArtistFinderInterface $missingArtistFinder,
        private VideoRepositoryInterface $videoRepository,
        private ZipHandlerInterface $zipHandler,
        private FunctionCheckerInterface $functionChecker,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        // set the browse type BEFORE running the search (for the search bar)
        $searchType = $this->requestParser->getFromRequest('type');
        $rule_1     = $this->requestParser->getFromRequest('rule_1');
        if ($searchType === '' || $searchType === '0') {
            $searchType = (in_array($rule_1, Search::VALID_TYPES, true))
                ? str_replace('_name', ' ', $rule_1)
                : 'song';
            // set the search type when you don't set one.
            $_REQUEST['type'] = $searchType;
            if ($searchType != 'song') {
                $_REQUEST['rule_1'] = 'title';
            }
        }

        if ($rule_1 !== 'missing_artist') {
            $browse = $this->browseFactory->create();
            echo new SearchFormView(
                $browse,
                null,
                $searchType,
                $this->videoRepository,
                AmpConfig::get_web_path(),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            )->render();
            echo new SearchOptionsView(
                $browse,
                $searchType,
                $this->zipHandler,
                AmpConfig::get_web_path(),
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)
            )->render();
            $results = Search::run($_REQUEST);
            $browse->set_type($searchType);
            $browse->show_objects($results);
            $browse->store();
        } else {
            $wartists = $this->missingArtistFinder->find($this->requestParser->getFromRequest('rule_1_input'));
            echo new MissingArtistsView(AmpConfig::get_web_path(), $wartists)->render();

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
