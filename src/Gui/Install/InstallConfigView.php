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

use Ampache\Module\System\InstallationHelperInterface;
use Override;

/**
 * The configuration step: database credentials, web path and the optional backends.
 */
final class InstallConfigView extends AbstractInstallStepView
{
    public function __construct(
        string $webPath,
        string $charset,
        string $documentLanguage,
        private readonly InstallationHelperInterface $installationHelper,
        private readonly string $htaccessPlayFile,
        private readonly string $htaccessRestFile,
    ) {
        parent::__construct($webPath, $charset, $documentLanguage);
    }

    public function checkPlayRewrite(): bool
    {
        return (bool) $this->installationHelper->install_check_rewrite_rules($this->htaccessPlayFile, $this->getWebPathGuess());
    }

    public function checkRestRewrite(): bool
    {
        return (bool) $this->installationHelper->install_check_rewrite_rules($this->htaccessRestFile, $this->getWebPathGuess());
    }

    /**
     * @return array<string, string>
     */
    /**
     * The "recheck config" link, whose database host and name come straight from the request.
     */
    public function getConfigPath(): string
    {
        return __DIR__ . '/../../../config/ampache.cfg.php';
    }

    public function getLocalPassword(): string
    {
        return (string) ($_REQUEST['db_password'] ?? $_REQUEST['local_pass'] ?? '');
    }

    public function getLocalUsername(): string
    {
        return ($_REQUEST['db_user'] ?? '')
            ? (string) ($_REQUEST['db_username'] ?? '')
            : (string) ($_REQUEST['local_username'] ?? '');
    }

    public function getRecheckUrl(): string
    {
        return $this->getWebPath() . '/install.php?' . http_build_query([
            'action' => 'show_create_config',
            'htmllang' => $this->getDocumentLanguage(),
            'charset' => $this->getCharset(),
            'local_db' => (string) ($_REQUEST['local_db'] ?? ''),
            'local_host' => (string) ($_REQUEST['local_host'] ?? ''),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getTranscodeModes(): array
    {
        return $this->installationHelper->install_get_transcode_modes();
    }

    public function getWebPathGuess(): string
    {
        $guess = (string) ($_REQUEST['web_path'] ?? '');

        return ($guess === '') ? get_web_path() : $guess;
    }

    public function hasBackends(): bool
    {
        return array_key_exists('backends', $_REQUEST);
    }

    public function hasUsecase(): bool
    {
        return array_key_exists('usecase', $_REQUEST);
    }

    public function isApache(): bool
    {
        return (bool) $this->installationHelper->install_check_server_apache();
    }

    /**
     * Whether the config on disk parses and carries every value the installer needs.
     */
    public function isConfigConfigured(): bool
    {
        $values = ($this->isConfigPresent()) ? parse_ini_file($this->getConfigPath()) : false;

        return (bool) check_config_values($values ?: []);
    }

    public function isConfigPresent(): bool
    {
        return is_readable($this->getConfigPath());
    }

    public function isPlayHtaccessPresent(): bool
    {
        return is_readable($this->htaccessPlayFile);
    }

    public function isRestHtaccessPresent(): bool
    {
        return is_readable($this->htaccessRestFile);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('install/install_config.phtml');
    }
}
