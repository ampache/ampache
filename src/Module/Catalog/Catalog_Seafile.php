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

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\VaInfo;
use Exception;
use ReflectionException;

/**
 * This class handles all actual work in regards to remote Seafile catalogs.
 */
class Catalog_Seafile extends Catalog
{
    private static string $version     = '000001';
    private static string $type        = 'seafile';
    private static string $description = 'Seafile Remote Catalog';
    private static string $table_name  = 'catalog_seafile';

    /** @var SeafileAdapter seafile */
    private $seafile = null;
    private int $catalog_id;
    private int $count = 0;

    private $api_key;
    private $api_call_delay;

    public $server_uri;
    public $library_name;

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return self::$description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return self::$version;
    }

    /**
     * get_path
     * This returns the current catalog path/uri
     */
    public function get_path(): string
    {
        return $this->server_uri;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type(): string
    {
        return self::$type;
    }

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        $help = "<ul><li>" . T_("Install a Seafile server as described in the documentation") . "</li><li>" . T_("Enter URL to server (e.g. 'https://seafile.example.com') and library name (e.g. 'Music').") . "</li><li>" . T_("API Call Delay is the delay inserted between repeated requests to Seafile (such as during an Add or Clean action) to accommodate Seafile's Rate Limiting.") . "<br/>" . T_("The default is tuned towards Seafile's default rate limit settings.") . "</li><li>" . T_("After creating the Catalog, you must 'Make it ready' on the Catalog table.") . "</li></ul>";

        return sprintf(
            $help,
            "<a target='_blank' href='https://www.seafile.com/'>https://www.seafile.com/</a>",
            "<a href='https://forum.syncwerk.com/t/too-many-requests-when-using-web-api-status-code-429/2330'>",
            "</a>"
        );
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE '" . self::$table_name . "'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = "CREATE TABLE `" . self::$table_name . "` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `server_uri` VARCHAR(255) COLLATE $collation NOT NULL, `api_key` VARCHAR(100) COLLATE $collation NOT NULL, `library_name` VARCHAR(255) COLLATE $collation NOT NULL, `api_call_delay` INT NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    }

    /**
     * catalog_fields
     *
     * Return the necessary settings fields for creating a new Seafile catalog
     * @return array
     */
    public function catalog_fields(): array
    {
        $fields = [];

        $fields['server_uri'] = [
            'description' => T_('Server URI'),
            'type' => 'text',
            'value' => 'https://seafile.example.org/'
        ];
        $fields['library_name']   = ['description' => T_('Library Name'), 'type' => 'text', 'value' => 'Music'];
        $fields['api_call_delay'] = ['description' => T_('API Call Delay'), 'type' => 'number', 'value' => '250'];
        $fields['username']       = ['description' => T_('Seafile Username/Email'), 'type' => 'text', 'value' => ''];
        $fields['password']       = ['description' => T_('Seafile Password'), 'type' => 'password', 'value' => ''];

        return $fields;
    }

    /**
     * isReady
     *
     * Returns whether the catalog is ready for use.
     */
    public function isReady(): bool
    {
        return $this->seafile->ready();
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * @param string $catalog_id
     * @param array $data
     */
    public static function create_type($catalog_id, $data): bool
    {
        $server_uri     = rtrim(trim($data['server_uri']), '/');
        $library_name   = trim($data['library_name']);
        $api_call_delay = trim($data['api_call_delay']);
        $username       = trim($data['username']);
        $password       = trim($data['password']);

        if (!strlen($server_uri)) {
            AmpError::add('general', T_('Seafile server URL is required'));

            return false;
        }
        if (!strlen($library_name)) {
            AmpError::add('general', T_('Seafile server library name is required'));

            return false;
        }
        if (!strlen($username)) {
            AmpError::add('general', T_('Seafile username is required'));

            return false;
        }
        if (!strlen($password)) {
            AmpError::add('general', T_('Seafile password is required'));

            return false;
        }
        if (!is_numeric($api_call_delay)) {
            AmpError::add('general', T_('API Call Delay must have a numeric value'));

            return false;
        }

        try {
            $api_key = SeafileAdapter::request_api_key($server_uri, $username, $password);
            $sql     = "INSERT INTO `catalog_seafile` (`server_uri`, `api_key`, `library_name`, `api_call_delay`, `catalog_id`) VALUES (?, ?, ?, ?, ?)";
            Dba::write($sql, [$server_uri, $api_key, $library_name, (int)($api_call_delay), $catalog_id]);
            debug_event('seafile_catalog', 'Retrieved API token for user ' . $username . '.', 1);

            return true;
        } catch (Exception $error) {
            /* HINT: exception error message */
            AmpError::add(
                'general',
                sprintf(T_('There was a problem authenticating against the Seafile API: %s'), $error->getMessage())
            );
            debug_event('seafile_catalog', 'Exception while Authenticating: ' . $error->getMessage(), 2);
        }

        return false;
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     * @param int $catalog_id
     */
    public function __construct($catalog_id = null)
    {
        if ($catalog_id) {
            $this->id = (int)$catalog_id;
            $info     = $this->get_info($catalog_id, static::DB_TABLENAME);
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }

            $this->seafile = new SeafileAdapter(
                $info['server_uri'],
                $info['library_name'],
                $info['api_call_delay'],
                $info['api_key']
            );
        }
    }

    /**
     * @param string $file_path
     */
    public function get_rel_path($file_path): string
    {
        $arr = $this->seafile->from_virtual_path($file_path);

        return $arr['path'] . '/' . $arr['filename'];
    }

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     * @param array $options
     */
    public function add_to_catalog($options = null): int
    {
        // Prevent the script from timing out
        set_time_limit(0);

        if (!defined('SSE_OUTPUT') && !defined('API')) {
            Ui::show_box_top(T_('Running Seafile Remote Update'));
        }

        $success = 0;
        if ($this->seafile->prepare()) {
            $count = $this->seafile->for_all_files(function ($file) {
                if ($file->size == 0) {
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, 0 bytes", 5);

                    return 0;
                }

                $is_audio_file = Catalog::is_audio_file($file->name);
                $is_video_file = Catalog::is_video_file($file->name);

                if ($is_audio_file && count($this->get_gather_types('music')) > 0) {
                    if ($this->insert_song($file)) {
                        return 1;
                    }
                } elseif ($is_video_file && count($this->get_gather_types('video')) > 0) {
                    // TODO $this->insert_video();
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, video is unsupported", 5);
                } elseif (!$is_audio_file && !$is_video_file) {
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, unknown media file type", 5);
                } else {
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, bad media type for this catalog.", 5);
                }

                return 0;
            });

            Ui::update_text(T_('Catalog Updated'), /* HINT: count of songs updated */ sprintf(T_('Total Media: [%s]'), $count));

            if ($count < 1) {
                AmpError::add('general', T_('No media was updated, did you respect the patterns?'));
            } else {
                $success = 1;
            }
        }

        if (!defined('SSE_OUTPUT') && !defined('API')) {
            Ui::show_box_bottom();
        }

        $this->update_last_add();

        return $success;
    }

    /**
     * _insert_local_song
     *
     * Insert a song that isn't already in the database.
     * @param $file
     */
    private function insert_song($file): ?int
    {
        if ($this->check_remote_song($this->seafile->to_virtual_path($file))) {
            debug_event('seafile_catalog', 'Skipping existing song ' . $file->name, 5);
            /* HINT: filename (File path) */
            Ui::update_text('', sprintf(T_('Skipping existing song: %s'), $file->name));
        } else {
            debug_event('seafile_catalog', 'Adding song ' . $file->name, 5);
            try {
                $tempfilename = $this->seafile->download($file);
                $results      = $this->download_metadata($tempfilename, '', '', null, true);
                /* HINT: filename (File path) */
                Ui::update_text('', sprintf(T_('Adding a new song: %s'), $file->name));
                $added = Song::insert($results);

                if ($added) {
                    parent::gather_art([$added]);
                    // Restore the Seafile virtual path
                    $virtpath = $this->seafile->to_virtual_path($file);
                    Dba::write("UPDATE `song` SET `file` = ? WHERE `id` = ?", [$virtpath, $added]);
                    $this->count++;
                }

                return $added;
            } catch (Exception $error) {
                /* HINT: %1 filename (File path), %2 error message */
                debug_event('seafile_catalog', sprintf('Could not add song "%1$s": %2$s', $file->name, $error->getMessage()), 1);
                /* HINT: filename (File path) */
                Ui::update_text('', sprintf(T_('Could not add song: %s'), $file->name));
            } finally {
                if (isset($tempfilename)) {
                    $this->clean_tmp_file($tempfilename);
                }
            }
        }

        return null;
    }

    /**
     * @param $file
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @param array $gather_types
     * @param bool $keep
     * @return array
     * @throws Exception
     */
    private function download_metadata($file, $sort_pattern = '', $rename_pattern = '', $gather_types = null, $keep = false): array
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }
        $is_cached = (is_string($file) && is_file($file));

        if ($is_cached) {
            debug_event('seafile_catalog', 'Using tmp file ' . $file, 5);
            $tempfilename = $file;
        } else {
            debug_event('seafile_catalog', 'Downloading partial song ' . $file->name, 5);
            $tempfilename = $this->seafile->download($file, true);
        }

        if ($gather_types === null) {
            $gather_types = $this->get_gather_types('music');
        }

        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $tempfilename,
            $gather_types,
            '',
            '',
            (string) $sort_pattern,
            (string) $rename_pattern
        );
        if (!$is_cached) {
            $vainfo->forceSize((int)$file->size);
        }
        $vainfo->gather_tags();
        $key = VaInfo::get_tag_type($vainfo->tags);

        if (!$is_cached) {
            $vainfo->tags['general']['size'] = (int)($file->size);
        }

        $results = ($is_cached)
            ? VaInfo::clean_tag_info($vainfo->tags, $key, $file)
            : VaInfo::clean_tag_info($vainfo->tags, $key, $file->name);

        // Set the remote path
        $results['catalog'] = $this->id;
        $results['file']    = ($is_cached)
            ? $file
            : $this->seafile->to_virtual_path($file);

        // remove the temp file
        if (!$keep) {
            $this->clean_tmp_file($tempfilename);
        }

        return $results;
    }

    /**
     * @return int
     * @throws ReflectionException
     */
    public function verify_catalog_proc(): int
    {
        set_time_limit(0);

        $date    = time();
        $results = 0;
        if ($this->seafile->prepare()) {
            $sql        = 'SELECT `id`, `file`, `title` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, [$this->id]);
            while ($row = Dba::fetch_assoc($db_results)) {
                debug_event('seafile_catalog', 'Verify starting work on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
                $fileinfo = $this->seafile->from_virtual_path($row['file']);
                $file     = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);
                $metadata = null;
                if ($file !== null) {
                    $metadata = $this->download_metadata($file);
                }
                if ($metadata !== null) {
                    debug_event('seafile_catalog', 'Verify updating song', 5);
                    $song = new Song($row['id']);
                    $info = ($song->id) ? self::update_song_from_tags($metadata, $song) : [];
                    if ($info['change']) {
                        Ui::update_text('', sprintf(T_('Updated song: "%s"'), $row['title']));
                        $results++;
                    } else {
                        Ui::update_text('', sprintf(T_('Song up to date: "%s"'), $row['title']));
                    }
                } else {
                    debug_event('seafile_catalog', 'Verify removing song', 5);
                    Ui::update_text('', sprintf(T_('Removing song: "%s"'), $row['title']));
                    //$dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', [$row['id']]);
                }
            }

            $this->update_last_update($date);
        }

        return $results;
    }

    /**
     * @param Media $media
     * @param array $gather_types
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @return array
     * @throws Exception
     */
    public function get_media_tags($media, $gather_types, $sort_pattern, $rename_pattern): array
    {
        // if you have the file it's all good
        /** @var Song $media */
        if (!empty($media->file) && is_file($media->file)) {
            return $this->download_metadata($media->file, $sort_pattern, $rename_pattern, $gather_types);
        }
        if ($this->seafile->prepare()) {
            $fileinfo = $this->seafile->from_virtual_path((string)$media->file);

            $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

            if ($file !== null) {
                return $this->download_metadata($file, $sort_pattern, $rename_pattern, $gather_types);
            }
        }

        return [];
    }

    /**
     * clean_tmp_file
     *
     * Clean up temp files after use.
     *
     * @param string|null $tempfilename
     */
    public function clean_tmp_file($tempfilename): void
    {
        if ($tempfilename !== null && file_exists($tempfilename)) {
            unlink($tempfilename);
        }
    }

    /**
     * clean_catalog_proc
     *
     * Removes songs that no longer exist.
     */
    public function clean_catalog_proc(): int
    {
        $dead = 0;

        set_time_limit(0);

        if ($this->seafile->prepare()) {
            $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, [$this->id]);
            while ($row = Dba::fetch_assoc($db_results)) {
                debug_event('seafile_catalog', 'Clean starting work on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
                $file = $this->seafile->from_virtual_path($row['file']);

                try {
                    $exists = $this->seafile->get_file($file['path'], $file['filename']) !== null;
                } catch (Exception $error) {
                    Ui::update_text(
                        T_('There Was a Problem'),
                        /* HINT: %1 filename (File path), %2 Error Message */
                        sprintf(
                            T_('There was an error while checking this song "%1$s": %2$s'),
                            $file['filename'],
                            $error->getMessage()
                        )
                    );
                    debug_event('seafile_catalog', 'Clean Exception: ' . $error->getMessage(), 2);

                    continue;
                }

                if ($exists) {
                    debug_event('seafile_catalog', 'Clean keeping song', 5);
                    /* HINT: filename (File path) */
                    Ui::update_text('', sprintf(T_('Keeping song: %s'), $file['filename']));
                } else {
                    /* HINT: filename (File path) */
                    Ui::update_text('', sprintf(T_('Removing song: "%s"'), $file['filename']));
                    debug_event('seafile_catalog', 'Clean removing song', 5);
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', [$row['id']]);
                }
            }

            $this->update_last_clean();
        }

        return $dead;
    }

    /**
     * @return array
     */
    public function check_catalog_proc(): array
    {
        return [];
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     * @param string $new_path
     */
    public function move_catalog_proc($new_path): bool
    {
        return false;
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        return false;
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it finds a song it returns the ID
     */
    public function check_remote_song(string $file): ?int
    {
        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, [$file]);

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return null;
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format(): void
    {
        parent::format();

        if ($this->seafile != null) {
            $this->f_info      = $this->seafile->get_format_string();
            $this->f_full_info = $this->seafile->get_format_string();
        } else {
            $this->f_info      = "Seafile Catalog";
            $this->f_full_info = "Seafile Catalog";
        }
    }

    /**
     * @param Song|Podcast_Episode|Video $media
     * @return array{
     *    file_path: string,
     *    file_name: string,
     *    file_size: int,
     *    file_type: string
     * }
     */
    public function prepare_media($media): array
    {
        $stream_path = (string) $media->file;
        $stream_name = $media->getFileName();
        $size        = $media->size;

        if ($this->seafile->prepare()) {
            set_time_limit(0);

            $fileinfo = $this->seafile->from_virtual_path((string)$media->file);

            $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

            $stream_path = $this->seafile->download($file);
            $stream_name = $fileinfo['filename'];

            // in case this didn't get set for some reason
            if ($size == 0) {
                $size = Core::get_filesize($stream_path);
            }
        }

        return [
            'file_path' => $stream_path,
            'file_name' => $stream_name,
            'file_size' => $size,
            'file_type' => $media->type
        ];
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getUtilityFactory(): UtilityFactoryInterface
    {
        global $dic;

        return $dic->get(UtilityFactoryInterface::class);
    }
}
