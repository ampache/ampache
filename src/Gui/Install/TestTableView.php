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

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\EnvironmentInterface;
use Override;

/**
 * The environment checklist shared by the installer and the standalone test page.
 *
 * The config rows only make sense once there is a config to read, so the installer asks for them off.
 */
final class TestTableView extends AbstractView
{
    /** @var array<string, mixed>|false|null */
    private array|false|null $configValues = null;

    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly string $configFile,
        private readonly bool $showConfigChecks = true,
    ) {}

    public function checkDatabase(): bool
    {
        return $this->environment->check_php_pdo() && Dba::check_database();
    }

    public function checkDatabaseInserted(): bool
    {
        return $this->environment->check_php_pdo() && Dba::check_database_inserted();
    }

    /**
     * A warning rather than an error: these are optional, so a red cross would misreport them.
     */
    public function debugWarning(bool $status): string
    {
        $value = ($status) ? T_('OK') : T_('WARNING');

        return '<button type="button" class="btn btn-' . (($status) ? 'success' : 'warning') . '">'
            . scrub_out($value) . '</button>';
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    public function isConfigReadable(): bool
    {
        return is_readable($this->configFile);
    }

    /**
     * Parsing the config also publishes it, which is what the web path row below then tests.
     */
    public function isConfigValid(): bool
    {
        $values = $this->getConfigValues();
        if ($values === false) {
            return false;
        }

        AmpConfig::set_by_array($values);

        return (bool) check_config_values($values);
    }

    public function showConfigChecks(): bool
    {
        return $this->showConfigChecks;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('install/test_table.phtml');
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getConfigValues(): array|false
    {
        return $this->configValues ??= @parse_ini_file($this->configFile);
    }
}
