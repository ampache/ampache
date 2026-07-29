<?php

declare(strict_types=0);

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

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Preference;
use Exception;
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
    protected static function _is_develop(): bool
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
    protected static function _is_git_repository(): bool
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
                !in_array((string)$matches[1], ['master', 'release5', 'release6', 'release7'])
            ) {
                return (string)$matches[1];
            }
        }

        return '';
    }

    /**
     * Check if branch develop exists in git repository.
     */
    protected static function _is_branch_develop_exists(): bool
    {
        return is_readable(__DIR__ . '/../../../.git/refs/heads/develop');
    }

    /**
     * Perform a GitHub request.
     */
    public static function github_request(string $action): ?object
    {
        try {
            // https is mandatory
            $url     = "https://api.github.com/repos/ampache/ampache" . $action;
            $request = Requests::get($url, [], Core::requests_options());
            if ($request->status_code != 200) {
                debug_event(self::class, 'GitHub API request ' . $url . ' failed with http code ' . $request->status_code, 1);
                // Not connected / API rate limit exceeded: just ignore, it will pass next time
                self::_set_lastcheck(time());

                return null;
            }
            debug_event(self::class, 'GitHub API request ' . $url, 5);
            $result = json_decode((string)$request->body);

            return (is_object($result))
                ? $result
                : null;
        } catch (Exception $error) {
            debug_event(self::class, 'Request error: ' . $error->getMessage(), 1);

            return null;
        }
    }

    /**
     * Check if last GitHub check expired.
     */
    public static function lastcheck_expired(): bool
    {
        // if you're not auto updating the check should never expire
        if (!AmpConfig::get('autoupdate', false)) {
            return false;
        }

        $lastcheck = AmpConfig::get('autoupdate_lastcheck');
        if (!$lastcheck) {
            Preference::update_all('autoupdate_lastcheck', 1);
            AmpConfig::set('autoupdate_lastcheck', '1', true);
        }

        return ((time() - 3600) > $lastcheck);
    }

    /**
     * Get latest available version from GitHub.
     */
    public static function get_latest_version(?bool $force = false): string
    {
        $lastversion = (string) AmpConfig::get('autoupdate_lastversion');

        // Don't spam the GitHub API
        if (
            $force === false &&
            self::lastcheck_expired() === false
        ) {
            return $lastversion;
        }

        // Always update last check time to avoid infinite check on permanent errors (proxy, firewall, ...)
        self::_set_lastcheck(time());

        $git_branch = self::is_force_git_branch();
        // Development version, get latest commit on develop branch
        if (
            self::_is_develop() ||
            $git_branch !== ''
        ) {
            if (
                self::_is_develop() ||
                $git_branch == 'develop'
            ) {
                $commits = self::github_request('/commits/develop');
            } else {
                $commits = self::github_request('/commits/' . $git_branch);
            }
            if (
                !empty($commits) &&
                isset($commits->sha)
            ) {
                $lastversion = $commits->sha;
                Preference::update_all('autoupdate_lastversion', $lastversion);
                AmpConfig::set('autoupdate_lastversion', $lastversion, true);

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
                Preference::update_all('autoupdate_lastversion', $lastversion);
                AmpConfig::set('autoupdate_lastversion', $lastversion, true);

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
        }

        return AmpConfig::get('version');
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
        if (self::_is_branch_develop_exists()) {
            return trim((string)file_get_contents(__DIR__ . '/../../../.git/refs/heads/develop'));
        }

        return '';
    }

    /**
     * Check if an update is available.
     */
    public static function is_update_available(?bool $force = false): bool
    {
        if (
            $force === false &&
            self::lastcheck_expired() === false
        ) {
            return (bool)AmpConfig::get('autoupdate_lastversion_new', false);
        }

        if ($force) {
            self::_set_lastcheck(time());
        }

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
                self::_is_develop() ||
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
        //if (self::_is_develop()) {
        //    echo '<div id="autoupdate">';
        //    echo '<span>' . T_("WARNING") . '</span>';
        //    echo ' (Ampache Develop is about to go through a major change!)<br />';
        //    echo '<a href="https://github.com/ampache/ampache/pull/4387" target="_blank">' . T_('View changes') . '</a><br /> ';
        //    echo '</div>';
        //}
    }

    protected static function _set_lastcheck(int $time): void
    {
        //debug_event(self::class, 'Set autoupdate_lastcheck to ' . $time, 5);
        Preference::update_all('autoupdate_lastcheck', $time);
        AmpConfig::set('autoupdate_lastcheck', $time, true);
    }

    /**
     * Reset and clear information about impending updates for git installs
     */
    public static function clear_status(): void
    {
        $time = time();
        // reset the update status
        Preference::update_all('autoupdate_lastversion', null);
        AmpConfig::set('autoupdate_lastversion', null, true);
        Preference::update_all('autoupdate_lastversion_new', 0);
        AmpConfig::set('autoupdate_lastversion_new', false, true);
        Preference::update_all('autoupdate_lastcheck', $time);
        AmpConfig::set('autoupdate_lastcheck', (string)$time, true);
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
        $develop_check = self::_is_develop() || $git_branch != '';
        $changelog     = ($git_branch == '') ? 'master' : $git_branch;
        $zip_name      = ($git_branch == '') ? 'develop' : $git_branch;

        echo '<div id="autoupdate">';
        echo '<span>' . T_('Update available') . '</span>';
        echo ' (' . $latest . ')<br />';
        echo '<a href="https://github.com/ampache/ampache/' . (($develop_check) ? 'compare/' . $current . '...' . $latest : 'blob/' . $changelog . '/docs/CHANGELOG.md') . '" target="_blank">' . T_('View changes') . '</a> ';
        if ($develop_check) {
            echo ' | <a href="https://github.com/ampache/ampache/archive/' . $zip_name . '.zip' . '" target="_blank">' . T_('Download') . '</a>';
        } else {
            echo ' | <a href="' . self::get_zip_url() . '" target="_blank">' . T_('Download') . '</a>';
        }
        if (self::_is_git_repository()) {
            echo ' | <a class="nohtml" href="' . AmpConfig::get_web_path() . '/update.php?type=sources&action=update"> <b>' . T_('Update') . '</b></a>';
            echo ' | <a class="nohtml" href="' . AmpConfig::get_web_path() . '/update.php?type=sources&action=clear">' . T_('Ignore') . '</a>';
        }
        echo '</div>';
    }

    /**
     * Update local git repository. Returns false when git was unavailable or exited with an error.
     */
    public static function update_files(?bool $api = false): bool
    {
        if (!self::_can_execute((bool) $api)) {
            return false;
        }

        $cmd        = 'git pull https://github.com/ampache/ampache.git';
        $git_branch = self::is_force_git_branch();
        if ($git_branch !== '') {
            $cmd = 'git pull https://github.com/ampache/ampache.git ' . $git_branch;
        } elseif (self::_is_develop()) {
            $cmd = 'git pull https://github.com/ampache/ampache.git develop';
        }
        if (!$api) {
            echo T_('Updating Ampache sources with `' . $cmd . '` ...') . '<br />';
        }
        self::_flush_output();
        chdir(__DIR__ . '/../../../');
        $success = self::_run_command($cmd, (bool) $api);
        if (!$api) {
            echo(($success) ? T_('Done') : T_('Update failed. Please check the logs for further information.')) . '<br />';
        }
        self::_flush_output();

        if (!$success) {
            return false;
        }

        $commit = self::get_current_commit();
        if (!empty($commit)) {
            // reset the update status
            Preference::update_all('autoupdate_lastversion', $commit);
            AmpConfig::set('autoupdate_lastversion', $commit, true);
            Preference::update_all('autoupdate_lastversion_new', 0);
            AmpConfig::set('autoupdate_lastversion_new', false, true);
        }

        return true;
    }

    /**
     * Update project dependencies. Returns false when a command was unavailable or exited with an error.
     */
    public static function update_dependencies(
        ConfigContainerInterface $config,
        bool $api = false
    ): bool {
        if (!self::_can_execute($api)) {
            return false;
        }

        chdir(__DIR__ . '/../../../');

        $cmdComposer = sprintf(
            '%s install %s',
            $config->getComposerBinaryPath(),
            $config->getComposerParameters()
        );

        // npm must install the dev packages because the postinstall (npm-run-all, copyfiles) and build (vite)
        // scripts that produce every shipped asset live there, and a webserver may inherit NODE_ENV=production
        $cmdNpm = sprintf(
            '%s install --include=dev --loglevel info',
            $config->getNpmBinaryPath()
        );

        $cmdNpmBuild = sprintf(
            '%s run build --loglevel info',
            $config->getNpmBinaryPath()
        );

        if (!$api) {
            echo T_('Updating dependencies with `' . $cmdComposer . '` ...') . '<br />';
            echo T_('Updating dependencies with `' . $cmdNpm . '` ...') . '<br />';
            echo T_('Updating npm build with `' . $cmdNpmBuild . '` ...') . '<br />';
        }

        // node_modules, vendor and the generated asset directories are written by these commands, so a checkout
        // owned by a different user than the webserver fails halfway through and leaves a broken install behind
        $unwritable = self::_unwritable_dependency_paths();
        if (!empty($unwritable)) {
            $message = sprintf(
                /* HINT: comma separated list of filesystem paths */
                T_('Unable to update dependencies: the web server user cannot write to %s'),
                implode(', ', $unwritable)
            );
            debug_event(self::class, $message, 1);
            if (!$api) {
                echo scrub_out($message) . '<br />';
            }

            return false;
        }

        // set NPM paths to allow AutoUpdate to work with the webserver
        if ((strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) {
            $cmdNpm      = 'export PATH=$PATH:./node_modules/.bin/ && ' . $cmdNpm;
            $cmdNpmBuild = 'export PATH=$PATH:./node_modules/.bin/ && ' . $cmdNpmBuild;
        }

        self::_redirect_npm_home();

        $restored = self::_reset_dirty_vendor_checkouts();
        if (!empty($restored)) {
            $message = sprintf(
                'Restored: %s',
                implode(', ', $restored)
            );
            debug_event(self::class, $message, 3);
            if (!$api) {
                echo scrub_out($message) . '<br />';
            }
        }

        self::_flush_output();

        $success = self::_run_command($cmdComposer, $api);
        $success = self::_run_command($cmdNpm, $api) && $success;
        $success = self::_run_command($cmdNpmBuild, $api) && $success;

        if (!$api) {
            echo(($success) ? T_('Done') : T_('Update failed. Please check the logs for further information.')) . '<br />';
        }

        self::_flush_output();

        sleep(5);

        return $success;
    }

    /**
     * Check that shell commands can be run at all. Hosts regularly blacklist exec() in disable_functions, where
     * calling it would abort the request with a fatal error after the sources have already been pulled.
     */
    private static function _can_execute(bool $api): bool
    {
        if (function_exists('exec')) {
            return true;
        }

        $message = T_('Unable to run the update: the PHP exec() function is disabled on this server');
        debug_event(self::class, $message, 1);
        if (!$api) {
            echo scrub_out($message) . '<br />';
        }

        return false;
    }

    /**
     * Send anything buffered so far to the browser so a long running update reports progress as it happens.
     */
    private static function _flush_output(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    /**
     * npm refuses to run when it cannot create its cache directory, which is what happens under a web server user
     * whose home directory isn't writable, so point it at a directory we know can be written instead.
     */
    private static function _redirect_npm_home(): void
    {
        $home = (string) getenv('HOME');
        if (!empty($home) && is_writable($home)) {
            return;
        }

        $cache = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'ampache-npm';
        if (!is_dir($cache) && !mkdir($cache, 0755, true) && !is_dir($cache)) {
            debug_event(self::class, 'Unable to create an npm cache directory at ' . $cache, 1);

            return;
        }

        putenv('HOME=' . $cache);
        putenv('npm_config_cache=' . $cache);
    }

    /**
     * Restore vendor git checkouts that were dirtied by install time code generation.
     *
     * Composer refuses to remove or update a package installed from source when its working tree has local
     * modifications, and packages writing into their own directory during install (phpstan/extension-installer
     * regenerates src/GeneratedConfig.php) leave that state behind. Installs made before the switch to
     * --prefer-dist still hold those checkouts, so a --no-dev run would abort partway through removing the dev
     * packages and leave vendor in a broken state.
     *
     * @return string[] the packages that were restored
     */
    private static function _reset_dirty_vendor_checkouts(): array
    {
        $vendor = realpath(__DIR__ . '/../../../vendor');
        if ($vendor === false) {
            return [];
        }

        $restored = [];
        foreach (glob($vendor . '/*/*/.git', GLOB_ONLYDIR) ?: [] as $gitDir) {
            $path   = dirname($gitDir);
            $output = [];
            $status = 0;

            // composer only inspects tracked files here, so untracked leftovers are not worth touching
            exec(sprintf('git -C %s status --porcelain --untracked-files=no 2>&1', escapeshellarg($path)), $output, $status);
            if ($status !== 0 || empty($output)) {
                continue;
            }

            $discard = [];
            exec(sprintf('git -C %s checkout -- . 2>&1', escapeshellarg($path)), $discard, $status);
            if ($status !== 0) {
                debug_event(self::class, 'Unable to restore the modified dependency sources at ' . $path, 1);

                continue;
            }

            $restored[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($vendor) + 1));
        }

        return $restored;
    }

    /**
     * Run an update command, echoing its output, and report whether it exited cleanly.
     */
    private static function _run_command(string $command, bool $api): bool
    {
        $output = [];
        $status = 0;
        exec($command . ' 2>&1', $output, $status);

        if ($status !== 0) {
            debug_event(self::class, sprintf('Command exited with status %d: %s', $status, $command), 1);
            foreach ($output as $line) {
                debug_event(self::class, $line, 1);
            }
        }

        if (!$api) {
            // the tail carries the reason a command stopped, which is all an admin has to work from in the browser
            foreach (array_slice($output, -30) as $line) {
                echo scrub_out($line) . '<br />';
            }
        }

        return $status === 0;
    }

    /**
     * Collect the paths the dependency install has to write to that the current user cannot write.
     *
     * @return string[]
     */
    private static function _unwritable_dependency_paths(): array
    {
        $root       = realpath(__DIR__ . '/../../../') ?: __DIR__ . '/../../../';
        $unwritable = [];
        foreach ([$root, $root . '/node_modules', $root . '/vendor'] as $path) {
            // a missing directory is created by the command itself, so the parent is what has to be writable
            $target = (is_dir($path)) ? $path : dirname($path);
            if (!self::_is_writable_directory($target)) {
                $unwritable[] = $path;
            }
        }

        return $unwritable;
    }

    /**
     * Test a directory by writing to it. is_writable() reports the permission bits, which disagree with reality
     * on anything using ACLs or a remote filesystem, and a false alarm here would block an update that works.
     */
    private static function _is_writable_directory(string $path): bool
    {
        $probe = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '.ampache-update-write-test';
        if (!@touch($probe)) {
            return false;
        }

        @unlink($probe);

        return true;
    }
}
