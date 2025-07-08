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

namespace Ampache\Repository\Model;

use Ampache\Plugin\AmpacheDiscogs;
use Ampache\Plugin\AmpacheMusicBrainz;
use Ampache\Plugin\AmpacheTheaudiodb;
use Ampache\Config\AmpConfig;
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Art\Collector\MetaTagCollectorModule;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\System\Core;
use Ampache\Repository\SongRepositoryInterface;
use WpOrg\Requests\Autoload;
use WpOrg\Requests\Requests;
use Exception;
use RuntimeException;

/**
 * This class handles the images / artwork in ampache
 * This was initially in the album class, but was pulled out
 * to be more general and potentially apply to albums, artists etc
 */
class Art extends database_object
{
    protected const DB_TABLENAME = 'image';

    public const VALID_TYPES = [
        'bmp',
        'gif',
        'jp2',
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    public ?int $id = 0;

    public string $object_type;

    public int $object_id;

    public int $width = 0;

    public int $height = 0;

    public string $raw = '';

    public string $raw_mime = '';

    public string $kind = 'default';

    public ?string $thumb = null;

    public ?string $thumb_mime = null;

    private bool $fallback = false;

    /**
     * Constructor
     * Art constructor, takes the UID of the object and the object type.
     */
    public function __construct(
        ?int $uid = 0,
        string $type = 'album',
        string $kind = 'default'
    ) {
        if (!$uid || !$type || !$kind) {
            return;
        }

        if (self::is_valid_type($type)) {
            $this->object_type = $type;
            $this->object_id   = $uid;
            $this->kind        = $kind;
        }
    }

    public function getId(): int
    {
        return $this->id ?: 0;
    }

    private static function _hasGD(): bool
    {
        return (
            AmpConfig::get('resize_images') &&
            ((extension_loaded('gd') || extension_loaded('gd2')) && function_exists('gd_info'))
        );
    }

    public static function is_valid_type(?string $type = null): bool
    {
        if (!$type) {
            return false;
        }

        return (
            InterfaceImplementationChecker::is_library_item($type) ||
            $type == 'user'
        );
    }

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point
     * @param list<int> $object_ids
     */
    public static function build_cache(array $object_ids, ?string $type = null): bool
    {
        if ($object_ids === []) {
            return false;
        }

        $idlist = '(' . implode(',', $object_ids) . ')';
        $sql    = 'SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_id` IN ' . $idlist;
        if ($type !== null) {
            $sql .= sprintf(' AND `object_type` = \'%s\'', $type);
        }

        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('art', $row['object_type'] . $row['object_id'] . $row['size'], $row);
        }

        return true;
    }

    /**
     * extension
     * This returns the file extension for the currently loaded art
     */
    public static function extension(?string $mime): string
    {
        if (empty($mime)) {
            return '';
        }

        $data      = explode('/', $mime);
        $extension = $data['1'] ?? '';

        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }

