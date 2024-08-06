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

namespace Ampache\Config;

use Ampache\Module\Util\Environment;
use Ampache\Module\Util\EnvironmentInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

// Register autoloaders
$composer_autoload = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($composer_autoload) === false) {
    throw new RuntimeException('Composer autoload file not found - please run `composer install`');
}

require_once $composer_autoload;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/DicBuilder.php';

// Core includes we can't do with the autoloader
require_once __DIR__ . '/functions.php';

$environment = $dic->get(EnvironmentInterface::class);

// Do a check for the minimum required php version because nothing will work without it
if ($environment->check_php_version() === false) {
    throw new RuntimeException(
        sprintf('Ampache requires PHP version >= %s', Environment::PHP_VERSION)
    );
}

//error_reporting(E_ERROR); // Only show fatal errors in production

AmpConfig::set('load_time_begin', microtime(true));

// We still allow scripts to run (it could be the purpose of the maintenance)
if ($environment->isCli() === false) {
    if (file_exists(__DIR__ . '/../../public/client/.maintenance')) {
        require_once  __DIR__ . '/../../public/client/.maintenance';
    }
}

// Merge GET then POST into REQUEST effectively stripping COOKIE without
// depending on a PHP setting change for the effect
$_REQUEST = array_merge($_GET, $_POST);

return $dic;
