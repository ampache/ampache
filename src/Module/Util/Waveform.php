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

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use RuntimeException;

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
    /**
     * Get a song or podcast_episode waveform.
     * @throws RuntimeException
     */
    public static function get(Podcast_Episode|Song $media, string $object_type): ?string
    {
        $waveform = null;

        if ($media->isNew() === false) {
            if (AmpConfig::get('album_art_store_disk')) {
                $waveform = self::get_from_file($media->id, $object_type);
            } else {
                // TODO waveforms aren't saved for podcast episodes.
                if ($media instanceof Song) {
                    $media->fill_ext_info('waveform');
                }
                $waveform = $media->waveform;
            }
            if (empty($waveform)) {
                $catalog = Catalog::create_from_id($media->catalog);
                if ($catalog !== null && $catalog->get_type() == 'local') {
                    $transcode_to  = 'wav';
                    $transcode_cfg = AmpConfig::get('transcode', 'default');
                    $valid_types   = $media->get_stream_types();

                    if ($media->type != $transcode_to) {
                        $basedir = Core::get_tmp_dir();
                        if ($basedir) {
                            if ($transcode_cfg != 'never' && in_array('transcode', $valid_types)) {
                                $tmpfile = tempnam($basedir, $transcode_to);
                                if (!$tmpfile) {
                                    return null;
                                }
                                $tfp = fopen($tmpfile, 'wb');
                                if (!is_resource($tfp)) {
                                    debug_event(self::class, "Failed to open " . $tmpfile, 3);

                                    return null;
                                }

                                $transcode_settings = $media->get_transcode_settings($transcode_to);
                                $transcoder         = Stream::start_transcode($media, $transcode_settings);
                                if (empty($transcoder)) {
                                    return null;
                                }

                                $filepointer = $transcoder['handle'];
                                if (!is_resource($filepointer)) {
                                    debug_event(self::class, "Failed to open " . $media->file . " for waveform.", 3);

                                    return null;
                                }

                                do {
                                    if ($buf = fread($filepointer, 2048)) {
                                        fwrite($tfp, $buf);
                                    }
                                } while (!feof($filepointer));

                                fclose($filepointer);
                                fclose($tfp);

                                Stream::kill_process($transcoder);

                                $waveform = self::create_waveform($tmpfile);

                                if (unlink($tmpfile) === false) {
                                    throw new RuntimeException('The file handle ' . $tmpfile . ' could not be unlinked');
                                }
                            } else {
                                debug_event(self::class, 'transcode setting to wav required for waveform.', 3);
                            }
                        } else {
                            debug_event(self::class, 'tmp_dir_path setting required for waveform.', 3);
                        }
                    } elseif ($media->file !== null) {
                        // Already wav file, no transcode required
                        $waveform = self::create_waveform($media->file);
                    }
                }

                if (!empty($waveform)) {
                    if (AmpConfig::get('album_art_store_disk')) {
                        self::save_to_file($media->id, $object_type, $waveform);
                    } else {
                        self::save_to_db($media->id, $object_type, $waveform);
                    }
                }
            }
        }

        return $waveform;
    }

    /**
     * Return full path of the Waveform file.
     */
    public static function get_filepath(int $object_id, string $object_type): ?string
    {
        $path = AmpConfig::get('local_metadata_dir');
        if (!$path) {
            debug_event(self::class, 'local_metadata_dir setting is required to store waveform on disk.', 1);

            return null;
        }
        // Create subdirectory based on the 2 last digit of the Song Id. We prevent having thousands of file in one directory.
        $dir1 = substr((string)$object_id, -1, 1);
        $dir2 = substr((string)$object_id, -2, 1);
        $path .= "/waveform/" . $object_type . '/' . $dir1 . '/' . $dir2 . "/";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $old_target_file = $path . "/waveform/" . $dir1 . '/' . $dir2 . "/" . $object_id . ".png";
        // move the song waveforms to the right place if they're in the old path
        if ($object_type == 'song' && is_file($old_target_file)) {
            rename($old_target_file, $path . $object_id . ".png");
            debug_event(self::class, 'Moved: ' . $object_id . ' from: {' . $old_target_file . '}' . ' to: {' . $path . $object_id . ".png" . '}', 5);
        }

        return $path . $object_id . ".png";
    }

    /**
     * Return content of a Waveform file.
     */
    public static function get_from_file(int $object_id, string $object_type): ?string
    {
        $file = self::get_filepath($object_id, $object_type);
        if (!empty($file) && file_exists($file)) {
            debug_event(self::class, 'get_from_file ' . $file, 5);
            $waveform = file_get_contents($file);

            if ($waveform !== false) {
                return $waveform;
            }
        }

        return null;
    }

    /**
     * Save content of a Waveform into a file.
     */
    public static function save_to_file(int $object_id, string $object_type, string $waveform): void
    {
        $file = self::get_filepath($object_id, $object_type);
        if (!empty($file)) {
            file_put_contents($file, $waveform);
        }
    }

    /**
     * findValues
     */
    protected static function findValues(string $byte1, string $byte2): float|int
    {
        $byte1 = hexdec(bin2hex($byte1));
        $byte2 = hexdec(bin2hex($byte2));

        return ($byte1 + ($byte2 * 256));
    }

    /**
     * Great function slightly modified as posted by Minux at
     * http://forums.clantemplates.com/showthread.php?t=133805
     * @param string $input
     * @return array{float|int, float|int, float|int}
     */
    protected static function html2rgb(string $input): array
    {
        $input = ($input[0] == "#") ? substr($input, 1, 6) : substr($input, 0, 6);

        return [
            hexdec(substr($input, 0, 2)),
            hexdec(substr($input, 2, 2)),
            hexdec(substr($input, 4, 2)),
        ];
    }

    /**
     * Create waveform from song file.
     */
    protected static function create_waveform(string $filename): ?string
    {
        if (!file_exists($filename)) {
            debug_event(self::class, 'File ' . $filename . ' doesn\'t exists', 1);

            return null;
        }

        // FIXME remove...
        global $dic;

        if (!$dic->get(EnvironmentInterface::class)->check_php_gd()) {
            debug_event(self::class, 'GD extension must be loaded', 1);

            return null;
        }

        $detail     = 5;
        $width      = (int)AmpConfig::get('waveform_width', 400);
        $height     = (int)AmpConfig::get('waveform_height', 32);
        $foreground = (string)AmpConfig::get('waveform_color', '#FF0000');
        $draw_flat  = (bool)AmpConfig::get('waveform_drawflat', true);

        // generate foreground color
        list($red, $green, $blue) = self::html2rgb($foreground);

        $handle = fopen($filename, "r");
        if ($handle === false) {
            debug_event(self::class, 'Cannot open filename.', 1);

            return null;
        }

        // wav file header retrieval
        $heading   = [];
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex((string)fread($handle, 4));
        $heading[] = fread($handle, 4);
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex((string)fread($handle, 4));
        $heading[] = bin2hex((string)fread($handle, 2));
        $heading[] = bin2hex((string)fread($handle, 2));
        $heading[] = bin2hex((string)fread($handle, 4));
        $heading[] = bin2hex((string)fread($handle, 4));
        $heading[] = bin2hex((string)fread($handle, 2));
        $heading[] = bin2hex((string)fread($handle, 2));
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex((string)fread($handle, 4));

        // wav bitrate
        $peek = hexdec(substr($heading[10], 0, 2));
        $byte = $peek / 8;

        // checking whether a mono or stereo wav
        $channel = hexdec(substr($heading[6], 0, 2));

        $ratio = ($channel == 2)
            ? 40
            : 80;

        // start putting together the initial canvas
        // $data_size = (size_of_file - header_bytes_read) / skipped_bytes + 1
        $data_size  = floor((Core::get_filesize($filename) - 44) / ($ratio + $byte) + 1);
        $data_point = 0;
        $img_width  = (int) ($data_size / $detail);

        // create original image width based on amount of detail
        // each waveform to be processed with be $height high, but will be condensed
        // and resized later (if specified)
        $img = ($img_width > 0 && $height > 0)
            ? imagecreatetruecolor($img_width, $height)
            : false;
        if ($img === false) {
            debug_event(self::class, 'Cannot create image.', 1);

            return null;
        }

        // fill background of image
        // transparent background specified
        imagesavealpha($img, true);
        $transparentColor = (int)imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparentColor);
        while (!feof($handle) && $data_point < $data_size) {
            if ($data_point++ % $detail == 0) {
                $bytes = [];

                // get number of bytes depending on bitrate
                for ($count = 0; $count < $byte; $count++) {
                    $bytes[$count] = (string)fgetc($handle);
                }

                switch ($byte) {
                    case 1:
                        // get value for 8-bit wav
                        $data = self::findValues($bytes[0], $bytes[1]);
                        break;
                    case 2:
                        // get value for 16-bit wav
                        if (ord((string)$bytes[1]) & 128) {
                            $temp = 0;
                        } else {
                            $temp = 128;
                        }
                        $temp = chr((ord((string)$bytes[1]) & 127) + $temp);
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
                $value = (int)($data / 255 * $height);

                // don't print flat values on the canvas if not necessary
                if (!($value / $height == 0.5 && !$draw_flat)) {
                    // draw the line on the image using the $value and centering it vertically on the canvas
                    imageline(
                        $img, // x1
                        (int)($data_point / $detail),
                        // y1: height of the image minus as a percentage of the height for the wave amplitude
                        $height - $value, // x2
                        (int)($data_point / $detail), // y2: same as y1, but from the bottom of the image
                        $height - ($height - $value),
                        (int)imagecolorallocate($img, (int)$red, (int)$green, (int)$blue)
                    );
                }
            } else {
                // skip this one due to lack of detail
                fseek($handle, (int)($ratio + $byte), SEEK_CUR);
            }
        }

        // close and cleanup
        fclose($handle);

        ob_start();
        // want it resized?
        if ($width) {
            // resample the image to the proportions defined in the form
            $rimg = imagecreatetruecolor((int) $width, (int) $height);
            if ($rimg !== false) {
                // save alpha from original image
                imagesavealpha($rimg, true);
                imagealphablending($rimg, false);
                // copy to resized
                imagecopyresampled($rimg, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));
                imagepng($rimg);
                imagedestroy($rimg);
            }
        } else {
            imagepng($img);
        }
        imagedestroy($img);

        $imgdata = ob_get_contents();
        ob_clean();

        return $imgdata ?: null;
    }

    /**
     * Save waveform to db.
     */
    protected static function save_to_db(int $object_id, string $object_type, string $waveform): void
    {
        $sql = ($object_type == 'podcast_episode')
            ? "UPDATE `podcast_episode` SET `waveform` = ? WHERE `id` = ?"
            : "UPDATE `song_data` SET `waveform` = ? WHERE `song_id` = ?";

        Dba::write($sql, [$waveform, $object_id]);
    }
}
