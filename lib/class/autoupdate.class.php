<?php
declare(strict_types=0);
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

/**
 * AutoUpdate Class
 *
 * This class handles autoupdate check from Github.
 */

class AutoUpdate
{
    /**
     * Constructor
     *
     * This should never be called
     */
    private function __construct()
    {
        // static class
    }

    /**
     * Check if current version is a development version.
     * the string for develop is just 'develop'
     * @return boolean
     */
    protected static function is_develop()
    {
        $version    = (string) AmpConfig::get('version');
        $git_branch = self::is_force_git_branch();

        if ($git_branch == 'develop' || $version == 'develop') {
            return true;
        }

        return false;
    }

    /**
     * Check if current version is a git repository.
     * @return boolean
     */
    protected static function is_git_repository()
    {
        return is_dir(AmpConfig::get('prefix') . '/.git');
    }

    /**
     * Check if there is a default branch set in the config file.
     * @return string
     */
    protected static function is_force_git_branch()
    {
        return (string) AmpConfig::get('github_force_branch');
    }

    /**
     * Check if branch develop exists in git repository.
     * @return boolean
     */
    protected static function is_branch_develop_exists()
    {
        return is_readable(AmpConfig::get('prefix') . '/.git/refs/heads/develop');
    }

    /**
     * Perform a GitHub request.
     * @param string $action
     * @return string|null
     */
    public static function github_request($action)
    {
        try {
            // https is mandatory
            $url     = "https://api.github.com/repos/ampache/ampache" . $action;
            $request = Requests::get($url, array(), Core::requests_options());

            // Not connected / API rate limit exceeded: just ignore, it will pass next time
            if ($request->status_code != 200) {
                debug_event(self::class, 'Github API request ' . $url . ' failed with http code ' . $request->status_code, 1);

                return null;
            }

            return json_decode((string) $request->body);
        } catch (Exception $error) {
            debug_event(self::class, 'Request error: ' . $error->getMessage(), 1);

            return null;
        }
    }

    /**
     * Check if last github check expired.
     * @return boolean
     */
    protected static function lastcheck_expired()
    {
        $lastcheck = AmpConfig::get('autoupdate_lastcheck');
        if (!$lastcheck) {
            Preference::update('autoupdate_lastcheck', Core::get_global('user')->id, 1);
            AmpConfig::set('autoupdate_lastcheck', '1', true);
        }

        return ((time() - (3600 * 8)) > $lastcheck);
    }

    /**
     * Get latest available version from GitHub.
     * @param boolean $force
     * @return string
     */
    public static function get_latest_version($force = false)
    {
        $lastversion = (string) AmpConfig::get('autoupdate_lastversion');
        // Forced or last check expired, check latest version from Github
        if ($force || (self::lastcheck_expired() && AmpConfig::get('autoupdate'))) {
            // Always update last check time to avoid infinite check on permanent errors (proxy, firewall, ...)
            $time       = time();
            $git_branch = self::is_force_git_branch();
            $is_develop = self::is_develop();
            Preference::update('autoupdate_lastcheck', Core::get_global('user')->id, $time);
            AmpConfig::set('autoupdate_lastcheck', $time, true);

            if ($is_develop) {
                // Get latest commit on develop branch
                $commits = self::github_request('/commits/develop');
            } elseif ($git_branch !== '') {
                // Get latest commit on a custom branch (edge? master?)
                $commits = self::github_request('/commits/' . $git_branch);
            }
            // check against the releases on github
            if (!empty($commits)) {
                $lastversion = $commits->sha;
                Preference::update('autoupdate_lastversion', Core::get_global('user')->id, $lastversion);
                AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                $available = self::is_update_available(true);
                Preference::update('autoupdate_lastversion_new', Core::get_global('user')->id, $available);
                AmpConfig::set('autoupdate_lastversion_new', $available, true);

                return $lastversion;
            }
            // Otherwise it is stable version, get latest tag
            $tags = self::github_request('/tags');
            foreach ($tags as $release) {
                $str = strstr($release->name, "-"); // ignore ALL tagged releases (e.g. 4.2.5-preview 4.2.5-beta)
                if (empty($str)) {
                    $lastversion = $release->name;
                    Preference::update('autoupdate_lastversion', Core::get_global('user')->id, $lastversion);
                    AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                    $available = self::is_update_available(true);
                    Preference::update('autoupdate_lastversion_new', Core::get_global('user')->id, $available);
                    AmpConfig::set('autoupdate_lastversion_new', $available, true);

                    return $lastversion;
                }
            }
        }

        return $lastversion;
    }

    /**
     * Get current local version.
     * @return string
     */
    public static function get_current_version()
    {
        $git_branch = self::is_force_git_branch();
        $is_develop = self::is_develop();
        if ($is_develop || $git_branch !== '') {
            return self::get_current_commit();
        } else {
            return AmpConfig::get('version');
        }
    }

