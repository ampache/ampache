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

namespace Ampache\Module\Catalog;

use Ahc\Cli\IO\Interactor;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\SongFieldEnum;
use Ampache\Repository\Model\Video;
use Exception;
use Override;
use Seafile\Client\Type\DirectoryItem;

/**
 * This class handles all actual work in regards to remote Seafile catalogs.
 */
class Catalog_Seafile extends Catalog
{
    private static string $description = 'Seafile Remote Catalog';
    private static string $type        = 'seafile';
    private static string $version     = '000001';
    public string $library_name;
    public string $server_uri;
    private ?int $api_call_delay = null;
    private ?string $api_key     = null;
    private int $count           = 0;
    private SeafileAdapter $seafile;

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct(?int $catalog_id = null)
    {
        if ($catalog_id) {
            $info                 = $this->get_info($catalog_id, static::DB_TABLENAME);
            $this->id             = (int) ($info['id'] ?? 0);
            $this->name           = $info['name'] ?? null;
            $this->catalog_type   = $info['catalog_type'] ?? null;
            $this->enabled        = (bool) ($info['enabled'] ?? false);
            $this->last_update    = (int) ($info['last_update'] ?? 0);
            $this->last_add       = (int) ($info['last_add'] ?? 0);
            $this->last_clean     = (int) ($info['last_clean'] ?? 0);
            $this->rename_pattern = $info['rename_pattern'] ?? '';
            $this->sort_pattern   = $info['sort_pattern'] ?? '';
            $this->gather_types   = $info['gather_types'] ?? '';

            $this->library_name   = $info['library_name'] ?? '';
            $this->server_uri     = $info['server_uri'] ?? '';
            $this->api_call_delay = $info['api_call_delay'] ?? null;
            $this->api_key        = $info['api_key'] ?? null;

            $this->seafile = new SeafileAdapter(
                $this->server_uri,
                $this->library_name,
                $this->api_call_delay,
                $this->api_key
            );
        }
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     *
     * @param array{
     *     server_uri?: string,
     *     library_name?: ?string,
     *     api_call_delay?: string|int|null,
     *     username?: ?string,
     *     password?: ?string,
     * } $data
     */
    public static function create_type(int $catalog_id, array $data): bool
    {
        $server_uri     = rtrim(trim($data['server_uri'] ?? ''), '/');
        $library_name   = trim($data['library_name'] ?? '');
        $api_call_delay = isset($data['api_call_delay']) ? (int) $data['api_call_delay'] : null;
        $username       = trim($data['username'] ?? '');
        $password       = trim($data['password'] ?? '');

        if ($server_uri === '') {
            AmpError::add('general', T_('Seafile server URL is required'));

            return false;
        }

        if ($library_name === '') {
            AmpError::add('general', T_('Seafile server library name is required'));

            return false;
        }

        if ($username === '') {
            AmpError::add('general', T_('Seafile username is required'));

            return false;
        }

        if ($password === '') {
            AmpError::add('general', T_('Seafile password is required'));

            return false;
        }

        if (!is_numeric($api_call_delay)) {
            AmpError::add('general', T_('API Call Delay must have a numeric value'));

            return false;
        }

        try {
            $api_key = SeafileAdapter::request_api_key($server_uri, $username, $password);
            self::getCatalogRepository()->insertSubType(
                CatalogTypeEnum::SEAFILE,
                [
                    'server_uri' => $server_uri,
                    'api_key' => $api_key,
                    'library_name' => $library_name,
                    'api_call_delay' => $api_call_delay,
                ],
                $catalog_id
            );
            debug_event('seafile_catalog', 'Retrieved API token for user ' . $username . '.', 1);

            return true;
        } catch (Exception $exception) {
            /* HINT: exception error message */
            AmpError::add(
                'general',
                sprintf(T_('There was a problem authenticating against the Seafile API: %s'), $exception->getMessage())
            );
            debug_event('seafile_catalog', 'Exception while Authenticating: ' . $exception->getMessage(), 2);
        }

        return false;
    }

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        // Prevent the script from timing out
        set_time_limit(0);

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
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

                if ($is_audio_file && $this->get_gather_types('music') !== []) {
                    if ($this->insert_song($file)) {
                        return 1;
                    }
                } elseif ($is_video_file && $this->get_gather_types('video') !== []) {
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

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        $this->update_last_add();

        return $success;
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        return false;
    }

    /**
     * catalog_fields
     *
     * Return the necessary settings fields for creating a new Seafile catalog
     * @return array<
     *     string,
     *     array{description: string, type: string, value: scalar}
     * >
     */
    public function catalog_fields(): array
    {
        return [
            'server_uri' => [
                'description' => T_('Server URI'),
                'type' => 'text',
                'value' => 'https://seafile.example.org/',
            ],
            'library_name' => [
                'description' => T_('Library Name'),
                'type' => 'text',
                'value' => 'Music'
            ],
            'api_call_delay' => [
                'description' => T_('API Call Delay'),
                'type' => 'number',
                'value' => '250'
            ],
            'username' => [
                'description' => T_('Seafile Username/Email'),
                'type' => 'text',
                'value' => ''
            ],
            'password' => [
                'description' => T_('Seafile Password'),
                'type' => 'password',
                'value' => ''
            ]
        ];
    }

    /**
     * @return string[]
     */
    public function check_catalog_proc(?Interactor $interactor = null): array
    {
        return [];
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it finds a song it returns the ID
     */
    public function check_remote_song(string $file): ?int
    {
        return self::getSongRepository()->findIdByFile($file);
    }

    /**
     * clean_catalog_proc
     *
     * Removes songs that no longer exist.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        $dead = 0;

        set_time_limit(0);

        if ($this->seafile->prepare()) {
            $songRepository = self::getSongRepository();
            foreach ($songRepository->getFilesByCatalog($this->getId()) as $songId => $songFile) {
                $row = ['id' => $songId, 'file' => $songFile];
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
                    $songRepository->delete((int) $row['id']);
                }
            }

            $this->update_last_clean();
        }

        return $dead;
    }

    /**
     * clean_tmp_file
     *
     * Clean up temp files after use.
     */
    public function clean_tmp_file(?string $tempfilename = null): void
    {
        if ($tempfilename !== null && file_exists($tempfilename)) {
            unlink($tempfilename);
        }
    }

    public function count_scan_folders(?Interactor $interactor = null): void {}

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
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return self::$description;
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->seafile->get_format_string();
    }

    /**
     * @param string[] $gather_types
     * @return array<string, mixed>
     * @throws Exception
     */
    #[Override]
    public function get_media_tags(Podcast_Episode|Video|Song $media, array $gather_types, string $sort_pattern, string $rename_pattern, ?string $file_override = null): array
    {
        // if you have the file it's all good
        $media_file = $file_override ?? $media->file;

        if ($media_file && is_file($media_file)) {
            return $this->download_metadata($media_file, $sort_pattern, $rename_pattern, $gather_types);
        }

        if ($this->seafile->prepare()) {
            $fileinfo = $this->seafile->from_virtual_path((string) $media_file);

            $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

            if ($file !== null) {
                return $this->download_metadata($file, $sort_pattern, $rename_pattern, $gather_types);
            }
        }

        return [];
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
     * get_rel_path
     */
    public function get_rel_path(string $file_path): string
    {
        $arr = $this->seafile->from_virtual_path($file_path);

        return $arr['path'] . '/' . $arr['filename'];
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
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return self::$version;
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        self::getCatalogRepository()->createSubTypeTable(CatalogTypeEnum::SEAFILE, ['server_uri' => 'VARCHAR(255)', 'api_key' => 'VARCHAR(100)', 'library_name' => 'VARCHAR(255)', 'api_call_delay' => 'INT']);

        return true;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        return self::getCatalogRepository()->subTypeTableExists(CatalogTypeEnum::SEAFILE);
    }

    /**
     * isReady
     *
     * Returns whether the catalog is ready for use.
     */
    #[Override]
    public function isReady(): bool
    {
        return $this->seafile->ready();
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     */
    public function move_catalog_proc(string $new_path): bool
    {
        return false;
    }

    /**
     * @return array{
     *    file_path: string,
     *    file_name: string,
     *    file_size: int,
     *    file_type: string
     * }
     */
    public function prepare_media(Podcast_Episode|Video|Song $media): array
    {
        $stream_path = (string) $media->file;
        $stream_name = $media->getFileName();
        $size        = $media->size;

        if ($this->seafile->prepare()) {
            set_time_limit(0);

            $fileinfo = $this->seafile->from_virtual_path((string) $media->file);

            $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

            if ($file !== null) {
                $stream_path = $this->seafile->download($file);
                $stream_name = $fileinfo['filename'];
            }

            // in case this didn't get set for some reason
            if ($size === 0) {
                $size = Core::get_filesize($stream_path);
            }
        }

        return [
            'file_path' => $stream_path,
            'file_name' => $stream_name,
            'file_size' => $size,
            'file_type' => $media->type,
        ];
    }

    /**
     * scan_catalog_folders
     */
    public function scan_catalog_folders(?Interactor $interactor = null, bool $skipCounts = false): int
    {
        return 0;
    }

    public function verify_catalog_proc(?int $limit = 0, ?Interactor $interactor = null): int
    {
        set_time_limit(0);

        $date    = time();
        $results = 0;
        if ($this->seafile->prepare()) {
            $songRepository = self::getSongRepository();
            foreach ($songRepository->getFileRowsByCatalog($this->getId()) as $row) {
                debug_event('seafile_catalog', 'Verify starting work on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
                $fileinfo = $this->seafile->from_virtual_path($row['file']);
                $file     = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);
                $metadata = null;
                if ($file !== null) {
                    try {
                        $metadata = $this->download_metadata($file);
                    } catch (Exception $error) {
                        /* HINT: %1 filename (File path), %2 error message */
                        debug_event('seafile_catalog', sprintf('Could not add song "%1$s": %2$s', $file->name, $error->getMessage()), 1);
                        /* HINT: filename (File path) */
                        Ui::update_text('', sprintf(T_('Could not add song: %s'), $file->name));
                    }
                }

                if ($metadata !== null) {
                    debug_event('seafile_catalog', 'Verify updating song', 5);
                    $song = new Song($row['id']);
                    $info = ($song->id !== 0) ? self::update_song_from_tags($metadata, $song) : [];
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
                    $songRepository->delete((int) $row['id']);
                }
            }

            $this->update_last_update($date);
        }

        return $results;
    }

    /**
     * @param null|string[] $gather_types
     * @return array<string, mixed>
     * @throws Exception
     */
    private function download_metadata(DirectoryItem|string $file, string $sort_pattern = '', string $rename_pattern = '', ?array $gather_types = null, bool $keep = false): array
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }

        $is_diritem = ($file instanceof DirectoryItem);
        $is_cached  = (!$is_diritem && is_file($file));

        if ($is_cached) {
            debug_event('seafile_catalog', 'Using tmp file ' . $file, 5);
            $tempfilename = $file;
        } else {
            debug_event('seafile_catalog', 'Downloading partial song', 5);
            $tempfilename = ($file instanceof DirectoryItem)
                ? $this->seafile->download($file, true)
                : $file;
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
        if ($is_diritem) {
            $vainfo->forceSize((int) $file->size);
        }

        $vainfo->gather_tags();
        $key = VaInfo::get_tag_type($vainfo->tags);

        if ($is_diritem) {
            $vainfo->tags['general']['size'] = (int) ($file->size);
        }

        $results = VaInfo::clean_tag_info($vainfo->tags, $key, $tempfilename);

        // Set the remote path
        $results['catalog'] = $this->getId();
        $results['file']    = ($is_diritem)
            ? $this->seafile->to_virtual_path($file)
            : $tempfilename;

        // remove the temp file
        if (!$keep) {
            $this->clean_tmp_file($tempfilename);
        }

        return $results;
    }

    private function getUtilityFactory(): UtilityFactoryInterface
    {
        global $dic;

        return $dic->get(UtilityFactoryInterface::class);
    }

    /**
     * _insert_local_song
     *
     * Insert a song that isn't already in the database.
     */
    private function insert_song(DirectoryItem $file): ?int
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
                    self::getSongRepository()->setField($added, SongFieldEnum::FILE, $virtpath);
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
}
