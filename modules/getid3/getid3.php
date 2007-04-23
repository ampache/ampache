<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 James Heinrich, Allan Hansen                 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2 of the GPL license,         |
// | that is bundled with this package in the file license.txt and is     |
// | available through the world-wide-web at the following url:           |
// | http://www.gnu.org/copyleft/gpl.html                                 |
// +----------------------------------------------------------------------+
// | getID3() - http://getid3.sourceforge.net or http://www.getid3.org    |
// +----------------------------------------------------------------------+
// | Authors: James Heinrich <infoØgetid3*org>                            |
// |          Allan Hansen <ahØartemis*dk>                                |
// +----------------------------------------------------------------------+
// | getid3.php                                                           |
// | Main getID3() file.                                                  |
// | dependencies: modules.                                               |
// +----------------------------------------------------------------------+
//
// $Id: getid3.php,v 1.26 2006/12/25 23:44:23 ah Exp $


class getid3
{
    //// Settings Section - do NOT modify this file - change setting after newing getid3!

    // Encoding
    public $encoding                 = 'ISO-8859-1';      // CASE SENSITIVE! - i.e. (must be supported by iconv() - see http://www.gnu.org/software/libiconv/).  Examples:  ISO-8859-1  UTF-8  UTF-16  UTF-16BE.
    public $encoding_id3v1           = 'ISO-8859-1';      // Override SPECIFICATION encoding for broken ID3v1 tags caused by bad tag programs. Examples: 'EUC-CN' for "Chinese MP3s" and 'CP1251' for "Cyrillic".
    public $encoding_id3v2           = 'ISO-8859-1';      // Override ISO-8859-1 encoding for broken ID3v2 tags caused by BRAINDEAD tag programs that writes system codepage as 'ISO-8859-1' instead of UTF-8.

    // Tags - disable for speed
    public $option_tag_id3v1         = true;              // Read and process ID3v1 tags.
    public $option_tag_id3v2         = true;              // Read and process ID3v2 tags.
    public $option_tag_lyrics3       = true;              // Read and process Lyrics3 tags.
    public $option_tag_apetag        = true;              // Read and process APE tags.

    // Misc calucations - disable for speed
    public $option_analyze           = true;              // Analyze file - disable if you only need to detect file format.
    public $option_accurate_results  = true;              // Disable to greatly speed up parsing of some file formats at the cost of accuracy.
    public $option_tags_process      = true;              // Copy tags to root key 'tags' and 'comments' and encode to $this->encoding.
    public $option_tags_images       = false;             // Scan tags for binary image data - ID3v2 and vorbiscomments only.
    public $option_extra_info        = true;              // Calculate/return additional info such as bitrate, channelmode etc.
    public $option_max_2gb_check     = false;             // Check whether file is larger than 2 Gb and thus not supported by PHP.

    // Misc data hashes - slow - require hash module
    public $option_md5_data          = false;             // Get MD5 sum of data part - slow.
    public $option_md5_data_source   = false;             // Use MD5 of source file if available - only FLAC, MAC, OptimFROG and Wavpack4.
    public $option_sha1_data         = false;             // Get SHA1 sum of data part - slow.

    // Public variables
    public $filename;                                     // Filename of file being analysed.
    public $fp;                                           // Filepointer to file being analysed.
    public $info;                                         // Result array.

    // Protected variables
    protected $include_path;                              // getid3 include path.
    protected $warnings = array ();
    protected $iconv_present;

    // Class constants
    const VERSION           = '2.0.0b4';
    const FREAD_BUFFER_SIZE = 16384;                      // Read buffer size in bytes.
    const ICONV_TEST_STRING = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~€‚ƒ„…†‡ˆ‰Š‹ŒŽ‘’“”•–—˜™š›œžŸ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ';



    // Constructor - check PHP enviroment and load library.
    public function __construct() {

        // Static varibles - no need to recalc every time we new getid3.
        static $include_path;
        static $iconv_present;


        static $initialized;
        if ($initialized) {

            // Import static variables
            $this->include_path  = $include_path;
            $this->iconv_present = $iconv_present;

            // Run init checks only on first instance.
            return;
        }

        // Get include_path
        $this->include_path = $include_path = dirname(__FILE__) . '/';

        // Check for presence of iconv() and make sure it works (simpel test only).
        if (function_exists('iconv') && @iconv('UTF-16LE', 'ISO-8859-1', @iconv('ISO-8859-1', 'UTF-16LE', getid3::ICONV_TEST_STRING)) == getid3::ICONV_TEST_STRING) {
            $this->iconv_present = $iconv_present = true;
        }

        // iconv() not present - load replacement module.
        else {
            $this->include_module('lib.iconv_replacement');
            $this->iconv_present = $iconv_present = false;
        }


        // Require magic_quotes_runtime off
        if (get_magic_quotes_runtime()) {
            throw new getid3_exception('magic_quotes_runtime must be disabled before running getID3(). Surround getid3 block by set_magic_quotes_runtime(0) and set_magic_quotes_runtime(1).');
        }


        // Check memory limit.
        $memory_limit = ini_get('memory_limit');
        if (eregi('([0-9]+)M', $memory_limit, $matches)) {
            // could be stored as "16M" rather than 16777216 for example
            $memory_limit = $matches[1] * 1048576;
        }
        if ($memory_limit <= 0) {
            // Should not happen.
        } elseif ($memory_limit <= 4194304) {
            $this->warning('[SERIOUS] PHP has less than 4 Mb available memory and will very likely run out. Increase memory_limit in php.ini.');
        } elseif ($memory_limit <= 12582912) {
            $this->warning('PHP has less than 12 Mb available memory and might run out if all modules are loaded. Increase memory_limit in php.ini if needed.');
        }


        // Check safe_mode off
        if ((bool)ini_get('safe_mode')) {
            $this->warning('Safe mode is on, shorten support disabled, md5data/sha1data for ogg vorbis disabled, ogg vorbis/flac tag writing disabled.');
        }

        $initialized = true;
    }



