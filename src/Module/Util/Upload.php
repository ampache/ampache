<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Util;

use Ampache\Repository\Model\Album;
use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use RuntimeException;

class Upload
{
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * process
     * @return boolean
     */
    public static function process()
    {
        header('Content-Type: application/json');
        ob_start();
        define('CLI', true);

        $can_upload = Access::check('interface', AmpConfig::get('upload_access_level', 25));
        $catalog_id = AmpConfig::get('upload_catalog');
        $catalog    = self::check($catalog_id);
        if ($catalog !== null) {
            debug_event(self::class, 'Uploading to catalog ID ' . $catalog_id, 4);

            $rootdir = self::get_root($catalog);
            // check the catalog path is valid
            if (!$targetdir = self::check_target_dir($rootdir)) {
                return self::rerror();
            }
            // check the file is valid and doesn't already exist
            if (!$targetfile = self::check_target_path($targetdir . DIRECTORY_SEPARATOR . $_FILES['upl']['name'])) {
                return self::rerror();
            }
            // check that the minimum level of permission is there
            if (!$can_upload) {
                return self::rerror($targetfile);
            }
            if (move_uploaded_file($_FILES['upl']['tmp_name'], $targetfile)) {
                debug_event(self::class, 'File uploaded to `' . $targetfile . '`.', 5);

                // run upload script if set
                self::upload_script($targetdir, $targetfile);

                $options                = array();
                $options['user_upload'] = Core::get_global('user')->id;
                if (isset($_POST['license'])) {
                    $options['license'] = Core::get_post('license');
                }

                if (Core::get_request('artist') !== '') {
                    $options['artist_id'] = (int)Core::get_request('artist');
                }
                // Try to create a new artist
                if (Core::get_request('artist_name') !== '') {
                    if (!$artist_id = self::check_artist(Core::get_request('artist_name'), Core::get_global('user')->id)) {
                        return self::rerror($targetfile);
                    }
                    $artist = new Artist($artist_id);
                    if ($artist->get_user_owner() != $options['user_upload']) {
                        debug_event(self::class, "Artist owner doesn't match the current user.", 3);

                        return self::rerror($targetfile);
                    }
                    $options['artist_id'] = $artist_id;
                }

                // Try to create a new album
                if (Core::get_request('album_name') !== '') {
                    if (!$album_id = self::check_album(Core::get_request('album_name'), $options['artist_id'])) {
                        return self::rerror($targetfile);
                    }
                    $album = new Album($album_id);
                    if ($album->get_user_owner() != $options['user_upload']) {
                        debug_event(self::class, "Album owner doesn't match the current user.", 3);

                        return self::rerror($targetfile);
                    }
                    $options['album_id'] = $album_id;
                }

                if (AmpConfig::get('upload_catalog_pattern')) {
                    $options['move_match_pattern'] = true;
                }

                if (!$catalog->add_file($targetfile, $options)) {
                    debug_event(self::class, 'Failed adding uploaded file to catalog.', 1);

                    return self::rerror($targetfile);
                }
                Album::update_table_counts();
                Artist::update_table_counts();

                ob_get_contents();
                ob_end_clean();
                echo '{"status":"success"}';

                return true;
            } else {
                debug_event(self::class, 'Cannot copy the file to target directory. Please check write access.', 1);
            }
        } else {
            debug_event(self::class, 'No catalog target upload configured.', 1);
        }

        return self::rerror();
    } // process

    /**
     * check
     * Can you even upload?
     * @param $catalog_id
     * @return Catalog|null
     */
    public static function check($catalog_id)
    {
        $allowed   = explode('|', AmpConfig::get('catalog_file_pattern'));
        $extension = strtolower((string) pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            debug_event(self::class, 'File extension `' . $extension . '` not allowed.', 2);

            return null;
        }
        if (array_key_exists('upl', $_FILES) && $_FILES['upl']['error'] == 0) {
            $upload_catalog = Catalog::create_from_id($catalog_id);
            if ($upload_catalog->catalog_type == "local") {
                return $upload_catalog;
            }
        } else {
            debug_event(self::class, 'File upload error (check filesize limits).', 2);
        }

        return null;
    } // check

    /**
     * can_upload
     * check settings and permissions for uploads
     * @param User|string|null $user
     * @return boolean
     * @throws RuntimeException
     */
    public static function can_upload($user = null): bool
    {
        if (empty($user)) {
            $user = Core::get_global('user');
        }
        $user_access = $user->access ?? -1;

        return AmpConfig::get('allow_upload') &&
            $user_access >= AmpConfig::get('upload_access_level', 25);
    }

