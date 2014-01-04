<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * AutoUpdate Class
 *
 * This class handles autoupdate check from Github.
 */

class AutoUpdate
{
    /*
     * Constructor
     *
     * This should never be called
     */
    private function __construct()
    {
        // static class
    }

    protected static function is_develop()
    {
        $version = AmpConfig::get('version');
        $vspart = explode('-', $version);

        return ($vspart[count($vspart) - 1] == 'develop');
    }

    protected static function is_git_repository()
    {
        return is_dir(AmpConfig::get('prefix') . '/.git');
    }

    public static function github_request($action)
    {
        $url = "https://api.github.com/repos/ampache-doped/ampache" . $action;
        $request = Requests::get($url);

        // Not connected / API rate limit exceeded: just ignore, it will pass next time
        if ($request->status_code != 200) {
            debug_event('autoupdate', 'Github API request ' . $url . ' failed with http code ' . $request->status_code, '1');
            return;
        }
        return json_decode($request->body);
    }

    protected static function lastcheck_expired()
    {
        $lastcheck = AmpConfig::get('autoupdate_lastcheck');
        if (!$lastcheck) {
            User::rebuild_all_preferences();
            Preference::update('autoupdate_lastcheck', $GLOBALS['user']->id, '1');
            AmpConfig::set('autoupdate_lastcheck', '1', true);
        }

        return ((time() - (3600 * 3)) > $lastcheck);
    }

    public static function get_latest_version($force = false)
    {
        // Forced or last check expired, check latest version from Github
        if ($force || (self::lastcheck_expired() && AmpConfig::get('autoupdate'))) {
            // Development version, get latest commit on develop branch
            if (self::is_develop()) {
                $commits = self::github_request('/commits/develop');
                if (!empty($commits)) {
                    $lastversion = $commits[0]->sha;
                    Preference::update('autoupdate_lastversion', $GLOBALS['user']->id, $lastversion);
                    AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                    $time = time();
                    Preference::update('autoupdate_lastcheck', $GLOBALS['user']->id, $time);
                    AmpConfig::set('autoupdate_lastcheck', $time, true);
                    $available = self::is_update_available(true);
                    Preference::update('autoupdate_lastversion_new', $GLOBALS['user']->id, $available);
                    AmpConfig::set('autoupdate_lastversion_new', $available, true);
                }
            }
            // Otherwise it is stable version, get latest tag
            else {
                $tags = self::github_request('/tags');
                if (!empty($tags)) {
                    $lastversion = $tags[0]->name;
                    Preference::update('autoupdate_lastversion', $GLOBALS['user']->id, $lastversion);
                    AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                    $time = time();
                    Preference::update('autoupdate_lastcheck', $GLOBALS['user']->id, $time);
                    AmpConfig::set('autoupdate_lastcheck', $time, true);
                    $available = self::is_update_available(true);
                    Preference::update('autoupdate_lastversion_new', $GLOBALS['user']->id, $available);
                    AmpConfig::set('autoupdate_lastversion_new', $available, true);
                }
            }
        }
        // Otherwise retrieve the cached version number
        else {
            $lastversion = AmpConfig::get('autoupdate_lastversion');
        }

        return $lastversion;
    }

    public static function get_current_version()
    {
        if (self::is_develop()) {
            return self::get_current_commit();
        } else {
            return AmpConfig::get('version');
        }
    }

    public static function get_current_commit()
    {
        return trim(file_get_contents(AmpConfig::get('prefix') . '/.git/refs/heads/develop'));
    }

    public static function is_update_available($force = false)
    {
        if (!$force && (!self::lastcheck_expired() || !AmpConfig::get('autoupdate'))) {
            return AmpConfig::get('autoupdate_lastversion_new');
        }

        $available = false;
        $current = self::get_current_version();
        $latest = self::get_latest_version();

        if ($current != $latest) {
            if (self::is_develop()) {
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

    public static function show_new_version()
    {
        echo '<div>';
        echo '<font color="#ff0000">' . T_('Update') . ':</font> ';
        echo T_('A new Ampache version is available');
        echo ' (' . self::get_latest_version() . ').<br />';

        echo T_('See') . ' <a href="https://github.com/ampache-doped/ampache/' . (self::is_develop() ? 'compare/' . self::get_latest_version() . '...' . self::get_current_version() : 'blob/master/docs/CHANGELOG.md') . '" target="_blank">' . T_('ChangeLog') . '</a> ';
        echo T_('or') . ' <a href="https://github.com/ampache-doped/ampache/archive/' . (self::is_develop() ? 'develop.zip' : self::get_latest_version() . '.zip') . '" target="_blank"><b>' . T_('download') . '</b></a>.';
        echo '</div>';
    }
}
