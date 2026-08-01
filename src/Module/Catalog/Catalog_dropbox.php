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
use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Art;
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
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use Kunnu\Dropbox\Models\ModelInterface;
use Override;
use ReflectionException;

/**
 * This class handles all actual work in regards to remote Dropbox catalogs.
 */
class Catalog_dropbox extends Catalog
{
    public bool $getchunk;
    public string $path;
    private string $apikey = '';

    //private string $authcode;
    private string $authtoken;
    private int $count          = 0;
    private string $description = 'Dropbox Remote Catalog';
    private string $secret;
    private string $type    = 'dropbox';
    private string $version = '000002';

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

            $this->getchunk       = (bool) ($info['getchunk'] ?? false);
            $this->path           = $info['path'] ?? '';
            $this->apikey         = $info['apikey'] ?? '';
            $this->authtoken      = $info['authtoken'] ?? '';
            $this->secret         = $info['secret'] ?? '';
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
     */
    public static function create_type(int $catalog_id, array $data): bool
    {
        $apikey    = trim($data['apikey'] ?? '');
        $secret    = trim($data['secret'] ?? '');
        $authtoken = trim($data['authtoken'] ?? '');
        $path      = $data['path'] ?? '';
        $getchunk  = (bool) ($data['getchunk'] ?? 0);

        $dropbox = self::_connect_dropbox($apikey, $secret, $authtoken);
        if (!$dropbox) {
            return false;
        }

        try {
            $dropbox->listFolder($path);
        } catch (DropboxClientException $dropboxClientException) {
            AmpError::add('general', T_('Invalid "dropbox-path": ' . $dropboxClientException->getMessage()));

            return false;
        }

        // Make sure this catalog isn't already in use by an existing catalog
        $catalogRepository = self::getCatalogRepository();
        if ($catalogRepository->subTypeValueExists(CatalogTypeEnum::DROPBOX, 'apikey', $apikey)) {
            debug_event('dropbox.catalog', 'Cannot add catalog with duplicate key ' . $apikey, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $apikey));

            return false;
        }

