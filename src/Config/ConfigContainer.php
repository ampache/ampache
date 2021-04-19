<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Config;

/**
 * The ConfigContainer is a containment for all of Ampache's configuration data.
 * Once initialized, the data is immutable
 */
final class ConfigContainer implements ConfigContainerInterface
{
    private array $configuration;

    public function __construct(
        array $configuration
    ) {
        $this->configuration = $configuration;
    }

    public function updateConfig(array $configuration): ConfigContainerInterface
    {
        $this->configuration = array_merge(
            $this->configuration,
            $configuration
        );

        return $this;
    }

    public function get(string $configKey)
    {
        return $this->configuration[$configKey] ?? null;
    }

    public function getSessionName(): string
    {
        return $this->configuration[ConfigurationKeyEnum::SESSION_NAME] ?? '';
    }

    public function isWebDavBackendEnabled(): bool
    {
        return (bool) ($this->configuration[ConfigurationKeyEnum::BACKEND_WEBDAV] ?? false);
    }

    public function isAuthenticationEnabled(): bool
    {
        return (bool) ($this->configuration[ConfigurationKeyEnum::USE_AUTH] ?? true);
    }

    public function getRawWebPath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::RAW_WEB_PATH] ?? '';
    }

    public function getWebPath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::WEB_PATH] ?? '';
    }

    public function getTypesAllowedForZip(): array
    {
        $typeList = $this->configuration[ConfigurationKeyEnum::ALLOWED_ZIP_TYPES] ?? null;

        if ($typeList === null) {
            return [];
        }

        return array_map(
            'trim',
            explode(',', $typeList)
        );
    }

    public function getComposerBinaryPath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::COMPOSER_BINARY_PATH] ?? 'composer';
    }

    public function isFeatureEnabled(string $feature): bool
    {
        $value = $this->configuration[$feature] ?? false;

        return $value === 'true' || $value === true || $value === 1 || $value === '1';
    }

    public function getThemePath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::THEME_PATH] ?? '';
    }

    public function isDebugMode(): bool
    {
        return $this->isFeatureEnabled(ConfigurationKeyEnum::DEBUG_MODE);
    }

    public function isDemoMode(): bool
    {
        return $this->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE);
    }

    public function getConfigFilePath(): string
    {
        return __DIR__ . '/../../config/ampache.cfg.php';
    }
}