        return $extension;
    }

    /**
     * test_image
     * Runs some sanity checks on the putative image
     * @throws RuntimeException
     */
    private function test_image(string $source): bool
    {
        // Check to make sure PHP:GD exists. Don't test things you can't change
        if (!function_exists('imagecreatefromstring')) {
            return true;
        }

        $test  = false;
        $image = false;
        if (!empty($source)) {
            $test  = true;
            $image = imagecreatefromstring($source);
            if (!$image || imagesx($image) < 5 || imagesy($image) < 5) {
                debug_event(self::class, 'Image failed PHP-GD test', 1);
                $test = false;
            }
        }

        if ($test && $image && imagedestroy($image) === false) {
            throw new RuntimeException('The image handle from source: ' . $source . ' could not be destroyed');
        }

        return $test;
    }

    /**
     * test_size
     * Runs some sanity checks on the putative image
     * @throws RuntimeException
     */
    private function test_size(string $source): bool|string
    {
        $source_size = strlen($source);
        if ($source_size < 10) {
            debug_event(self::class, 'Invalid image passed', 1);

            return 'invalid_image';
        }

        $max_upload_size = (int)AmpConfig::get('max_upload_size', 0);

        // Check image size doesn't exceed the limit
        if ($max_upload_size > 0 && $source_size > $max_upload_size) {
            debug_event(self::class, 'Image size (' . $source_size . ') exceed the limit (' . $max_upload_size . ').', 1);

            return 'max_upload_size';
        }

        return true;
    }

    /**
     * get
     * This returns the art for our current object, this can
     * look in the database and will return the thumb if it
     * exists, if it doesn't depending on settings it will try
     * to create it.
     */
    public function get(string $size = 'original', bool $fallback = false): string
    {
        // Get the data either way (allow forcing to fallback image)
        if (!$this->has_db_info($size, $fallback)) {
            return '';
        }

        if ($size === 'original' || !$this->thumb) {
            return $this->raw;
        } else {
            return $this->thumb;
        }
    }

    /**
     * get_image
     * fill the default image raw, mime and thumb details
     */
    public function get_image(bool $fallback = false, ?string $size = null): bool
    {
        $sql        = "SELECT `id`, `image`, `width`, `height`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = 'original' AND `kind` = ?";
        $db_results = Dba::read($sql, [$this->object_type, $this->object_id, $this->kind]);

        if ($results = Dba::fetch_assoc($db_results)) {
            if (AmpConfig::get('album_art_store_disk')) {
                $this->raw = (string)self::read_from_dir($results['size'], $this->object_type, $this->object_id, $this->kind, $results['mime']);
            } else {
                $this->raw = $results['image'];
            }

            $this->raw_mime = $results['mime'];
            $this->id       = (int)$results['id'];
            $this->width    = (int)$results['width'];
            $this->height   = (int)$results['height'];
        }

        // return a default image if fallback is requested
        if (!$this->raw && $fallback) {
            $this->raw      = $this->get_blankalbum($size);
            $this->raw_mime = 'image/png';
            $this->fallback = true;
        }

        // If we get nothing return false
        if (!$this->raw) {
            return false;
        }

        return true;
    }

    /**
     * has_db_info
     * This pulls the information out from the database, depending
     * on if we want to resize and if there is not a thumbnail go
     * ahead and try to resize
     */
    public function has_db_info(string $size = 'original', bool $fallback = false): bool
    {
        if (
            $size === 'original' ||
            !self::_hasGD()
        ) {
            return $this->get_image($fallback, $size);
        }

        if (preg_match('/^[0-9]+x[0-9]+$/', $size)) {
            $dimensions           = explode('x', $size);
            $width                = (int)$dimensions[0];
            $height               = (int)$dimensions[1];
            $thumb_size           = [];
            $thumb_size['width']  = $width;
            $thumb_size['height'] = $height;
        } else {
            $width      = 0;
            $height     = 0;
            $thumb_size = [
                'width' => 275,
                'height' => 275
            ];
        }

        // Thumbnails might already be in the database
        if ($width > 0 && $height > 0) {
            $sql    = "SELECT `id`, `image`, `width`, `height`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND (`size` = ? OR (`size` = 'original' AND `width` = ? AND `height` = ?)) AND `kind` = ?";
            $params = [$this->object_type, $this->object_id, $size, $width, $height, $this->kind];
        } else {
            $sql    = "SELECT `id`, `image`, `width`, `height`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ? AND `kind` = ?";
            $params = [$this->object_type, $this->object_id, $size, $this->kind];
        }
        $db_results = Dba::read($sql, $params);
        if ($results = Dba::fetch_assoc($db_results)) {
            $this->id         = (int)$results['id'];
            $this->width      = (int)$results['width'];
            $this->height     = (int)$results['height'];
            $this->thumb_mime = $results['mime'];
            $this->thumb      = (AmpConfig::get('album_art_store_disk'))
                ? (string)self::read_from_dir($results['size'], $this->object_type, $this->object_id, $this->kind, $results['mime'])
                : $results['image'];

            if (!empty($this->thumb)) {
                return true;
            }
        }

        // If there is no thumb in the database and we want one we have to generate it
        if ($this->get_image($fallback, $size)) {
            $data = $this->generate_thumb($this->raw, $thumb_size, $this->raw_mime);

            // thumb wasn't generated
            if ($data === []) {
                debug_event(self::class, 'Art id {' . $this->id . '} Unable to generate thumbnail for ' . $this->object_type . ': ' . $this->object_id, 1);

                return false;
            }

            if (!$this->fallback) {
                $this->save_thumb($data['thumb'], $data['thumb_mime'], $thumb_size);
            }

            $this->thumb      = $data['thumb'];
            $this->thumb_mime = $data['thumb_mime'];

            return true;
        }

        return false;
    }

    /**
     * This check if an object has an associated image in db.
     */
    public static function has_db(int $object_id, string $object_type, ?string $kind = 'default', ?string $size = 'original'): bool
    {
        if (database_object::is_cached('art_has_db_' . $object_type, $object_id)) {
            $nb_img = database_object::get_from_cache('art_has_db_' . $object_type, $object_id)[0];

            return ($nb_img > 0);
        }
        $sql        = "SELECT COUNT(`id`) AS `nb_img` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, [$object_type, $object_id, $size, $kind]);
        $nb_img     = 0;
        if ($results = Dba::fetch_assoc($db_results)) {
            $nb_img = (int)$results['nb_img'];
        }
        database_object::add_to_cache('art_has_db_' . $object_type, $object_id, [$nb_img]);

        return ($nb_img > 0);
    }

    /**
     * This insert art from url.
     */
    public function insert_url(string $url): void
    {
        debug_event(self::class, 'Insert art from url ' . $url, 4);
        $image = self::get_from_source(['url' => $url], $this->object_type);
        $rurl  = pathinfo($url);
        $ext   = (isset($rurl['extension']) && !str_starts_with($rurl['extension'], 'php')) ? $rurl['extension'] : 'jpeg';
        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $query);
        $rurl = (isset($query['name']) && is_string($query['name']))
            ? pathinfo($query['name'])
            : pathinfo($url);

        $ext  = (isset($rurl['extension']) && !str_starts_with($rurl['extension'], 'php')) ? $rurl['extension'] : 'jpeg';
        $mime = "image/" . $ext;
        $this->insert($image, $mime);
    }

    /**
     * insert
     * This takes the string representation of an image and inserts it into
     * the database. You must also pass the mime type.
     */
    public function insert(string $source, ?string $mime = ''): bool|string
    {
        // Disabled in demo mode cause people suck and upload porn
        if (AmpConfig::get('demo_mode')) {
            return false;
        }

        $test_size = $this->test_size($source);
        if ($test_size !== true) {
            debug_event(self::class, 'Not inserting image for ' . $this->object_type . ' ' . $this->object_id . ', failed check: ' . $test_size, 1);

            return $test_size;
        }

        // Check to make sure we like this image
        if (!$this->test_image($source)) {
            debug_event(self::class, 'Not inserting image for ' . $this->object_type . ' ' . $this->object_id . ', invalid data passed', 1);

            return false;
        }

        $dimensions = Core::image_dimensions($source);
        $width      = $dimensions['width'];
        $height     = $dimensions['height'];
        $sizetext   = 'original';

        if (!self::check_dimensions($dimensions)) {
            debug_event(self::class, 'Not inserting image for ' . $this->object_type . ' ' . $this->object_id . ', dimensions are wrong (' . $width . 'x' . $height . ')', 1);

            return 'check_dimensions';
        }

        // Default to image/jpeg if they don't pass anything
        $mime = (empty($mime))
            ? 'image/jpeg'
            : $mime;
        // Blow it away!
        $this->reset();
        $picturetypeid = ($this->object_type == 'album') ? 3 : 8;

        if (AmpConfig::get('write_tags', false)) {
            $className = ObjectTypeToClassNameMapper::map($this->object_type);
            /** @var playable_item $object */
            $object = new $className($this->object_id);
            $songs  = [];
            debug_event(self::class, 'Inserting ' . $this->object_type . ' image' . $object->get_fullname() . ' for song files.', 5);
            if ($this->object_type === 'album') {
                /** Use special treatment for albums */
                $songs = $this->getSongRepository()->getByAlbum($object->getId());
            } elseif ($this->object_type === 'artist') {
                /** Use special treatment for artists */
                $songs = $this->getSongRepository()->getByArtist($object->getId());
            }

            global $dic;
            $utilityFactory = $dic->get(UtilityFactoryInterface::class);

            foreach ($songs as $song_id) {
                $song        = new Song($song_id);
                $description = ($this->object_type == 'artist') ? $song->get_artist_fullname() : $object->get_fullname();
                $vainfo      = $utilityFactory->createVaInfo(
                    $song->file
                );

                $ndata      = [];
                $data       = $vainfo->read_id3();
                $fileformat = $data['fileformat'];
                $apics      = ($fileformat == 'flac' || $fileformat == 'ogg')
                    ? $data['flac']['PICTURE']
                    : $data['id3v2']['APIC'];

                /* is the file flac or mp3? */
                $apic_typeid = ($fileformat == 'flac' || $fileformat == 'ogg')
                    ? 'typeid'
                    : 'picturetypeid';
                $apic_mimetype = ($fileformat == 'flac' || $fileformat == 'ogg')
                    ? 'image_mime'
                    : 'mime';
                $new_pic = [
                    'data' => $source,
                    'description' => $description,
                    'mime' => $mime,
                    'picturetypeid' => $picturetypeid,
                ];

                if (is_null($apics)) {
                    $ndata['attached_picture'][] = $new_pic;
                } else {
                    switch (count($apics)) {
                        case 1:
                            $idx = $this->check_for_duplicate($apics, $ndata, $new_pic, $apic_typeid);
                            if (is_null($idx)) {
                                $ndata['attached_picture'][] = $new_pic;
                                $ndata['attached_picture'][] = [
                                    'data' => $apics[0]['data'],
                                    'description' => $apics[0]['description'],
                                    'mime' => $apics[0]['mime'],
                                    'picturetypeid' => $apics[0]['picturetypeid'],
                                ];
                            }
                            break;
                        case 2:
                            $idx = $this->check_for_duplicate($apics, $ndata, $new_pic, $apic_typeid);
                            /* If $idx is null, it means both images are of opposite types
                             * of the new image. Either image could be replaced to have
                             * one cover and one artist image.
                             */
                            if (is_null($idx)) {
                                $ndata['attached_picture'][0] = $new_pic;
                            } else {
                                $apicsId                             = ($idx == 0) ? 1 : 0;
                                $ndata['attached_picture'][$apicsId] = [
                                    'data' => $apics[$apicsId]['data'],
                                    'description' => $apics[$apicsId]['description'],
                                    'mime' => $apics[$apicsId][$apic_mimetype],
                                    'picturetypeid' => $apics[$apicsId][$apic_typeid],
                                ];
                            }
                            break;
                    }
                }

                unset($apics);
                $tags  = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'vorbiscomment' : 'id3v2';
                $ndata = array_merge($ndata, $vainfo->prepare_metadata_for_writing($data['tags'][$tags]));
                $vainfo->write_id3($ndata);
            } // foreach song
        } // write_id3

        if (AmpConfig::get('album_art_store_disk') && self::write_to_dir($source, $sizetext, $this->object_type, $this->object_id, $this->kind, $mime)) {
            $source = null;
        }

        // Insert it!
        $sql = "REPLACE INTO `image` (`image`, `width`, `height`, `mime`, `size`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [
            $source,
            $width,
            $height,
            $mime,
            $sizetext,
            $this->object_type,
            $this->object_id,
            $this->kind,
        ]);

        // clear object cache on insert
        if (parent::is_cached('art', $this->object_type . $this->object_id . 'original')) {
            parent::clear_cache();
        }

        return true;
    }

    /**
     * check_for_duplicate
     * @param array<int, array{data: string, description: null|string, mime: null|string, picturetypeid: int}> $apics
     * @param array<string, array<int, array{data: string, description: null|string, mime: null|string, picturetypeid: int}>> $ndata
     * @param array{data: string, description: null|string, mime: null|string, picturetypeid: int} $new_pic
     */
    private function check_for_duplicate(array $apics, array &$ndata, array $new_pic, string $apic_typeid): ?int
    {
        $idx = null;
        $cnt = count($apics);
        for ($i = 0; $i < $cnt; ++$i) {
            if ($new_pic['picturetypeid'] == $apics[$i][$apic_typeid]) {
                $ndata['attached_picture'][$i]['data']          = $new_pic['data'];
                $ndata['attached_picture'][$i]['description']   = $new_pic['description'];
                $ndata['attached_picture'][$i]['mime']          = $new_pic['mime'];
                $ndata['attached_picture'][$i]['picturetypeid'] = $new_pic['picturetypeid'];
                $idx                                            = $i;
                break;
            }
        }

        return $idx;
    }

    /**
     * check_dimensions
     * @param array{width: int, height: int} $dimensions
     */
    public static function check_dimensions(array $dimensions): bool
    {
        $width  = $dimensions['width'];
        $height = $dimensions['height'];

        if ($width > 0 && $height > 0) {
            $minw = AmpConfig::get('album_art_min_width', 0);
            $maxw = AmpConfig::get('album_art_max_width', 0);
            $minh = AmpConfig::get('album_art_min_height', 0);
            $maxh = AmpConfig::get('album_art_max_height', 0);

            // minimum width is set and current width is too low
            if ($minw > 0 && $width < $minw) {
                debug_event(self::class, sprintf('Image width not in range (min=%s, max=%s, current=%d).', $minw, $maxw, $width), 1);

                return false;
            }

            // max width is set and current width is too high
            if ($maxw > 0 && $width > $maxw) {
                debug_event(self::class, sprintf('Image width not in range (min=%s, max=%s, current=%d).', $minw, $maxw, $width), 1);

                return false;
            }

            if ($minh > 0 && $height < $minh) {
                debug_event(self::class, sprintf('Image height not in range (min=%s, max=%s, current=%d).', $minh, $maxh, $height), 1);

                return false;
            }

            if ($maxh > 0 && $height > $maxh) {
                debug_event(self::class, sprintf('Image height not in range (min=%s, max=%s, current=%d).', $minh, $maxh, $height), 1);

                return false;
            }
        }

        return true;
    }

    /**
     * get_dir_on_disk
     */
    public static function get_dir_on_disk(string $type, int $uid, string $kind = '', bool $autocreate = false): ?string
    {
        $path = AmpConfig::get('local_metadata_dir');
        if (!$path) {
            debug_event(self::class, 'local_metadata_dir setting is required to store art on disk.', 1);

            return null;
        }

        // Correctly detect the slash we need to use here
        $slash_type = (str_contains((string) $path, '/')) ? '/' : '\\';

        $path .= $slash_type . $type;
        if ($autocreate && !Core::is_readable($path)) {
            mkdir($path);
        }

        $path .= $slash_type . $uid;
        if ($autocreate && !Core::is_readable($path)) {
            mkdir($path);
        }

        if (!empty($kind)) {
            $path .= $slash_type . $kind;
            if ($autocreate && !Core::is_readable($path)) {
                mkdir($path);
            }
        }

        return $path . $slash_type;
    }

    /**
     * write_to_dir
     */
    private static function write_to_dir(
        string $source,
        string $sizetext,
        string $type,
        int $uid,
        string $kind,
        ?string $mime
    ): bool {
        $path = self::get_dir_on_disk($type, $uid, $kind, true);
        if (!$path) {
            return false;
        }

        if (!Core::is_readable($path)) {
            debug_event(self::class, 'Local image art directory ' . $path . ' does not exist.', 1);

            return false;
        }

        $path .= "art-" . $sizetext . "." . self::extension($mime);
        if (Core::is_readable($path)) {
            unlink($path);
        }

        $filepath = fopen($path, "wb");
        if ($filepath) {
            fwrite($filepath, $source);
            fclose($filepath);
        }

        return true;
    }

    private function get_blankalbum(?string $size = null): string
    {
        switch ($size) {
            case '128x128':
                $path         = __DIR__ . '/../../../public/client/images/blankalbum_128x128.png';
                $this->width  = 128;
                $this->height = 128;
                break;
            case '256x256':
                $path         = __DIR__ . '/../../../public/client/images/blankalbum_256x256.png';
                $this->width  = 256;
                $this->height = 256;
                break;
            case '384x384':
                $path         = __DIR__ . '/../../../public/client/images/blankalbum_384x384.png';
                $this->width  = 384;
                $this->height = 384;
                break;
            case '768x768':
                $path         = __DIR__ . '/../../../public/client/images/blankalbum_768x768.png';
                $this->width  = 768;
                $this->height = 768;
                break;
            default:
                $path         = __DIR__ . '/../../../public/client/images/blankalbum.png';
                $this->width  = 1400;
                $this->height = 1400;
        }

        if (!Core::is_readable($path)) {
            debug_event(self::class, 'read_from_images ' . $path . ' cannot be read.', 1);

            return '';
        }

        $image    = '';
        $filepath = fopen($path, "rb");
        if ($filepath) {
            do {
                $image .= fread($filepath, 2048);
            } while (!feof($filepath));

            fclose($filepath);
        }

        return $image;
    }

    /**
     * read_from_dir
     */
    private static function read_from_dir(string $sizetext, string $type, int $uid, string $kind, string $mime): ?string
    {
        $path = self::get_dir_on_disk($type, $uid, $kind);
        if (!$path) {
            return null;
        }

        $path .= "art-" . $sizetext . '.' . self::extension($mime);
        if (!Core::is_readable($path)) {
            debug_event(self::class, 'Local image art ' . $path . ' cannot be read.', 1);

            return null;
        }

        $image    = '';
        $filepath = fopen($path, "rb");
        if ($filepath) {
            do {
                $image .= fread($filepath, 2048);
            } while (!feof($filepath));

            fclose($filepath);
        }

        return $image;
    }

    /**
     * delete_from_dir
     */
    public static function delete_from_dir(string $type, int $uid, ?string $kind = '', ?string $size = '', ?string $mime = ''): void
    {
        if ($type && $uid) {
            $path = self::get_dir_on_disk($type, $uid, (string)$kind);
            if ($path !== null) {
                self::delete_rec_dir(rtrim($path, '/'), $size, $mime);
            }
        }
    }

    /**
     * delete_rec_dir
     */
    private static function delete_rec_dir(string $path, ?string $size = '', ?string $mime = ''): void
    {
        $has_size = $size && $mime && preg_match('/^[0-9]+x[0-9]+$/', $size);

        if ($has_size) {
            debug_event(self::class, 'Deleting ' . $path . ' by file size... ' . $size, 5);
        } else {
            debug_event(self::class, 'Deleting ' . $path . ' directory...', 5);
        }

        if (Core::is_readable($path)) {
            $scandir = scandir($path) ?: [];
            foreach ($scandir as $file) {
                if ('.' === $file || '..' === $file) {
                    continue;
                } elseif (is_dir($path . '/' . $file)) {
                    self::delete_rec_dir(rtrim($path, '/') . '/' . $file, $size);
                } elseif ($has_size) {
                    // If we are deleting a specific size, check the file name
                    if (!str_ends_with($file, '-' . $size . '.' . self::extension($mime))) {
                        continue;
                    }

                    debug_event(self::class, 'Found ' . $file, 5);
                }

                unlink($path . '/' . $file);
            }

            // Don't delete the whole directory if you're keeping the original image
            if (!$has_size) {
                rmdir($path);
            }
        }
    }

    /**
     * reset
     * This resets the art in the database
     */
    public function reset(): void
    {
        $this->getArtCleanup()->deleteForArt($this);

        parent::clear_cache();
    }

    /**
     * save_thumb
     * This saves the thumbnail that we're passed
     * @param string $source
     * @param string $mime
     * @param array{width: int, height: int} $size
     * @return bool
     */
    public function save_thumb(string $source, string $mime, array $size): bool
    {
        $test_size = $this->test_size($source);
        if ($test_size !== true) {
            debug_event(self::class, 'Not inserting thumbnail, failed check: ' . $test_size, 1);

            return false;
        }
        // Quick sanity check
        if (!$this->test_image($source)) {
            debug_event(self::class, 'Not inserting thumbnail, invalid data passed', 1);

            return false;
        }

        $width    = $size['width'];
        $height   = $size['height'];
        $sizetext = $width . 'x' . $height;

        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `kind` = ?";
        Dba::write($sql, [$this->object_id, $this->object_type, $sizetext, $this->kind]);

        if (AmpConfig::get('album_art_store_disk') && self::write_to_dir($source, $sizetext, $this->object_type, $this->object_id, $this->kind, $mime)) {
            $source = null;
        }

        $sql = "REPLACE INTO `image` (`image`, `width`, `height`, `mime`, `size`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [
            $source,
            $width,
            $height,
            $mime,
            $sizetext,
            $this->object_type,
            $this->object_id,
            $this->kind,
        ]);

        $art_id = Dba::insert_id() ?: null;
        if (is_string($art_id)) {
            $this->id = (int)$art_id;
        }

        return true;
    }

    /**
     * get_thumb
     * Returns the specified resized image.  If the requested size doesn't
     * already exist, create and cache it.
     * @param array{width: int, height: int} $size
     * @return array{thumb?: string, thumb_mime?: string}
     */
    public function get_thumb(array $size): array
    {
        $sizetext   = $size['width'] . 'x' . $size['height'];
        $sql        = "SELECT `image`, `mime` FROM `image` WHERE `size` = ? AND `object_type` = ? AND `object_id` = ? AND `kind` = ?";
        $db_results = Dba::read($sql, [$sizetext, $this->object_type, $this->object_id, $this->kind]);

        $results = Dba::fetch_assoc($db_results);
        if ($results !== []) {
            if (AmpConfig::get('album_art_store_disk')) {
                $image = self::read_from_dir($sizetext, $this->object_type, $this->object_id, $this->kind, $results['mime']);
            } else {
                $image = $results['image'];
            }

            if ($image != null) {
                return ['thumb' => (AmpConfig::get('album_art_store_disk'))
                    ? self::read_from_dir($sizetext, $this->object_type, $this->object_id, $this->kind, $results['mime'])
                    : $results['image'], 'thumb_mime' => $results['mime']];
            } else {
                debug_event(self::class, 'Thumb entry found in database but associated data cannot be found.', 3);
            }
        }

        // If we didn't get a result try again
        $results = [];
        if (!$this->raw && $this->thumb) {
            $results = $this->generate_thumb($this->thumb, $size, $this->raw_mime);
        }

        if ($this->raw) {
            $results = $this->generate_thumb($this->raw, $size, $this->raw_mime);
        }

        if ($results !== []) {
            $this->save_thumb($results['thumb'], $results['thumb_mime'], $size);
        }

        return $results;
    }

    /**
     * generate_thumb
     * Automatically resizes the image for thumbnail viewing.
     * Only works on gif/jpg/png/bmp. Fails if PHP-GD isn't available
     * or lacks support for the requested image type.
     * @param string $image
     * @param array{width: int, height: int} $size
     * @param string $mime
     * @return array{thumb?: string, thumb_mime?: string}
     */
    public function generate_thumb(string $image, array $size, string $mime): array
    {
        $test_size = $this->test_size($image);
        if ($test_size !== true) {
            debug_event(self::class, 'Not inserting thumbnail, failed check: ' . $test_size, 1);

            return [];
        }
        if (!$this->test_image($image)) {
            debug_event(self::class, 'Not trying to generate thumbnail, invalid data passed', 1);

            return [];
        }

        if (!self::_hasGD()) {
            debug_event(self::class, 'PHP-GD Not found - unable to resize art', 1);

            return [];
        }

        $source = imagecreatefromstring($image);
        if (!$source) {
            debug_event(self::class, 'Failed to create Image from string - Source Image is damaged / malformed', 2);

            return [];
        }

        $src_width  = (int)imagesx($source);
        $src_height = (int)imagesy($source);
        $dst_width  = (int)$size['width'];
        $dst_height = (int)$size['height'];

        // Calculate aspect ratios
        $src_ratio  = $src_width / $src_height;
        $dst_ratio  = $dst_width / $dst_height;
        $difference = $src_ratio - $dst_ratio;
        if ($difference > 0.3 || $difference < -0.3) {
            if ($difference > 0.3) {
                // Source is wider than destination, crop width
                $new_width  = (int)($src_height * $dst_ratio);
                $new_height = $src_height;
                $src_x      = (int)(($src_width - $new_width) / 2);
                $src_y      = 0;
            } else {
                // Source is taller than destination, crop height, with upward bias
                $new_width     = $src_width;
                $new_height    = (int)($src_width / $dst_ratio);
                $src_x         = 0;
                $center_offset = ($src_height - $new_height) / 2;
                $src_y         = (int)($center_offset * 0.8);
            }
        } else {
            $new_width     = $src_width;
            $new_height    = $src_height;
            $src_x         = 0;
            $center_offset = ($src_height - $new_height) / 2;
            $src_y         = (int)($center_offset * 0.8);
        }

        $thumbnail = imagecreatetruecolor($dst_width, $dst_height);

        if (!imagecopyresampled($thumbnail, $source, 0, 0, $src_x, $src_y, $dst_width, $dst_height, $new_width, $new_height)) {
            debug_event(self::class, 'Unable to create resized image', 1);
            imagedestroy($source);
            imagedestroy($thumbnail);

            return [];
        }

        imagedestroy($source);

        $data = explode('/', (string) $mime);
        $type = ((string)($data[1] ?? '') !== '') ? strtolower($data[1]) : 'jpg';

        // Start output buffer
        ob_start();

        // Generate the image to our OB
        switch ($type) {
            case 'jpg':
            case 'jpeg':
            case 'jpg?v=2':
            case '(null)':
                imagejpeg($thumbnail, null, 75);
                $mime_type = image_type_to_mime_type(IMAGETYPE_JPEG);
                break;
            case 'gif':
                imagegif($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_GIF);
                break;
            case 'bmp':
            case 'png':
                // Turn bmps into pngs
                imagepng($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_PNG);
                break;
            case 'webp':
                imagewebp($thumbnail);
                $mime_type = image_type_to_mime_type(IMAGETYPE_WEBP);
                break;
            default:
                $mime_type = null;
        }

        if ($mime_type === null) {
            debug_event(self::class, 'Error: No mime type found using: ' . $mime, 2);

            return [];
        }

        $data = (string) ob_get_contents();
        ob_end_clean();

        imagedestroy($thumbnail);

        if ($data === '') {
            debug_event(self::class, 'Unknown Error resizing art', 1);

            return [];
        }

        return [
            'thumb' => $data,
            'thumb_mime' => $mime_type,
        ];
    }

    /**
     * get_from_source
     * This gets an image for the album art from a source as
     * defined in the passed array. Because we don't know where
     * it's coming from we are a passed an array that can look like
     * ['url']      = URL *** OPTIONAL ***
     * ['file']     = FILENAME *** OPTIONAL ***
     * ['raw']      = Actual Image data, already captured
     * @param array{
     *     url?: string,
     *     file?: string,
     *     raw?: string,
     *     title?: string,
     *     db?: int,
     *     song?: string,
     * } $data
     * @param string $type
     * @return string
     */
    public static function get_from_source(array $data, string $type): string
    {
        if (empty($data)) {
            return '';
        }

        // Already have the data, this often comes from id3tags
        if (isset($data['raw'])) {
            return $data['raw'];
        }

        // If it came from the database
        if (isset($data['db'])) {
            $sql        = "SELECT * FROM `image` WHERE `id` = ?;";
            $db_results = Dba::read($sql, [$data['db']]);
            if ($row = Dba::fetch_assoc($db_results)) {
                if (AmpConfig::get('album_art_store_disk')) {
                    return (string)self::read_from_dir('original', $type, $row['object_id'], 'default', $row['mime']);
                } else {
                    return $row['image'];
                }
            }
        } // came from the db

        // Check to see if it's a URL
        if (array_key_exists('url', $data) && filter_var($data['url'], FILTER_VALIDATE_URL)) {
            debug_event(self::class, 'CHECKING URL ' . $data['url'], 2);
            $options = [];
            try {
                $options['timeout'] = 10;
                Autoload::register();
                $request = Requests::get($data['url'], [], Core::requests_options($options));
                $raw     = $request->body;
            } catch (Exception $error) {
                debug_event(self::class, 'Error getting art: ' . $error->getMessage(), 2);
                $raw = '';
            }

            return $raw;
        }

        // Check to see if it's a FILE
        if (isset($data['file'])) {
            $handle = fopen($data['file'], 'rb');
            $size   = Core::get_filesize($data['file']);
            if (
                $handle &&
                $size > 0
            ) {
                $image_data = (string)fread($handle, $size);
                fclose($handle);

                return $image_data;
            }
        }

        // Check to see if it is embedded in id3 of a song
        if (isset($data['song'])) {
            $images = MetaTagCollectorModule::gatherFileArt($data['song']);
            foreach ($images as $image) {
                if (isset($data['title']) && $data['title'] == $image['title']) {
                    return $image['raw'];
                }
            }
        } // if data song

        return '';
    }

    /**
     * url
     * This returns the constructed URL for the art in question
     */
    public static function url(int $uid, string $type, ?string $sid = null, ?int $thumb = null): ?string
    {
        if (!self::is_valid_type($type)) {
            return null;
        }

        if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
            $sid = ($sid)
                ? scrub_out($sid)
                : scrub_out(session_id() ?: 'none');
            if ($sid == null) {
                $sid = Session::create(['type' => 'api']);
            }
        } else {
            $sid = 'none';
        }

        $has_gd = self::_hasGD();
        $mime   = null;
        $art_id = null;

        $size = 'original';
        if ($has_gd && $thumb !== null) {
            $size_array = self::get_thumb_size($thumb);
            $size       = $size_array['width'] . 'x' . $size_array['height'];
        }

        $key = $type . $uid . $size;
        if (parent::is_cached('art', $key)) {
            $row    = parent::get_from_cache('art', $key);
            $mime   = $row['mime'];
            $art_id = $row['id'] ?? null;
        }

        if (empty($mime)) {
            $sql        = "SELECT `id`, `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ?;";
            $db_results = Dba::read($sql, [$type, $uid, $size]);

            if ($row = Dba::fetch_assoc($db_results)) {
                parent::add_to_cache('art', $key, $row);
                $mime   = $row['mime'];
                $art_id = $row['id'];
            } else {
                $sql        = "SELECT `id`, `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ?;";
                $db_results = Dba::read($sql, [$type, $uid, 'original']);

                if ($row = Dba::fetch_assoc($db_results)) {
                    parent::add_to_cache('art', $key, $row);
                    $mime = $row['mime'];
                }
            }
        }

        $extension = self::extension($mime);
        if (
            $extension === '' ||
            $extension === '0'
        ) {
            $extension = 'jpg';
        }

        if (
            $type !== 'user' &&
            AmpConfig::get('stream_beautiful_url') &&
            $size !== 'original'
        ) {
            // e.g. https://demo.ampache.dev/play/art/{sessionid}/artist/1240/size400x400.png
            $url = AmpConfig::get_web_path('/client') . '/play/art/' . $sid . '/' . scrub_out($type) . '/' . $uid . '/size' . $size . '.' . $extension;
        } else {
            $actionStr = ($type === 'user')
                    ? 'action=show_user_avatar&'
                    : '';
            $url = AmpConfig::get_web_path('/client') . '/image.php?' . $actionStr . 'object_id=' . $uid . '&object_type=' . scrub_out($type);
            if ($size !== 'original') {
                $url .= '&size=' . $size;
            }

            if ($art_id !== null) {
                $url .= '&id=' . $art_id;
            }

            $url .= '&name=' . 'art.' . $extension;
        }

        return $url;
    }

    /**
     * Duplicate an object associate images to a new object
     */
    public static function duplicate(string $object_type, int $old_object_id, int $new_object_id, ?string $new_object_type = null): void
    {
        $write_type = ($new_object_type !== null && self::is_valid_type($new_object_type))
            ? $new_object_type
            : $object_type;

        if (
            !$new_object_id ||
            self::has_db($new_object_id, $write_type) ||
            $old_object_id == $new_object_id
        ) {
            return;
        }

        if (AmpConfig::get('album_art_store_disk')) {
            $sql        = "SELECT `size`, `kind`, `mime` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            $db_results = Dba::read($sql, [$object_type, $old_object_id]);
            while ($row = Dba::fetch_assoc($db_results)) {
                $image = self::read_from_dir($row['size'], $object_type, $old_object_id, $row['kind'], $row['mime']);
                if ($image !== null) {
                    self::write_to_dir($image, $row['size'], $write_type, $new_object_id, $row['kind'], $row['mime']);
                }
            }
        }

        $sql = "INSERT IGNORE INTO `image` (`image`, `width`, `height`, `mime`, `size`, `object_type`, `object_id`, `kind`) SELECT `image`, `width`, `height`, `mime`, `size`, ? AS `object_type`, ? AS `object_id`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?";

        if (Dba::write($sql, [$write_type, $new_object_id, $object_type, $old_object_id])) {
            debug_event(self::class, 'duplicate... type:' . $object_type . ' old_id:' . $old_object_id . ' new_type:' . $write_type . ' new_id:' . $new_object_id, 5);
        }
    }

    /**
     * Gather metadata from plugin.
     * @param AmpacheDiscogs|AmpacheMusicBrainz|AmpacheTheaudiodb $plugin
     * @param string $type
     * @param array<string, mixed> $options
     * @return list<array{
     *     url: string,
     *     mime: string,
     *     title: string
     * }>
     */
    public static function gather_metadata_plugin(
        AmpacheMusicBrainz|AmpacheTheaudiodb|AmpacheDiscogs $plugin,
        string $type,
        array $options
    ): array {
        $gtypes     = [];
        $media_info = [];
        switch ($type) {
            case 'song':
                $media_info['mb_trackid'] = $options['mb_trackid'];
                $media_info['title']      = $options['title'];
                $media_info['artist']     = $options['artist'];
                $media_info['album']      = $options['album'];
                $gtypes[]                 = 'song';
                break;
            case 'album':
                $media_info['mb_albumid']       = $options['mb_albumid'];
                $media_info['mb_albumid_group'] = $options['mb_albumid_group'];
                $media_info['artist']           = $options['artist'];
                $media_info['title']            = $options['album'];
                $gtypes[]                       = 'music';
                $gtypes[]                       = 'album';
                break;
            case 'artist':
                $media_info['mb_artistid'] = $options['mb_artistid'];
                $media_info['title']       = $options['artist'];
                $gtypes[]                  = 'music';
                $gtypes[]                  = 'artist';
                break;
            case 'label':
                $media_info['mb_artistid'] = $options['mb_labelid'];
                $media_info['title']       = $options['label'];
                $gtypes[]                  = 'music';
                $gtypes[]                  = 'label';
                break;
        }

        $meta   = $plugin->get_metadata($gtypes, $media_info);
        $images = [];

        if (array_key_exists('art', $meta)) {
            $url      = $meta['art'];
            $ures     = pathinfo((string) $url);
            $images[] = [
                'url' => $url,
                'mime' => 'image/' . ($ures['extension'] ?? 'jpg'),
                'title' => $plugin->name
            ];
        }

        return $images;
    }

    /**
     * Get thumb size from thumb type.
     * @return array{width: int, height: int}
     * @deprecated use size parameter for art display
     */
    public static function get_thumb_size(int $thumb): array
    {
        $size = [];

        switch ($thumb) {
            case 1:
                // This is used by the now_playing / browse stuff
                $size['height'] = 100;
                $size['width']  = 100;
                break;
            case 2:
                // live stream, artist pages
                $size['height'] = 128;
                $size['width']  = 128;
                break;
            case 22:
                $size['height'] = 256;
                $size['width']  = 256;
                break;
            case 32:
                // Single Album & Podcast pages
                $size['height'] = 384;
                $size['width']  = 384;
                break;
            case 3:
                // This is used by the embedded web player
                $size['height'] = 80;
                $size['width']  = 80;
                break;
            case 5:
                // Web Player size
                $size['height'] = 32;
                $size['width']  = 32;
                break;
            case 6:
                // Video browsing size
                $size['height'] = 150;
                $size['width']  = 100;
                break;
            case 34:
                // small 34x34
                $size['height'] = 34;
                $size['width']  = 34;
                break;
            case 64:
                // medium 64x64
                $size['height'] = 64;
                $size['width']  = 64;
                break;
            case 174:
                // large 174x174
                $size['height'] = 174;
                $size['width']  = 174;
                break;
            case 300:
                // extralarge, mega 300x300
            case 7:
                // Video page size
                $size['height'] = 300;
                $size['width']  = 200;
                break;
            case 8:
                // Video preview size
                $size['height'] = 200;
                $size['width']  = 470;
                break;
            case 9:
                // Video preview size
                $size['height'] = 84;
                $size['width']  = 150; // cel_cover max-width is 150px
                break;
            case 10:
                // Search preview size
                $size['height'] = 24;
                $size['width']  = 24;
                break;
            case 4:
                // Popup Web Player size
            case 11:
                // Large view browse size
            case 12:
                // Search preview size
                $size['height'] = 150;
                $size['width']  = 150;
                break;
            default:
                $size['height'] = 200;
                $size['width']  = 200;
                break;
        }

        // For @2x output
        $size['height'] *= 2;
        $size['width'] *= 2;

        return $size;
    }

    /**
     * Display item art.
     * This function requires you to call the explicit size of the thumbnail
     * This removes the ambiguity of Art::display() by requiring a thumbnail size array
     * @param array{width: int, height: int} $size
     */
    public static function display(
        string $object_type,
        int $object_id,
        string $name,
        array $size,
        ?string $link = null,
        bool $show_default = true,
        bool $thumb_link = true,
        string $kind = 'default'
    ): bool {
        if (!self::is_valid_type($object_type)) {
            return false;
        }

        if ($object_type === 'video' && $kind !== 'default') {
            Video::generate_preview($object_id);
        }

        $art    = new Art($object_id, $object_type, $kind);
        $has_db = $art->has_db_info();
        // Don't show any image if not available
        if (!$show_default && !$has_db) {
            return false;
        }

        // Expand wide art slightly if it's larger than the desired thumbnail size
        if (!$thumb_link && $art->width && $art->height) {
            $src_ratio  = $art->width / $art->height;
            $dst_ratio  = $size['width'] / $size['height'];
            $difference = $src_ratio - $dst_ratio;
            if ($difference > 0.3) {
                // keep original height and widen a bit
                $size['width'] = (int)($size['height'] * (min($src_ratio, 1.5)));
            }
            if ($difference < -0.1) {
                // extend the height a little bit and thin it out
                $size['height'] = (int)($size['height'] * (min(($art->height / $art->width), 1.1)));
                $size['width']  = (int)($size['height'] * (min($src_ratio, 0.8)));
            }
        }

        // double the image output size for display scaling
        $out_size = (AmpConfig::get('upscale_images', true))
            ? ($size['width'] * 2) . 'x' . ($size['height'] * 2)
            : $size['width'] . 'x' . $size['height'];

        $web_path = AmpConfig::get_web_path('/client');

        $prettyPhoto = ($link === null);
        if ($link === null) {
            $link = $web_path . "/image.php?object_id=" . $object_id . "&object_type=" . $object_type;
            if ($thumb_link) {
                $link .= "&size=" . $out_size;
            }
            if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
                $link .= "&auth=" . session_id();
            }

            if ($kind != 'default') {
                $link .= '&kind=' . $kind;
            }
            if ($has_db) {
                $link .= '&id=' . $art->id;
            }
        }

        echo "<div class=\"item_art\">";
        echo "<a href=\"" . $link . "\" title=\"" . $name . "\"";
        if ($prettyPhoto) {
            echo " rel=\"prettyPhoto\"";
        }

        echo ">";
        $imgurl = $web_path . "/image.php?object_id=" . $object_id . "&object_type=" . $object_type . "&size=" . $out_size;
        if ($kind != 'default') {
            $imgurl .= '&kind=' . $kind;
        }

        // This to keep browser cache feature but force a refresh in case image just changed
        if ($has_db) {
            if ($art->has_db_info($out_size)) {
                $imgurl .= '&id=' . $art->id;
            }
        }

        echo "<img src=\"" . $imgurl . "\" alt=\"" . $name . "\" height=\"" . $size['height'] . "\" width=\"" . $size['width'] . "\" />";

        $item_art_play = ($size['height'] == 150)
            ? "<div class=\"item_art_play_150\">"
            : "<div class=\"item_art_play\">";
        // don't put the play icon on really large images.
        if ($size['width'] == 150 && $size['height'] == 150) {
            echo $item_art_play;
            echo Ajax::text(
                '?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id . '\' + getPagePlaySettings() + \'',
                '<span class="item_art_play_icon" title="' . T_('Play') . '" />',
                'directplay_art_' . $object_type . '_' . $object_id
            );
            echo "</div>";
        }

        if ($prettyPhoto) {
            $user      = Core::get_global('user');
            $className = ObjectTypeToClassNameMapper::map($object_type);
            /** @var class-string<library_item> $className */
            $libitem = new $className($object_id);
            echo "<div class=\"item_art_actions\">";
            if (
                $user instanceof User &&
                ($user->has_access(AccessLevelEnum::CONTENT_MANAGER) || $user->has_access(AccessLevelEnum::USER) && $user->id == $libitem->get_user_owner())
            ) {
                $ajax_str = ((AmpConfig::get('ajax_load')) ? '#' : '');
                echo "<a href=\"javascript:NavigateTo('" . $web_path . "/" . $ajax_str . "arts.php?action=show_art_dlg&object_type=" . $object_type . "&object_id=" . $object_id . "&burl=' + getCurrentPage());\">";
                echo Ui::get_material_symbol('edit', T_('Edit/Find Art'));
                echo "</a>";
                if ($has_db) {
                    echo "<a href=\"javascript:NavigateTo('" . $web_path . "/" . $ajax_str . "arts.php?action=clear_art&object_type=" . $object_type . "&object_id=" . $object_id . '&kind=' . $kind . "&burl=' + getCurrentPage());\" onclick=\"return confirm('" . T_('Do you really want to reset art?') . "');\">";
                    echo Ui::get_material_symbol('close', T_('Reset Art'));
                    echo "</a>";
                }
            }

            echo "</div>";
        }

        echo "</a>\n";
        echo "</div>";

        return true;
    }

    /**
     * show the art file to the browser
     * return 404 on error or missing files
     */
    public function show(string $size, bool $fallback): bool
    {
        if ($this->has_db_info($size, $fallback)) {
            header('Access-Control-Allow-Origin: *');
            if (
                $size &&
                preg_match('/^[0-9]+x[0-9]+$/', $size)
            ) {
                if ($this->thumb && $this->thumb_mime) {
                    // found the thumb by looking up the size
                    $this->raw_mime = $this->thumb_mime;
                    $this->raw      = $this->thumb;
                } elseif (self::_hasGD()) {
                    // resize the image if requested
                    $dimensions     = explode('x', $size);
                    $size           = [];
                    $size['width']  = (int)$dimensions[0];
                    $size['height'] = (int)$dimensions[1];
                    if ($size['width'] === 0 || $size['height'] === 0) {
                        // art not found
                        http_response_code(404);

                        return false;
                    }

                    $thumb = $this->get_thumb($size);
                    if (!empty($thumb)) {
                        header('Content-type: ' . $thumb['thumb_mime']);
                        header('Content-Length: ' . strlen((string)$thumb['thumb']));
                        echo $thumb['thumb'];

                        return true;
                    }

                    // art not found
                    http_response_code(404);

                    return false;
                }
            }

            header('Content-type: ' . $this->raw_mime);
            header('Content-Length: ' . strlen((string) $this->raw));
            echo $this->raw;

            return true;
        }
        // art not found
        http_response_code(404);

        return false;
    }

    /**
     * @deprecated Inject dependency
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private function getArtCleanup(): ArtCleanupInterface
    {
        global $dic;

        return $dic->get(ArtCleanupInterface::class);
    }
}
