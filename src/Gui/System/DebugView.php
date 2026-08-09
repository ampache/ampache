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

namespace Ampache\Gui\System;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\EnvironmentInterface;
use Override;

/**
 * The admin debug page: configuration, php settings and update state.
 *
 * It prints the whole running configuration, so the credentials are stripped before anything is rendered
 * rather than skipped at display time.
 */
final class DebugView extends AbstractView
{
    /**
     * Configuration keys never rendered, because they are credentials or per-request noise.
     */
    private const array HIDDEN_KEYS = [
        'daap_pass',
        'database_password',
        'lastfm_api_secret',
        'ldap_password',
        'load_time_begin',
        'mail_auth_pass',
        'musicbrainz_password',
        'oidc_client_secret',
        'phpversion',
        'proxy_pass',
        'secret_key',
        'spotify_client_secret',
    ];

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly string $adminPath,
        private readonly EnvironmentInterface $environment,
        private readonly array $configuration,
        private readonly string $latestVersion,
        private readonly int $lastCronDate,
    ) {}

    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    /**
     * One row per configuration entry, with a boolean kept apart so it can render as an on/off badge.
     *
     * @return list<array{key: string, lines: list<string>, flag: bool|null}>
     */
    public function getConfigurationRows(): array
    {
        $rows = [];
        foreach ($this->configuration as $key => $value) {
            if (in_array($key, self::HIDDEN_KEYS, true)) {
                continue;
            }

            if (Preference::is_boolean($key)) {
                $rows[] = ['key' => $key, 'lines' => [], 'flag' => (bool) $value];

                continue;
            }

            if (is_array($value)) {
                $rows[] = ['key' => $key, 'lines' => $this->flatten($value), 'flag' => null];

                continue;
            }

            // anything else printable is a scalar; objects and resources have no useful rendering here
            if ($value === null || is_scalar($value)) {
                $rows[] = ['key' => $key, 'lines' => [(string) $value], 'flag' => null];
            }
        }

        return $rows;
    }

    public function getCurrentVersion(): string
    {
        return AutoUpdate::get_current_version();
    }

    /**
     * @return list<array{name: string, loaded: bool, description: string, required: bool}>
     */
    public function getExtensions(): array
    {
        return $this->environment->getExtensionStatus();
    }

    public function getForcedGitBranch(): string
    {
        return AutoUpdate::is_force_git_branch();
    }

    public function getLastCronDate(): string
    {
        return get_datetime($this->lastCronDate);
    }

    public function getLastUpdateCheck(): string
    {
        $checked = (int) AmpConfig::get('autoupdate_lastcheck', 0);

        return ($checked) ? get_datetime($checked) : T_('Unknown');
    }

    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }

    public function getMaxExecutionTime(): string
    {
        return (string) ini_get('max_execution_time');
    }

    public function getMemoryLimit(): string
    {
        return (string) ini_get('memory_limit');
    }

    public function getOpenBasedir(): string
    {
        return (string) ini_get('open_basedir');
    }

    public function getPhpVersion(): string
    {
        return phpversion();
    }

    public function getStructure(): string
    {
        return (string) ($this->configuration['structure'] ?? '');
    }

    /**
     * The `public` layout is the default one, so only the others are worth naming beside the version.
     */
    public function hasNamedStructure(): bool
    {
        return $this->getStructure() !== '' && $this->getStructure() !== 'public';
    }

    /**
     * Raising the limit and reading it back is the only way to tell whether the host allows it.
     */
    public function mayOverrideExecutionTime(): bool
    {
        set_time_limit(0);

        return !ini_get('max_execution_time');
    }

    public function showAutoUpdate(): bool
    {
        return (bool) AmpConfig::get('autoupdate', false);
    }

    public function showCron(): bool
    {
        return (bool) AmpConfig::get('cron_cache', false);
    }

    public function showNewVersion(): bool
    {
        return $this->getCurrentVersion() !== $this->latestVersion || AutoUpdate::is_update_available();
    }

    public function showPerpetualApiSession(): bool
    {
        return (bool) AmpConfig::get('perpetual_api_session');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('system/debug.phtml');
    }

    /**
     * @param array<mixed> $value
     * @return list<string>
     */
    private function flatten(array $value): array
    {
        $lines = [];
        foreach ($value as $setting) {
            if (is_array($setting)) {
                foreach ($setting as $nested) {
                    if ($nested === null || is_scalar($nested)) {
                        $lines[] = (string) $nested;
                    }
                }

                continue;
            }

            if ($setting === null || is_scalar($setting)) {
                $lines[] = (string) $setting;
            }
        }

        return $lines;
    }
}
