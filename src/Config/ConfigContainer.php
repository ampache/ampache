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

namespace Ampache\Config;

/**
 * The ConfigContainer is a containment for all of Ampache's configuration data.
 * Once initialized, the data is immutable
 */
final class ConfigContainer implements ConfigContainerInterface
{
    /**
     * Every object type batch download can build a zip from, used when `allow_zip_types` names none.
     *
     * @var list<string>
     */
    private const array DEFAULT_ZIP_TYPES = [
        'album',
        'album_disk',
        'artist',
        'playlist',
        'search',
        'song',
        'tmp_playlist',
        'video',
    ];

    /** @var array<string, mixed> $configuration */
    private array $configuration;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        array $configuration,
    ) {
        $this->configuration = $configuration;
    }

    public function get(string $configKey): mixed
    {
        // this container holds a snapshot taken before the user's preferences were merged, so the live value wins
        return AmpConfig::get($configKey) ?? $this->configuration[$configKey] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getArray(string $configKey): array
    {
        return AmpConfig::to_array($this->get($configKey));
    }

    public function getBool(string $configKey, bool $default = false): bool
    {
        return AmpConfig::to_bool($this->get($configKey) ?? $default, $default);
    }

    public function getComposerBinaryPath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::COMPOSER_BINARY_PATH] ?? 'composer';
    }

    public function getComposerParameters(): string
    {
        return ($this->configuration[ConfigurationKeyEnum::COMPOSER_NO_DEV] ?? true)
            ? '--prefer-dist --no-interaction --no-dev'
            : '--prefer-dist --no-interaction';
    }

    public function getConfigFilePath(): string
    {
        return __DIR__ . '/../../config/ampache.cfg.php';
    }

    public function getInt(string $configKey, int $default = 0): int
    {
        return AmpConfig::to_int($this->get($configKey) ?? $default, $default);
    }

    public function getNpmBinaryPath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::NPM_BINARY_PATH] ?? 'npm';
    }

    public function getRawWebPath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::RAW_WEB_PATH] ?? '';
    }

    public function getSessionName(): string
    {
        return $this->configuration[ConfigurationKeyEnum::SESSION_NAME] ?? '';
    }

    public function getThemePath(): string
    {
        return $this->configuration[ConfigurationKeyEnum::THEME_PATH] ?? '';
    }

    /**
     * Return a list of types which are zip-able
     *
     * @return array<string>
     */
    public function getTypesAllowedForZip(): array
    {
        $typeList = $this->getArray(ConfigurationKeyEnum::ALLOWED_ZIP_TYPES);

        // naming no type means every supported one, which is what ampache.cfg.php.dist documents
        return ($typeList === [])
            ? self::DEFAULT_ZIP_TYPES
            : $typeList;
    }

    /**
     * Returns the current Ampache version
     */
    public function getVersion(): string
    {
        return $this->configuration[ConfigurationKeyEnum::VERSION] ?? '';
    }

    public function getWebPath(?string $suffix = ''): string
    {
        return ($this->configuration[ConfigurationKeyEnum::WEB_PATH] ?? '') . $suffix;
    }

    public function isAuthenticationEnabled(): bool
    {
        return (bool) ($this->configuration[ConfigurationKeyEnum::USE_AUTH] ?? true);
    }

    public function isDebugMode(): bool
    {
        return $this->isFeatureEnabled(ConfigurationKeyEnum::DEBUG_MODE);
    }

    public function isDemoMode(): bool
    {
        return $this->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        $value = $this->get($feature) ?? false;

        return (
            $value === 'true'
            || $value === true
            || $value === 1
            || $value === '1'
        );
    }

    public function isWebDavBackendEnabled(): bool
    {
        return (bool) ($this->configuration[ConfigurationKeyEnum::BACKEND_WEBDAV] ?? false);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function updateConfig(array $configuration): ConfigContainerInterface
    {
        $this->configuration = array_merge(
            $this->configuration,
            $configuration
        );

        return $this;
    }
}
