<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

 /**
  * Waveform code generation license:
  *
  *
  * Copyright (c) 2011, Andrew Freiday
  * All rights reserved.
  *
  * Redistribution and use in source and binary forms, with or without modification,
  * are permitted provided that the following conditions are met:
  *
  * - Redistributions of source code must retain the above copyright notice,
  *     this list of conditions and the following disclaimer.
  * - Redistributions in binary form must reproduce the above copyright notice,
  *     this list of conditions and the following disclaimer in the documentation and/or
  *     other materials provided with the distribution.
  *
  * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
  * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
  * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL
  * THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
  * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
  * GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
  * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
  * POSSIBILITY OF SUCH DAMAGE.
  *
  *
  * https://github.com/afreiday/php-waveform-png
  *
  */

class Waveform
{
    public $id;

    /**
     * Constructor
     */
    private function __construct()
    {
        // Static
        return false;
    } // Constructor

    /**
     * Get a song waveform.
     * @param int $song_id
     * @return binary|string|null
     */
    public static function get($song_id)
    {
        $song     = new Song($song_id);
        $waveform = null;

        if ($song->id) {
            $song->format();
            $waveform = $song->waveform;
            if (!$waveform) {
                $catalog = Catalog::create_from_id($song->catalog);
                if ($catalog->get_type() == 'local') {
                    $transcode_to  = 'wav';
                    $transcode_cfg = AmpConfig::get('transcode');
                    $valid_types   = $song->get_stream_types();

                    if ($song->type != $transcode_to) {
                        $basedir = Core::get_tmp_dir();
                        if ($basedir) {
                            if ($transcode_cfg != 'never' && in_array('transcode', $valid_types)) {
                                $tmpfile = tempnam($basedir, $transcode_to);

                                $tfp = fopen($tmpfile, 'wb');
                                if (!is_resource($tfp)) {
                                    debug_event('waveform', "Failed to open " . $tmpfile, 3);

                                    return null;
                                }

                                $transcoder = Stream::start_transcode($song, $transcode_to);
                                $fp         = $transcoder['handle'];
                                if (!is_resource($fp)) {
                                    debug_event('waveform', "Failed to open " . $song->file . " for waveform.", 3);

                                    return null;
                                }

                                do {
                                    $buf = fread($fp, 2048);
                                    fwrite($tfp, $buf);
                                } while (!feof($fp));

                                fclose($fp);
                                fclose($tfp);

                                Stream::kill_process($transcoder);

                                $waveform = self::create_waveform($tmpfile);
                                //$waveform = self::create_waveform("C:\\tmp\\test.wav");

                                @unlink($tmpfile);
                            } else {
                                debug_event('waveform', 'transcode setting to wav required for waveform.', '3');
                            }
                        } else {
                            debug_event('waveform', 'tmp_dir_path setting required for waveform.', '3');
                        }
                    }
                    // Already wav file, no transcode required
                    else {
                        $waveform = self::create_waveform($song->file);
                    }
                }

                if ($waveform) {
                    self::save_to_db($song_id, $waveform);
                }
            }
        }

        return $waveform;
    }

    protected static function findValues($byte1, $byte2)
    {
        $byte1 = hexdec(bin2hex($byte1));
        $byte2 = hexdec(bin2hex($byte2));

        return ($byte1 + ($byte2 * 256));
    }

    /**
     * Great function slightly modified as posted by Minux at
     * http://forums.clantemplates.com/showthread.php?t=133805
     * @param string $input
     * @return array
     */
    protected static function html2rgb($input)
    {
        $input=($input[0] == "#")?substr($input, 1, 6):substr($input, 0, 6);

        return array(
            hexdec(substr($input, 0, 2)),
            hexdec(substr($input, 2, 2)),
            hexdec(substr($input, 4, 2))
        );
    }

