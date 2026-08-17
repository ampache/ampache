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

namespace Ampache\Gui\Install;

use Ampache\Module\Util\EnvironmentInterface;
use Override;

/**
 * The requirements step, which is the test table plus the three files the installer itself needs.
 */
final class InstallCheckView extends AbstractInstallStepView
{
    private const string ROOT = __DIR__ . '/../../../';

    public function __construct(
        string $webPath,
        string $charset,
        string $documentLanguage,
        private readonly EnvironmentInterface $environment,
    ) {
        parent::__construct($webPath, $charset, $documentLanguage);
    }

    public function getConfigDistPath(): string
    {
        return $this->resolve('config/ampache.cfg.php.dist');
    }

    public function getConfigPath(): string
    {
        return $this->resolve('config/ampache.cfg.php');
    }

    public function getSqlPath(): string
    {
        return $this->resolve('resources/sql/ampache.sql');
    }

    public function isConfigDistReadable(): bool
    {
        return is_readable($this->getConfigDistPath());
    }

    public function isConfigWritable(): bool
    {
        return (bool) check_config_writable();
    }

    public function isSqlReadable(): bool
    {
        return is_readable($this->getSqlPath());
    }

    /**
     * The config rows are off: there is no config yet, which is the point of running the installer.
     */
    public function renderChecks(): string
    {
        return new TestTableView($this->environment, $this->getConfigPath(), false)->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('install/install_check.phtml');
    }

    /**
     * These paths are printed for the user to act on, so a `../..` chain would be unhelpful.
     */
    private function resolve(string $path): string
    {
        $resolved = realpath(self::ROOT . $path);
        if ($resolved !== false) {
            return $resolved;
        }

        // the config file does not exist yet during a fresh install, so resolve its directory instead
        $directory = realpath(self::ROOT . dirname($path));

        return ($directory !== false) ? $directory . '/' . basename($path) : self::ROOT . $path;
    }
}
