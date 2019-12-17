<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
            debug_event('upload.class', 'Uploading to catalog ID ' . $catalog_id, 4);
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog->catalog_type == "local") {
                $allowed = explode('|', AmpConfig::get('catalog_file_pattern'));
                debug_event('upload.class', 'Uploading to local catalog', 5);

                if (isset($_FILES['upl']) && $_FILES['upl']['error'] == 0) {
                    debug_event('upload.class', '$_FILES[upl] ' . $_FILES['upl']['name'], 5);
                    $extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);

                    if (!in_array(strtolower((string) $extension), $allowed)) {
                        debug_event('upload.class', 'File extension `' . $extension . '` not allowed.', 2);

                        return self::rerror();
                    }

                    $rootdir   = self::get_root($catalog);
                    $targetdir = $rootdir;
                    $folder    = Core::get_post('folder');
                    if ($folder == '..') {
                        $folder = '';
                    }
                    if (!empty($folder)) {
                        $targetdir .= DIRECTORY_SEPARATOR . $folder;
                    }

                    $targetdir = realpath($targetdir);
                    debug_event('upload.class', 'Target Directory `' . $targetdir, 4);
                    if (strpos($targetdir, $rootdir) === false) {
                        debug_event('upload.class', 'Something wrong with final upload path.', 1);

                        return self::rerror();
                    }

                    $targetfile = $targetdir . DIRECTORY_SEPARATOR . $_FILES['upl']['name'];
                    debug_event('upload.class', 'Target File `' . $targetfile, 4);
                    if (Core::is_readable($targetfile)) {
                        debug_event('upload.class', 'File `' . $targetfile . '` already exists.', 3);
                        $ext        = pathinfo($targetfile, PATHINFO_EXTENSION);
                        $targetfile = str_replace(('.' . $ext), '_' . ((string) time() . '.' . $ext), $targetfile);
                        if (Core::is_readable($targetfile)) {
                            debug_event('upload.class', 'File `' . $targetfile . '` already exists.', 1);

                            return self::rerror();
                        }
                    }

                    if (move_uploaded_file($_FILES['upl']['tmp_name'], $targetfile)) {
                        debug_event('upload.class', 'File uploaded to `' . $targetfile . '`.', 5);

                        if (AmpConfig::get('upload_script')) {
                            chdir($targetdir);
                            $script = AmpConfig::get('upload_script');
                            $script = str_replace('%FILE%', $targetfile, $script);
                            exec($script);
                        }

                        $options                = array();
                        $options['user_upload'] = Core::get_global('user')->id;
                        if (filter_has_var(INPUT_POST, 'license')) {
                            $options['license'] = Core::get_post('license');
                        }
                        $artist_id = (int) (Core::get_request('artist'));
                        $album_id  = (int) (Core::get_request('album'));

                        // Try to create a new artist
                        if (Core::get_request('artist_name') !== '') {
                            $artist_id = Artist::check(Core::get_request('artist_name'), null, true);
                            if ($artist_id !== null && !Access::check('interface', 50)) {
                                debug_event('upload.class', 'An artist with the same name already exists, uploaded song skipped.', 3);

                                return self::rerror($targetfile);
                            }
                            $artist = new Artist($artist_id);
                            if (!$artist->get_user_owner()) {
                                $artist->update_artist_user($options['user_upload']);
                            }
                        }
                        if ($artist_id === null) {
                            debug_event('upload.class', 'Artist information required, uploaded song skipped.', 3);

                            return self::rerror($targetfile);
                        }
                        $artist = new Artist($artist_id);
                        if (!Access::check('interface', 50) && $artist->get_user_owner() != $options['user_upload']) {
                            debug_event('upload.class', "Artist owner doesn't match the current user.", 3);

                            return self::rerror($targetfile);
                        }
                        
                        // Try to create a new album
                        if (Core::get_request('album_name') !== '') {
                            $album_id = Album::check(Core::get_request('album_name'), 0, 0, null, null, $artist_id);
                        }
                        if ($album_id === null) {
                            debug_event('upload.class', 'Album information required, uploaded song skipped.', 3);

                            return self::rerror($targetfile);
                        }
                        $album = new Album($album_id);
                        if ($album->get_user_owner() != $options['user_upload']) {
                            debug_event('upload.class', "Album owner doesn't match the current user.", 3);

                            return self::rerror($targetfile);
                        }
                        $options['artist_id'] = $artist_id;
                        $options['album_id']  = $album_id;

                        if (AmpConfig::get('upload_catalog_pattern')) {
                            $options['move_match_pattern'] = true;
                        }

                        if (!$catalog->add_file($targetfile, $options)) {
                            debug_event('upload.class', 'Failed adding uploaded file to catalog.', 1);

                            return self::rerror($targetfile);
                        }

                        ob_get_contents();
                        ob_end_clean();
                        echo '{"status":"success"}';

                        return true;
                    } else {
                        debug_event('upload.class', 'Cannot copy the file to target directory. Please check write access.', 1);
                    }
                }
            } else {
                debug_event('upload.class', 'The catalog must be local to upload files on it.', 1);
            }
        } else {
            debug_event('upload.class', 'No catalog target upload configured.', 1);
        }

        return self::rerror();
    }

    /**
     * @param string $file
     */
    public static function rerror($file = null)
    {
        if ($file !== null) {
            if (unlink($file) === false) {
                throw new \RuntimeException('The file handle ' . $file . ' could not be unlinked');
            }
        }
        header(Core::get_server('SERVER_PROTOCOL') . ' 500 File Upload Error', true, 500);
        ob_get_contents();
        ob_end_clean();
        echo '{"status":"error"}';

        return false;
    }

    /**
     * @param Catalog $catalog
     */
    public static function get_root($catalog = null, $username = null)
    {
        if ($catalog == null) {
            $catalog_id = AmpConfig::get('upload_catalog');
            if ($catalog_id > 0) {
                $catalog = Catalog::create_from_id($catalog_id);
            }
        }

        if ($username === null) {
            $username = Core::get_global('user')->username;
        }

        $rootdir = "";
        if ($catalog != null && $catalog->id) {
            $rootdir = realpath($catalog->path);
            if (!empty($rootdir)) {
                if (AmpConfig::get('upload_subdir')) {
                    $rootdir .= DIRECTORY_SEPARATOR . $username;
                    if (!Core::is_readable($rootdir)) {
                        debug_event('upload.class', 'Target user directory `' . $rootdir . "` doesn't exist. Creating it...", 5);
                        mkdir($rootdir);
                    }
                }
            }
        }

        return $rootdir;
    }
} // Upload class
