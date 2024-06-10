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

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Exception;
use Ampache\Repository\Model\Preference;
use WpOrg\Requests\Requests;

/**
 * AutoUpdate Class
 *
 * This class handles autoupdate check from GitHub.
 */
class AutoUpdate
{
    /**
     * Check if current version is a development version.
     */
    protected static function is_develop(): bool
    {
        $version    = (string)AmpConfig::get('version');
        $vspart     = explode('-', $version);
        $git_branch = self::is_force_git_branch();

        if ($git_branch == 'develop') {
            return true;
        }
        // if you are using a non-develop branch
        if ($git_branch !== '') {
            return false;
        }

        return ($vspart[count($vspart) - 1] == 'develop');
    }

    /**
     * Check if current version is a git repository.
     */
    protected static function is_git_repository(): bool
    {
        return is_dir(__DIR__ . '/../../../.git');
    }

    /**
     * Check if there is a default branch set in the config file.
     */
    public static function is_force_git_branch(): string
    {
        $config_branch = (string)AmpConfig::get('github_force_branch');
        if (!empty($config_branch)) {
            return $config_branch;
        }
        if (is_readable(__DIR__ . '/../../../.git/HEAD')) {
            $current = file_get_contents(__DIR__ . '/../../../.git/HEAD');
            $pattern = '/ref: refs\/heads\/(.*)/';
            $matches = [];
            if (
                is_string($current) &&
                preg_match($pattern, $current, $matches) &&
                !in_array((string)$matches[1], array('master', 'release5', 'release6', 'release7'))
            ) {
                return (string)$matches[1];
            }
        }

        return '';
    }

    /**
     * Check if branch develop exists in git repository.
     */
    protected static function is_branch_develop_exists(): bool
    {
        return is_readable(__DIR__ . '/../../../.git/refs/heads/develop');
    }

    /**
     * Perform a GitHub request.
     * @param string $action
     * @return Object|null
     */
    public static function github_request($action)
    {
        try {
            // https is mandatory
            $url     = "https://api.github.com/repos/ampache/ampache" . $action;
            $request = Requests::get($url, array(), Core::requests_options());

            if ($request->status_code != 200) {
                debug_event(self::class, 'GitHub API request ' . $url . ' failed with http code ' . $request->status_code, 1);
                // Not connected / API rate limit exceeded: just ignore, it will pass next time
                AmpConfig::set('autoupdate_lastcheck', time(), true);

                return null;
            }
            debug_event(self::class, 'GitHub API request ' . $url, 5);

            return json_decode((string)$request->body);
        } catch (Exception $error) {
            debug_event(self::class, 'Request error: ' . $error->getMessage(), 1);

            return null;
        }
    }

    /**
     * Check if last GitHub check expired.
     */
    protected static function lastcheck_expired(): bool
    {
        // if you're not auto updating the check should never expire
        if (!AmpConfig::get('autoupdate', false)) {
            return false;
        }
        $lastcheck = AmpConfig::get('autoupdate_lastcheck');
        if (!$lastcheck) {
            Preference::update('autoupdate_lastcheck', (int)(Core::get_global('user')->id ?? 0), 1);
            AmpConfig::set('autoupdate_lastcheck', '1', true);
        }

        return ((time() - 3600) > $lastcheck);
    }

