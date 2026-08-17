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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use RuntimeException;
use Throwable;

class Upload
{
    /**
     * Adds a file that has already been placed inside the upload catalog to the library.
     *
     * The file is in the catalog from here on, so anything that throws has to take it back out again; a false return
     * tells the caller to unlink it.
     *
     * @param array{license?: mixed, artist_id?: mixed, artist_name?: mixed, album_id?: mixed, album_name?: mixed} $request
     */
    public static function add_to_catalog(
        Catalog_local $catalog,
        string $targetdir,
        string $targetfile,
        array $request,
        int $user_id,
    ): bool {
        try {
            // run upload script if set
            self::upload_script($targetdir, $targetfile);

            $options                = [];
            $options['user_upload'] = $user_id;
            $options['license']     = $request['license'] ?? '';

            // Require a license with the upload if it's enabled
            if (AmpConfig::get('licensing') && $options['license'] == '') {
                debug_event(self::class, 'error: license is required.', 3);

                return false;
            }

            if (!empty($request['artist_id'])) {
                $options['artist_id'] = (int) $request['artist_id'];
            }

            // Try to create a new artist
            if (!empty($request['artist_name'])) {
                $artist_id = self::check_artist((string) $request['artist_name'], $user_id);
                if (!$artist_id) {
                    debug_event(self::class, 'error: check_artist.', 3);

                    return false;
                }

                $artist = new Artist($artist_id);
                if (
                    $artist->get_user_owner()
                    && $artist->get_user_owner() != $options['user_upload']
                ) {
                    debug_event(self::class, "Artist owner doesn't match the current user.", 3);

                    return false;
                }

                $options['artist_id'] = $artist_id;
            }

            if (!empty($request['album_id'])) {
                $options['album_id'] = (int) $request['album_id'];
            }

            // Try to create a new album
            if (!empty($request['album_name'])) {
                $album_id = self::check_album((string) $request['album_name'], ($options['artist_id'] ?? null));
                if (!is_int($album_id)) {
                    debug_event(self::class, 'error: check_album.', 3);

                    return false;
                }

                $album = new Album($album_id);
                if (
                    $album->get_user_owner()
                    && $album->get_user_owner() != $options['user_upload']
                ) {
                    debug_event(self::class, "Album owner doesn't match the current user.", 3);

                    return false;
                }

                $options['album_id'] = $album_id;
            }

            if (AmpConfig::get('upload_catalog_pattern')) {
                $options['move_match_pattern'] = true;
            }

            if (!$catalog->add_file($targetfile, $options)) {
                debug_event(self::class, 'Failed adding uploaded file to catalog.', 1);

                return false;
            }

            Album::update_table_counts();
            Artist::update_table_counts();
        } catch (Throwable $throwable) {
            debug_event(self::class, 'Upload failed: ' . $throwable->getMessage(), 1);

            return false;
        }

        return true;
    }

    /**
     * can_upload
     * check settings and permissions for uploads
     * @throws RuntimeException
     */
    public static function can_upload(string|User|null $user = null): bool
    {
        if (empty($user)) {
            $user = Core::get_global('user');
        }

        $user_access = $user->access ?? -1;

        return AmpConfig::get('allow_upload')
            && $user_access >= AmpConfig::get(ConfigurationKeyEnum::UPLOAD_ACCESS_LEVEL, AccessLevelEnum::USER->value);
    }

    /**
     * check
     * Can you even upload?
     */
    public static function check(int $catalog_id): ?Catalog
    {
        if ($catalog_id === 0) {
            return null;
        }

        $allowed   = explode('|', (string) AmpConfig::get('catalog_file_pattern'));
        $extension = strtolower(pathinfo((string) $_FILES['upl']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            debug_event(self::class, 'File extension `' . $extension . '` not allowed.', 2);

            return null;
        }

        if (array_key_exists('upl', $_FILES) && $_FILES['upl']['error'] == 0) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                return null;
            }

            if ($catalog->catalog_type == "local") {
                return $catalog;
            }
        } else {
            debug_event(self::class, 'File upload error (check filesize limits).', 2);
        }

