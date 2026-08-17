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
use Ampache\Gui\Search\OpenSearchDescriptorView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DescriptorAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'descriptor';

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $webPath = AmpConfig::get_web_path();
        $charset = (string) AmpConfig::get('site_charset', 'UTF-8');

        header(sprintf('Content-type: application/opensearchdescription+xml; charset=%s; filename=opensearch.xml', $charset));

        echo new OpenSearchDescriptorView(
            $webPath,
            $charset,
            (string) AmpConfig::get('site_title'),
            (string) (AmpConfig::get('custom_favicon', false) ?: $webPath . '/favicon.ico')
        )->render();

        return null;
    }
}
