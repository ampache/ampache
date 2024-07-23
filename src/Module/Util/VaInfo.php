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

namespace Ampache\Module\Util;

use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;
use Exception;
use getID3;
use getid3_writetags;

/**
 * This class handles the retrieval of media tags
 */
final class VaInfo implements VaInfoInterface
{
    private const DEFAULT_INFO = [
        'albumartist' => null,
        'album' => null,
        'artist' => null,
        'artists' => null,
        'art' => null,
        'audio_codec' => null,
        'barcode' => null,
        'bitrate' => null,
        'catalog_number' => null,
        'channels' => null,
        'comment' => null,
        'composer' => null,
        'description' => null,
        'disk' => null,
        'disksubtitle' => null,
        'display_x' => null,
        'display_y' => null,
        'encoding' => null,
        'file' => null,
        'frame_rate' => null,
        'genre' => null,
        'isrc' => null,
        'language' => null,
        'lyrics' => null,
        'mb_albumartistid' => null,
        'mb_albumartistid_array' => null,
        'mb_albumid_group' => null,
        'mb_albumid' => null,
        'mb_artistid' => null,
        'mb_artistid_array' => null,
        'mb_trackid' => null,
        'mime' => null,
        'mode' => null,
        'original_name' => null,
        'original_year' => null,
        'publisher' => null,
        'r128_album_gain' => null,
        'r128_track_gain' => null,
        'rate' => null,
        'rating' => null,
        'release_date' => null,
        'release_status' => null,
        'release_type' => null,
        'replaygain_album_gain' => null,
        'replaygain_album_peak' => null,
        'replaygain_track_gain' => null,
        'replaygain_track_peak' => null,
        'resolution_x' => null,
        'resolution_y' => null,
        'size' => null,
        'version' => null,
        'summary' => null,
        'time' => null,
        'title' => null,
        'totaldisks' => null,
        'totaltracks' => null,
        'track' => null,
        'tvshow_art' => null,
        'tvshow_episode' => null,
        'tvshow' => null,
        'tvshow_season_art' => null,
        'tvshow_season' => null,
        'tvshow_summary' => null,
        'tvshow_year' => null,
        'video_bitrate' => null,
        'video_codec' => null,
        'year' => null
    ];
    private const MBID_REGEX = '/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/';

    public $encoding      = '';
    public $encodingId3v1 = '';
    public $encodingId3v2 = '';
    public $filename      = '';
    public $type          = '';
    public $tags          = [];
    public $gatherTypes   = [];
    public $islocal;

    protected $_raw           = [];
    protected $_getID3        = null;
    protected $_forcedSize    = 0;
    protected $_file_encoding = '';
    protected $_file_pattern  = '';
    protected $_dir_pattern   = '';

    private $_broken = false;
    private $_pathinfo;