    /**
     * Get current local git commit.
     * @return string
     */
    public static function get_current_commit()
    {
        $git_branch = self::is_force_git_branch();
        if ($git_branch !== '' && is_readable(AmpConfig::get('prefix') . '/.git/refs/heads/' . $git_branch)) {
            return trim(file_get_contents(AmpConfig::get('prefix') . '/.git/refs/heads/' . $git_branch));
        }
        if (self::is_branch_develop_exists()) {
            return trim(file_get_contents(AmpConfig::get('prefix') . '/.git/refs/heads/develop'));
        }

        return '';
    }

    /**
     * Check if an update is available.
     * @param boolean $force
     * @return boolean
     */
    public static function is_update_available($force = false)
    {
        if (!$force && (!self::lastcheck_expired() || !AmpConfig::get('autoupdate'))) {
            return AmpConfig::get('autoupdate_lastversion_new');
        }

        debug_event(self::class, 'Checking latest version online...', 5);

        $available  = false;
        $git_branch = self::is_force_git_branch();
        $is_develop = self::is_develop();
        $current    = self::get_current_version();
        $latest     = self::get_latest_version();

        if ($current != $latest && !empty($current)) {
            if ($is_develop || $git_branch !== '') {
                $ccommit = self::github_request('/commits/' . $current);
                $lcommit = self::github_request('/commits/' . $latest);

                if (!empty($ccommit) && !empty($lcommit)) {
                    // Comparison based on commit date
                    $ctime = strtotime($ccommit->commit->author->date);
                    $ltime = strtotime($lcommit->commit->author->date);

                    $available = ($ctime < $ltime);
                }
            } else {
                $cpart = explode('-', $current);
                $lpart = explode('-', $latest);

                $available = (version_compare($cpart[0], $lpart[0]) < 0);
            }
        }

        return $available;
    }

    /**
     * Display information from the Ampache Project as a message. (Develop branch only)
     */
    public static function show_ampache_message()
    {
        $is_develop = self::is_develop();
        if ($is_develop) {
            echo '<div id="autoupdate">';
            echo '<span>' . T_("WARNING") . '</span>';
            echo ' (Ampache Develop is about to go through a major change!)<br />';
            echo '<a href="https://github.com/ampache/ampache/wiki/Ampache-Next-Changes' . '" target="_blank">' . T_('View changes') . '</a><br /> ';
            echo '</div>';
        }
    }

    /**
     * Display new version information and update link if possible.
     */
    public static function show_new_version()
    {
        echo '<div id="autoupdate">';
        echo '<span>' . T_('Update available') . '</span>';
        echo ' (' . self::get_latest_version() . ').<br />';
        $git_branch    = self::is_force_git_branch();
        $is_develop    = self::is_develop();
        $develop_check = $is_develop || $git_branch != '';
        $changelog     = ($git_branch == '') ? 'master' : $git_branch;
        $zip_name      = ($git_branch == '') ? 'develop' : $git_branch;

        echo '<a href="https://github.com/ampache/ampache/' . ($develop_check ? 'compare/' . self::get_current_version() . '...' . self::get_latest_version() : 'blob/' . $changelog . '/docs/CHANGELOG.md') . '" target="_blank">' . T_('View changes') . '</a> ';
        if ($develop_check) {
            echo ' | <a href="https://github.com/ampache/ampache/archive/' . $zip_name . '.zip' . '" target="_blank">' . T_('Download') . '</a>';
        } else {
            echo ' | <a href="https://github.com/ampache/ampache/releases/download/' . self::get_latest_version() .
              '/ampache-' . self::get_latest_version() . '_all.zip"' . ' target="_blank">' . T_('Download') . '</a>';
        }
        if (self::is_git_repository()) {
            echo ' | <a class="nohtml" href="' . AmpConfig::get('web_path') . '/update.php?type=sources&action=update"> <b>' . T_('Update') . '</b></a>';
        }
        echo '</div>';
    }

    /**
     * Update local git repository.
     * @param boolean $api
     */
    public static function update_files($api = false)
    {
        $cmd        = 'git pull https://github.com/ampache/ampache.git';
        $git_branch = self::is_force_git_branch();
        $is_develop = self::is_develop();
        if ($git_branch !== '') {
            $cmd = 'git pull https://github.com/ampache/ampache.git ' . $git_branch;
        } elseif ($is_develop) {
            $cmd = 'git pull https://github.com/ampache/ampache.git develop';
        }
        if (!$api) {
            echo T_('Updating Ampache sources with `' . $cmd . '` ...') . '<br />';
        }
        ob_flush();
        chdir(AmpConfig::get('prefix'));
        exec($cmd);
        if (!$api) {
            echo T_('Done') . '<br />';
        }
        ob_flush();
        self::get_latest_version(true);
    }

    /**
     * Update project dependencies.
     * @param boolean $api
     */
    public static function update_dependencies($api = false)
    {
        $cmd = 'composer install --prefer-source --no-interaction';
        if (!$api) {
            echo T_('Updating dependencies with `' . $cmd . '` ...') . '<br />';
        }
        ob_flush();
        chdir(AmpConfig::get('prefix'));
        exec($cmd);
        if (!$api) {
            echo T_('Done') . '<br />';
        }
        ob_flush();
    }
} // end autoupdate.class
