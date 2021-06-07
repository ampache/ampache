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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Browse;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;

abstract class AbstractBrowseAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    protected function getBrowse(): Browse
    {
        if (isset($_REQUEST['browse_id'])) {
            $browse_id = $_REQUEST['browse_id'];
        } else {
            $browse_id = null;
        }

        debug_event('browse.ajax', 'Called for action: {' . Core::get_request('action') . '}', 5);

        $browse = $this->modelFactory->createBrowse($browse_id);

        if (isset($_REQUEST['show_header']) && $_REQUEST['show_header']) {
            $browse->set_show_header($_REQUEST['show_header'] == 'true');
        }

        return $browse;
    }

    /**
     * @return array<mixed, mixed>|false|string
     */
    protected function getArgument()
    {
        $argument = false;
        if ($_REQUEST['argument']) {
            $argument = scrub_in($_REQUEST['argument']);
        }

        return $argument;
    }
}
