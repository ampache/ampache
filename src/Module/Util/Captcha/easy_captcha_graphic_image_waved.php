<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Util\Captcha;

/**
 * Class easy_captcha_graphic_image_waved
 *
 * waived captcha image II
 */
class easy_captcha_graphic_image_waved extends easy_captcha_graphic
{
    /* returns jpeg file stream with unscannable letters encoded
       in front of colorful disturbing background
    */
    /**
     * @return false|string
     */
    public function jpeg()
    {
        #-- step by step
        $this->img = $this->create();
        $this->text();
        //$this->debug_grid();
        $this->fog();
        $this->distort();

        return $this->output();
    }


    #-- initialize in-memory image with gd library

    /**
     * @return false|resource
     */
    public function create()
    {
        $img = imagecreatetruecolor($this->width, $this->height);
        // imagealphablending($img, TRUE);
        imagefilledrectangle(
            $img,
            0,
            0,
            $this->width,
            $this->height,
            $this->inverse ? $this->bg ^ 0xFFFFFF : $this->bg
        ); //$this->rgb(255,255,255)
        if (function_exists("imageantialias")) {
            imageantialias($img, true);
        }

        return ($img);
    }


    #-- add the real text to it
    public function text()
    {
        $w    = $this->width;
        $h    = $this->height;
        $SIZE = rand(30, 36);
        $DEG  = rand(-2, 9);
        $LEN  = strlen($this->solution);
        $left = $w - $LEN * 25;
        $top  = ($h - $SIZE - abs($DEG * 2));
        imagettftext(
            $this->img,
            $SIZE,
            $DEG,
            rand(5, $left - 5),
            $h - rand(3, $top - 3),
            $this->rgb(0, 0, 0),
            $this->font(),
            $this->solution
        );
    }

    #-- to visualize the sinus waves
    public function debug_grid()
    {
        for ($x = 0; $x < 250; $x += 10) {
            imageline($this->img, $x, 0, $x, 70, 0x333333);
            imageline($this->img, 0, $x, 250, $x, 0x333333);
        }
    }

    #-- add lines
    public function fog()
    {
        $num = rand(10, 25);
        $x   = $this->width;
        $y   = $this->height;
        $s   = rand(0, 270);
        for ($n = 0; $n < $num; $n++) {
            imagesetthickness($this->img, rand(1, 2));
            imagearc(
                $this->img,
                rand(0.1 * $x, 0.9 * $x),
                rand(0.1 * $y, 0.9 * $y),  // x,y
                rand(0.1 * $x, 0.3 * $x),
                rand(0.1 * $y, 0.3 * $y),  // w,h
                $s,
                rand($s + 5, $s + 90),     // s,e
                rand(0, 1) ? 0xFFFFFF : 0x000000   // col
            );
        }
        imagesetthickness($this->img, 1);
    }

