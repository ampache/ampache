<?php

declare(strict_types=1);

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

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Share\CleanAction;
use Ampache\Module\Application\Share\ConsumeAction;
use Ampache\Module\Application\Share\CreateAction;
use Ampache\Module\Application\Share\DeleteAction;
use Ampache\Module\Application\Share\ExternalShareAction;
use Ampache\Module\Application\Share\ShowCreateAction;
use Ampache\Module\Application\Share\ShowDeleteAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

$action = $_REQUEST['action'] ?? '';
if (empty($action) || $action == 'stream' || $action == 'download') {
    define('NO_SESSION', '1');
    define('OUTDATED_DATABASE_OK', 1);

    /** @var ContainerInterface $dic */
    $dic = require __DIR__ . '/../src/Config/Init.php';

    $dic->get(ApplicationRunner::class)->run(
        $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
        [
            ConsumeAction::REQUEST_KEY => ConsumeAction::class,
        ],
        ConsumeAction::REQUEST_KEY
    );
} else {
    /** @var ContainerInterface $dic */
    $dic = require __DIR__ . '/../src/Config/Init.php';

    $dic->get(ApplicationRunner::class)->run(
        $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
        [
            ShowCreateAction::REQUEST_KEY => ShowCreateAction::class,
            CreateAction::REQUEST_KEY => CreateAction::class,
            ShowDeleteAction::REQUEST_KEY => ShowDeleteAction::class,
            DeleteAction::REQUEST_KEY => DeleteAction::class,
            CleanAction::REQUEST_KEY => CleanAction::class,
            ExternalShareAction::REQUEST_KEY => ExternalShareAction::class,
        ],
        '',
    );
}
