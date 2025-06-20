<?php

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

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\VaInfo;
use Exception;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use Kunnu\Dropbox\Models\ModelInterface;
use ReflectionException;

/**
 * This class handles all actual work in regards to remote Dropbox catalogs.
 */
class Catalog_dropbox extends Catalog
{
    private string $version     = '000002';
    private string $type        = 'dropbox';
    private string $description = 'Dropbox Remote Catalog';
    private int $count          = 0;

    private int $catalog_id;
    private string $apikey = '';
    private string $secret;
    private string $authcode;
    private string $authtoken;

    public string $path;

    public int $getchunk;

    /** @var array<int> */
    private array $videos_to_gather;

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * get_path
     * This returns the current catalog path/uri
     */
    public function get_path(): string
    {
        return $this->path;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        return "<ul><li>" . T_("Go to https://www.dropbox.com/developers/apps/create") . "</li><li>" . T_("Select 'Dropbox API app'") . "</li><li>" . T_("Select 'Full Dropbox'") . "</li><li>" . T_("Give a name to your application and create it") . "</li><li>" . T_("Click the 'Generate' button to create an Access Token") . "</li><li>" . T_("Copy your App key and App secret and Access Token into the following fields.") . "</li></ul>";
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'catalog_dropbox'";
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

        $sql = "CREATE TABLE `catalog_dropbox` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `apikey` VARCHAR(255) COLLATE $collation NOT NULL, `secret` VARCHAR(255) COLLATE $collation NOT NULL, `path` VARCHAR(255) COLLATE $collation NOT NULL, `authtoken` VARCHAR(255) COLLATE $collation NOT NULL, `getchunk` TINYINT(1) NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string, value?: scalar}
     * >
     */
    public function catalog_fields(): array
    {
        $fields = [];

        $fields['apikey']    = ['description' => T_('API key'), 'type' => 'text'];
        $fields['secret']    = ['description' => T_('Secret'), 'type' => 'password'];
        $fields['authtoken'] = ['description' => T_('Access Token'), 'type' => 'text'];
        $fields['path']      = ['description' => T_('Path'), 'type' => 'text', 'value' => '/'];
        $fields['getchunk']  = [
            'description' => T_('Get chunked files on analyze'),
            'type' => 'checkbox',
            'value' => true,
        ];

        return $fields;
    }

    /**
     * isReady
     */
    public function isReady(): bool
    {
        return (!empty($this->authtoken));
    }

    public function show_ready_process(): void
    {
        // $this->showAuthToken();
    }

    public function perform_ready(): void
    {
        // $this->authcode = $_REQUEST['authcode'];
        // $this->completeAuthToken();
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct(?int $catalog_id = null)
    {
        if ($catalog_id) {
            $this->id = (int)$catalog_id;
            $info     = $this->get_info($catalog_id, static::DB_TABLENAME);
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param array{
     *     apikey?: ?string,
     *     secret?: ?string,
     *     authtoken?: ?string,
     *     path?: ?string,
     *     getchunk?: string|int|null,
     * } $data
     * @throws DropboxClientException
     */
    public static function create_type(string $catalog_id, array $data): bool
    {
        $apikey    = trim($data['apikey'] ?? '');
        $secret    = trim($data['secret'] ?? '');
        $authtoken = trim($data['authtoken'] ?? '');
        $path      = $data['path'] ?? '';
        $getchunk  = (int)($data['getchunk'] ?? 0);

        if (!strlen($apikey) || !strlen($secret) || !strlen($authtoken)) {
            AmpError::add('general', T_('Error: API Key, Secret and Access Token Required for Dropbox Catalogs'));

            return false;
        }
        try {
            $app = new DropboxApp($apikey, $secret, $authtoken);
        } catch (DropboxClientException $error) {
            AmpError::add('general', T_('Invalid "API key", "secret", or "access token": ' . $error->getMessage()));

            return false;
        }
        $dropbox = new Dropbox($app);

        try {
            $dropbox->listFolder($path);
        } catch (DropboxClientException $error) {
            AmpError::add('general', T_('Invalid "dropbox-path": ' . $error->getMessage()));

            return false;
        }

        // Make sure this catalog isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_dropbox` WHERE `apikey` = ?';
        $db_results = Dba::read($sql, [$apikey]);

        if (Dba::num_rows($db_results)) {
            debug_event('dropbox.catalog', 'Cannot add catalog with duplicate key ' . $apikey, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $apikey));

            return false;
        }

        $sql = 'INSERT INTO `catalog_dropbox` (`apikey`, `secret`, `authtoken`, `path`, `getchunk`, `catalog_id`) VALUES (?, ?, ?, ?, ?, ?)';
        Dba::write($sql, [$apikey, $secret, $authtoken, $path, $getchunk, $catalog_id]);

        return true;
    }

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     * @param null|Interactor $interactor
     * @return int
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        // Prevent the script from timing out
        set_time_limit(0);

        if ($options != null) {
            $this->authcode = (string)$options['authcode'];
        }

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top(T_('Running Dropbox Remote Update') . '. . .');
        }
        $songsadded = $this->update_remote_catalog();
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return $songsadded;
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog(): int
    {
        $app         = new DropboxApp($this->apikey, $this->secret, $this->authtoken);
        $dropbox     = new Dropbox($app);
        $this->count = 0;
        $songsadded  = $this->add_files($dropbox, $this->path);
        /* Update the Catalog last_add */
        $this->update_last_add();

        Ui::update_text('', sprintf(T_('Catalog Update Finished.  Total Media: [%s]'), $this->count));

        return $songsadded;
    }

    /**
     * add_files
     *
     * Recurses through directories and pulls out all media files
     */
    public function add_files(Dropbox $dropbox, string $path): int
    {
        debug_event('dropbox.catalog', "List contents for " . $path, 5);
        $listFolderContents = $dropbox->listFolder($path, ['recursive' => true]);
        $songsadded         = 0;

        // Fetch items on the first page
        $items = $listFolderContents->getItems();
        foreach ($items as $item) {
            if ($item instanceof ModelInterface && $item->getDataProperty('.tag') == "file") {
                $subpath = $item->getDataProperty('path_display');
                if (is_string($subpath) && $this->add_file($dropbox, $subpath)) {
                    $songsadded++;
                }
            }
        }

        // Dropbox lists items in pages so you need to set your current
        // position then re-fetch the list from that cursor position.
        if ($listFolderContents->hasMoreItems()) {
            do {
                $cursor             = $listFolderContents->getCursor();
                $listFolderContinue = $dropbox->listFolderContinue($cursor);
                $remainingItems     = $listFolderContinue->getItems();
                foreach ($remainingItems as $item) {
                    if ($item->getDataProperty('.tag') == "file") {
                        $subpath = $item->getDataProperty('path_display');
                        if ($this->add_file($dropbox, $subpath)) {
                            $songsadded++;
                        }
                    }
                }
            } while ($listFolderContinue->hasMoreItems() === true);
        }

        return $songsadded;
    }

    public function add_file(Dropbox $dropbox, string $path): bool
    {
        $file = $dropbox->getMetadata(
            $path,
            [
                'include_media_info' => true,
                'include_deleted' => true
            ]
        );
        $filesize = $file->getDataProperty('size');
        if ($filesize > 0) {
            $is_audio_file = Catalog::is_audio_file($path);
            $is_video_file = Catalog::is_video_file($path);

            if ($is_audio_file) {
                if (count($this->get_gather_types('music')) > 0 && $this->insert_song($dropbox, $path)) {
                    return true;
                } else {
                    debug_event('dropbox.catalog', "read " . $path . " ignored, bad media type for this catalog.", 5);
                }
            } elseif (count($this->get_gather_types('video')) > 0) {
                if ($is_video_file && $this->insert_video($dropbox, $path)) {
                    return true;
                } else {
                    debug_event('dropbox.catalog', "read " . $path . " ignored, bad media type for this video catalog.", 5);
                }
            }
        } else {
            debug_event('dropbox.catalog', "read " . $path . " ignored, 0 bytes", 5);
        }

        return false;
    }

    /**
     * _insert_local_song
     *
     * Insert a song that isn't already in the database.
     * @throws DropboxClientException|Exception
     */
    private function insert_song(Dropbox $dropbox, string $path): bool
    {
        if ($this->check_remote_file($path)) {
            debug_event('dropbox_catalog', 'Skipping existing song ' . $path, 5);

            return false;
        }

        $meta    = $dropbox->getMetadata($path);
        $outfile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

        // Download File
        $this->download($dropbox, $path, -1, $outfile);

        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $outfile,
            $this->get_gather_types('music'),
            '',
            '',
            (string) $this->sort_pattern,
            (string) $this->rename_pattern
        );
        $vainfo->gather_tags();

        $key     = VaInfo::get_tag_type($vainfo->tags);
        $results = VaInfo::clean_tag_info($vainfo->tags, $key, $outfile);
        // Set the remote path
        $results['file']    = $path;
        $results['catalog'] = $this->id;

        // Set the remote path
        if (!empty($results['artist']) && !empty($results['album'])) {
            $this->count++;
            $results['file'] = $outfile;
            $song_id         = Song::insert($results);
            if ($song_id) {
                parent::gather_art([$song_id]);
            }
            $results['file'] = $path;
            $sql             = "UPDATE `song` SET `file` = ? WHERE `id` = ?";
            Dba::write($sql, [$results['file'], $song_id]);
        } else {
            debug_event(
                'dropbox.catalog',
                $results['file'] . " ignored because it is an orphan songs. Please check your catalog patterns.",
                5
            );
        }

        unlink($outfile);

        return true;
    }

    /**
     * insert_local_video
     * This inserts a video file into the video file table the tag
     * information we can get is super sketchy so it's kind of a crap shoot here
     * @throws DropboxClientException|Exception
     */
    public function insert_video(Dropbox $dropbox, string $path): int
    {
        if ($this->check_remote_file($path)) {
            debug_event('dropbox_catalog', 'Skipping existing song ' . $path, 5);

            return 0;
        }

        /* Create the vainfo object and get info */
        $readfile = true;
        $meta     = $dropbox->getMetadata($path);
        $outfile  = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

        // Download File
        $res = $this->download($dropbox, $path, 40960, $outfile);

        if ($res) {
            $gtypes = $this->get_gather_types('video');

            $vainfo = $this->getUtilityFactory()->createVaInfo(
                $outfile,
                $gtypes,
                '',
                '',
                (string) $this->sort_pattern,
                (string) $this->rename_pattern,
                $readfile
            );
            $vainfo->gather_tags();

            $tag_name           = VaInfo::get_tag_type($vainfo->tags, 'metadata_order_video');
            $results            = VaInfo::clean_tag_info($vainfo->tags, $tag_name, $outfile);
            $results['catalog'] = $this->id;

            $results['file'] = $outfile;
            $video_id        = Video::insert($results);
            if ($results['art']) {
                $art = new Art($video_id, 'video');
                $art->insert_url($results['art']);

                if (AmpConfig::get('generate_video_preview')) {
                    Video::generate_preview($video_id);
                }
            } else {
                $this->videos_to_gather[] = $video_id;
            }
            $results['file'] = $path;
            $sql             = "UPDATE `video` SET `file` = ? WHERE `id` = ?";
            Dba::write($sql, [$results['file'], $video_id]);

            return $video_id;
        } else {
            debug_event('dropbox.catalog', 'failed to download file', 5);

            return 0;
        }
    }

    /**
     * @throws DropboxClientException
     */
    public function download(Dropbox $dropbox, string $path, ?int $maxlen = null, ?string $dropboxFile = null): bool
    {
        // Path cannot be null
        if (empty($path)) {
            throw new DropboxClientException("Path cannot be null.");
        }

        // Make Dropbox File if target is specified
        $dropboxFile = ($dropboxFile)
            ? $dropbox->makeDropboxFile($dropboxFile, $maxlen, null, DropboxFile::MODE_WRITE)
            : null;

        // Download File
        $response = $dropbox->postToContent('/files/download', ['path' => $path], null, $dropboxFile);
        if ($response->getHttpStatusCode() == 200) {
            return true;
        }

        return false;
    }

    /**
     * @throws ReflectionException|DropboxClientException
     */
    public function verify_catalog_proc(?int $limit = 0, ?Interactor $interactor = null): int
    {
        set_time_limit(0);

        $date           = time();
        $updated        = 0;
        $utilityFactory = $this->getUtilityFactory();
        $app            = new DropboxApp($this->apikey, $this->secret, $this->authtoken);
        $dropbox        = new Dropbox($app);
        try {
            $sql        = 'SELECT `id`, `file`, `title` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, [$this->id]);
            while ($row = Dba::fetch_assoc($db_results)) {
                debug_event('dropbox.catalog', 'Starting verify on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
                $path     = $row['file'];
                $filesize = 40960;
                $meta     = $dropbox->getMetadata($path);
                $outfile  = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

                $res = $this->download($dropbox, $path, $filesize, $outfile);
                if ($res) {
                    debug_event('dropbox.catalog', 'updating song', 5);
                    $song = new Song($row['id']);

                    $vainfo = $utilityFactory->createVaInfo(
                        $outfile,
                        $this->get_gather_types('music'),
                        '',
                        '',
                        (string) $this->sort_pattern,
                        (string) $this->rename_pattern
                    );
                    $vainfo->forceSize((int)$filesize);
                    $vainfo->gather_tags();

                    $key     = VaInfo::get_tag_type($vainfo->tags);
                    $results = VaInfo::clean_tag_info($vainfo->tags, $key, $outfile);
                    // Must compare to original path, not temporary location.
                    $results['file'] = $path;
                    $info            = ($song->id) ? self::update_song_from_tags($results, $song) : [];
                    if ($info['change']) {
                        Ui::update_text('', sprintf(T_('Updated song: "%s"'), $row['title']));
                        $updated++;
                    } else {
                        Ui::update_text('', sprintf(T_('Song up to date: "%s"'), $row['title']));
                    }
                } else {
                    debug_event('dropbox.catalog', 'removing song', 5);
                    Ui::update_text('', sprintf(T_('Removing song: "%s"'), $row['title']));
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', [$row['id']]);
                }
            }

            $this->update_last_update($date);
        } catch (DropboxClientException $error) {
            AmpError::add('general', T_('Invalid "API key", "secret", or "access token": ' . $error->getMessage()));
        }

        return $updated;
    }

    /**
     * clean_catalog_proc
     *
     * Removes songs that no longer exist.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        $dead    = 0;
        $app     = new DropboxApp($this->apikey, $this->secret, $this->authtoken);
        $dropbox = new Dropbox($app);

        $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, [$this->id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('dropbox.catalog', 'Starting clean on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
            $file = $row['file'];
            try {
                $metadata = $dropbox->getMetadata($file, ["include_deleted" => true]);
            } catch (DropboxClientException $error) {
                if ($error->getCode() == 409) {
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', [$row['id']]);
                } else {
                    AmpError::add('general', T_('API Error: cannot connect to Dropbox.'));
                }
            }
        }
        $this->update_last_clean();

        return $dead;
    }

    /**
     * @return string[]
     */
    public function check_catalog_proc(?Interactor $interactor = null): array
    {
        return [];
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
     * if it find a song it returns the UID
     */
    public function check_remote_file(string $file): ?int
    {
        $is_audio_file = Catalog::is_audio_file($file);
        if ($is_audio_file) {
            $sql = 'SELECT `id` FROM `song` WHERE `file` = ?';
        } else {
            $sql = 'SELECT `id` FROM `video` WHERE `file` = ?';
        }
        $db_results = Dba::read($sql, [$file]);
        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return null;
    }

    /**
     * get_virtual_path
     * @param string $file
     */
    public function get_virtual_path($file): string
    {
        return $this->apikey . '|' . $file;
    }

    /**
     * get_rel_path
     */
    public function get_rel_path(string $file_path): string
    {
        $path = strpos($file_path, "|");
        if ($path !== false) {
            $path++;
        }

        return substr($file_path, $path);
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->apikey;
    }

    /**
     * @param Podcast_Episode|Song|Video $media
     * @return array{
     *     file_path: string,
     *     file_name: string,
     *     file_size: int,
     *     file_type: string
     * }
     * @throws DropboxClientException
     */
    public function prepare_media(Podcast_Episode|Video|Song $media): array
    {
        $app     = new DropboxApp($this->apikey, $this->secret, $this->authtoken);
        $dropbox = new Dropbox($app);

        $file = (string) $media->file;

        try {
            set_time_limit(0);
            $meta    = $dropbox->getMetadata((string)$media->file);
            $outfile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

            // Download File
            $this->download($dropbox, $file, null, $outfile);

            $file = $outfile;
        } catch (DropboxClientException) {
            debug_event('dropbox.catalog', 'File not found on Dropbox: ' . $media->file, 5);
        }

        return [
            'file_path' => $file,
            'file_name' => $media->getFileName(),
            'file_size' => $media->size,
            'file_type' => $media->type,
        ];
    }

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and tries
     * to find the art for them from the mp3s
     * @param Song[]|null $songs
     * @param Video[]|null $videos
     * @throws DropboxClientException
     */
    public function gather_art(?array $songs = null, ?array $videos = null, ?Interactor $interactor = null): bool
    {
        // Make sure they've actually got methods
        $art_order = AmpConfig::get('art_order');
        if (!count($art_order)) {
            $interactor?->info(
                'art_order not set, Catalog::gather_art aborting',
                true
            );
            debug_event('dropbox.catalog', 'art_order not set, Catalog::gather_art aborting', 3);

            return true;
        }
        $app     = new DropboxApp($this->apikey, $this->secret, $this->authtoken);
        $dropbox = new Dropbox($app);
        $songs   = $this->get_songs();

        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        $searches     = [];
        if ($songs == null) {
            $searches['album']  = $this->get_album_ids();
            $searches['artist'] = $this->get_artist_ids();
        } else {
            $searches['album']  = [];
            $searches['artist'] = [];
            foreach ($songs as $song) {
                if ($song->isNew() === false && !empty($song->file)) {
                    $meta    = $dropbox->getMetadata($song->file);
                    $outfile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

                    // Download File
                    $res = $this->download($dropbox, $song->file, 40960, $outfile);
                    if ($res) {
                        $sql = "UPDATE `song` SET `file` = ? WHERE `id` = ?";
                        Dba::write($sql, [$outfile, $song->id]);
                        parent::gather_art([$song->id]);
                        $sql = "UPDATE `song` SET `file` = ? WHERE `id` = ?";
                        Dba::write($sql, [$song->file, $song->id]);
                        $search_count++;
                        if (Ui::check_ticker()) {
                            Ui::update_text('count_art_' . $this->id, $search_count);
                        }
                    }
                }
            }
        }

        // One last time for good measure
        Ui::update_text('count_art_' . $this->id, $search_count);

        return true;
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