    #-- distortion: wave-transform
    public function distort()
    {

        #-- init
        $single_pixel = (self::CAPTCHA_PIXEL <= 1);   // very fast
        $greyscale2x2 = (self::CAPTCHA_PIXEL <= 2);   // quicker than exact smooth 2x2 copy
        $width        = $this->width;
        $height       = $this->height;
        $image        = &$this->img;
        $dest         = $this->create();

        #-- URL param ?hires=1 influences used drawing scheme
        if (isset($_GET['hires'])) {
            $single_pixel = 0;
        }

        #-- prepare distortion
        $wave = new easy_captcha_dxy_wave($width, $height);
        // $spike = new easy_captcha_dxy_spike($width, $height);

        #-- generate each new x,y pixel individually from orig $img
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                #-- pixel movement
                list($distortx, $distorty) = $wave->dxy($x, $y);   // x- and y- sinus wave
                // list($qx, $qy) = $spike->dxy($x, $y);

                #-- if not out of bounds
                if (($distortx + $x >= 0) && ($distorty + $y >= 0) && ($distortx + $x < $width) && ($distorty + $y < $height)) {
                    #-- get source pixel(s), paint dest
                    if ($single_pixel) {
                        // single source dot: one-to-one duplicate (unsmooth, hard edges)
                        imagesetpixel($dest, $x, $y, imagecolorat($image, (int)$distortx + $x, (int)$distorty + $y));
                    } elseif ($greyscale2x2) {
                        // merge 2x2 simple/greyscale (3 times as slow)
                        $cXY = $this->get_2x2_greyscale($image, (int)$distortx + $x, (int)$distorty + $y);
                        imagesetpixel($dest, $x, $y, imagecolorallocate($dest, $cXY, $cXY, $cXY));
                    } else {
                        // exact and smooth transformation (5 times as slow)
                        list($cXY_R, $cXY_G, $cXY_B) = $this->get_2x2_smooth($image, $x + $distortx, $y + $distorty);
                        imagesetpixel($dest, $x, $y, imagecolorallocate($dest, (int)$cXY_R, (int)$cXY_G, (int)$cXY_B));
                    }
                }
            }
        }

        #-- simply overwrite ->img
        imagedestroy($image);
        $this->img = $dest;
    }

    #-- get 4 pixels from source image, merges BLUE value simply

    /**
     * @param $image
     * @param $xaxis
     * @param $yaxis
     * @return integer
     */
    public function get_2x2_greyscale(&$image, $xaxis, $yaxis)
    {
        // this is a pretty simplistic method, actually adds more artefacts
        // than it "smoothes" it just merges the brightness from 4 adjoining pixels into one
        $cXY = (imagecolorat($image, $xaxis, $yaxis) & 0xFF)
            + (imagecolorat($image, $xaxis, $yaxis + 1) & 0xFF)
            + (imagecolorat($image, $xaxis + 1, $yaxis) & 0xFF)
            + (imagecolorat($image, $xaxis + 1, $yaxis + 1) & 0xFF);
        $cXY = (int)($cXY / 4);

        return $cXY;
    }

    #-- smooth pixel reading (with x,y being reals, not integers)

    /**
     * @param $i
     * @param $x
     * @param $y
     * @return array
     */
    public function get_2x2_smooth(&$i, $x, $y)
    {
        // get R,G,B values from 2x2 source area
        $c00 = $this->get_RGB($i, $x, $y);      //  +------+------+
        $c01 = $this->get_RGB($i, $x, $y + 1);    //  |dx,dy | x1,y0|
        $c10 = $this->get_RGB($i, $x + 1, $y);    //  | rx-> |      |
        $c11 = $this->get_RGB($i, $x + 1, $y + 1);  //  +----##+------+
        // weighting by $distortx/$distorty fraction part   //  |    ##|<-ry  |
        $rx  = $x - floor($x);
        $rx_ = 1 - $rx;  //  |x0,y1 | x1,y1|
        $ry  = $y - floor($y);
        $ry_ = 1 - $ry;  //  +------+------+
        // this is extremely slow, but necessary for correct color merging,
        // the source pixel lies somewhere in the 2x2 quadrant, that's why
        // RGB values are added proportionately (rx/ry/_)
        // we use no for-loop because that would slow it even further
        $cXY_R = (int)(($c00[0]) * $rx_ * $ry_) + (int)(($c01[0]) * $rx_ * $ry)      // division by 4 not necessary,
            + (int)(($c10[0]) * $rx * $ry_)      // because rx/ry/rx_/ry_ add up
            + (int)(($c11[0]) * $rx * $ry);      // to 255 (=1.0) at most
        $cXY_G = (int)(($c00[1]) * $rx_ * $ry_) + (int)(($c01[1]) * $rx_ * $ry) + (int)(($c10[1]) * $rx * $ry_) + (int)(($c11[1]) * $rx * $ry);
        $cXY_B = (int)(($c00[2]) * $rx_ * $ry_) + (int)(($c01[2]) * $rx_ * $ry) + (int)(($c10[2]) * $rx * $ry_) + (int)(($c11[2]) * $rx * $ry);

        return array($cXY_R, $cXY_G, $cXY_B);
    }

    #-- imagegetcolor from current ->$img split up into RGB array

    /**
     * @param $img
     * @param $x
     * @param $y
     * @return array
     */
    public function get_RGB(&$img, $x, $y)
    {
        $rgb = imagecolorat($img, $x, $y);

        return array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, ($rgb) & 0xFF);
    }
}