    // Analyze file by name
    public function Analyze($filename) {

        // Init and save values
        $this->filename = $filename;
        $this->warnings = array ();

        // Init result array and set parameters
        $this->info = array ();
        $this->info['GETID3_VERSION'] = getid3::VERSION;

        // Remote files not supported
        if (preg_match('/^(ht|f)tp:\/\//', $filename)) {
            throw new getid3_exception('Remote files are not supported - please copy the file locally first.');
        }

        // Open local file
        if (!$this->fp = @fopen($filename, 'rb')) {
            throw new getid3_exception('Could not open file "'.$filename.'"');
        }

        // Set filesize related parameters
        $this->info['filesize']     = filesize($filename);
        $this->info['avdataoffset'] = 0;
        $this->info['avdataend']    = $this->info['filesize'];

        // Option_max_2gb_check
        if ($this->option_max_2gb_check) {
            // PHP doesn't support integers larger than 31-bit (~2GB)
            // filesize() simply returns (filesize % (pow(2, 32)), no matter the actual filesize
            // ftell() returns 0 if seeking to the end is beyond the range of unsigned integer
            fseek($this->fp, 0, SEEK_END);
            if ((($this->info['filesize'] != 0) && (ftell($this->fp) == 0)) ||
                ($this->info['filesize'] < 0) ||
                (ftell($this->fp) < 0)) {
                    unset($this->info['filesize']);
                    fclose($this->fp);
                    throw new getid3_exception('File is most likely larger than 2GB and is not supported by PHP.');
            }
        }


        // ID3v2 detection (NOT parsing) done to make fileformat easier.
        if (!$this->option_tag_id3v2) {

            fseek($this->fp, 0, SEEK_SET);
            $header = fread($this->fp, 10);
            if (substr($header, 0, 3) == 'ID3'  &&  strlen($header) == 10) {
                $this->info['id3v2']['header']        = true;
                $this->info['id3v2']['majorversion']  = ord($header{3});
                $this->info['id3v2']['minorversion']  = ord($header{4});
                $this->info['avdataoffset']          += getid3_lib::BigEndian2Int(substr($header, 6, 4), 1) + 10; // length of ID3v2 tag in 10-byte header doesn't include 10-byte header length
            }
        }


        // Handle tags
        foreach (array ("id3v2", "id3v1", "apetag", "lyrics3") as $tag_name) {

            $option_tag = 'option_tag_' . $tag_name;
            if ($this->$option_tag) {
                $this->include_module('tag.'.$tag_name);
                try {
                    $tag_class = 'getid3_' . $tag_name;
                    $tag = new $tag_class($this);
                    $tag->Analyze();
                }
                catch (getid3_exception $e) {
                    throw $e;
                }
            }
        }



        //// Determine file format by magic bytes in file header.

        // Read 32 kb file data
        fseek($this->fp, $this->info['avdataoffset'], SEEK_SET);
        $filedata = fread($this->fp, 32774);

        // Get huge FileFormatArray
        $file_format_array = getid3::GetFileFormatArray();

        // Identify file format - loop through $format_info and detect with reg expr
        foreach ($file_format_array as $name => $info) {

            if (preg_match('/'.$info['pattern'].'/s', $filedata)) {                         // The /s switch on preg_match() forces preg_match() NOT to treat newline (0x0A) characters as special chars but do a binary match

                // Format detected but not supported
                if (!@$info['module'] || !@$info['group']) {
                    fclose($this->fp);
                    $this->info['fileformat'] = $name;
                    $this->info['mime_type']  = $info['mime_type'];
                    $this->warning('Format only detected. Parsing not available yet.');
                    $this->info['warning'] = $this->warnings;
                    return $this->info;
                }

                $determined_format = $info;  // copy $info deleted by foreach()
                continue;
            }
        }

        // Unable to determine file format
        if (!@$determined_format) {

            // Too many mp3 encoders on the market put gabage in front of mpeg files
            // use assume format on these if format detection failed
            if (preg_match('/\.mp[123a]$/i', $filename)) {
                $determined_format = $file_format_array['mp3'];
            }

            else {
                fclose($this->fp);
                throw new getid3_exception('Unable to determine file format');
            }
        }

        // Free memory
        unset($file_format_array);

        // Check for illegal ID3 tags
        if (@$determined_format['fail_id3'] && (@$this->info['id3v1'] || @$this->info['id3v2'])) {
            if ($determined_format['fail_id3'] === 'ERROR') {
                fclose($this->fp);
                throw new getid3_exception('ID3 tags not allowed on this file type.');
            }
            elseif ($determined_format['fail_id3'] === 'WARNING') {
                @$this->info['id3v1'] and $this->warning('ID3v1 tags not allowed on this file type.');
                @$this->info['id3v2'] and $this->warning('ID3v2 tags not allowed on this file type.');
            }
        }

        // Check for illegal APE tags
        if (@$determined_format['fail_ape'] && @$this->info['tags']['ape']) {
            if ($determined_format['fail_ape'] === 'ERROR') {
                fclose($this->fp);
                throw new getid3_exception('APE tags not allowed on this file type.');
            } elseif ($determined_format['fail_ape'] === 'WARNING') {
                $this->warning('APE tags not allowed on this file type.');
            }
        }


        // Set mime type
        $this->info['mime_type'] = $determined_format['mime_type'];

        // Calc module file name
        $determined_format['include'] = 'module.'.$determined_format['group'].'.'.$determined_format['module'].'.php';

        // Supported format signature pattern detected, but module deleted.
        if (!file_exists($this->include_path.$determined_format['include'])) {
            fclose($this->fp);
            throw new getid3_exception('Format not supported, module, '.$determined_format['include'].', was removed.');
        }

        // Include module
        $this->include_module($determined_format['group'].'.'.$determined_format['module']);

        // Instantiate module class and analyze
        $class_name = 'getid3_'.$determined_format['module'];
        if (!class_exists($class_name)) {
            throw new getid3_exception('Format not supported, module, '.$determined_format['include'].', is corrupt.');
        }
        $class = new $class_name($this);

        try {
             $this->option_analyze and $class->Analyze();
            }
        catch (getid3_exception $e) {
            throw $e;
        }
        catch (Exception $e) {
            throw new getid3_exception('Corrupt file.');
        }

        // Close file
        fclose($this->fp);

        // Optional - Process all tags - copy to 'tags' and convert charsets
        if ($this->option_tags_process) {
            $this->HandleAllTags();
        }


        //// Optional - perform more calculations
        if ($this->option_extra_info) {

            // Set channelmode on audio
            if (@$this->info['audio']['channels'] == '1') {
                $this->info['audio']['channelmode'] = 'mono';
            } elseif (@$this->info['audio']['channels'] == '2') {
                $this->info['audio']['channelmode'] = 'stereo';
            }

            // Calculate combined bitrate - audio + video
            $combined_bitrate  = 0;
            $combined_bitrate += (isset($this->info['audio']['bitrate']) ? $this->info['audio']['bitrate'] : 0);
            $combined_bitrate += (isset($this->info['video']['bitrate']) ? $this->info['video']['bitrate'] : 0);
            if (($combined_bitrate > 0) && empty($this->info['bitrate'])) {
                $this->info['bitrate'] = $combined_bitrate;
            }
            if (!isset($this->info['playtime_seconds']) && !empty($this->info['bitrate'])) {
                $this->info['playtime_seconds'] = (($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['bitrate'];
            }

            // Set playtime string
            if (!empty($this->info['playtime_seconds']) && empty($this->info['playtime_string'])) {
                $this->info['playtime_string'] =  floor(round($this->info['playtime_seconds']) / 60) . ':' . str_pad(floor(round($this->info['playtime_seconds']) % 60), 2, 0, STR_PAD_LEFT);;
            }


            // CalculateCompressionRatioVideo() {
            if (@$this->info['video'] && @$this->info['video']['resolution_x'] && @$this->info['video']['resolution_y'] && @$this->info['video']['bits_per_sample']) {

                // From static image formats
                if (in_array($this->info['video']['dataformat'], array ('bmp', 'gif', 'jpeg', 'jpg', 'png', 'tiff'))) {
                    $frame_rate         = 1;
                    $bitrate_compressed = $this->info['filesize'] * 8;
                }

                // From video formats
                else {
                    $frame_rate         = @$this->info['video']['frame_rate'];
                    $bitrate_compressed = @$this->info['video']['bitrate'];
                }

                if ($frame_rate && $bitrate_compressed) {
                    $this->info['video']['compression_ratio'] = $bitrate_compressed / ($this->info['video']['resolution_x'] * $this->info['video']['resolution_y'] * $this->info['video']['bits_per_sample'] * $frame_rate);
                }
            }


            // CalculateCompressionRatioAudio() {
            if (@$this->info['audio']['bitrate'] && @$this->info['audio']['channels'] && @$this->info['audio']['sample_rate']) {
                $this->info['audio']['compression_ratio'] = $this->info['audio']['bitrate'] / ($this->info['audio']['channels'] * $this->info['audio']['sample_rate'] * (@$this->info['audio']['bits_per_sample'] ? $this->info['audio']['bits_per_sample'] : 16));
            }

            if (@$this->info['audio']['streams']) {
                foreach ($this->info['audio']['streams'] as $stream_number => $stream_data) {
                    if (@$stream_data['bitrate'] && @$stream_data['channels'] && @$stream_data['sample_rate']) {
                        $this->info['audio']['streams'][$stream_number]['compression_ratio'] = $stream_data['bitrate'] / ($stream_data['channels'] * $stream_data['sample_rate'] * (@$stream_data['bits_per_sample'] ? $stream_data['bits_per_sample'] : 16));
                    }
                }
            }


            // CalculateReplayGain() {
            if (@$this->info['replay_gain']) {
                if (!@$this->info['replay_gain']['reference_volume']) {
                     $this->info['replay_gain']['reference_volume'] = 89;
                }
                if (isset($this->info['replay_gain']['track']['adjustment'])) {
                    $this->info['replay_gain']['track']['volume'] = $this->info['replay_gain']['reference_volume'] - $this->info['replay_gain']['track']['adjustment'];
                }
                if (isset($this->info['replay_gain']['album']['adjustment'])) {
                    $this->info['replay_gain']['album']['volume'] = $this->info['replay_gain']['reference_volume'] - $this->info['replay_gain']['album']['adjustment'];
                }

                if (isset($this->info['replay_gain']['track']['peak'])) {
                    $this->info['replay_gain']['track']['max_noclip_gain'] = 0 - 20 * log10($this->info['replay_gain']['track']['peak']);
                }
                if (isset($this->info['replay_gain']['album']['peak'])) {
                    $this->info['replay_gain']['album']['max_noclip_gain'] = 0 - 20 * log10($this->info['replay_gain']['album']['peak']);
                }
            }


            // ProcessAudioStreams() {
            if (@!$this->info['audio']['streams'] && (@$this->info['audio']['bitrate'] || @$this->info['audio']['channels'] || @$this->info['audio']['sample_rate'])) {
                  foreach ($this->info['audio'] as $key => $value) {
                    if ($key != 'streams') {
                        $this->info['audio']['streams'][0][$key] = $value;
                    }
                }
            }
        }


        // Get the md5/sha1sum of the audio/video portion of the file - without ID3/APE/Lyrics3/etc header/footer tags.
        if ($this->option_md5_data || $this->option_sha1_data) {

            // Load data-hash library if needed
            $this->include_module('lib.data_hash');

            if ($this->option_sha1_data) {
                new getid3_lib_data_hash($this, 'sha1');
            }

            if ($this->option_md5_data) {

                // no md5_data_source or option disabled -- md5_data_source supported by FLAC, MAC, OptimFROG, Wavpack4
                if (!$this->option_md5_data_source || !@$this->info['md5_data_source']) {
                    new getid3_lib_data_hash($this, 'md5');
                }

                // copy md5_data_source to md5_data if option set to true
                elseif ($this->option_md5_data_source && @$this->info['md5_data_source']) {
                    $this->info['md5_data'] = $this->info['md5_data_source'];
                }
            }
        }

        // Set warnings
        if ($this->warnings) {
            $this->info['warning'] = $this->warnings;
        }

        // Return result
        return $this->info;
    }



    // Return array of warnings
    public function warnings() {

        return $this->warnings;
    }



    // Add warning(s) to $this->warnings[]
    public function warning($message) {

        if (is_array($message)) {
            $this->warnings = array_merge($this->warnings, $message);
        }
        else {
            $this->warnings[] = $message;
        }
    }



    //  Clear all warnings when cloning
    public function __clone() {

        $this->warnings = array ();

        // Copy info array, otherwise it will be a reference.
        $temp = $this->info;
        unset($this->info);
        $this->info = $temp;
    }



    // Convert string between charsets -- iconv() wrapper
    public function iconv($in_charset, $out_charset, $string, $drop01 = false) {

        if ($drop01 && ($string === "\x00" || $string === "\x01")) {
            return '';
        }


        if (!$this->iconv_present) {
            return getid3_iconv_replacement::iconv($in_charset, $out_charset, $string);
        }


        // iconv() present
        if ($result = @iconv($in_charset, $out_charset.'//TRANSLIT', $string)) {

            if ($out_charset == 'ISO-8859-1') {
                return rtrim($result, "\x00");
            }
            return $result;
        }

        $this->warning('iconv() was unable to convert the string: "' . $string . '" from ' . $in_charset . ' to ' . $out_charset);
        return $string;
    }



    public function include_module($name) {

        if (!file_exists($this->include_path.'module.'.$name.'.php')) {
            throw new getid3_exception('Required module.'.$name.'.php is missing.');
        }

        include_once($this->include_path.'module.'.$name.'.php');
    }



    public function include_module_optional($name) {

        if (!file_exists($this->include_path.'module.'.$name.'.php')) {
            return;
        }

        include_once($this->include_path.'module.'.$name.'.php');
        return true;
    }


    // Return array containing information about all supported formats
    public static function GetFileFormatArray() {

        static $format_info = array (

                // Audio formats

                // AC-3   - audio      - Dolby AC-3 / Dolby Digital
                'ac3'  => array (
                            'pattern'   => '^\x0B\x77',
                            'group'     => 'audio',
                            'module'    => 'ac3',
                            'mime_type' => 'audio/ac3',
                          ),

                // AAC  - audio       - Advanced Audio Coding (AAC) - ADIF format
                'adif' => array (
                            'pattern'   => '^ADIF',
                            'group'     => 'audio',
                            'module'    => 'aac_adif',
                            'mime_type' => 'application/octet-stream',
                            'fail_ape'  => 'WARNING',
                          ),


                // AAC  - audio       - Advanced Audio Coding (AAC) - ADTS format (very similar to MP3)
                'adts' => array (
                            'pattern'   => '^\xFF[\xF0-\xF1\xF8-\xF9]',
                            'group'     => 'audio',
                            'module'    => 'aac_adts',
                            'mime_type' => 'application/octet-stream',
                            'fail_ape'  => 'WARNING',
                          ),


                // AU   - audio       - NeXT/Sun AUdio (AU)
                'au'   => array (
                            'pattern'   => '^\.snd',
                            'group'     => 'audio',
                            'module'    => 'au',
                            'mime_type' => 'audio/basic',
                          ),

                // AVR  - audio       - Audio Visual Research
                'avr'  => array (
                            'pattern'   => '^2BIT',
                            'group'     => 'audio',
                            'module'    => 'avr',
                            'mime_type' => 'application/octet-stream',
                          ),

                // BONK - audio       - Bonk v0.9+
                'bonk' => array (
                            'pattern'   => '^\x00(BONK|INFO|META| ID3)',
                            'group'     => 'audio',
                            'module'    => 'bonk',
                            'mime_type' => 'audio/xmms-bonk',
                          ),

                // DTS  - audio       - Dolby Theatre System
				'dts'  => array(
							'pattern'   => '^\x7F\xFE\x80\x01',
							'group'     => 'audio',
							'module'    => 'dts',
							'mime_type' => 'audio/dts',
						),

                // FLAC - audio       - Free Lossless Audio Codec
                'flac' => array (
                            'pattern'   => '^fLaC',
                            'group'     => 'audio',
                            'module'    => 'xiph',
                            'mime_type' => 'audio/x-flac',
                          ),

                // LA   - audio       - Lossless Audio (LA)
                'la'   => array (
                            'pattern'   => '^LA0[2-4]',
                            'group'     => 'audio',
                            'module'    => 'la',
                            'mime_type' => 'application/octet-stream',
                          ),

                // LPAC - audio       - Lossless Predictive Audio Compression (LPAC)
                'lpac' => array (
                            'pattern'   => '^LPAC',
                            'group'     => 'audio',
                            'module'    => 'lpac',
                            'mime_type' => 'application/octet-stream',
                          ),

                // MIDI - audio       - MIDI (Musical Instrument Digital Interface)
                'midi' => array (
                            'pattern'   => '^MThd',
                            'group'     => 'audio',
                            'module'    => 'midi',
                            'mime_type' => 'audio/midi',
                          ),

                // MAC  - audio       - Monkey's Audio Compressor
                'mac'  => array (
                            'pattern'   => '^MAC ',
                            'group'     => 'audio',
                            'module'    => 'monkey',
                            'mime_type' => 'application/octet-stream',
                          ),

                // MOD  - audio       - MODule (assorted sub-formats)
                'mod'  => array (
                            'pattern'   => '^.{1080}(M.K.|[5-9]CHN|[1-3][0-9]CH)',
                            'mime_type' => 'audio/mod',
                          ),

                // MOD  - audio       - MODule (Impulse Tracker)
                'it'   => array (
                            'pattern'   => '^IMPM',
                            'mime_type' => 'audio/it',
                          ),

                // MOD  - audio       - MODule (eXtended Module, various sub-formats)
                'xm'   => array (
                            'pattern'   => '^Extended Module',
                            'mime_type' => 'audio/xm',
                          ),

                // MOD  - audio       - MODule (ScreamTracker)
                's3m'  => array (
                            'pattern'   => '^.{44}SCRM',
                            'mime_type' => 'audio/s3m',
                          ),

                // MPC  - audio       - Musepack / MPEGplus SV7+
                'mpc'  => array (
                            'pattern'   => '^(MP\+)',
                            'group'     => 'audio',
                            'module'    => 'mpc',
                            'mime_type' => 'audio/x-musepack',
                          ),

                // MPC  - audio       - Musepack / MPEGplus SV4-6
                'mpc_old' => array (
                            'pattern'   => '^([\x00\x01\x10\x11\x40\x41\x50\x51\x80\x81\x90\x91\xC0\xC1\xD0\xD1][\x20-37][\x00\x20\x40\x60\x80\xA0\xC0\xE0])',
                            'group'     => 'audio',
                            'module'    => 'mpc_old',
                            'mime_type' => 'application/octet-stream',
                          ),


                // MP3  - audio       - MPEG-audio Layer 3 (very similar to AAC-ADTS)
                'mp3'  => array (
                            'pattern'   => '^\xFF[\xE2-\xE7\xF2-\xF7\xFA-\xFF][\x00-\xEB]',
                            'group'     => 'audio',
                            'module'    => 'mp3',
                            'mime_type' => 'audio/mpeg',
                          ),

                // OFR  - audio       - OptimFROG
                'ofr'  => array (
                            'pattern'   => '^(\*RIFF|OFR)',
                            'group'     => 'audio',
                            'module'    => 'optimfrog',
                            'mime_type' => 'application/octet-stream',
                          ),

                // RKAU - audio       - RKive AUdio compressor
                'rkau' => array (
                            'pattern'   => '^RKA',
                            'group'     => 'audio',
                            'module'    => 'rkau',
                            'mime_type' => 'application/octet-stream',
                          ),

                // SHN  - audio       - Shorten
                'shn'  => array (
                            'pattern'   => '^ajkg',
                            'group'     => 'audio',
                            'module'    => 'shorten',
                            'mime_type' => 'audio/xmms-shn',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // TTA  - audio       - TTA Lossless Audio Compressor (http://tta.corecodec.org)
                'tta'  => array (
                            'pattern'   => '^TTA',  // could also be '^TTA(\x01|\x02|\x03|2|1)'
                            'group'     => 'audio',
                            'module'    => 'tta',
                            'mime_type' => 'application/octet-stream',
                          ),

                // VOC  - audio       - Creative Voice (VOC)
                'voc'  => array (
                            'pattern'   => '^Creative Voice File',
                            'group'     => 'audio',
                            'module'    => 'voc',
                            'mime_type' => 'audio/voc',
                          ),

                // VQF  - audio       - transform-domain weighted interleave Vector Quantization Format (VQF)
                'vqf'  => array (
                            'pattern'   => '^TWIN',
                            'group'     => 'audio',
                            'module'    => 'vqf',
                            'mime_type' => 'application/octet-stream',
                          ),

                // WV  - audio        - WavPack (v4.0+)
                'vw'  => array(
                            'pattern'   => '^wvpk',
                            'group'     => 'audio',
                            'module'    => 'wavpack',
                            'mime_type' => 'application/octet-stream',
                          ),


                // Audio-Video formats

                // ASF  - audio/video - Advanced Streaming Format, Windows Media Video, Windows Media Audio
                'asf'  => array (
                            'pattern'   => '^\x30\x26\xB2\x75\x8E\x66\xCF\x11\xA6\xD9\x00\xAA\x00\x62\xCE\x6C',
                            'group'     => 'audio-video',
                            'module'    => 'asf',
                            'mime_type' => 'video/x-ms-asf',
                          ),

                // BINK  - audio/video - Bink / Smacker
                'bink' => array(
                            'pattern'   => '^(BIK|SMK)',
                            'mime_type' => 'application/octet-stream',
                          ),

                // FLV  - audio/video - FLash Video
                'flv' => array(
                            'pattern'   => '^FLV\x01',
                            'group'     => 'audio-video',
                            'module'    => 'flv',
                            'mime_type' => 'video/x-flv',
                          ),

                // MKAV - audio/video - Mastroka
                'matroska' => array (
                            'pattern'   => '^\x1A\x45\xDF\xA3',
                            'mime_type' => 'application/octet-stream',
                          ),

                // MPEG - audio/video - MPEG (Moving Pictures Experts Group)
                'mpeg' => array (
                            'pattern'   => '^\x00\x00\x01(\xBA|\xB3)',
                            'group'     => 'audio-video',
                            'module'    => 'mpeg',
                            'mime_type' => 'video/mpeg',
                          ),

                // NSV  - audio/video - Nullsoft Streaming Video (NSV)
                'nsv'  => array (
                            'pattern'   => '^NSV[sf]',
                            'group'     => 'audio-video',
                            'module'    => 'nsv',
                            'mime_type' => 'application/octet-stream',
                          ),

                // Ogg  - audio/video - Ogg (Ogg Vorbis, OggFLAC, Speex, Ogg Theora(*), Ogg Tarkin(*))
                'ogg'  => array (
                            'pattern'   => '^OggS',
                            'group'     => 'audio',
                            'module'    => 'xiph',
                            'mime_type' => 'application/ogg',
                            'fail_id3'  => 'WARNING',
                            'fail_ape'  => 'WARNING',
                          ),

                // QT   - audio/video - Quicktime
                'quicktime' => array (
                            'pattern'   => '^.{4}(cmov|free|ftyp|mdat|moov|pnot|skip|wide)',
                            'group'     => 'audio-video',
                            'module'    => 'quicktime',
                            'mime_type' => 'video/quicktime',
                          ),

                // RIFF - audio/video - Resource Interchange File Format (RIFF) / WAV / AVI / CD-audio / SDSS = renamed variant used by SmartSound QuickTracks (www.smartsound.com) / FORM = Audio Interchange File Format (AIFF)
                'riff' => array (
                            'pattern'   => '^(RIFF|SDSS|FORM)',
                            'group'     => 'audio-video',
                            'module'    => 'riff',
                            'mime_type' => 'audio/x-wave',
                            'fail_ape'  => 'WARNING',
                          ),

                // Real - audio/video - RealAudio, RealVideo
                'real' => array (
                            'pattern'   => '^(\.RMF|.ra)',
                            'group'     => 'audio-video',
                            'module'    => 'real',
                            'mime_type' => 'audio/x-realaudio',
                          ),

                // SWF - audio/video - ShockWave Flash
                'swf' => array (
                            'pattern'   => '^(F|C)WS',
                            'group'     => 'audio-video',
                            'module'    => 'swf',
                            'mime_type' => 'application/x-shockwave-flash',
                          ),


                // Still-Image formats

                // BMP  - still image - Bitmap (Windows, OS/2; uncompressed, RLE8, RLE4)
                'bmp'  => array (
                            'pattern'   => '^BM',
                            'group'     => 'graphic',
                            'module'    => 'bmp',
                            'mime_type' => 'image/bmp',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // GIF  - still image - Graphics Interchange Format
                'gif'  => array (
                            'pattern'   => '^GIF',
                            'group'     => 'graphic',
                            'module'    => 'gif',
                            'mime_type' => 'image/gif',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // JPEG - still image - Joint Photographic Experts Group (JPEG)
                'jpeg'  => array (
                            'pattern'   => '^\xFF\xD8\xFF',
                            'group'     => 'graphic',
                            'module'    => 'jpeg',
                            'mime_type' => 'image/jpeg',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // PCD  - still image - Kodak Photo CD
                'pcd'  => array (
                            'pattern'   => '^.{2048}PCD_IPI\x00',
                            'group'     => 'graphic',
                            'module'    => 'pcd',
                            'mime_type' => 'image/x-photo-cd',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),


                // PNG  - still image - Portable Network Graphics (PNG)
                'png'  => array (
                            'pattern'   => '^\x89\x50\x4E\x47\x0D\x0A\x1A\x0A',
                            'group'     => 'graphic',
                            'module'    => 'png',
                            'mime_type' => 'image/png',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),


                // SVG  - still image - Scalable Vector Graphics (SVG)
				'svg'  => array(
							'pattern'   => '<!DOCTYPE svg PUBLIC ',
							'mime_type' => 'image/svg+xml',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


                // TIFF  - still image - Tagged Information File Format (TIFF)
                'tiff' => array (
                            'pattern'   => '^(II\x2A\x00|MM\x00\x2A)',
                            'group'     => 'graphic',
                            'module'    => 'tiff',
                            'mime_type' => 'image/tiff',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),


                // Data formats

                'exe'  => array(
                            'pattern'   => '^MZ',
                            'mime_type' => 'application/octet-stream',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // ISO  - data        - International Standards Organization (ISO) CD-ROM Image
                'iso'  => array (
                            'pattern'   => '^.{32769}CD001',
                            'group'     => 'misc',
                            'module'    => 'iso',
                            'mime_type' => 'application/octet-stream',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // RAR  - data        - RAR compressed data
                'rar'  => array(
                            'pattern'   => '^Rar\!',
                            'mime_type' => 'application/octet-stream',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // SZIP - audio       - SZIP compressed data
                'szip' => array (
                            'pattern'   => '^SZ\x0A\x04',
                            'group'     => 'archive',
                            'module'    => 'szip',
                            'mime_type' => 'application/octet-stream',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // TAR  - data        - TAR compressed data
                'tar'  => array(
                            'pattern'   => '^.{100}[0-9\x20]{7}\x00[0-9\x20]{7}\x00[0-9\x20]{7}\x00[0-9\x20\x00]{12}[0-9\x20\x00]{12}',
                            'group'     => 'archive',
                            'module'    => 'tar',
                            'mime_type' => 'application/x-tar',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),

                // GZIP  - data        - GZIP compressed data
                'gz'  => array(
                            'pattern'   => '^\x1F\x8B\x08',
                            'group'     => 'archive',
                            'module'    => 'gzip',
                            'mime_type' => 'application/x-gzip',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),


                // ZIP  - data        - ZIP compressed data
                'zip'  => array (
                            'pattern'   => '^PK\x03\x04',
                            'group'     => 'archive',
                            'module'    => 'zip',
                            'mime_type' => 'application/zip',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),


                // PAR2 - data        - Parity Volume Set Specification 2.0
                'par2' => array (
                			'pattern'   => '^PAR2\x00PKT',
							'mime_type' => 'application/octet-stream',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


                 // PDF  - data       - Portable Document Format
                 'pdf' => array(
                            'pattern'   => '^\x25PDF',
                            'mime_type' => 'application/pdf',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                           ),

                 // DOC  - data       - Microsoft Word
                 'msoffice' => array(
                            'pattern'   => '^\xD0\xCF\x11\xE0', // D0CF11E == DOCFILE == Microsoft Office Document
                            'mime_type' => 'application/octet-stream',
                            'fail_id3'  => 'ERROR',
                            'fail_ape'  => 'ERROR',
                          ),
            );

        return $format_info;
    }



    // Recursive over array - converts array to $encoding charset from $this->encoding
    function CharConvert(&$array, $encoding) {

        // Identical encoding - end here
        if ($encoding == $this->encoding) {
            return;
        }

        // Loop thru array
        foreach ($array as $key => $value) {

            // Go recursive
            if (is_array($value)) {
                $this->CharConvert($array[$key], $encoding);
            }

            // Convert string
            elseif (is_string($value)) {
                $array[$key] = $this->iconv($encoding, $this->encoding, $value);
            }
        }
    }



    // Convert and copy tags
    protected function HandleAllTags() {

        // Key name => array (tag name, character encoding)
        static $tags = array (
            'asf'       => array ('asf',           'UTF-16LE'),
            'midi'      => array ('midi',          'ISO-8859-1'),
            'nsv'       => array ('nsv',           'ISO-8859-1'),
            'ogg'       => array ('vorbiscomment', 'UTF-8'),
            'png'       => array ('png',           'UTF-8'),
            'tiff'      => array ('tiff',          'ISO-8859-1'),
            'quicktime' => array ('quicktime',     'ISO-8859-1'),
            'real'      => array ('real',          'ISO-8859-1'),
            'vqf'       => array ('vqf',           'ISO-8859-1'),
            'zip'       => array ('zip',           'ISO-8859-1'),
            'riff'      => array ('riff',          'ISO-8859-1'),
            'lyrics3'   => array ('lyrics3',       'ISO-8859-1'),
            'id3v1'     => array ('id3v1',         ''),            // change below - cannot assign variable to static array
            'id3v2'     => array ('id3v2',         'UTF-8'),       // module converts all frames to UTF-8
            'ape'       => array ('ape',           'UTF-8')
        );
        $tags['id3v1'][1] = $this->encoding_id3v1;

        // Loop thru tags array
        foreach ($tags as $comment_name => $tag_name_encoding_array) {
            list($tag_name, $encoding) = $tag_name_encoding_array;

            // Fill in default encoding type if not already present
            @$this->info[$comment_name]  and  $this->info[$comment_name]['encoding'] = $encoding;

            // Copy comments if key name set
            if (@$this->info[$comment_name]['comments']) {

                foreach ($this->info[$comment_name]['comments'] as $tag_key => $value_array) {
                    foreach ($value_array as $key => $value) {
                        if (strlen(trim($value)) > 0) {
                            $this->info['tags'][$tag_name][trim($tag_key)][] = $value; // do not trim!! Unicode characters will get mangled if trailing nulls are removed!
                        }
                    }

                }

                if (!@$this->info['tags'][$tag_name]) {
                    // comments are set but contain nothing but empty strings, so skip
                    continue;
                }

                $this->CharConvert($this->info['tags'][$tag_name], $encoding);
            }
        }


        // Merge comments from ['tags'] into common ['comments']
        if (@$this->info['tags']) {

            foreach ($this->info['tags'] as $tag_type => $tag_array) {

                foreach ($tag_array as $tag_name => $tagdata) {

                    foreach ($tagdata as $key => $value) {

                        if (!empty($value)) {

                            if (empty($this->info['comments'][$tag_name])) {

                                // fall through and append value
                            }
                            elseif ($tag_type == 'id3v1') {

                                $new_value_length = strlen(trim($value));
                                foreach ($this->info['comments'][$tag_name] as $existing_key => $existing_value) {
                                    $old_value_length = strlen(trim($existing_value));
                                    if (($new_value_length <= $old_value_length) && (substr($existing_value, 0, $new_value_length) == trim($value))) {
                                        // new value is identical but shorter-than (or equal-length to) one already in comments - skip
                                        break 2;
                                    }
                                }
                            }
                            else {

                                $new_value_length = strlen(trim($value));
                                foreach ($this->info['comments'][$tag_name] as $existing_key => $existing_value) {
                                    $old_value_length = strlen(trim($existing_value));
                                    if (($new_value_length > $old_value_length) && (substr(trim($value), 0, strlen($existing_value)) == $existing_value)) {
                                        $this->info['comments'][$tag_name][$existing_key] = trim($value);
                                        break 2;
                                    }
                                }
                            }

                            if (empty($this->info['comments'][$tag_name]) || !in_array(trim($value), $this->info['comments'][$tag_name])) {
                                $this->info['comments'][$tag_name][] = trim($value);
                            }
                        }
                    }
                }
            }
        }

        return true;
    }
}


abstract class getid3_handler
{

    protected $getid3;                          // pointer

    protected $data_string_flag = false;        // analyzing filepointer or string
    protected $data_string;                     // string to analyze
    protected $data_string_position = 0;        // seek position in string


    public function __construct(getID3 $getid3) {

        $this->getid3 = $getid3;
    }


    // Analyze from file pointer
    abstract public function Analyze();



    // Analyze from string instead
    public function AnalyzeString(&$string) {

        // Enter string mode
        $this->data_string_flag = true;
        $this->data_string      = $string;

        // Save info
        $saved_avdataoffset = $this->getid3->info['avdataoffset'];
        $saved_avdataend    = $this->getid3->info['avdataend'];
        $saved_filesize     = $this->getid3->info['filesize'];

        // Reset some info
        $this->getid3->info['avdataoffset'] = 0;
        $this->getid3->info['avdataend']    = $this->getid3->info['filesize'] = strlen($string);

        // Analyze
        $this->Analyze();

        // Restore some info
        $this->getid3->info['avdataoffset'] = $saved_avdataoffset;
        $this->getid3->info['avdataend']    = $saved_avdataend;
        $this->getid3->info['filesize']     = $saved_filesize;

        // Exit string mode
        $this->data_string_flag = false;
    }


    protected function ftell() {

        if ($this->data_string_flag) {
            return $this->data_string_position;
        }
        return ftell($this->getid3->fp);
    }


    protected function fread($bytes) {

        if ($this->data_string_flag) {
            $this->data_string_position += $bytes;
            return substr($this->data_string, $this->data_string_position - $bytes, $bytes);
        }
        return fread($this->getid3->fp, $bytes);
    }


    protected function fseek($bytes, $whence = SEEK_SET) {

        if ($this->data_string_flag) {
            switch ($whence) {
                case SEEK_SET:
                    $this->data_string_position = $bytes;
                    return;

                case SEEK_CUR:
                    $this->data_string_position += $bytes;
                    return;

                case SEEK_END:
                    $this->data_string_position = strlen($this->data_string) + $bytes;
                    return;
            }
        }
        return fseek($this->getid3->fp, $bytes, $whence);
    }

}




abstract class getid3_handler_write
{
    protected $filename;
    protected $user_abort;

    private $fp_lock;
    private $owner;
    private $group;
    private $perms;


    public function __construct($filename) {

        if (!file_exists($filename)) {
            throw new getid3_exception('File does not exist: "' . $filename . '"');
        }

        if (!is_writeable($filename)) {
            throw new getid3_exception('File is not writeable: "' . $filename . '"');
        }

        if (!is_writeable(dirname($filename))) {
            throw new getid3_exception('Directory is not writeable: ' . dirname($filename) . ' (need to write lock file).');
        }

        $this->user_abort = ignore_user_abort(true);

        $this->fp_lock = fopen($filename . '.getid3.lock', 'w');
        flock($this->fp_lock, LOCK_EX);

        $this->filename = $filename;
    }


    public function __destruct() {

        flock($this->fp_lock, LOCK_UN);
        fclose($this->fp_lock);
        unlink($this->filename . '.getid3.lock');

        ignore_user_abort($this->user_abort);
    }
    
    
    protected function save_permissions() {
        
        $this->owner = fileowner($this->filename);
        $this->group = filegroup($this->filename);
        $this->perms = fileperms($this->filename);
    }
    
    
    protected function restore_permissions() {
        
        @chown($this->filename, $this->owner);
        @chgrp($this->filename, $this->group);
        @chmod($this->filename, $this->perms);
    }


    abstract public function read();

    abstract public function write();

    abstract public function remove();

}




class getid3_exception extends Exception
{
    public $message;

}




class getid3_lib
{

    // Convert Little Endian byte string to int - max 32 bits
    public static function LittleEndian2Int($byte_word, $signed = false) {

        return getid3_lib::BigEndian2Int(strrev($byte_word), $signed);
    }



    // Convert number to Little Endian byte string
    public static function LittleEndian2String($number, $minbytes=1, $synchsafe=false) {
        $intstring = '';
        while ($number > 0) {
            if ($synchsafe) {
                $intstring = $intstring.chr($number & 127);
                $number >>= 7;
            } else {
                $intstring = $intstring.chr($number & 255);
                $number >>= 8;
            }
        }
        return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
    }



    // Convert Big Endian byte string to int - max 32 bits
    public static function BigEndian2Int($byte_word, $signed = false) {

        $int_value = 0;
        $byte_wordlen = strlen($byte_word);

        for ($i = 0; $i < $byte_wordlen; $i++) {
            $int_value += ord($byte_word{$i}) * pow(256, ($byte_wordlen - 1 - $i));
        }

        if ($signed) {
            $sign_mask_bit = 0x80 << (8 * ($byte_wordlen - 1));
            if ($int_value & $sign_mask_bit) {
                $int_value = 0 - ($int_value & ($sign_mask_bit - 1));
            }
        }

        return $int_value;
    }



    // Convert Big Endian byte sybc safe string to int - max 32 bits
    public static function BigEndianSyncSafe2Int($byte_word) {

        $int_value = 0;
        $byte_wordlen = strlen($byte_word);

        // disregard MSB, effectively 7-bit bytes
        for ($i = 0; $i < $byte_wordlen; $i++) {
            $int_value = $int_value | (ord($byte_word{$i}) & 0x7F) << (($byte_wordlen - 1 - $i) * 7);
        }
        return $int_value;
    }



    // Convert Big Endian byte string to bit string
    public static function BigEndian2Bin($byte_word) {

        $bin_value = '';
        $byte_wordlen = strlen($byte_word);
        for ($i = 0; $i < $byte_wordlen; $i++) {
            $bin_value .= str_pad(decbin(ord($byte_word{$i})), 8, '0', STR_PAD_LEFT);
        }
        return $bin_value;
    }



    public static function BigEndian2Float($byte_word) {

		// ANSI/IEEE Standard 754-1985, Standard for Binary Floating Point Arithmetic
		// http://www.psc.edu/general/software/packages/ieee/ieee.html
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee.html

		$bit_word = getid3_lib::BigEndian2Bin($byte_word);
		if (!$bit_word) {
            return 0;
        }
		$sign_bit = $bit_word{0};

		switch (strlen($byte_word) * 8) {
			case 32:
				$exponent_bits = 8;
				$fraction_bits = 23;
				break;

			case 64:
				$exponent_bits = 11;
				$fraction_bits = 52;
				break;

			case 80:
				// 80-bit Apple SANE format
				// http://www.mactech.com/articles/mactech/Vol.06/06.01/SANENormalized/
				$exponent_string = substr($bit_word, 1, 15);
				$is_normalized = intval($bit_word{16});
				$fraction_string = substr($bit_word, 17, 63);
				$exponent = pow(2, getid3_lib::Bin2Dec($exponent_string) - 16383);
				$fraction = $is_normalized + getid3_lib::DecimalBinary2Float($fraction_string);
				$float_value = $exponent * $fraction;
				if ($sign_bit == '1') {
					$float_value *= -1;
				}
				return $float_value;
				break;

			default:
				return false;
				break;
		}
		$exponent_string = substr($bit_word, 1, $exponent_bits);
		$fraction_string = substr($bit_word, $exponent_bits + 1, $fraction_bits);
		$exponent = bindec($exponent_string);
		$fraction = bindec($fraction_string);

		if (($exponent == (pow(2, $exponent_bits) - 1)) && ($fraction != 0)) {
			// Not a Number
			$float_value = false;
		} elseif (($exponent == (pow(2, $exponent_bits) - 1)) && ($fraction == 0)) {
			if ($sign_bit == '1') {
				$float_value = '-infinity';
			} else {
				$float_value = '+infinity';
			}
		} elseif (($exponent == 0) && ($fraction == 0)) {
			if ($sign_bit == '1') {
				$float_value = -0;
			} else {
				$float_value = 0;
			}
			$float_value = ($sign_bit ? 0 : -0);
		} elseif (($exponent == 0) && ($fraction != 0)) {
			// These are 'unnormalized' values
			$float_value = pow(2, (-1 * (pow(2, $exponent_bits - 1) - 2))) * getid3_lib::DecimalBinary2Float($fraction_string);
			if ($sign_bit == '1') {
				$float_value *= -1;
			}
		} elseif ($exponent != 0) {
			$float_value = pow(2, ($exponent - (pow(2, $exponent_bits - 1) - 1))) * (1 + getid3_lib::DecimalBinary2Float($fraction_string));
			if ($sign_bit == '1') {
				$float_value *= -1;
			}
		}
		return (float) $float_value;
	}



	public static function LittleEndian2Float($byte_word) {

		return getid3_lib::BigEndian2Float(strrev($byte_word));
	}



	public static function DecimalBinary2Float($binary_numerator) {
		$numerator   = bindec($binary_numerator);
		$denominator = bindec('1'.str_repeat('0', strlen($binary_numerator)));
		return ($numerator / $denominator);
	}


	public static function PrintHexBytes($string, $hex=true, $spaces=true, $html_safe=true) {

        $return_string = '';
        for ($i = 0; $i < strlen($string); $i++) {
            if ($hex) {
                $return_string .= str_pad(dechex(ord($string{$i})), 2, '0', STR_PAD_LEFT);
            } else {
                $return_string .= ' '.(ereg("[\x20-\x7E]", $string{$i}) ? $string{$i} : '¤');
            }
            if ($spaces) {
                $return_string .= ' ';
            }
        }
        if ($html_safe) {
            $return_string = htmlentities($return_string);
        }
        return $return_string;
    }



    // Process header data string - read several values with algorithm and add to target
    //   algorithm is one one the getid3_lib::Something2Something() function names
    //   parts_array is  index => length    -  $target[index] = algorithm(substring(data))
    //   - OR just substring(data) if length is negative!
    //  indexes == 'IGNORE**' are ignored

    public static function ReadSequence($algorithm, &$target, &$data, $offset, $parts_array) {

        // Loop thru $parts_array
        foreach ($parts_array as $target_string => $length) {

            // Add to target
            if (!strstr($target_string, 'IGNORE')) {

                // substr(....length)
                if ($length < 0) {
                    $target[$target_string] = substr($data, $offset, -$length);
                }

                // algorithm(substr(...length))
                else {
                    $target[$target_string] = getid3_lib::$algorithm(substr($data, $offset, $length));
                }
            }

            // Move pointer
            $offset += abs($length);
        }
    }

}



class getid3_lib_replaygain
{

    public static function NameLookup($name_code) {

        static $lookup = array (
            0 => 'not set',
            1 => 'Track Gain Adjustment',
            2 => 'Album Gain Adjustment'
        );

        return @$lookup[$name_code];
    }



    public static function OriginatorLookup($originator_code) {

        static $lookup = array (
            0 => 'unspecified',
            1 => 'pre-set by artist/producer/mastering engineer',
            2 => 'set by user',
            3 => 'determined automatically'
        );

        return @$lookup[$originator_code];
    }



    public static function AdjustmentLookup($raw_adjustment, $sign_bit) {

        return (float)$raw_adjustment / 10 * ($sign_bit == 1 ? -1 : 1);
    }



    public static function GainString($name_code, $originator_code, $replaygain) {

        $sign_bit = $replaygain < 0 ? 1 : 0;

        $stored_replaygain = intval(round($replaygain * 10));
        $gain_string  = str_pad(decbin($name_code), 3, '0', STR_PAD_LEFT);
        $gain_string .= str_pad(decbin($originator_code), 3, '0', STR_PAD_LEFT);
        $gain_string .= $sign_bit;
        $gain_string .= str_pad(decbin($stored_replaygain), 9, '0', STR_PAD_LEFT);

        return $gain_string;
    }

}




?>