    /**
     * Get latest available version from GitHub.
     * @param bool $force
     */
    public static function get_latest_version($force = false): string
    {
        $lastversion = (string) AmpConfig::get('autoupdate_lastversion');

        // Don't spam the GitHub API
        if (
            $force === false ||
            self::lastcheck_expired() === false
        ) {
            return $lastversion;
        }


        // Always update last check time to avoid infinite check on permanent errors (proxy, firewall, ...)
        $time       = time();
        $git_branch = self::is_force_git_branch();
        Preference::update('autoupdate_lastcheck', (int)(Core::get_global('user')->id ?? 0), $time);
        AmpConfig::set('autoupdate_lastcheck', $time, true);

        // Development version, get latest commit on develop branch
        if (
            self::is_develop() ||
            $git_branch !== ''
        ) {
            if (
                self::is_develop() ||
                $git_branch == 'develop'
            ) {
                $commits = self::github_request('/commits/develop');
            } else {
                $commits = self::github_request('/commits/' . $git_branch);
            }
            if (!empty($commits)) {
                $lastversion = $commits->sha;
                Preference::update('autoupdate_lastversion', (int)(Core::get_global('user')->id ?? 0), $lastversion);
                AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                $available = self::is_update_available(true);
                Preference::update('autoupdate_lastversion_new', (int)(Core::get_global('user')->id ?? 0), $available);
                AmpConfig::set('autoupdate_lastversion_new', $available, true);

                return $lastversion;
            }
        }
        // Otherwise it is stable version, get latest tag
        $tags = self::github_request('/tags');
        if (!$tags) {
            return $lastversion;
        }
        foreach ($tags as $release) {
            $str = strstr($release->name, "-"); // ignore ALL tagged releases (e.g. 4.2.5-preview 4.2.5-beta)
            if (empty($str)) {
                $lastversion = $release->name;
                Preference::update('autoupdate_lastversion', (int)(Core::get_global('user')->id ?? 0), $lastversion);
                AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                $available = self::is_update_available(true);
                Preference::update('autoupdate_lastversion_new', (int)(Core::get_global('user')->id ?? 0), $available);
                AmpConfig::set('autoupdate_lastversion_new', $available, true);

                return $lastversion;
            }
        }

        return $lastversion;
    }

    /**
     * Get the correct zip for your version.
     * e.g. https://github.com/ampache/ampache/releases/download/6.0.0/ampache-6.0.0_all_php8.2.zip
     * e.g. https://github.com/ampache/ampache/releases/download/6.0.0/ampache-6.0.0_all_squashed_php8.2.zip
     */
    public static function get_zip_url(): string
    {
        $ampversion = self::get_latest_version();
        $structure  = (AmpConfig::get('structure') == 'squashed')
            ? '_squashed'
            : '';
        $phpversion = AmpConfig::get('phpversion');

        return 'https://github.com/ampache/ampache/releases/download/' . $ampversion . '/ampache-' . $ampversion . '_all' . $structure . '_php' . $phpversion . '.zip';
    }

    /**
     * Get current local version.
     */
    public static function get_current_version(): string
    {
        $commit = self::get_current_commit();
        if (!empty($commit)) {
            return $commit;
        } else {
            return AmpConfig::get('version');
        }
    }

    /**
     * Get current local git commit.
     */
    public static function get_current_commit(): string
    {
        $git_branch = self::is_force_git_branch();
        if (
            $git_branch !== '' &&
            is_readable(__DIR__ . '/../../../.git/refs/heads/' . $git_branch)
        ) {
            return trim((string)file_get_contents(__DIR__ . '/../../../.git/refs/heads/' . $git_branch));
        }
        if (self::is_branch_develop_exists()) {
            return trim((string)file_get_contents(__DIR__ . '/../../../.git/refs/heads/develop'));
        }

        return '';
    }

    /**
     * Check if an update is available.
     * @param bool $force
     */
    public static function is_update_available($force = false): bool
    {
        if (
            $force === false ||
            self::lastcheck_expired() === false
        ) {
            return (bool)AmpConfig::get('autoupdate_lastversion_new', false);
        }
        $time = time();
        Preference::update('autoupdate_lastcheck', (int)(Core::get_global('user')->id ?? 0), $time);
        AmpConfig::set('autoupdate_lastcheck', $time, true);

        $available  = false;
        $git_branch = self::is_force_git_branch();
        $current    = self::get_current_version();
        $latest     = self::get_latest_version($force);

        debug_event(self::class, 'Checking latest version online...', 5);
        if (
            !empty($latest) &&
            $current !== $latest
        ) {
            if (
                preg_match("/^[0-9]+\.[0-9]+\.[0-9]+$/", $current) &&
                preg_match("/^[0-9]+\.[0-9]+\.[0-9]+$/", $latest)
            ) {
                $cpart = explode('-', $current);
                $lpart = explode('-', $latest);

                // work around any possible mistakes in the order
                $current = ($cpart[0] == 'release') ? $cpart[1] : $cpart[0];
                $latest  = ($lpart[0] == 'release') ? $lpart[1] : $lpart[0];
                // returns -1 if the first version is lower than the second, (e.g. version_compare(6.3.3, 7.0.0) = -1)
                $available = (version_compare($current, $latest) === -1);
            } elseif (
                self::is_develop() ||
                $git_branch !== ''
            ) {
                $ccommit = AmpConfig::get($current) ?? self::github_request('/commits/' . $current);
                $lcommit = AmpConfig::get($latest) ?? self::github_request('/commits/' . $latest);

                if (
                    !empty($ccommit) &&
                    !empty($lcommit)
                ) {
                    // Comparison based on commit date
                    $ctime = strtotime($ccommit->commit->author->date);
                    $ltime = strtotime($lcommit->commit->author->date);
                    AmpConfig::set($current, $ctime, true);
                    AmpConfig::set($latest, $ltime, true);

                    $available = ($ctime < $ltime);
                }
            }
        }

        return $available;
    }