        return $catalogRepository->insertSubType(
            CatalogTypeEnum::DROPBOX,
            ['apikey' => $apikey, 'secret' => $secret, 'authtoken' => $authtoken, 'path' => $path, 'getchunk' => $getchunk],
            $catalog_id
        );
    }

    private static function _connect_dropbox(string $apikey, string $secret, string $authtoken = ''): ?Dropbox
    {
        if (!strlen($apikey) || !strlen($secret) || !strlen($authtoken)) {
            AmpError::add('general', T_('Error: API Key, Secret and Access Token Required for Dropbox Catalogs'));

            return null;
        }

        $app = new DropboxApp($apikey, $secret, $authtoken);
        try {
            $dropbox = new Dropbox($app);
        } catch (DropboxClientException $dropboxClientException) {
            AmpError::add('general', T_('Invalid "API key", "secret", or "access token": ' . $dropboxClientException->getMessage()));

            return null;
        }

        return $dropbox;
    }

    public function add_file(Dropbox $dropbox, string $path): bool
    {
        try {
            $file = $dropbox->getMetadata(
                $path,
                [
                    'include_media_info' => true,
                    'include_deleted' => true
                ]
            );
        } catch (DropboxClientException $dropboxClientException) {
            debug_event('dropbox.catalog', 'Error: ' . $dropboxClientException->getMessage(), 3);

            return false;
        }
        $filesize = $file->getDataProperty('size');
        if ($filesize > 0) {
            $is_audio_file = Catalog::is_audio_file($path);
            $is_video_file = Catalog::is_video_file($path);

            if ($is_audio_file) {
                if ($this->get_gather_types('music') !== [] && $this->insert_song($dropbox, $path)) {
                    return true;
                }

                debug_event('dropbox.catalog', "read " . $path . " ignored, bad media type for this catalog.", 5);
            } elseif ($this->get_gather_types('video') !== []) {
                if ($is_video_file && $this->insert_video($dropbox, $path)) {
                    return true;
                }

                debug_event('dropbox.catalog', "read " . $path . " ignored, bad media type for this video catalog.", 5);
            }
        } else {
            debug_event('dropbox.catalog', "read " . $path . " ignored, 0 bytes", 5);
        }

        return false;
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

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        // Prevent the script from timing out
        set_time_limit(0);

        //if ($options != null) {
        //    $this->authcode = (string)$options['authcode'];
        //}

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
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        return false;
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string, value?: scalar}
     * >
     */
    public function catalog_fields(): array
    {
        return ['apikey' => ['description' => T_('API key'), 'type' => 'text'], 'secret' => ['description' => T_('Secret'), 'type' => 'password'], 'authtoken' => ['description' => T_('Access Token'), 'type' => 'text'], 'path' => ['description' => T_('Path'), 'type' => 'text', 'value' => '/'], 'getchunk' => [
            'description' => T_('Get chunked files on analyze'),
            'type' => 'checkbox',
            'value' => true,
        ]];
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
     * if it find a song it returns the UID
     */
    public function check_remote_file(string $file): ?int
    {
        return (Catalog::is_audio_file($file))
            ? self::getSongRepository()->findIdByFile($file)
            : self::getVideoRepository()->findIdByFile($file);
    }

    /**
     * clean_catalog_proc
     *
     * Removes songs that no longer exist.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        $dead = 0;

        $dropbox = self::_connect_dropbox($this->apikey, $this->secret, $this->authtoken);
        if (!$dropbox) {
            return $dead;
        }

        $songRepository = self::getSongRepository();
        foreach ($songRepository->getFilesByCatalog($this->getId()) as $songId => $songFile) {
            $row = ['id' => $songId, 'file' => $songFile];
            debug_event('dropbox.catalog', 'Starting clean on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
            $file = $row['file'];
            try {
                $dropbox->getMetadata($file, ["include_deleted" => true]);
            } catch (DropboxClientException $error) {
                if ($error->getCode() == 409) {
                    $dead++;
                    $songRepository->delete((int) $row['id']);
                } else {
                    AmpError::add('general', T_('API Error: cannot connect to Dropbox.'));
                }
            }
        }

        $this->update_last_clean();

        return $dead;
    }

    public function count_scan_folders(?Interactor $interactor = null): void {}

    /**
     * @throws DropboxClientException
     */
    public function download(Dropbox $dropbox, string $path, ?int $maxlen = null, ?string $dropboxFile = null): bool
    {
        // Path cannot be null
        if ($path === '' || $path === '0') {
            throw new DropboxClientException("Path cannot be null.");
        }

        // Make Dropbox File if target is specified
        $dropboxFile = ($dropboxFile)
            ? $dropbox->makeDropboxFile($dropboxFile, $maxlen, null, DropboxFile::MODE_WRITE)
            : null;

        // Download File
        try {
            $response = $dropbox->postToContent('/files/download', ['path' => $path], null, $dropboxFile);
            if ($response->getHttpStatusCode() == 200) {
                return true;
            }
        } catch (Exception $exception) {
            debug_event('dropbox.catalog', 'download error: ' . $exception->getMessage(), 3);
        }

        return false;
    }

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and tries
     * to find the art for them from the mp3s
     * @param int[]|null $songs
     * @param int[]|null $videos
     * @throws DropboxClientException
     */
    #[Override]
    public function gather_art(?array $songs = null, ?array $videos = null, ?Interactor $interactor = null): bool
    {
        // Make sure they've actually got methods
        $art_order = AmpConfig::get('art_order');
        if (count($art_order) === 0) {
            $interactor?->info(
                'art_order not set, Catalog::gather_art aborting',
                true
            );
            debug_event('dropbox.catalog', 'art_order not set, Catalog::gather_art aborting', 3);

            return true;
        }

        $dropbox = self::_connect_dropbox($this->apikey, $this->secret, $this->authtoken);
        if (!$dropbox) {
            return false;
        }

        $songs = $this->get_songs();

        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        foreach ($songs as $song) {
            if ($song->isNew() === false && !empty($song->file)) {
                $meta    = $dropbox->getMetadata($song->file);
                $outfile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

                // Download File
                $res = $this->download($dropbox, $song->file, 40960, $outfile);
                if ($res) {
                    $songRepository = self::getSongRepository();
                    // the art gatherer reads the file off disk, so the row points at the download while it runs
                    $songRepository->setField($song->id, SongFieldEnum::FILE, $outfile);
                    parent::gather_art([$song->id]);

                    $songRepository->setField($song->id, SongFieldEnum::FILE, $song->file);
                    $search_count++;
                    if (Ui::check_ticker()) {
                        Ui::update_text('count_art_' . $this->getId(), $search_count);
                    }
                }
            }
        }

        // One last time for good measure
        Ui::update_text('count_art_' . $this->getId(), $search_count);

        return true;
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
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->apikey;
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
     * get_rel_path
     */
    public function get_rel_path(string $file_path): string
    {
        $path = strpos($file_path, "|");

        if ($path === false) {
            return $file_path;
        }

        return substr($file_path, $path + 1);
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
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * get_virtual_path
     */
    public function get_virtual_path(string $file): string
    {
        return $this->apikey . '|' . $file;
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
        $meta    = $dropbox->getMetadata($path);
        $outfile = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $meta->getName();

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
                (string) $this->rename_pattern
            );
            $vainfo->gather_tags();

            $tag_name           = VaInfo::get_tag_type($vainfo->tags, 'metadata_order_video');
            $results            = VaInfo::clean_tag_info($vainfo->tags, $tag_name, $outfile);
            $results['catalog'] = $this->getId();

            $results['file'] = $outfile;
            $video_id        = Video::insert($results);
            if ($results['art']) {
                $art = new Art($video_id, 'video');
                $art->insert_url($results['art']);

                if (AmpConfig::get('generate_video_preview')) {
                    Video::generate_preview($video_id);
                }
            }

            $results['file'] = $path;
            self::getVideoRepository()->setFile($video_id, $results['file']);

            return $video_id;
        }

        debug_event('dropbox.catalog', 'failed to download file', 5);

        return 0;
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        self::getCatalogRepository()->createSubTypeTable(CatalogTypeEnum::DROPBOX, ['apikey' => 'VARCHAR(255)', 'secret' => 'VARCHAR(255)', 'path' => 'VARCHAR(255)', 'authtoken' => 'VARCHAR(2048)', 'getchunk' => 'TINYINT(1)']);

        return true;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        return self::getCatalogRepository()->subTypeTableExists(CatalogTypeEnum::DROPBOX);
    }

    /**
     * isReady
     */
    #[Override]
    public function isReady(): bool
    {
        return (isset($this->authtoken) && ($this->authtoken !== '' && $this->authtoken !== '0'));
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     */
    public function move_catalog_proc(string $new_path): bool
    {
        return false;
    }

    #[Override]
    public function perform_ready(): void
    {
        // $this->authcode = $_REQUEST['authcode'];
        // $this->completeAuthToken();
    }

    /**
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
        $dropbox = self::_connect_dropbox($this->apikey, $this->secret, $this->authtoken);
        if (!$dropbox) {
            throw new DropboxClientException('Could not connect to Dropbox.');
        }

        $file = (string) $media->file;

        try {
            set_time_limit(0);
            $meta    = $dropbox->getMetadata((string) $media->file);
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
     * scan_catalog_folders
     */
    public function scan_catalog_folders(?Interactor $interactor = null, bool $skipCounts = false): int
    {
        return 0;
    }

    #[Override]
    public function show_ready_process(): void
    {
        // $this->showAuthToken();
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog(): int
    {
        $this->count = 0;

        $dropbox = self::_connect_dropbox($this->apikey, $this->secret, $this->authtoken);
        if (!$dropbox) {
            return 0;
        }

        $songsadded = $this->add_files($dropbox, $this->path);

        /* Update the Catalog last_add */
        $this->update_last_add();

        Ui::update_text('', sprintf(T_('Catalog Update Finished.  Total Media: [%s]'), $this->count));

        return $songsadded;
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
        $dropbox        = self::_connect_dropbox($this->apikey, $this->secret, $this->authtoken);
        if (!$dropbox) {
            return 0;
        }
        try {
            $songRepository = self::getSongRepository();
            foreach ($songRepository->getFileRowsByCatalog($this->getId()) as $row) {
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
                    $vainfo->forceSize($filesize);
                    $vainfo->gather_tags();

                    $key     = VaInfo::get_tag_type($vainfo->tags);
                    $results = VaInfo::clean_tag_info($vainfo->tags, $key, $outfile);
                    // Must compare to original path, not temporary location.
                    $results['file'] = $path;
                    $info            = ($song->id !== 0) ? self::update_song_from_tags($results, $song) : [];
                    if ($info['change']) {
                        Ui::update_text('', sprintf(T_('Updated song: "%s"'), $row['title']));
                        $updated++;
                    } else {
                        Ui::update_text('', sprintf(T_('Song up to date: "%s"'), $row['title']));
                    }
                } else {
                    debug_event('dropbox.catalog', 'removing song', 5);
                    Ui::update_text('', sprintf(T_('Removing song: "%s"'), $row['title']));
                    $songRepository->delete((int) $row['id']);
                }
            }

            $this->update_last_update($date);
        } catch (DropboxClientException $dropboxClientException) {
            AmpError::add('general', T_('Invalid "API key", "secret", or "access token": ' . $dropboxClientException->getMessage()));
        }

        return $updated;
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
        $results['catalog'] = $this->getId();

        // Set the remote path
        if (isset($results['artist']) && ($results['artist'] !== '' && $results['artist'] !== '0') && (isset($results['album']) && ($results['album'] !== '' && $results['album'] !== '0'))) {
            $this->count++;
            $results['file'] = $outfile;
            $song_id         = Song::insert($results);
            if ($song_id) {
                parent::gather_art([$song_id]);

                $results['file'] = $path;
                self::getSongRepository()->setField($song_id, SongFieldEnum::FILE, $results['file']);
            }
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
}