    /**
     * rerror
     * @param string $file
     * @return boolean
     * @throws RuntimeException
     */
    public static function rerror($file = null)
    {
        if ($file !== null) {
            if (unlink($file) === false) {
                throw new RuntimeException('The file handle ' . $file . ' could not be unlinked');
            }
        }
        header(Core::get_server('SERVER_PROTOCOL') . ' 500 File Upload Error', true, 500);
        ob_get_contents();
        ob_end_clean();
        echo '{"status":"error"}';

        return false;
    } // rerror

    /**
     * upload_script
     * @param string $targetdir
     * @param string $targetfile
     */
    public static function upload_script($targetdir, $targetfile)
    {
        $script = AmpConfig::get('upload_script');
        if (AmpConfig::get('allow_upload_scripts') && $script) {
            chdir($targetdir);
            $script = str_replace('%FILE%', $targetfile, $script);
            exec($script);
        }
    } // upload_script

    /**
     * check_artist
     * @param string $artist_name
     * @param integer $user_id
     * @return boolean|integer
     */
    public static function check_artist($artist_name, $user_id)
    {
        debug_event(self::class, 'check_artist: looking for ' . $artist_name, 5);
        if ($artist_name !== '') {
            if (Artist::check($artist_name, null, true) !== null) {
                debug_event(self::class, 'An artist with the name "' . $artist_name . '" already exists, uploaded song skipped.', 3);

                return false;
            }
            $artist_id = Artist::check($artist_name, null);
            if ((int) $artist_id < 0) {
                debug_event(self::class, 'Artist information required, uploaded song skipped.', 3);

                return false;
            }
            $artist = new Artist($artist_id);
            $artist->update_artist_user($user_id); // take ownership of the new artist

            return (int) $artist_id;
        }

        return false;
    } // check_artist

    /**
     * check_album
     * @param string $album_name
     * @param int $artist_id
     * @return boolean|integer
     */
    public static function check_album($album_name, $artist_id)
    {
        debug_event(self::class, 'check_album: looking for ' . $album_name, 5);
        if ($album_name !== '') {
            $album_id = Album::check(AmpConfig::get('upload_catalog'), $album_name, 0, null, null, $artist_id);
            if ((int)$album_id < 0) {
                debug_event(self::class, 'Album information required, uploaded song skipped.', 3);

                return false;
            }

            return (int)$album_id;
        }

        return false;
    } // check_album

    /**
     * check_target_path
     * @param string $targetfile
     * @return boolean|string
     */
    public static function check_target_path($targetfile)
    {
        debug_event(self::class, 'Target File `' . $targetfile, 4);
        if (Core::is_readable($targetfile)) {
            debug_event(self::class, 'File `' . $targetfile . '` already exists.', 3);
            $ext        = pathinfo($targetfile, PATHINFO_EXTENSION);
            $targetfile = str_replace(('.' . $ext), '_' . ((string) time() . '.' . $ext), $targetfile);
            if (Core::is_readable($targetfile)) {
                debug_event(self::class, 'File `' . $targetfile . '` already exists.', 1);

                return false;
            }
        }

        return $targetfile;
    } // check_target_path

    /**
     * check_target_dir
     * @param string $catalog_dir
     * @return boolean|string
     */
    public static function check_target_dir($catalog_dir)
    {
        $targetdir = $catalog_dir;
        $folder    = (Core::get_post('folder') == '..') ? '' : Core::get_post('folder');

        if (!empty($folder)) {
            $targetdir .= DIRECTORY_SEPARATOR . $folder;
        }

        $targetdir = realpath($targetdir);
        debug_event(self::class, 'Target Directory `' . $targetdir, 4);
        if (strpos($targetdir, $catalog_dir) === false) {
            debug_event(self::class, 'Something wrong with final upload path.', 1);

            return false;
        }

        return $targetdir;
    } // check_target_dir

    /**
     * get_root
     * @param Catalog $catalog
     * @param string $username
     * @return string
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
            $rootdir = realpath($catalog->get_path());
            if (!empty($rootdir)) {
                if (AmpConfig::get('upload_subdir')) {
                    $rootdir .= DIRECTORY_SEPARATOR . $username;
                    if (!Core::is_readable($rootdir)) {
                        debug_event(self::class, 'Target user directory `' . $rootdir . "` doesn't exist. Creating it...", 5);
                        mkdir($rootdir);
                    }
                }
            }
        }

        return $rootdir;
    } // get_root
}