    /**
     * Create waveform from song file.
     * @param string $filename
     * @return binary|string|null
     */
    protected static function create_waveform($filename)
    {
        if (!file_exists($filename)) {
            debug_event('waveform', 'File ' . $filename . ' doesn\'t exists', 1);

            return null;
        }
        
        if (!check_php_gd()) {
            debug_event('waveform', 'GD extension must be loaded', 1);

            return null;
        }

        $detail     = 5;
        $width      = 400;
        $height     = 32;
        $foreground = AmpConfig::get('waveform_color') ?: '#FF0000';
        $background = '';
        $draw_flat  = true;

        // generate foreground color
        list($r, $g, $b) = self::html2rgb($foreground);

        $handle = fopen($filename, "r");
        // wav file header retrieval
        $heading   = array();
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = fread($handle, 4);
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));

        // wav bitrate
        $peek = hexdec(substr($heading[10], 0, 2));
        $byte = $peek / 8;

        // checking whether a mono or stereo wav
        $channel = hexdec(substr($heading[6], 0, 2));

        $ratio = ($channel == 2 ? 40 : 80);

        // start putting together the initial canvas
        // $data_size = (size_of_file - header_bytes_read) / skipped_bytes + 1
        $data_size  = floor((Core::get_filesize($filename) - 44) / ($ratio + $byte) + 1);
        $data_point = 0;

        // create original image width based on amount of detail
        // each waveform to be processed with be $height high, but will be condensed
        // and resized later (if specified)
        $img = imagecreatetruecolor($data_size / $detail, $height);
        if ($img === false) {
            debug_event('waveform', 'Cannot create image.', 1);

            return null;
        }

        // fill background of image
        if ($background == "") {
            // transparent background specified
            imagesavealpha($img, true);
            $transparentColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparentColor);
        } else {
            list($br, $bg, $bb) = self::html2rgb($background);
            imagefilledrectangle($img, 0, 0, (int) ($data_size / $detail), $height, imagecolorallocate($img, $br, $bg, $bb));
        }
        while (!feof($handle) && $data_point < $data_size) {
            if ($data_point++ % $detail == 0) {
                $bytes = array();

                // get number of bytes depending on bitrate
                for ($i = 0; $i < $byte; $i++) {
                    $bytes[$i] = fgetc($handle);
                }

                switch ($byte) {
                // get value for 8-bit wav
                case 1:
                  $data = self::findValues($bytes[0], $bytes[1]);
                break;
                // get value for 16-bit wav
                case 2:
                  if (ord($bytes[1]) & 128) {
                      $temp = 0;
                  } else {
                      $temp = 128;
                  }
                  $temp = chr((ord($bytes[1]) & 127) + $temp);
                  $data = floor(self::findValues($bytes[0], $temp) / 256);
                break;
                default:
                    $data = 0;
                break;
              }

                // skip bytes for memory optimization
                fseek($handle, $ratio, SEEK_CUR);

                // draw this data point
                // relative value based on height of image being generated
                // data values can range between 0 and 255
                $v = (int) ($data / 255 * $height);

                // don't print flat values on the canvas if not necessary
                if (!($v / $height == 0.5 && !$draw_flat)) {
                    // draw the line on the image using the $v value and centering it vertically on the canvas
                    imageline(
                  $img,
                  // x1
                  (int) ($data_point / $detail),
                  // y1: height of the image minus $v as a percentage of the height for the wave amplitude
                  $height - $v,
                  // x2
                  (int) ($data_point / $detail),
                  // y2: same as y1, but from the bottom of the image
                  $height - ($height - $v),
                  imagecolorallocate($img, $r, $g, $b)
                );
                }
            } else {
                // skip this one due to lack of detail
                fseek($handle, $ratio + $byte, SEEK_CUR);
            }
        }

        // close and cleanup
        fclose($handle);

        ob_start();
        // want it resized?
        if ($width) {
            // resample the image to the proportions defined in the form
            $rimg = imagecreatetruecolor($width, $height);
            // save alpha from original image
            imagesavealpha($rimg, true);
            imagealphablending($rimg, false);
            // copy to resized
            imagecopyresampled($rimg, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));
            imagepng($rimg);
            imagedestroy($rimg);
        } else {
            imagepng($img);
        }
        imagedestroy($img);

        $imgdata = ob_get_contents();
        ob_clean();

        return $imgdata;
    }

    /**
     * Save waveform to db.
     * @param int $song_id
     * @param binary|string $waveform
     * @return boolean
     */
    protected static function save_to_db($song_id, $waveform)
    {
        $sql = "UPDATE `song_data` SET `waveform` = ? WHERE `song_id` = ?";

        return Dba::write($sql, array($waveform, $song_id));
    }
} // Waveform class