    private UserRepositoryInterface $userRepository;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * This function just sets up the class, it doesn't pull the information.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param UserRepositoryInterface $userRepository
     * @param ConfigContainerInterface $configContainer
     * @param LoggerInterface $logger
     * @param string $file
     * @param array $gatherTypes
     * @param string $encoding
     * @param string $encodingId3v1
     * //TODO: where did this go? param string $encodingId3v2
     * @param string $dirPattern
     * @param string $filePattern
     * @param bool $islocal
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        $file,
        $gatherTypes = [],
        $encoding = null,
        $encodingId3v1 = null,
        $dirPattern = '',
        $filePattern = '',
        $islocal = true
    ) {
        $this->islocal     = $islocal;
        $this->filename    = $file;
        $this->gatherTypes = $gatherTypes;
        $this->encoding    = $encoding ?? $configContainer->get(ConfigurationKeyEnum::SITE_CHARSET) ?? 'UTF-8';

        /* These are needed for the filename mojo */
        $this->_file_pattern = $filePattern;
        $this->_dir_pattern  = $dirPattern;

        // FIXME: This looks ugly and probably wrong
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $this->_pathinfo = str_replace('%3A', ':', urlencode($this->filename));
            $this->_pathinfo = pathinfo(str_replace('%5C', '\\', $this->_pathinfo));
        } else {
            $this->_pathinfo = pathinfo(str_replace('%2F', '/', urlencode($this->filename)));
        }
        $this->_pathinfo['extension'] = strtolower($this->_pathinfo['extension']);

        // convert all tag sources always to lowercase or results doesn't contains plugin results
        $enabled_sources = array_map('strtolower', $this->get_metadata_order());

        if (in_array('getid3', $enabled_sources) && $this->islocal) {
            // Initialize getID3 engine
            $this->_getID3 = new getID3();

            $this->_getID3->option_md5_data        = false;
            $this->_getID3->option_md5_data_source = false;
            $this->_getID3->option_tags_html       = false;
            $this->_getID3->option_extra_info      = true;
            $this->_getID3->option_tag_lyrics3     = true;
            $this->_getID3->option_tags_process    = true;
            $this->_getID3->option_tag_apetag      = true;

            // get id3tag encoding (try to work around off-spec id3v1 tags)
            try {
                $this->_raw = $this->_getID3->analyze(Core::conv_lc_file($file));
            } catch (Exception $error) {
                $logger->error(
                    'getID3 Broken file detected: $file: ' . $error->getMessage(),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                $this->_broken = true;

                return false;
            }
            //$logger->error('RAW TAGS: ' . print_r($this->_raw, true), [LegacyLogger::CONTEXT_TYPE => self::class]);

            if ($configContainer->get(ConfigurationKeyEnum::MB_DETECT_ORDER)) {
                $mb_order = $configContainer->get(ConfigurationKeyEnum::MB_DETECT_ORDER);
            } elseif (function_exists('mb_detect_order')) {
                $mb_order = (mb_detect_order()) ? implode(", ", mb_detect_order()) : 'auto';
            } else {
                $mb_order = 'auto';
            }

            $test_tags = ['artist', 'album', 'genre', 'title'];

            if ($encodingId3v1 !== null) {
                $this->encodingId3v1 = $encodingId3v1;
            } else {
                $tags = [];
                foreach ($test_tags as $tag) {
                    if (array_key_exists('id3v1', $this->_raw) && array_key_exists($tag, $this->_raw['id3v1']) && $value = $this->_raw['id3v1'][$tag]) {
                        $tags[$tag] = $value;
                    }
                }

                $this->encodingId3v1           = self::_detect_encoding($tags, $mb_order);
                $this->_getID3->encoding_id3v1 = $this->encodingId3v1;
            }

            if ($configContainer->get(ConfigurationKeyEnum::GETID3_DETECT_ID3V2_ENCODING)) {
                // The user has told us to be moronic, so let's do that thing
                $tags = [];
                foreach ($test_tags as $tag) {
                    if (array_key_exists('id3v2', $this->_raw) && array_key_exists($tag, $this->_raw['id3v2']) && $value = $this->_raw['id3v2']['comments'][$tag]) {
                        $tags[$tag] = $value;
                    }
                }

                $this->encodingId3v2     = self::_detect_encoding($tags, $mb_order);
                $this->_getID3->encoding = $this->encodingId3v2;
            }
        }

        $this->userRepository  = $userRepository;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;

        return true;
    }

    /**
     * forceSize
     */
    public function forceSize(int $size): void
    {
        $this->_forcedSize = $size;
    }

    /**
     * _detect_encoding
     *
     * Takes an array of tags and attempts to automatically detect their
     * encoding.
     * @param $tags
     * @param $mb_order
     */
    private static function _detect_encoding($tags, $mb_order): string
    {
        if (!function_exists('mb_detect_encoding')) {
            return 'ISO-8859-1';
        }

        $encodings = [];
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if (is_array($tag)) {
                    $tag = implode(" ", $tag);
                }
                $enc = mb_detect_encoding($tag, $mb_order, true);
                if ($enc !== false) {
                    if (!array_key_exists($enc, $encodings)) {
                        $encodings[$enc] = 0;
                    }
                    $encodings[$enc]++;
                }
            }
        } else {
            $enc = mb_detect_encoding($tags, $mb_order, true);
            if ($enc !== false) {
                $encodings[$enc] = 1;
            }
        }

        //!!debug_event(self::class, 'encoding detection: ' . json_encode($encodings), 5);
        $high     = 0;
        $encoding = 'ISO-8859-1';
        foreach ($encodings as $key => $value) {
            if ($value > $high) {
                $encoding = $key;
                $high     = $value;
            }
        }

        if ($encoding != 'ASCII') {
            return (string)$encoding;
        } else {
            return 'ISO-8859-1';
        }
    }

    /**
     * get_info
     *
     * This function runs the various steps to gathering the metadata. Filling $this->tags
     */
    public function gather_tags(): void
    {
        // If this is broken, don't waste time figuring it out a second time, just return their rotting carcass of a media file.
        if ($this->_broken) {
            $this->tags = $this->set_broken();

            return;
        }
        $enabled_sources = (array)$this->get_metadata_order();

        if (in_array('getid3', $enabled_sources) && $this->islocal) {
            try {
                $this->_raw = $this->_getID3->analyze(Core::conv_lc_file($this->filename));
            } catch (Exception $error) {
                $this->logger->error(
                    'getID3 Unable to catalog file: ' . $error->getMessage(),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }

        /* Figure out what type of file we are dealing with */
        $this->type = $this->_get_type() ?? '';

        if (in_array('filename', $enabled_sources)) {
            $this->tags['filename'] = $this->_parse_filename($this->filename);
        }

        if (in_array('getid3', $enabled_sources) && $this->islocal) {
            $this->tags['getid3'] = $this->_get_tags();
        }

        $this->_get_plugin_tags();
    }

    /**
     * check_time
     * check a cached file is close to the expected time
     * @param int $time
     */
    public function check_time($time): bool
    {
        $this->gather_tags();
        foreach ($this->tags as $results) {
            if (isset($results['time'])) {
                return ($time >= $results['time'] - 2);
            }
        }

        return false;
    }

    /**
     * write_id3
     * This function runs the various steps to gathering the metadata
     * @param $tagData
     * @throws Exception
     */
    public function write_id3($tagData): void
    {
        $TaggingFormat = 'UTF-8';
        $tagWriter     = new getid3_writetags();
        $extension     = pathinfo($this->filename, PATHINFO_EXTENSION);
        $extensionMap  = [
            'mp3' => 'id3v2.3',
            'flac' => 'metaflac',
            'oga' => 'vorbiscomment',
            'ogg' => 'vorbiscomment'
        ];
        if (!array_key_exists(strtolower($extension), $extensionMap)) {
            $this->logger->debug(
                sprintf('Writing Tags: Files with %s extensions are currently ignored.', $extension),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return;
        }

        $format                       = $extensionMap[$extension];
        $tagWriter->filename          = $this->filename;
        $tagWriter->tagformats        = [$format];
        $tagWriter->overwrite_tags    = true;
        $tagWriter->remove_other_tags = false;
        $tagWriter->tag_encoding      = $TaggingFormat;
        $tagWriter->tag_data          = $tagData;

        /*
        *  Currently getid3 doesn't remove pictures on *nix, only vorbiscomments.
        *  This hasn't been tested on Windows and there is evidence that
        *  metaflac.exe behaves differently.
        */
        if ($extension !== 'mp3') {
            if (php_uname('s') == 'Linux') {
                /* First check for installation of metaflac and
                *  vorbiscomment system tools.
                */
                exec('which metaflac', $output, $retval);
                exec('which vorbiscomment', $output, $retval1);

                if ($retval !== 0 || $retval1 !== 0) {
                    $this->logger->debug(
                        'Metaflac and vorbiscomments must be installed to write tags to flac and oga files',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );

                    return;
                }
            }

            if (GETID3_OS_ISWINDOWS) {
                $command = 'metaflac.exe --remove --block-type=PICTURE ' . escapeshellarg($this->filename);
            } else {
                $command = 'metaflac --remove --block-type=PICTURE ' . escapeshellarg($this->filename);
            }
            $commandError = `$command`;
        }
        if ($tagWriter->WriteTags()) {
            foreach ($tagWriter->warnings as $message) {
                $this->logger->debug(
                    'Warning Writing Tags: ' . $message,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
        if (!empty($tagWriter->errors)) {
            foreach ($tagWriter->errors as $message) {
                $this->logger->error(
                    'Error Writing Tags: ' . $message,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * prepare_metadata_for_writing
     * Prepares vorbiscomments/id3v2 metadata for writing tag to file
     * @param array $frames
     * @return array
     */
    public function prepare_metadata_for_writing($frames): array
    {
        $ndata = [];
        foreach ($frames as $key => $text) {
            switch ($key) {
                case 'text':
                    foreach ($text as $tkey => $data) {
                        $ndata['text'][] = ['data' => $data, 'description' => $tkey, 'encodingid' => 0];
                    }
                    break;
                default:
                    $ndata[$key][] = $text[0];
                    break;
            }
        }

        return $ndata;
    }

    /**
     * read_id3
     *
     * This function runs the various steps to gathering the metadata
     * @return array
     */
    public function read_id3(): array
    {
        // Get the Raw file information
        try {
            $this->_raw = $this->_getID3->analyze($this->filename);

            return $this->_raw;
        } catch (Exception $error) {
            $this->logger->error(
                'Unable to read file:' . $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        return [];
    }

    /**
     * get_tag_type
     *
     * This takes the result set and the tag_order defined in your config
     * file and tries to figure out which tag type(s) it should use. If your
     * tag_order doesn't match anything then it throws up its hands and uses
     * everything in random order.
     * @param array $results
     * @param string $configKey
     * @return array
     */
    public static function get_tag_type($results, $configKey = 'metadata_order'): array
    {
        $tagorderMap = [
            'metadata_order' => static::getConfigContainer()->get(ConfigurationKeyEnum::METADATA_ORDER),
            'metadata_order_video' => static::getConfigContainer()->get(ConfigurationKeyEnum::METADATA_ORDER_VIDEO),
            'getid3_tag_order' => static::getConfigContainer()->get(ConfigurationKeyEnum::GETID3_TAG_ORDER)
        ];


        $order = [];
        foreach ($tagorderMap[$configKey] ?? [] as $source) {
            //debug_event(self::class, "source: " . $source, true, 5);
            $order[] = strtolower($source);
        }

        // Iterate through the defined key order adding them to an ordered array.
        $returned_keys = [];
        foreach ($order as $value) {
            if (array_key_exists($value, $results)) {
                $returned_keys[] = $value;
            }
        }

        // return a default list of items (if you get here this is probably a bad file)
        if (empty($returned_keys)) {
            debug_event(self::class, "get_tag_type: Couln't find tags, this is probably a bad file", 5);
            $returned_keys = [
                'getid3',
                'filename',
                'general'
            ];
        }

        // Unless they explicitly set it, add bitrate/mode/mime/etc.
        if (is_array($returned_keys)) {
            if (!in_array('general', $returned_keys)) {
                $returned_keys[] = 'general';
            }
        }
        //debug_event(self::class, "get_tag_type: " . $configKey . print_r($returned_keys, true), 5);

        return $returned_keys;
    }

    /**
     * clean_tag_info
     *
     * This function takes the array from vainfo along with the
     * key we've decided on and the filename and returns it in a
     * sanitized format that Ampache can actually use
     * @param array $results
     * @param array $keys
     * @param string $filename
     * @return array
     */
    public static function clean_tag_info($results, $keys, $filename = null): array
    {
        $info         = self::DEFAULT_INFO;
        $info['file'] = $filename;

        foreach ($keys as $key) {
            // Ampache has a set list of columns to look for but we need to check through and fill it based on the collected tags
            $tags = $results[$key] ?? [];

            $info['file']     = (!$info['file'] && array_key_exists('file', $tags)) ? $tags['file'] : $info['file'];
            $info['bitrate']  = (!$info['bitrate'] && array_key_exists('bitrate', $tags)) ? (int) $tags['bitrate'] : $info['bitrate'];
            $info['rate']     = (!$info['rate'] && array_key_exists('rate', $tags)) ? (int) $tags['rate'] : $info['rate'];
            $info['mode']     = (!$info['mode'] && array_key_exists('mode', $tags)) ? $tags['mode'] : $info['mode'];
            $info['mime']     = (!$info['mime'] && array_key_exists('mime', $tags)) ? $tags['mime'] : $info['mime'];
            $info['encoding'] = (!$info['encoding'] && array_key_exists('encoding', $tags)) ? $tags['encoding'] : $info['encoding'];
            $info['rating']   = (!$info['rating'] && array_key_exists('rating', $tags)) ? $tags['rating'] : $info['rating'];
            $info['time']     = (!$info['time'] && array_key_exists('time', $tags)) ? (int) $tags['time'] : $info['time'];
            $info['channels'] = (!$info['channels'] && array_key_exists('channels', $tags)) ? $tags['channels'] : $info['channels'];

            // This because video title are almost always bad...
            $info['original_name'] = (!$info['original_name'] && array_key_exists('original_name', $tags)) ? stripslashes(trim((string)$tags['original_name'])) : $info['original_name'];
            $info['title']         = (!$info['title'] && array_key_exists('title', $tags)) ? stripslashes(trim((string)$tags['title'])) : $info['title'];

            // Not even sure if these can be negative, but better safe than llama.
            $info['year'] = (!$info['year'] && array_key_exists('year', $tags)) ? Catalog::normalize_year((int) $tags['year']) : $info['year'];
            $info['disk'] = (!$info['disk'] && array_key_exists('disk', $tags)) ? abs((int) $tags['disk']) : $info['disk'];

            $info['totaldisks']   = (!$info['totaldisks'] && array_key_exists('totaldisks', $tags)) ? (int) $tags['totaldisks'] : $info['totaldisks'];
            $info['disksubtitle'] = (!$info['disksubtitle'] && array_key_exists('disksubtitle', $tags)) ? trim((string)$tags['disksubtitle']) : $info['disksubtitle'];

            $info['artist']      = (!$info['artist'] && array_key_exists('artist', $tags)) ? trim((string)$tags['artist']) : $info['artist'];
            $info['albumartist'] = (!$info['albumartist'] && array_key_exists('albumartist', $tags)) ? trim((string)$tags['albumartist']) : $info['albumartist'];

            $info['album'] = (!$info['album'] && array_key_exists('album', $tags)) ? trim((string)$tags['album']) : $info['album'];

            $info['composer']  = (!$info['composer'] && array_key_exists('composer', $tags)) ? trim((string)$tags['composer']) : $info['composer'];
            $info['publisher'] = (!$info['publisher'] && array_key_exists('publisher', $tags)) ? trim((string)$tags['publisher']) : $info['publisher'];

            // genre is an array treat it as one
            $info['genre'] = (!$info['genre'] && array_key_exists('genre', $tags) && !empty($tags['genre']))
                ? $tags['genre']
                : $info['genre'];

            $info['mb_trackid']       = (!$info['mb_trackid'] && array_key_exists('mb_trackid', $tags)) ? trim((string)$tags['mb_trackid']) : $info['mb_trackid'];
            $info['isrc']             = (!$info['isrc'] && array_key_exists('isrc', $tags)) ? trim((string)$tags['isrc']) : $info['isrc'];
            $info['mb_albumid']       = (!$info['mb_albumid'] && array_key_exists('mb_albumid', $tags)) ? trim((string)$tags['mb_albumid']) : $info['mb_albumid'];
            $info['mb_albumid_group'] = (!$info['mb_albumid_group'] && array_key_exists('mb_albumid_group', $tags)) ? trim((string)$tags['mb_albumid_group']) : $info['mb_albumid_group'];
            $info['mb_artistid']      = (!$info['mb_artistid'] && array_key_exists('mb_artistid', $tags)) ? trim((string)$tags['mb_artistid']) : $info['mb_artistid'];
            $info['mb_albumartistid'] = (!$info['mb_albumartistid'] && array_key_exists('mb_albumartistid', $tags)) ? trim((string)$tags['mb_albumartistid']) : $info['mb_albumartistid'];
            // groups of artists can be ID'd using their mbid easily
            $info['mb_artistid_array'] = (!$info['mb_artistid_array'] && array_key_exists('mb_artistid_array', $tags) && !empty($tags['mb_artistid_array']))
                ? $tags['mb_artistid_array']
                : $info['mb_artistid_array'];
            $info['mb_albumartistid_array'] = (!$info['mb_albumartistid_array'] && array_key_exists('mb_albumartistid_array', $tags) && !empty($tags['mb_albumartistid_array']))
                ? $tags['mb_albumartistid_array']
                : $info['mb_albumartistid_array'];

            $info['release_type']   = (!$info['release_type'] && array_key_exists('release_type', $tags)) ? trim((string)$tags['release_type']) : $info['release_type'];
            $info['release_status'] = (!$info['release_status'] && array_key_exists('release_status', $tags)) ? trim((string)$tags['release_status']) : $info['release_status'];

            // artists is an array treat it as one
            if (!empty($tags['artists']) && !is_array($tags['artists'])) {
                $tags['artists'] = [$tags['artists']];
            }
            $info['artists'] = (!$info['artists'] && array_key_exists('artists', $tags) && !empty($tags['artists']))
                ? $tags['artists']
                : $info['artists'];

            $info['original_year']  = (!$info['original_year'] && array_key_exists('original_year', $tags)) ? trim((string)$tags['original_year']) : $info['original_year'];
            $info['barcode']        = (!$info['barcode'] && array_key_exists('barcode', $tags)) ? trim((string)$tags['barcode']) : $info['barcode'];
            $info['catalog_number'] = (!$info['catalog_number'] && array_key_exists('catalog_number', $tags)) ? trim((string)$tags['catalog_number']) : $info['catalog_number'];
            $info['version']        = (!$info['version'] && array_key_exists('version', $tags)) ? trim((string)$tags['version']) : $info['version'];

            $info['language'] = (!$info['language'] && array_key_exists('language', $tags)) ? trim((string)$tags['language']) : $info['language'];
            $info['comment']  = (!$info['comment'] && array_key_exists('comment', $tags)) ? trim((string)$tags['comment']) : $info['comment'];
            $info['lyrics']   = (!$info['lyrics'] && array_key_exists('lyrics', $tags)) ? strip_tags(nl2br((string) $tags['lyrics']), "<br>") : $info['lyrics'];

            // extended checks to make sure "0" makes it through, which would otherwise eval to false
            $info['replaygain_track_gain'] = (!$info['replaygain_track_gain'] && array_key_exists('replaygain_track_gain', $tags) && !is_null($tags['replaygain_track_gain'])) ? (float) $tags['replaygain_track_gain'] : $info['replaygain_track_gain'];
            $info['replaygain_track_peak'] = (!$info['replaygain_track_peak'] && array_key_exists('replaygain_track_peak', $tags) && !is_null($tags['replaygain_track_peak'])) ? (float) $tags['replaygain_track_peak'] : $info['replaygain_track_peak'];
            $info['replaygain_album_gain'] = (!$info['replaygain_album_gain'] && array_key_exists('replaygain_album_gain', $tags) && !is_null($tags['replaygain_album_gain'])) ? (float) $tags['replaygain_album_gain'] : $info['replaygain_album_gain'];
            $info['replaygain_album_peak'] = (!$info['replaygain_album_peak'] && array_key_exists('replaygain_album_peak', $tags) && !is_null($tags['replaygain_album_peak'])) ? (float) $tags['replaygain_album_peak'] : $info['replaygain_album_peak'];
            $info['r128_track_gain']       = (!$info['r128_track_gain'] && array_key_exists('r128_track_gain', $tags) && !is_null($tags['r128_track_gain'])) ? (int) $tags['r128_track_gain'] : $info['r128_track_gain'];
            $info['r128_album_gain']       = (!$info['r128_album_gain'] && array_key_exists('r128_album_gain', $tags) && !is_null($tags['r128_album_gain'])) ? (int) $tags['r128_album_gain'] : $info['r128_album_gain'];

            $info['track']         = (!$info['track'] && array_key_exists('track', $tags)) ? (int)$tags['track'] : $info['track'];
            $info['totaltracks']   = (!$info['totaltracks'] && array_key_exists('totaltracks', $tags)) ? (int)$tags['totaltracks'] : $info['totaltracks'];
            $info['resolution_x']  = (!$info['resolution_x'] && array_key_exists('resolution_x', $tags)) ? (int)$tags['resolution_x'] : $info['resolution_x'];
            $info['resolution_y']  = (!$info['resolution_y'] && array_key_exists('resolution_y', $tags)) ? (int)$tags['resolution_y'] : $info['resolution_y'];
            $info['display_x']     = (!$info['display_x'] && array_key_exists('display_x', $tags)) ? (int)$tags['display_x'] : $info['display_x'];
            $info['display_y']     = (!$info['display_y'] && array_key_exists('display_y', $tags)) ? (int)$tags['display_y'] : $info['display_y'];
            $info['frame_rate']    = (!$info['frame_rate'] && array_key_exists('frame_rate', $tags)) ? (float)$tags['frame_rate'] : $info['frame_rate'];
            $info['video_bitrate'] = (!$info['video_bitrate'] && array_key_exists('video_bitrate', $tags)) ? Catalog::check_int((int) $tags['video_bitrate'], 4294967294, 0) : $info['video_bitrate'];
            $info['audio_codec']   = (!$info['audio_codec'] && array_key_exists('audio_codec', $tags)) ? trim((string)$tags['audio_codec']) : $info['audio_codec'];
            $info['video_codec']   = (!$info['video_codec'] && array_key_exists('video_codec', $tags)) ? trim((string)$tags['video_codec']) : $info['video_codec'];
            $info['description']   = (!$info['description'] && array_key_exists('description', $tags)) ? trim((string)$tags['description']) : $info['description'];

            $info['tvshow']         = (!$info['tvshow'] && array_key_exists('tvshow', $tags)) ? trim((string)$tags['tvshow']) : $info['tvshow'];
            $info['tvshow_year']    = (!$info['tvshow_year'] && array_key_exists('tvshow_year', $tags)) ? trim((string)$tags['tvshow_year']) : $info['tvshow_year'];
            $info['tvshow_season']  = (!$info['tvshow_season'] && array_key_exists('tvshow_season', $tags)) ? trim((string)$tags['tvshow_season']) : $info['tvshow_season'];
            $info['tvshow_episode'] = (!$info['tvshow_episode'] && array_key_exists('tvshow_episode', $tags)) ? trim((string)$tags['tvshow_episode']) : $info['tvshow_episode'];
            $info['release_date']   = (!$info['release_date'] && array_key_exists('release_date', $tags)) ? trim((string)$tags['release_date']) : $info['release_date'];
            $info['summary']        = (!$info['summary'] && array_key_exists('summary', $tags)) ? trim((string)$tags['summary']) : $info['summary'];
            $info['tvshow_summary'] = (!$info['tvshow_summary'] && array_key_exists('tvshow_summary', $tags)) ? trim((string)$tags['tvshow_summary']) : $info['tvshow_summary'];

            $info['tvshow_art']        = (!$info['tvshow_art'] && array_key_exists('tvshow_art', $tags)) ? trim((string)$tags['tvshow_art']) : $info['tvshow_art'];
            $info['tvshow_season_art'] = (!$info['tvshow_season_art'] && array_key_exists('tvshow_season_art', $tags)) ? trim((string)$tags['tvshow_season_art']) : $info['tvshow_season_art'];
            $info['art']               = (!$info['art'] && array_key_exists('art', $tags)) ? trim((string)$tags['art']) : $info['art'];

            if (static::getConfigContainer()->get(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA) && is_array($tags)) {
                // Add rest of the tags without typecast to the array
                foreach ($tags as $tag => $value) {
                    if (!array_key_exists($tag, $info) && !is_array($value)) {
                        $info[$tag] = trim((string)$value);
                    }
                }
            }
        }

        // Determine the correct file size, do not get fooled by the size which may be returned by id3v2!
        $size         = $results['general']['size'] ?? Core::get_filesize(Core::conv_lc_file($filename));
        $info['size'] = $info['size'] ?? $size;

        return $info;
    }

    /**
     * get_mbid_array
     * @param string $mbid
     * @return array
     */
    public static function get_mbid_array($mbid): array
    {
        if (preg_match(self::MBID_REGEX, $mbid, $matches)) {
            return $matches;
        }

        return [$mbid];
    }

    /**
     * parse_mbid
     * Get the first valid mbid. (if it's valid)
     * @param string|array $mbid
     */
    public static function parse_mbid($mbid): ?string
    {
        if (empty($mbid)) {
            return null;
        }
        if (is_array($mbid)) {
            $mbid = implode(";", $mbid);
        }
        if (preg_match(self::MBID_REGEX, $mbid, $matches)) {
            return (string)$matches[0];
        }

        return null;
    }

    /**
     * parse_mbid_array
     * Return only valid mbid data
     * @param string|array $mbid
     * @return array
     */
    public static function parse_mbid_array($mbid): array
    {
        if (empty($mbid)) {
            return [];
        }
        if (is_array($mbid)) {
            $mbid = implode(";", $mbid);
        }
        if (preg_match_all(self::MBID_REGEX, $mbid, $matches)) {
            return $matches[0];
        }

        return [];
    }

    /**
     * is_mbid
     * @param null|string $mbid
     */
    public static function is_mbid($mbid): bool
    {
        if ($mbid === null) {
            return false;
        }
        if (preg_match(self::MBID_REGEX, $mbid)) {
            return true;
        }

        return false;
    }

    /**
     * _get_type
     *
     * This function takes the raw information and figures out what type of file we are dealing with.
     */
    private function _get_type(): ?string
    {
        // There are a few places that the file type can come from, in the end we trust the encoding type.
        if (array_key_exists('video', $this->_raw) && array_key_exists('dataformat', $this->_raw['video'])) {
            return $this->_clean_type($this->_raw['video']['dataformat']);
        }
        if (array_key_exists('audio', $this->_raw)) {
            if (array_key_exists('streams', $this->_raw['audio']) && array_key_exists('0', $this->_raw['audio']['streams']) && array_key_exists('dataformat', $this->_raw['audio']['streams']['0'])) {
                return $this->_clean_type($this->_raw['audio']['streams']['0']['dataformat']);
            }
            if (array_key_exists('dataformat', $this->_raw['audio'])) {
                return $this->_clean_type($this->_raw['audio']['dataformat']);
            }
        }
        if (array_key_exists('fileformat', $this->_raw)) {
            return $this->_clean_type($this->_raw['fileformat']);
        }

        return null;
    }

    /**
     * _get_tags
     *
     * This processes the raw getID3 output and bakes it.
     * @return array
     * @throws Exception
     */
    private function _get_tags(): array
    {
        $results = [];
        //$this->logger->debug('RAW TAGS ' . print_r($this->_raw, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        // The tags can come in many different shapes and colors depending on the encoding.
        if (array_key_exists('tags', $this->_raw) && is_array($this->_raw['tags'])) {
            foreach ($this->_raw['tags'] as $key => $tag_array) {
                switch ($key) {
                    case 'vorbiscomment':
                        //$this->logger->debug('Cleaning vorbis', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_vorbiscomment($tag_array);
                        break;
                    case 'id3v2':
                        //$this->logger->debug('Cleaning id3v2', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_id3v2($tag_array);
                        break;
                    case 'quicktime':
                        //$this->logger->debug('Cleaning quicktime', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_quicktime($tag_array);
                        break;
                    case 'riff':
                        //$this->logger->debug('Cleaning riff', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_riff($tag_array);
                        break;
                    case 'mpg':
                    case 'mpeg':
                        $key = 'mpeg';
                        //$this->logger->debug('Cleaning MPEG', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_generic($tag_array);
                        break;
                    case 'asf':
                    case 'wmv':
                    case 'wma':
                        $key = 'asf';
                        //$this->logger->debug('Cleaning WMV/WMA/ASF', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_asf($tag_array);
                        break;
                    case 'lyrics3':
                        //$this->logger->debug('Cleaning lyrics3', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_lyrics($tag_array);
                        break;
                    case 'id3v1':
                        //$this->logger->debug('Cleaning id3v1', [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_id3v1($tag_array);
                        break;
                    case 'ape':
                    case 'avi':
                    case 'flv':
                    case 'matroska':
                    default:
                        //$this->logger->debug('Cleaning tag type ' . $key . ' for file ' . $this->filename, [LegacyLogger::CONTEXT_TYPE => self::class]);
                        $parsed = $this->_cleanup_generic($tag_array);
                        break;
                }

                $results[$key] = $parsed;
            }
        }

        $results['general'] = $this->_parse_general($this->_raw);

        $cleaned        = self::clean_tag_info($results, self::get_tag_type($results, 'getid3_tag_order'), $this->filename);
        $cleaned['raw'] = $results;

        return $cleaned;
    }

    /**
     * get_metadata_order_key
     */
    private function get_metadata_order_key(): string
    {
        if (!in_array('music', $this->gatherTypes)) {
            return 'metadata_order_video';
        }

        return 'metadata_order';
    }

    /**
     * get_metadata_order
     * @return array
     */
    private function get_metadata_order(): array
    {
        $tagorderMap = [
            'metadata_order' => static::getConfigContainer()->get(ConfigurationKeyEnum::METADATA_ORDER),
            'metadata_order_video' => static::getConfigContainer()->get(ConfigurationKeyEnum::METADATA_ORDER_VIDEO),
            'getid3_tag_order' => static::getConfigContainer()->get(ConfigurationKeyEnum::GETID3_TAG_ORDER)
        ];

        // convert to lower case to be sure it matches plugin names in Ampache\Plugin\PluginEnum
        return array_map('strtolower', $tagorderMap[$this->get_metadata_order_key()] ?? []);
    }

    /**
     * _get_plugin_tags
     *
     * Get additional metadata from plugins
     */
    private function _get_plugin_tags(): void
    {
        $tag_order    = $this->get_metadata_order();
        $plugin_names = Plugin::get_plugins(PluginTypeEnum::METADATA_RETRIEVER);
        /** @var User $user */
        $user = (!empty(Core::get_global('user')))
            ? Core::get_global('user')
            : new User(-1);
        // don't loop over getid3 and filename
        $tag_order = array_diff($tag_order, ['getid3', 'filename']);
        foreach ($tag_order as $tag_source) {
            if (in_array($tag_source, $plugin_names)) {
                $plugin            = new Plugin($tag_source);
                $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
                if ($installed_version > 0) {
                    if ($plugin->_plugin !== null && $plugin->load($user)) {
                        $this->tags[$tag_source] = $plugin->_plugin->get_metadata(
                            $this->gatherTypes,
                            self::clean_tag_info(
                                $this->tags,
                                self::get_tag_type($this->tags, $this->get_metadata_order_key()),
                                $this->filename
                            )
                        );
                    }
                }
            } elseif (!in_array($tag_source, ['filename', 'getid3'])) {
                $this->logger->debug(
                    '_get_plugin_tags: ' . $tag_source . ' is not a valid metadata_order plugin',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * _parse_general
     *
     * Gather and return the general information about a file
     * (vbr/cbr, sample rate, channels, etc.)
     * @param $tags
     * @return array
     */
    private function _parse_general($tags): array
    {
        //$this->logger->debug('_parse_general: ' . print_r($tags, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        $parsed = [];
        if ((in_array('movie', $this->gatherTypes)) || (in_array('tvshow', $this->gatherTypes))) {
            $parsed['title'] = $this->formatVideoName(urldecode($this->_pathinfo['filename']));
        } else {
            $parsed['title'] = urldecode($this->_pathinfo['filename']);
        }
        if (array_key_exists('audio', $tags)) {
            $parsed['mode'] = $tags['audio']['bitrate_mode'] ?? 'vbr';
            if ($parsed['mode'] == 'con') {
                $parsed['mode'] = 'cbr';
            }
            $parsed['bitrate']     = $tags['audio']['bitrate'] ?? null;
            $parsed['channels']    = (!empty($tags['audio']['channels'])) ? (int)$tags['audio']['channels'] : null;
            $parsed['rate']        = (!empty($tags['audio']['sample_rate'])) ? (int)$tags['audio']['sample_rate'] : null;
            $parsed['audio_codec'] = $tags['audio']['dataformat'] ?? null;
        }
        if (array_key_exists('video', $tags)) {
            $parsed['video_codec']   = $tags['video']['dataformat'] ?? null;
            $parsed['resolution_x']  = $tags['video']['resolution_x'] ?? null;
            $parsed['resolution_y']  = $tags['video']['resolution_y'] ?? null;
            $parsed['display_x']     = $tags['video']['display_x'] ?? null;
            $parsed['display_y']     = $tags['video']['display_y'] ?? null;
            $parsed['frame_rate']    = $tags['video']['frame_rate'] ?? null;
            $parsed['video_bitrate'] = $tags['video']['bitrate'] ?? null;
        }
        $parsed['size']     = $this->_forcedSize ?? $tags['filesize'] ?? null;
        $parsed['encoding'] = $tags['encoding'] ?? null;
        $parsed['mime']     = $tags['mime_type'] ?? null;
        if (($parsed['size'] && array_key_exists('avdataoffset', $tags) && array_key_exists('bitrate', $tags))) {
            $parsed['time'] = (($parsed['size'] - $tags['avdataoffset']) * 8) / $tags['bitrate'];
        } elseif (array_key_exists('playtime_seconds', $tags) && $tags['playtime_seconds'] > 0) {
            $parsed['time'] = $tags['playtime_seconds'];
        } else {
            $this->logger->critical("UNABLE TO READ 'playtime_seconds'. This is probably a bad file " . $parsed['title'], [LegacyLogger::CONTEXT_TYPE => self::class]);
            $parsed['time'] = 0;
        }

        if (isset($tags['ape'])) {
            if (isset($tags['ape']['items'])) {
                foreach ($tags['ape']['items'] as $key => $tag) {
                    switch (strtolower($key)) {
                        case 'replaygain_track_gain':
                        case 'replaygain_track_peak':
                        case 'replaygain_album_gain':
                        case 'replaygain_album_peak':
                            $parsed[$key] = (!is_null($tag['data'][0])) ? (float) $tag['data'][0] : null;
                            break;
                        case 'r128_track_gain':
                        case 'r128_album_gain':
                            $parsed[$key] = (!is_null($tag['data'][0])) ? (int) $tag['data'][0] : null;
                            break;
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * @param string $string
     */
    private function trimAscii($string): string
    {
        return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim((string)$string));
    }

    /**
     * _clean_type
     * This standardizes the type that we are given into a recognized type.
     * @param $type
     */
    private function _clean_type($type): string
    {
        switch ($type) {
            case 'mp2':
            case 'mp3':
            case 'mpeg3':
                return 'mp3';
            case 'ogg':
            case 'opus':
            case 'vorbis':
                return 'ogg';
            case 'asf':
            case 'wma':
            case 'wmv':
                return 'asf';
            case 'mp4':
            case 'quicktime':
                return 'quicktime';
            case 'mpc':
                return 'ape';
            case 'avi':
            case 'flac':
            case 'flv':
            case 'mpg':
            case 'mpeg':
            case 'wav':
                return $type;
            default:
                /* Log the fact that we couldn't figure it out */
                $this->logger->warning(
                    'Unable to determine file type from ' . $type . ' on file ' . $this->filename,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return $type;
        }
    }

    /**
     * _cleanup_generic
     *
     * This does generic cleanup.
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_generic($tags): array
    {
        $parsed = [];
        foreach ($tags as $tagname => $data) {
            //$this->logger->debug('generic tag: ' . strtolower($tagname) . ' value: ' . print_r($data ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
            switch (strtolower($tagname)) {
                case 'artists':
                    $parsed['artists'] = $this->parseArtists($data);
                    break;
                case 'genre':
                    // Pass the array through
                    $parsed['genre'] = $this->parseGenres($data);
                    break;
                case 'discsubtitle':
                    $parsed['disksubtitle'] = $data[0];
                    break;
                case 'partofset':
                    $elements             = explode('/', $data[0]);
                    $parsed['disk']       = $elements[0];
                    $parsed['totaldisks'] = $elements[1] ?? null;
                    break;
                case 'track_number':
                case 'track':
                    $parsed['track'] = $data[0];
                    break;
                case 'musicbrainz_artistid':
                    $parsed['mb_artistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_artistid_array'] = (count($data) > 1) ? self::parse_mbid_array($data) : self::parse_mbid_array($data[0]);
                    break;
                case 'musicbrainz_albumid':
                    $parsed['mb_albumid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_albumartistid':
                    $parsed['mb_albumartistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_albumartistid_array'] = (count($data) > 1) ? self::parse_mbid_array($data) : self::parse_mbid_array($data[0]);
                    break;
                case 'musicbrainz_releasegroupid':
                    $parsed['mb_albumid_group'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_trackid':
                    $parsed['mb_trackid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_albumtype':
                    $parsed['release_type'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'musicbrainz_albumstatus':
                    $parsed['release_status'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'originalyear':
                case 'originalreleaseyear':
                    $parsed['original_year'] = $data[0];
                    break;
                case 'label':
                case 'publisher':
                    $parsed['publisher'] = $data[0];
                    break;
                case 'version':
                    $parsed['version'] = $data[0];
                    break;
                case 'music_cd_identifier':
                    // REMOVE_ME get rid of this annoying tag causing only problems with metadata
                    break;
                default:
                    $parsed[$tagname] = $data[0];
                    break;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_lyrics
     *
     * This is supposed to handle lyrics3. FIXME: does it?
     * @param $tags
     * @return array
     */
    private function _cleanup_lyrics($tags): array
    {
        $parsed = [];

        foreach ($tags as $tag => $data) {
            if ($tag == 'unsyncedlyrics' || $tag == 'unsynced lyrics' || $tag == 'unsynchronised lyric') {
                $tag = 'lyrics';
            }
            $parsed[strtolower($tag)] = $data[0];
        }

        return $parsed;
    }

    /**
     * _cleanup_vorbiscomment
     *
     * Standardizes tag names from vorbis.
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_vorbiscomment($tags): array
    {
        $parsed = [];

        foreach ($tags as $tag => $data) {
            //$this->logger->debug('Vorbis tag: ' . $tag . ' value: ' . print_r($data ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
            switch (strtolower($tag)) {
                case 'artists':
                    $parsed['artists'] = $this->parseArtists($data);
                    break;
                case 'genre':
                    $parsed['genre'] = $this->parseGenres($data);
                    break;
                case 'tracknumber':
                case 'track_number':
                    $parsed['track'] = $data[0];
                    break;
                case 'tracktotal':
                    $parsed['totaltracks'] = $data[0];
                    break;
                case 'discnumber':
                    $parsed['disk'] = $data[0];
                    break;
                case 'discsubtitle':
                    $parsed['disksubtitle'] = $data[0];
                    break;
                case 'totaldiscs':
                case 'disctotal':
                    $parsed['totaldisks'] = $data[0];
                    break;
                case 'albumartist':
                case 'album artist':
                    $parsed['albumartist'] = $data[0];
                    break;
                case 'isrc':
                    $parsed['isrc'] = $data[0];
                    break;
                case 'date':
                    $parsed['year'] = $data[0];
                    break;
                case 'musicbrainz_artistid':
                    $parsed['mb_artistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_artistid_array'] = (count($data) > 1) ? self::parse_mbid_array($data) : self::parse_mbid_array($data[0]);
                    break;
                case 'musicbrainz_albumid':
                    $parsed['mb_albumid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_albumartistid':
                    $parsed['mb_albumartistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_albumartistid_array'] = (count($data) > 1) ? self::parse_mbid_array($data) : self::parse_mbid_array($data[0]);
                    break;
                case 'musicbrainz_releasegroupid':
                    $parsed['mb_albumid_group'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_trackid':
                    $parsed['mb_trackid'] = self::parse_mbid($data[0]);
                    break;
                case 'releasetype':
                case 'musicbrainz_albumtype':
                    $parsed['release_type'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'releasestatus':
                case 'musicbrainz_albumstatus':
                    $parsed['release_status'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'unsyncedlyrics':
                case 'unsynced lyrics':
                case 'lyrics':
                    $parsed['lyrics'] = $data[0];
                    break;
                case 'originaldate':
                    $parsed['originaldate'] = strtotime(str_replace(" ", "", $data[0]));
                    if (strlen($data['0']) > 4) {
                        $data[0] = date('Y', $parsed['originaldate']);
                    }
                    $parsed['original_year'] = $parsed['original_year'] ?? $data[0];
                    break;
                case 'originalyear':
                    $parsed['original_year'] = $data[0];
                    break;
                case 'barcode':
                    $parsed['barcode'] = $data[0];
                    break;
                case 'catalognumber':
                    $parsed['catalog_number'] = $data[0];
                    break;
                case 'label':
                case 'organization':
                    $parsed['publisher'] = $data[0];
                    break;
                case 'version':
                    $parsed['version'] = $data[0];
                    break;
                case 'rating':
                    if (!is_numeric($data[0])) {
                        break;
                    }

                    $rating_user = -1;
                    if ($this->configContainer->get(ConfigurationKeyEnum::RATING_FILE_TAG_USER)) {
                        $rating_user = (int) $this->configContainer->get(ConfigurationKeyEnum::RATING_FILE_TAG_USER);
                    }

                    $parsed['rating'][$rating_user] = floor($data[0] * 5 / 100);
                    break;
                default:
                    // look for set ratings using email address
                    foreach (preg_grep("/^rating:.*@.*/", array_keys($parsed)) as $user_rating) {
                        /**
                         * @todo check functionality; looks like an array is used for a string
                         */
                        $rating_user = $this->userRepository->findByEmail(
                            array_map('trim', preg_split("/^rating:/", $user_rating))[1] ?? ''
                        );
                        if ($rating_user !== null) {
                            $parsed['rating'][$rating_user->id] = floor($data[0] * 5 / 100);
                        }
                    }
                    $parsed[strtolower($tag)] = $data[0];
                    break;
            }
        }
        // Replaygain stored by getID3
        if (isset($this->_raw['replay_gain'])) {
            if (isset($this->_raw['replay_gain']['track']['adjustment'])) {
                $parsed['replaygain_track_gain'] = (float) $this->_raw['replay_gain']['track']['adjustment'];
            }
            if (isset($this->_raw['replay_gain']['track']['peak'])) {
                $parsed['replaygain_track_peak'] = (float) $this->_raw['replay_gain']['track']['peak'];
            }
            if (isset($this->_raw['replay_gain']['album']['adjustment'])) {
                $parsed['replaygain_album_gain'] = (float) $this->_raw['replay_gain']['album']['adjustment'];
            }
            if (isset($this->_raw['replay_gain']['album']['peak'])) {
                $parsed['replaygain_album_peak'] = (float) $this->_raw['replay_gain']['album']['peak'];
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_id3v1
     *
     * Doesn't do much.
     * @param $tags
     * @return array
     */
    private function _cleanup_id3v1($tags): array
    {
        $parsed = [];

        foreach ($tags as $tag => $data) {
            // This is our baseline for naming so everything's already right,
            // we just need to shuffle off the array.
            $parsed[strtolower($tag)] = $data[0];
        }

        return $parsed;
    }

    /**
     * _cleanup_id3v2
     *
     * Whee, v2!
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_id3v2($tags): array
    {
        $parsed = [];

        foreach ($tags as $tag => $data) {
            //$this->logger->debug('id3v2 tag: ' . strtolower($tag) . ' value: ' . print_r($data ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
            switch (strtolower($tag)) {
                case 'artists':
                    $parsed['artists'] = $this->parseArtists($data);
                    break;
                case 'genre':
                    $parsed['genre'] = $this->parseGenres($data);
                    break;
                case 'discsubtitle':
                    $parsed['disksubtitle'] = $data[0];
                    break;
                case 'part_of_a_set':
                    $elements             = explode('/', $data[0]);
                    $parsed['disk']       = $elements[0];
                    $parsed['totaldisks'] = $elements[1] ?? null;
                    break;
                case 'track_number':
                    $parsed['track'] = $data[0];
                    break;
                case 'totaltracks':
                    $parsed['totaltracks'] = $data[0];
                    break;
                case 'comment':
                    // First array key can be xFF\xFE in case of UTF-8, better to get it this way
                    $parsed['comment'] = reset($data);
                    break;
                case 'band':
                    $parsed['albumartist'] = $data[0];
                    break;
                case 'composer':
                    $BOM                = chr(0xff) . chr(0xfe);
                    $parsed['composer'] = (strlen($data[0]) == 2 && $data[0] == $BOM)
                        ? str_replace($BOM, '', $data[0])
                        : reset($data);
                    break;
                case 'isrc':
                    $parsed['isrc'] = $data[0];
                    break;
                case 'comments':
                    $parsed['comment'] = $data[0];
                    break;
                case 'unsynchronised_lyric':
                    $parsed['lyrics'] = $data[0];
                    break;
                case 'original_release_time':
                case 'originaldate':
                    $parsed['originaldate'] = strtotime(str_replace(" ", "", $data[0]));
                    if (strlen($data['0']) > 4) {
                        $data[0] = date('Y', $parsed['originaldate']);
                    }
                    $parsed['original_year'] = (array_key_exists('original_year', $parsed)) ? ($parsed['original_year']) : $data[0];
                    break;
                case 'originalyear':
                    $parsed['original_year'] = $data[0];
                    break;
                case 'barcode':
                    $parsed['barcode'] = $data[0];
                    break;
                case 'catalognumber':
                    $parsed['catalog_number'] = $data[0];
                    break;
                case 'label':
                case 'publisher':
                    $parsed['publisher'] = $data[0];
                    break;
                case 'version':
                    $parsed['version'] = $data[0];
                    break;
                case 'music_cd_identifier':
                    // REMOVE_ME get rid of this annoying tag causing only problems with metadata
                    break;
                default:
                    if (array_key_exists(0, $data)) {
                        $parsed[strtolower($tag)] = $data[0];
                    }
                    break;
            }
        }

        // getID3 doesn't do all the parsing we need, so grab the raw data
        $id3v2 = $this->_raw['id3v2'];

        if (!empty($id3v2['UFID'])) {
            // Find the MBID for the track
            foreach ($id3v2['UFID'] as $ufid) {
                if ($ufid['ownerid'] == 'http://musicbrainz.org') {
                    $parsed['mb_trackid'] = $ufid['data'];
                }
            }
        }

        if (!empty($id3v2['TXXX'])) {
            // Find the MBIDs for the album and artist
            // Use trimAscii to remove noise (see #225 and #438 issues). Is this a GetID3 bug?
            // not a bug those strings are UTF-16 encoded
            // getID3 has copies of text properly converted to utf-8 encoding in comments/text
            $enable_custom_metadata = $this->configContainer->get(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA);
            foreach ($id3v2['TXXX'] as $txxx) {
                //$this->logger->debug('id3v2 TXXX: ' . strtolower($this->trimAscii($txxx['description'] ?? '')) . ' value: ' . print_r($id3v2['comments']['text'][$txxx['description']] ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
                switch (strtolower($this->trimAscii($txxx['description']))) {
                    case 'artists':
                        $parsed['artists'] = $this->parseArtists($id3v2['comments']['text'][$txxx['description']]);
                        break;
                    case 'album artist':
                        $parsed['albumartist'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'musicbrainz artist id':
                        $parsed['mb_artistid']       = self::parse_mbid($id3v2['comments']['text'][$txxx['description']]);
                        $parsed['mb_artistid_array'] = self::parse_mbid_array($id3v2['comments']['text'][$txxx['description']]);
                        break;
                    case 'musicbrainz album artist id':
                        $parsed['mb_albumartistid']       = self::parse_mbid($id3v2['comments']['text'][$txxx['description']]);
                        $parsed['mb_albumartistid_array'] = self::parse_mbid_array($id3v2['comments']['text'][$txxx['description']]);
                        break;
                    case 'musicbrainz album id':
                        $parsed['mb_albumid'] = self::parse_mbid($id3v2['comments']['text'][$txxx['description']]);
                        break;
                    case 'musicbrainz release group id':
                        $parsed['mb_albumid_group'] = self::parse_mbid($id3v2['comments']['text'][$txxx['description']]);
                        break;
                    case 'musicbrainz album type':
                        $parsed['release_type'] = (is_array($id3v2['comments']['text'][$txxx['description']])) ? implode(", ", $id3v2['comments']['text'][$txxx['description']]) : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $id3v2['comments']['text'][$txxx['description']]), ['']));
                        break;
                    case 'musicbrainz album status':
                        $parsed['release_status'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'replaygain_track_gain':
                        // FIXME: shouldn't here $txxx['data'] be replaced by $id3v2['comments']['text'][$txxx['description']]
                        // all replaygain values aren't always correctly retrieved
                        $parsed['replaygain_track_gain'] = (float) $txxx['data'];
                        break;
                    case 'replaygain_track_peak':
                        $parsed['replaygain_track_peak'] = (float) $txxx['data'];
                        break;
                    case 'replaygain_album_gain':
                        $parsed['replaygain_album_gain'] = (float) $txxx['data'];
                        break;
                    case 'replaygain_album_peak':
                        $parsed['replaygain_album_peak'] = (float) $txxx['data'];
                        break;
                    case 'r128_track_gain':
                        $parsed['r128_track_gain'] = (int) $txxx['data'];
                        break;
                    case 'r128_album_gain':
                        $parsed['r128_album_gain'] = (int) $txxx['data'];
                        break;
                    case 'original_year':
                        $parsed['original_year'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'discsubtitle':
                    case 'setsubtitle':
                        $parsed['disksubtitle'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'barcode':
                        $parsed['barcode'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'catalognumber':
                        $parsed['catalog_number'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'label':
                        $parsed['publisher'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    case 'version':
                        $parsed['version'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    default:
                        $frame = strtolower($this->trimAscii($txxx['description']));
                        if ($enable_custom_metadata && !isset(self::DEFAULT_INFO[$frame]) && !in_array($frame, $parsed)) {
                            $parsed[strtolower($this->trimAscii($txxx['description']))] = $id3v2['comments']['text'][$txxx['description']];
                        }
                        break;
                }
            }
        }

        // Find the rating
        if (array_key_exists('POPM', $id3v2) && is_array($id3v2['POPM'])) {
            foreach ($id3v2['POPM'] as $popm) {
                if (!is_numeric($popm['rating'])) {
                    continue;
                }

                if (
                    array_key_exists('email', $popm) &&
                    $user = $this->userRepository->findByEmail($popm['email'])
                ) {
                    if ($user instanceof User) {
                        // Ratings are out of 255; scale it
                        $parsed['rating'][$user->id] = $popm['rating'] / 255 * 5;
                    }
                    continue;
                }
                // Rating made by an unknown user, adding it to super user (id=-1)
                $rating_user = -1;
                if ($this->configContainer->get(ConfigurationKeyEnum::RATING_FILE_TAG_USER)) {
                    $rating_user = (int) $this->configContainer->get(ConfigurationKeyEnum::RATING_FILE_TAG_USER);
                }

                $parsed['rating'][$rating_user] = $popm['rating'] / 255 * 5;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_riff
     * @param $tags
     * @return array
     */
    private function _cleanup_riff($tags): array
    {
        $parsed = [];

        foreach ($tags as $tag => $data) {
            switch ($tag) {
                case 'product':
                    $parsed['album'] = $data[0];
                    break;
                default:
                    $parsed[strtolower($tag)] = $data[0];
                    break;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_quicktime
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_quicktime($tags): array
    {
        $parsed = [];

        foreach ($tags as $tag => $data) {
            //$this->logger->debug('Quicktime tag: ' . strtolower($tag) . ' value: ' . print_r($data ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
            switch (strtolower($tag)) {
                case 'artists':
                    $parsed['artists'] = $this->parseArtists($data);
                    break;
                case 'genre':
                    $parsed['genre'] = $this->parseGenres($data);
                    break;
                case 'creation_date':
                    $parsed['creation_date'] = strtotime(str_replace(" ", "", $data[0]));
                    if (strlen($data['0']) > 4) {
                        $data[0] = date('Y', $parsed['creation_date']);
                    }
                    $parsed['year'] = $data[0];
                    break;
                case 'musicbrainz track id':
                    $parsed['mb_trackid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz album id':
                    $parsed['mb_albumid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz album artist id':
                    $parsed['mb_albumartistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_albumartistid_array'] = self::parse_mbid_array($data);
                    break;
                case 'musicbrainz release group id':
                    $parsed['mb_albumid_group'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz artist id':
                    $parsed['mb_artistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_artistid_array'] = self::parse_mbid_array($data);
                    break;
                case 'musicbrainz album type':
                    $parsed['release_type'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'musicbrainz album status':
                    $parsed['release_status'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'track_number':
                    //$parsed['track'] = $data[0];
                    $elements              = explode('/', $data[0]);
                    $parsed['track']       = $elements[0];
                    $parsed['totaltracks'] = $elements[1] ?? null;
                    break;
                case 'disc_number':
                    $elements             = explode('/', $data[0]);
                    $parsed['disk']       = $elements[0];
                    $parsed['totaldisks'] = $elements[1] ?? null;
                    break;
                case 'discsubtitle':
                    $parsed['disksubtitle'] = $data[0];
                    break;
                case 'isrc':
                    $parsed['isrc'] = $data[0];
                    break;
                case 'album_artist':
                    $parsed['albumartist'] = $data[0];
                    break;
                case 'originaldate':
                    $parsed['originaldate'] = strtotime(str_replace(" ", "", $data[0]));
                    if (strlen($data['0']) > 4) {
                        $data[0] = date('Y', $parsed['originaldate']);
                    }
                    $parsed['original_year'] = $parsed['original_year'] ?? $data[0];
                    break;
                case 'originalyear':
                    $parsed['original_year'] = $data[0];
                    break;
                case 'barcode':
                    $parsed['barcode'] = $data[0];
                    break;
                case 'catalognumber':
                    $parsed['catalog_number'] = $data[0];
                    break;
                case 'label':
                    $parsed['publisher'] = $data[0];
                    break;
                case 'version':
                    $parsed['version'] = $data[0];
                    break;
                case 'tv_episode':
                    $parsed['tvshow_episode'] = $data[0];
                    break;
                case 'tv_season':
                    $parsed['tvshow_season'] = $data[0];
                    break;
                case 'tv_show_name':
                    $parsed['tvshow'] = $data[0];
                    break;
                default:
                    $parsed[strtolower($tag)] = $data[0];
                    break;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_asf
     *
     * This does WMA cleanup.
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_asf($tags): array
    {
        $parsed = [];
        foreach ($tags as $tagname => $data) {
            //$this->logger->debug('asf tag: ' . strtolower($tagname) . ' value: ' . print_r($data ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
            switch (strtolower($tagname)) {
                case 'artists':
                    $parsed['artists'] = $this->parseArtists($data);
                    break;
                case 'genre':
                    $parsed['genre'] = $this->parseGenres($data);
                    break;
                case 'partofset':
                    $elements             = explode('/', $data[0]);
                    $parsed['disk']       = $elements[0];
                    $parsed['totaldisks'] = $elements[1] ?? null;
                    break;
                case 'track_number':
                case 'track':
                    $parsed['track'] = $data[0];
                    break;
                case 'musicbrainz_artistid':
                    $parsed['mb_artistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_artistid_array'] = (count($data) > 1) ? self::parse_mbid_array($data) : self::parse_mbid_array($data[0]);
                    break;
                case 'musicbrainz_albumid':
                    $parsed['mb_albumid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_albumartistid':
                    $parsed['mb_albumartistid']       = self::parse_mbid($data[0]);
                    $parsed['mb_albumartistid_array'] = (count($data) > 1) ? self::parse_mbid_array($data) : self::parse_mbid_array($data[0]);
                    break;
                case 'musicbrainz_releasegroupid':
                    $parsed['mb_albumid_group'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_trackid':
                    $parsed['mb_trackid'] = self::parse_mbid($data[0]);
                    break;
                case 'musicbrainz_albumtype':
                    $parsed['release_type'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'musicbrainz_albumstatus':
                    $parsed['release_status'] = (is_array($data) && count($data) > 1)
                        ? implode(", ", $data)
                        : implode(', ', array_diff(preg_split("/[^a-zA-Z0-9*]/", $data[0]), ['']));
                    break;
                case 'releasecomment':
                case 'version':
                    $parsed['version'] = $data[0];
                    break;
                case 'originalreleaseyear':
                    $parsed['original_year'] = str_replace("\x00", '', $data[0]);
                    break;
                case 'music_cd_identifier':
                    // REMOVE_ME get rid of this annoying tag causing only problems with metadata
                    break;
                default:
                    $parsed[$tagname] = $data[0];
                    break;
            }
        }

        // WMA isn't read very well so dig into the raw data
        if (array_key_exists('asf', $this->_raw) && is_array($this->_raw['asf'])) {
            $enable_custom_metadata = $this->configContainer->get(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA);
            foreach ($this->_raw['asf']['extended_content_description_object']['content_descriptors'] as $wmaTag) {
                $value = str_replace("\x00", '', $wmaTag['value']);
                //$this->logger->debug('asf tag: ' . strtolower($wmaTag['name'] ?? '') . ' value: ' . print_r($value ?? '', true), [LegacyLogger::CONTEXT_TYPE => self::class]);
                switch (strtolower($this->trimAscii($wmaTag['name']))) {
                    case 'wm/artists':
                        $parsed['artists'] = $this->parseArtists($value);
                        break;
                    case 'wm/albumartist':
                        $parsed['albumartist'] = $value;
                        break;
                    case 'wm/setsubtitle':
                        $parsed['disksubtitle'] = $value;
                        break;
                    case 'musicbrainz/artist id':
                        $parsed['mb_artistid']       = self::parse_mbid($value);
                        $parsed['mb_artistid_array'] = self::parse_mbid_array($value);
                        break;
                    case 'musicbrainz/album artist id':
                        $parsed['mb_albumartistid']       = self::parse_mbid($value);
                        $parsed['mb_albumartistid_array'] = self::parse_mbid_array($value);
                        break;
                    case 'musicbrainz/album id':
                        $parsed['mb_albumid'] = self::parse_mbid($value);
                        break;
                    case 'musicbrainz/release group id':
                        $parsed['mb_albumid_group'] = self::parse_mbid($value);
                        break;
                    case 'musicbrainz/album type':
                        $parsed['release_type'] = $value;
                        break;
                    case 'musicbrainz/album status':
                        $parsed['release_status'] = $value;
                        break;
                    case 'wm/originalreleaseyear':
                        $parsed['original_year'] = (int)$value;
                        break;
                    case 'wm/barcode':
                        $parsed['barcode'] = $value;
                        break;
                    case 'wm/catalogno':
                        $parsed['catalog_number'] = $value;
                        break;
                    case 'wm/publisher':
                        $parsed['publisher'] = $value;
                        break;
                    default:
                        $frame = strtolower($this->trimAscii($wmaTag['name']));
                        if ($enable_custom_metadata && !isset(self::DEFAULT_INFO[$frame]) && !in_array($frame, $parsed)) {
                            $parsed[strtolower($this->trimAscii($wmaTag['name']))] = $value;
                        }
                        break;
                }
            }
        }

        return $parsed;
    }

    /**
     * _parse_filename
     * This function uses the file and directory patterns to pull out extra tag
     * information.
     *  parses TV show name variations:
     *    1. title.[date].S#[#]E#[#].ext        (Upper/lower case)
     *    2. title.[date].#[#]X#[#].ext        (both upper/lower case letters
     *    3. title.[date].Season #[#] Episode #[#].ext
     *    4. title.[date].###.ext        (maximum of 9 seasons)
     *  parse directory  path for name, season and episode numbers
     *   /TV shows/show name [(year)]/[season ]##/##.Episode.Title.ext
     *  parse movie names:
     *    title.[date].ext
     *    /movie title [(date)]/title.ext
     * @param string $filepath
     * @return array
     */
    private function _parse_filename($filepath): array
    {
        $origin  = $filepath;
        $results = [];
        $file    = pathinfo($filepath, PATHINFO_FILENAME);

        if (in_array('tvshow', $this->gatherTypes)) {
            $season  = [];
            $episode = [];
            $tvyear  = [];
            $temp    = [];
            preg_match("~(?<=\(\[\<\{)[1|2][0-9]{3}[^p]|[1|2][0-9]{3}[^p]~", $filepath, $tvyear);
            $results['year'] = (!empty($tvyear)) ? (int) $tvyear[0] : null;

            if (preg_match("~[Ss](\d+)[Ee](\d+)~", $file, $seasonEpisode)) {
                $temp = preg_split("~(((\.|_|\s)[Ss]\d+(\.|_)*[Ee]\d+))~", $file, 2);
                preg_match("~(?<=[Ss])\d+~", $file, $season);
                preg_match("~(?<=[Ee])\d+~", $file, $episode);
            } elseif (preg_match("~[\_\-\.\s](\d{1,2})[xX](\d{1,2})~", $file, $seasonEpisode)) {
                $temp = preg_split("~[\.\_\s\-\_]\d+[xX]\d{2}[\.\s\-\_]*|$~", $file);
                preg_match("~\d+(?=[Xx])~", $file, $season);
                preg_match("~(?<=[Xx])\d+~", $file, $episode);
            } elseif (preg_match("~[S|s]eason[\_\-\.\s](\d+)[\.\-\s\_]?\s?[e|E]pisode[\s\-\.\_]?(\d+)[\.\s\-\_]?~", $file, $seasonEpisode)) {
                $temp = preg_split(
                    "~[\.\s\-\_][S|s]eason[\s\-\.\_](\d+)[\.\s\-\_]?\s?[e|E]pisode[\s\-\.\_](\d+)([\s\-\.\_])*~",
                    $file,
                    3
                );
                preg_match("~(?<=[Ss]eason[\.\s\-\_])\d+~", $file, $season);
                preg_match("~(?<=[Ee]pisode[\.\s\-\_])\d+~", $file, $episode);
            } elseif (preg_match("~[\_\-\.\s](\d)(\d\d)[\_\-\.\s]*~", $file, $seasonEpisode)) {
                $temp      = preg_split("~[\.\s\-\_](\d)(\d\d)[\.\s\-\_]~", $file);
                $season[0] = $seasonEpisode[1];
                if (preg_match("~[\_\-\.\s](\d)(\d\d)[\_\-\.\s]~", $file, $seasonEpisode)) {
                    $temp       = preg_split("~[\.\s\-\_](\d)(\d\d)[\.\s\-\_]~", $file);
                    $season[0]  = $seasonEpisode[1];
                    $episode[0] = $seasonEpisode[2];
                }
            }

            $results['tvshow_season']  = $season[0];
            $results['tvshow_episode'] = $episode[0];
            $results['tvshow']         = $this->formatVideoName($temp[0]);
            $results['original_name']  = $this->formatVideoName($temp[1]);

            // Try to identify the show information from parent folder
            if (!$results['tvshow']) {
                $folders = preg_split("~" . DIRECTORY_SEPARATOR . "~", $filepath, -1, PREG_SPLIT_NO_EMPTY);
                if (array_key_exists('tvshow_season', $results) && array_key_exists('tvshow_episode', $results)) {
                    // We have season and episode, we assume parent folder is the tvshow name
                    $filetitle         = end($folders);
                    $results['tvshow'] = $this->formatVideoName($filetitle);
                } elseif (preg_match('/[\/\\\\]([^\/\\\\]*)[\/\\\\]Season (\d{1,2})[\/\\\\]((E|Ep|Episode)\s?(\d{1,2})[\/\\\\])?/i', $filepath, $matches)) {
                    // Or we assume each parent folder contains one missing information
                    if ($matches != null) {
                        $results['tvshow']        = $this->formatVideoName($matches[1]);
                        $results['tvshow_season'] = $matches[2];
                        if (isset($matches[5])) {
                            $results['tvshow_episode'] = $matches[5];
                        } elseif (preg_match("~^(\d\d)[\_\-\.\s]?(.*)~", $file, $matches)) {
                            // match pattern like 10.episode name.mp4
                            $results['tvshow_episode'] = $matches[1];
                            $results['original_name']  = $this->formatVideoName($matches[2]);
                        } else {
                            // Fallback to match any 3-digit Season/Episode that fails the standard pattern above.
                            preg_match("~(\d)(\d\d)[\_\-\.\s]?~", $file, $matches);
                            $results['tvshow_episode'] = $matches[2];
                        }
                    }
                }
            }

            $results['title'] = $results['tvshow'];
        }

        if (in_array('movie', $this->gatherTypes)) {
            $results['original_name'] = $results['title'] = $this->formatVideoName($file);
        }

        if (in_array('music', $this->gatherTypes) || in_array('clip', $this->gatherTypes)) {
            $patres  = VaInfo::parse_pattern($filepath, $this->_dir_pattern, $this->_file_pattern);
            $results = array_merge($results, $patres);
            if ($this->islocal) {
                $results['size'] = Core::get_filesize(Core::conv_lc_file($origin));
            }
        }

        return $results;
    }

    /**
     * parse_pattern
     * @param string $filepath
     * @param string $dirPattern
     * @param string $filePattern
     * @return array
     */
    public static function parse_pattern($filepath, $dirPattern, $filePattern): array
    {
        $logger          = static::getLogger();
        $results         = [];
        $slash_type_preg = DIRECTORY_SEPARATOR;
        if ($slash_type_preg == '\\') {
            $slash_type_preg .= DIRECTORY_SEPARATOR;
        }
        // Combine the patterns
        $pattern = preg_quote($dirPattern) . $slash_type_preg . preg_quote($filePattern);

        // Remove first left directories from filename to match pattern
        $cntslash = substr_count($pattern, preg_quote(DIRECTORY_SEPARATOR)) + 1;
        $filepart = explode(DIRECTORY_SEPARATOR, $filepath);
        if (count($filepart) > $cntslash) {
            $filepath = implode(DIRECTORY_SEPARATOR, array_slice($filepart, count($filepart) - $cntslash));
        }

        // Pull out the pattern codes into an array
        preg_match_all('/\%\w/', $pattern, $elements);

        // Mangle the pattern by turning the codes into regex captures
        $pattern = preg_replace('/\%[d]/', '([0-9]?)', $pattern);
        $pattern = preg_replace('/\%[TyY]/', '([0-9]+?)', $pattern);
        $pattern = preg_replace('/\%\w/', '(.+?)', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace(' ', '\s', $pattern);
        $pattern = '/' . $pattern . '\..+$/';

        // Pull out our actual matches
        preg_match($pattern, $filepath, $matches);
        //$logger->debug('Checking ' . $pattern . ' _ ' . print_r($matches, true) . ' on ' . $filepath, [LegacyLogger::CONTEXT_TYPE => self::class]);
        if ($matches != null) {
            // The first element is the full match text
            $matched = array_shift($matches);
            $logger->debug(
                $pattern . ' matched ' . $matched . ' on ' . $filepath,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            // Iterate over what we found
            foreach ($matches as $key => $value) {
                $new_key = self::translate_pattern_code($elements['0'][$key]);
                if ($new_key !== false) {
                    $results[$new_key] = $value;
                }
            }

            $results['title'] = $results['title'] ?? basename($filepath);
        }

        return $results;
    }

    /**
     * removeCommonAbbreviations
     * @param string $name
     */
    private function removeCommonAbbreviations($name): string
    {
        $abbr         = explode(",", $this->configContainer->get(ConfigurationKeyEnum::COMMON_ABBR));
        $commonabbr   = preg_replace("~\n~", '', $abbr);
        $commonabbr[] = '[1|2][0-9]{3}'; //Remove release year
        $abbr_count   = count($commonabbr);

        // scan for brackets, braces, etc and ignore case.
        for ($count = 0; $count < $abbr_count; $count++) {
            $commonabbr[$count] = "~\[*|\(*|\<*|\{*\b(?i)" . trim((string)$commonabbr[$count]) . "\b\]*|\)*|\>*|\}*~";
        }

        return preg_replace($commonabbr, '', $name);
    }

    /**
     * formatVideoName
     * @param string $name
     */
    private function formatVideoName($name): string
    {
        return ucwords(trim(
            (string)$this->removeCommonAbbreviations(str_replace(['.', '_', '-'], ' ', $name)),
            "\s\t\n\r\0\x0B\.\_\-"
        ));
    }

    /**
     * set_broken
     *
     * This fills all tag types with Unknown (Broken)
     *
     * @return array Return broken title, album, artist
     */
    public function set_broken(): array
    {
        /* Pull In the config option */
        $order = $this->configContainer->get(ConfigurationKeyEnum::TAG_ORDER);

        if (!is_array($order)) {
            $order = [$order];
        }

        $key = array_shift($order);

        $broken                 = [];
        $broken[$key]           = [];
        $broken[$key]['title']  = '**BROKEN** ' . $this->filename;
        $broken[$key]['album']  = 'Unknown (Broken)';
        $broken[$key]['artist'] = 'Unknown (Broken)';

        return $broken;
    }

    /**
     *
     * @param array|string $data
     * @return array
     * @throws Exception
     */
    private function parseGenres($data)
    {
        //debug_event(self::class, "parseGenres: " . print_r($data, true), 5);
        $result = null;
        if (is_array($data)) {
            $result = [];
            foreach ($data as $row) {
                if (!empty($row)) {
                    foreach (self::splitSlashedlist(str_replace("\x00", ';', str_replace('Folk, World, & Country', 'Folk World & Country', $row)), false) as $genre) {
                        $result[] = $genre;
                    }
                }
            }
        }
        if (is_string($data) && !empty($data)) {
            $result = self::splitSlashedlist(str_replace("\x00", ';', str_replace('Folk, World, & Country', 'Folk World & Country', $data)), false);
        }

        return $result;
    }

    /**
     *
     * @param array|string $data
     * @return array
     */
    private function parseArtists($data): array
    {
        //debug_event(self::class, "parseArtists: " . print_r($data, true), 5);
        $result = null;
        if (is_array($data)) {
            $result = [];
            foreach ($data as $row) {
                if (!empty($row)) {
                    foreach (explode(';', str_replace("\x00", ';', $row)) as $artist) {
                        $result[] = trim($artist);
                    }
                }
            }
        }
        if (is_string($data) && !empty($data)) {
            $result = explode(';', str_replace("\x00", ';', $data));
        }

        return $result;
    }

    /**
     * splitSlashedlist
     * Split items by configurable delimiter
     * Return first item as string = default
     * Return all items as array if doTrim = false passed as optional parameter
     * @param string $data
     * @param bool $doTrim
     * @return string|array
     * @throws Exception
     */
    public function splitSlashedlist($data, $doTrim = true)
    {
        $delimiters = $this->configContainer->get(ConfigurationKeyEnum::ADDITIONAL_DELIMITERS);
        if (!empty($data) && !empty($delimiters)) {
            $pattern = '~[\s]?(' . $delimiters . ')[\s]?~';
            $items   = preg_split($pattern, $data);
            $items   = array_map('trim', $items);
            if (empty($items)) {
                throw new Exception('Pattern given in additional_genre_delimiters is not functional. Please ensure is it a valid regex (delimiter ~)');
            }
            $data = $items;
        }
        if (isset($data[0]) && $doTrim) {
            return $data[0];
        }

        return $data;
    }

    /**
     * translate_pattern_code
     * This just contains a keyed array which it checks against to give you the
     * 'tag' name that said pattern code corresponds to. It returns false if nothing
     * is found.
     * @return string|false
     */
    private static function translate_pattern_code(string $code)
    {
        $code_array = [
            '%a' => 'artist',
            '%A' => 'album',
            '%b' => 'barcode',
            '%c' => 'comment',
            '%C' => 'catalog_number',
            '%d' => 'disk',
            '%g' => 'genre',
            '%l' => 'label',
            '%t' => 'title',
            '%T' => 'track',
            '%r' => 'release_type',
            '%R' => 'release_status',
            '%s' => 'subtitle',
            '%y' => 'year',
            '%Y' => 'original_year',
            '%o' => 'zz_other'
        ];

        if (isset($code_array[$code])) {
            return $code_array[$code];
        }

        return false;
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getConfigContainer(): ConfigContainerInterface
    {
        global $dic;

        return $dic->get(ConfigContainerInterface::class);
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getLogger(): LoggerInterface
    {
        global $dic;

        return $dic->get(LoggerInterface::class);
    }
}