    /**
     * Display information from the Ampache Project as a message. (Develop branch only)
     */
    public static function show_ampache_message(): void
    {
        if (self::is_develop()) {
            echo '<div id="autoupdate">';
            echo '<span>' . T_("WARNING") . '</span>';
            echo ' (Ampache Develop is about to go through a major change!)<br />';
            echo '<a href="https://github.com/ampache/ampache/wiki/ampache7-for-admins' . '" target="_blank">' . T_('View changes') . '</a><br /> ';
            echo '</div>';
        }
    }

    /**
     * Display new version information and update link if possible.
     */
    public static function show_new_version(): void
    {
        $current = self::get_current_version();
        $latest  = self::get_latest_version();

        // Don't show anything if the current version is newer than the second, (e.g. version_compare(7.0.0, 6.9.0) = 1)
        if (
            empty($latest) ||
            $current === $latest ||
            (
                preg_match("/^[0-9]+\.[0-9]+\.[0-9]+$/", $current) &&
                preg_match("/^[0-9]+\.[0-9]+\.[0-9]+$/", $latest) &&
                version_compare($current, $latest) === 1
            )
        ) {
            echo '<div id="autoupdate">';
            echo '</div>';

            return;
        }
        $git_branch    = self::is_force_git_branch();
        $develop_check = self::is_develop() || $git_branch != '';
        $changelog     = ($git_branch == '') ? 'master' : $git_branch;
        $zip_name      = ($git_branch == '') ? 'develop' : $git_branch;

        echo '<div id="autoupdate">';
        echo '<span>' . T_('Update available') . '</span>';
        echo ' (' . $latest . ').<br />';
        echo '<a href="https://github.com/ampache/ampache/' . ($develop_check ? 'compare/' . $current . '...' . $latest : 'blob/' . $changelog . '/docs/CHANGELOG.md') . '" target="_blank">' . T_('View changes') . '</a> ';
        if ($develop_check) {
            echo ' | <a href="https://github.com/ampache/ampache/archive/' . $zip_name . '.zip' . '" target="_blank">' . T_('Download') . '</a>';
        } else {
            echo ' | <a href="' . self::get_zip_url() . '" target="_blank">' . T_('Download') . '</a>';
        }
        if (self::is_git_repository()) {
            echo ' | <a class="nohtml" href="' . AmpConfig::get('web_path') . '/update.php?type=sources&action=update"> <b>' . T_('Update') . '</b></a>';
        }
        echo '</div>';
    }

    /**
     * Update local git repository.
     * @param bool $api
     */
    public static function update_files($api = false): void
    {
        $cmd        = 'git pull https://github.com/ampache/ampache.git';
        $git_branch = self::is_force_git_branch();
        if ($git_branch !== '') {
            $cmd = 'git pull https://github.com/ampache/ampache.git ' . $git_branch;
        } elseif (self::is_develop()) {
            $cmd = 'git pull https://github.com/ampache/ampache.git develop';
        }
        if (!$api) {
            echo T_('Updating Ampache sources with `' . $cmd . '` ...') . '<br />';
        }
        ob_flush();
        chdir(__DIR__ . '/../../../');
        exec($cmd);
        if (!$api) {
            echo T_('Done') . '<br />';
        }
        ob_flush();
        self::get_latest_version(true);
    }

    /**
     * Update project dependencies.
     */
    public static function update_dependencies(
        ConfigContainerInterface $config,
        bool $api = false
    ): void {
        $cmd = sprintf(
            '%s install --no-dev --prefer-source --no-interaction',
            $config->getComposerBinaryPath()
        );
        if (!$api) {
            echo T_('Updating dependencies with `' . $cmd . '` ...') . '<br />';
        }
        ob_flush();
        chdir(__DIR__ . '/../../../');
        exec($cmd);
        if (!$api) {
            echo T_('Done') . '<br />';
        }
        ob_flush();
    }
}