        return null;
    }

    /**
     * check_album
     */
    public static function check_album(string $album_name, ?int $artist_id = null): ?int
    {
        debug_event(self::class, 'check_album: looking for ' . $album_name, 5);
        if ($album_name !== '') {
            // the setting arrives as a string, so it is cast before both the guard and the typed call below
            $upload_catalog = AmpConfig::get_int('upload_catalog', 0);
            if ($upload_catalog === 0) {
                return null;
            }

            $album_id = Album::check($upload_catalog, $album_name, 0, null, null, $artist_id);
            if ($album_id === 0) {
                debug_event(self::class, 'Album information required, uploaded song skipped.', 3);

                return null;
            }

            return $album_id;
        }

        return null;
    }

    /**
     * check_artist
     */
    public static function check_artist(string $artist_name, int $user_id): ?int
    {
        debug_event(self::class, 'check_artist: looking for ' . $artist_name, 5);
        if ($artist_name !== '') {
            if (Artist::check($artist_name, '', $user_id, true)) {
                debug_event(self::class, 'An artist with the name "' . $artist_name . '" already exists, uploaded song skipped.', 3);

                return null;
            }

            $artist_id = (int) Artist::check($artist_name, '', $user_id);
            if (!$artist_id) {
                debug_event(self::class, 'Artist information required, uploaded song skipped.', 3);

                return null;
            }

            return $artist_id;
        }

        return null;
    }

    /**
     * check_target_dir
     */
    public static function check_target_dir(string $catalog_dir): ?string
    {
        $targetdir = $catalog_dir;
        $folder    = (Core::get_post('folder') === '..') ? '' : Core::get_post('folder');

        if ($folder !== '' && $folder !== '0') {
            $targetdir .= DIRECTORY_SEPARATOR . $folder;
        }

        $targetdir      = realpath($targetdir);
        $catalogRealDir = realpath($catalog_dir);
        debug_event(self::class, 'Target Directory `' . $targetdir, 4);
        if (
            $targetdir === false
            || $catalogRealDir === false
            || ($targetdir !== $catalogRealDir && !str_starts_with($targetdir, $catalogRealDir . DIRECTORY_SEPARATOR))
        ) {
            debug_event(self::class, 'Something wrong with final upload path.', 1);

            return null;
        }

        return $targetdir;
    }

    /**
     * check_target_path
     */
    public static function check_target_path(string $targetfile): ?string
    {
        debug_event(self::class, 'Target File `' . $targetfile, 4);
        // a name already in the catalog is refused rather than stored beside the original under a suffixed name
        if (Core::is_readable($targetfile)) {
            debug_event(self::class, 'File `' . $targetfile . '` already exists.', 1);

            return null;
        }

        return $targetfile;
    }

    /**
     * Reduces a client-supplied name to a bare file name.
     *
     * The name is concatenated onto the catalog directory, so a name carrying a path would place the upload outside
     * the catalog entirely.
     */
    public static function clean_filename(string $filename): string
    {
        return basename(str_replace('\\', '/', $filename));
    }

    /**
     * get_root
     */
    public static function get_root(Catalog $catalog, ?string $username = null): ?string
    {
        if ($username === null) {
            $username = Core::get_global('user')?->username;
        }

        $rootdir  = "";
        $pathname = realpath($catalog->get_path());
        if ($pathname) {
            $rootdir = $pathname;
            if (AmpConfig::get('upload_subdir')) {
                if (in_array($username, [null, '', '0'], true)) {
                    return null;
                }

                $rootdir .= DIRECTORY_SEPARATOR . $username;
                if (!Core::is_readable($rootdir)) {
                    debug_event(self::class, 'Target user directory `' . $rootdir . "` doesn't exist. Creating it...", 5);
                    if (!mkdir($rootdir, 0775)) {
                        return null;
                    }
                }
            }
        }

        return $rootdir;
    }

    /**
     * The largest upload php will accept, so a body streamed past the multipart handling is held to the same limit.
     */
    public static function max_upload_bytes(): int
    {
        $upload_max = return_bytes((string) ini_get('upload_max_filesize'));
        $post_max   = return_bytes((string) ini_get('post_max_size'));
        if ($post_max > 0 && ($post_max < $upload_max || $upload_max === 0)) {
            $upload_max = $post_max;
        }

        return $upload_max;
    }

    /**
     * process
     */
    public static function process(): bool
    {
        header('Content-Type: application/json');
        ob_start();
        define('CLI', true);

        $can_upload = Access::check(
            AccessTypeEnum::INTERFACE,
            AccessLevelEnum::from((int) AmpConfig::get(ConfigurationKeyEnum::UPLOAD_ACCESS_LEVEL, AccessLevelEnum::USER->value))
        );
        $catalog_id = AmpConfig::get_int('upload_catalog', 0);
        $catalog    = self::check($catalog_id);
        if ($catalog instanceof Catalog_local) {
            debug_event(self::class, 'Uploading to catalog ID ' . $catalog_id, 4);

            $rootdir = self::get_root($catalog);
            if ($rootdir === null) {
                return self::rerror();
            }

            // check the catalog path is valid
            $targetdir = self::check_target_dir($rootdir);
            if (!$targetdir) {
                return self::rerror();
            }

            // check the file is valid and doesn't already exist
            $targetfile = self::check_target_path($targetdir . DIRECTORY_SEPARATOR . self::clean_filename((string) $_FILES['upl']['name']));
            if (!$targetfile) {
                return self::rerror();
            }

            // check that the minimum level of permission is there
            if (!$can_upload) {
                return self::rerror($targetfile);
            }

            if (move_uploaded_file($_FILES['upl']['tmp_name'], $targetfile)) {
                debug_event(self::class, 'File uploaded to `' . $targetfile . '`.', 5);
                //debug_event(self::class, 'post ' . print_r($_POST, true), 5);

                if (
                    !self::add_to_catalog(
                        $catalog,
                        $targetdir,
                        $targetfile,
                        [
                            'license' => Core::get_post('license'),
                            'artist_id' => Core::get_request('artist_id'),
                            'artist_name' => Core::get_request('artist_name'),
                            'album_id' => Core::get_request('album_id'),
                            'album_name' => Core::get_request('album_name'),
                        ],
                        (int) (Core::get_global('user')?->getId())
                    )
                ) {
                    return self::rerror($targetfile);
                }

                ob_get_contents();
                ob_end_clean();
                echo '{"status":"success"}';

                return true;
            }

            debug_event(self::class, 'Cannot copy the file to target directory. Please check write access.', 1);
        } else {
            debug_event(self::class, 'No catalog target upload configured.', 1);
        }

        return self::rerror();
    }

    /**
     * rerror
     * @throws RuntimeException
     */
    public static function rerror(?string $file = null): bool
    {
        if ($file !== null && unlink($file) === false) {
            throw new RuntimeException('The file handle ' . $file . ' could not be unlinked');
        }

        header(Core::get_server('SERVER_PROTOCOL') . ' 500 File Upload Error', true, 500);
        ob_get_contents();
        ob_end_clean();
        echo '{"status":"error"}';

        return false;
    }

    /**
     * upload_script
     */
    public static function upload_script(string $targetdir, string $targetfile): void
    {
        $script = AmpConfig::get('upload_script');
        if (AmpConfig::get('allow_upload_scripts') && $script) {
            chdir($targetdir);
            $script = str_replace('%FILE%', escapeshellarg($targetfile), $script);
            exec($script);
        }
    }
}
