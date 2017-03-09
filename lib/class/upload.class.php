<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Upload
{
    /**
     * Constructor
     * This pulls the license information from the database and returns
     * a constructed object
     */
    protected function __construct()
    {
        return false;
    } // Constructor

    public static function process()
    {
        header('Content-Type: application/json');
        ob_start();
        define('CLI', true);

        $catalog_id = AmpConfig::get('upload_catalog');
        if ($catalog_id > 0) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog->catalog_type == "local") {
                $allowed = explode('|', AmpConfig::get('catalog_file_pattern'));

                if (isset($_FILES['upl']) && $_FILES['upl']['error'] == 0) {
                    $extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);

                    if (!in_array(strtolower($extension), $allowed)) {
                        debug_event('upload', 'File extension `' . $extension . '` not allowed.', '2');
                        return self::rerror();
                    }

                    $rootdir   = self::get_root($catalog);
                    $targetdir = $rootdir;
                    $folder    = $_POST['folder'];
                    if ($folder == '..') {
                        $folder = '';
                    }
                    if (!empty($folder)) {
                        $targetdir .= DIRECTORY_SEPARATOR . $folder;
                    }

                    $targetdir = realpath($targetdir);
                    if (strpos($targetdir, $rootdir) === false) {
                        debug_event('upload', 'Something wrong with final upload path.', 1);
                        return self::rerror();
                    }

                    $targetfile = $targetdir . DIRECTORY_SEPARATOR . $_FILES['upl']['name'];
                    if (Core::is_readable($targetfile)) {
                        debug_event('upload', 'File `' . $targetfile . '` already exists.', 3);
                        $targetfile .= '_' . time();
                        if (Core::is_readable($targetfile)) {
                            debug_event('upload', 'File `' . $targetfile . '` already exists.', 1);
                            return self::rerror();
                        }
                    }

                    if (move_uploaded_file($_FILES['upl']['tmp_name'], $targetfile)) {
                        debug_event('upload', 'File uploaded to `' . $targetfile . '`.', 5);

                        if (AmpConfig::get('upload_script')) {
                            chdir($targetdir);
                            $script = AmpConfig::get('upload_script');
                            $script = str_replace('%FILE%', $targetfile, $script);
                            exec($script);
                        }

                        $options                = array();
                        $options['user_upload'] = $GLOBALS['user']->id;
                        if (isset($_POST['license'])) {
                            $options['license'] = $_POST['license'];
                        }
                        $artist_id = intval($_REQUEST['artist']);
                        $album_id  = intval($_REQUEST['album']);

                        // Override artist information with artist's user
                        if (AmpConfig::get('upload_user_artist')) {
                            $artists = $GLOBALS['user']->get_artists();
                            $artist  = null;
                            // No associated artist yet, we create a default one for the user sender
                            if (count($artists) == 0) {
                                $artists[] = Artist::check($GLOBALS['user']->f_name);
                                $artist    = new Artist($artists[0]);
                                $artist->update_artist_user($GLOBALS['user']->id);
                            } else {
                                $artist = new Artist($artists[0]);
                            }
                            $artist_id = $artist->id;
                        } else {
                            // Try to create a new artist
                            if (isset($_REQUEST['artist_name'])) {
                                $artist_id = Artist::check($_REQUEST['artist_name'], null, true);
                                if ($artist_id && !Access::check('interface', 50)) {
                                    debug_event('upload', 'An artist with the same name already exists, uploaded song skipped.', 3);
                                    return self::rerror($targetfile);
                                } else {
                                    $artist_id = Artist::check($_REQUEST['artist_name']);
                                    $artist    = new Artist($artist_id);
                                    if (!$artist->get_user_owner()) {
                                        $artist->update_artist_user($GLOBALS['user']->id);
                                    }
                                }
                            }
                            if (!Access::check('interface', 50)) {
                                // If the user doesn't have privileges, check it is assigned to an artist he owns
                                if (!$artist_id) {
                                    debug_event('upload', 'Artist information required, uploaded song skipped.', 3);
                                    return self::rerror($targetfile);
                                }
                                $artist = new Artist($artist_id);
                                if ($artist->get_user_owner() != $GLOBALS['user']->id) {
                                    debug_event('upload', 'Artist owner doesn\'t match the current user.', 3);
                                    return self::rerror($targetfile);
                                }
                            }
                        }
                        // Try to create a new album
                        if (isset($_REQUEST['album_name'])) {
                            $album_id = Album::check($_REQUEST['album_name'], 0, 0, null, null, $artist_id);
                        }

                        if (!Access::check('interface', 50)) {
                            // If the user doesn't have privileges, check it is assigned to an album he owns
                            if (!$album_id) {
                                debug_event('upload', 'Album information required, uploaded song skipped.', 3);
                                return self::rerror($targetfile);
                            }
                            $album = new Album($album_id);
                            if ($album->get_user_owner() != $GLOBALS['user']->id) {
                                debug_event('upload', 'Album owner doesn\'t match the current user.', 3);
                                return self::rerror($targetfile);
                            }
                        }

                        if ($artist_id) {
                            $options['artist_id'] = $artist_id;
                        }
                        if ($album_id) {
                            $options['album_id'] = $album_id;
                        }
                        if (AmpConfig::get('upload_catalog_pattern')) {
                            $options['move_match_pattern'] = true;
                        }

                        if (!$catalog->add_file($targetfile, $options)) {
                            return self::rerror($targetfile);
                        }

                        ob_get_contents();
                        ob_end_clean();
                        echo '{"status":"success"}';
                        return true;
                    } else {
                        debug_event('upload', 'Cannot copy the file to target directory. Please check write access.', '1');
                    }
                }
            } else {
                debug_event('upload', 'The catalog must be local to upload files on it.', '1');
            }
        } else {
            debug_event('upload', 'No catalog target upload configured.', '1');
        }

        return self::rerror();
    }

    public static function rerror($file = null)
    {
        if ($file) {
            @unlink($file);
        }
        @header($_SERVER['SERVER_PROTOCOL'] . ' 500 File Upload Error', true, 500);
        ob_get_contents();
        ob_end_clean();
        echo '{"status":"error"}';
        return false;
    }

    public static function get_root($catalog = null, $username = null)
    {
        if ($catalog == null) {
            $catalog_id = AmpConfig::get('upload_catalog');
            if ($catalog_id > 0) {
                $catalog = Catalog::create_from_id($catalog_id);
            }
        }

        if (is_null($username)) {
            $username = $GLOBALS['user']->username;
        }

        $rootdir = "";
        if ($catalog != null && $catalog->id) {
            $rootdir = realpath($catalog->path);
            if (!empty($rootdir)) {
                if (AmpConfig::get('upload_subdir')) {
                    $rootdir .= DIRECTORY_SEPARATOR . $username;
                    if (!Core::is_readable($rootdir)) {
                        debug_event('upload', 'Target user directory `' . $rootdir . '` doesn\'t exists. Creating it...', '5');
                        mkdir($rootdir);
                    }
                }
            }
        }

        return $rootdir;
    }
} // Upload class
