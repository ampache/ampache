<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\WebDav;

use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Exception;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;

final class WebDavFactory implements WebDavFactoryInterface
{
    private AuthenticationManagerInterface $authenticationManager;

    public function __construct(
        AuthenticationManagerInterface $authenticationManager
    ) {
        $this->authenticationManager = $authenticationManager;
    }

    public function createWebDavAuth(): WebDavAuth
    {
        return new WebDavAuth(
            $this->authenticationManager
        );
    }

    public function createWebDavCatalog(int $catalog_id = 0): WebDavCatalog
    {
        return new WebDavCatalog($catalog_id);
    }

    /**
     * @param Tree|INode|array|null $node The tree object
     *
     * @throws Exception
     */
    public function createServer($node): Server
    {
        return new Server($node);
    }

    public function createPlugin(?BackendInterface $backend): Plugin
    {
        return new Plugin($backend);
    }
}
