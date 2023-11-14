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
 * Class easy_captcha_graphic
 *
 * image captchas, base and utility code
 */
class easy_captcha_graphic extends easy_captcha_fuzzy
{
    public $width;
    public $height;
    public $inverse;
    public $bg;
    public $maxsize;
    public $quality;
    public $solution;

    #-- config
    /**
     * easy_captcha_graphic constructor.
     * @param $x
     * @param $y
     */
    public function __construct($x = null, $y = null)
    {
        if (!$y) {
            $x = strtok(CAPTCHA_IMAGE_SIZE, "x,|/*;:");
            $y = strtok(",.");
            $x = rand((int)$x * 0.9, (int)$x * 1.2);
            $y = rand((int)$y - 5, (int)$y + 15);
        }
        $this->width    = $x;
        $this->height   = $y;
        $this->inverse  = CAPTCHA_INVERSE;
        $this->bg       = CAPTCHA_BGCOLOR;
        $this->maxsize  = 0xFFFFF;
        $this->quality  = 66;
        $this->solution = $this->mkpass();
    }


    #-- return a single .ttf font filename

    /**
     * @return mixed
     */
    public function font()
    {
        $fonts = array(/*"FreeMono.ttf"*/);
        $fonts += glob(CAPTCHA_FONT_DIR . "/*.ttf");

        return $fonts[rand(0, count($fonts) - 1)];
    }


    #-- makes string of random letters (for embedding into image)

    /**
     * @return false|string
     */
    public function mkpass()
    {
        $string = '';
        for ($n = 0; $n < 10; $n++) {
            $string .= chr(rand(0, 255));
        }
        $string = base64_encode($string);   // base64-set, but filter out unwanted chars
        $string = preg_replace("/[+\/=IG0ODQR]/i", "",
            $string);  // strips hard to discern letters, depends on used font type
        $string = substr($string, 0, rand(CAPTCHA_MIN_CHARS, CAPTCHA_MAX_CHARS));

        return ($string);
    }


    #-- return GD color

    /**
     * @param $a
     * @param $b
     * @return false|int
     */
    public function random_color($a, $b)
    {
        $R = $this->inverse ? 0xFF : 0x00;

        return imagecolorallocate($this->img, rand($a, $b) ^ $R, rand($a, $b) ^ $R, rand($a, $b) ^ $R);
    }

    /**
     * @param $r
     * @param $g
     * @param $b
     * @return false|int
     */
    public function rgb($r, $g, $b)
    {
        $R = $this->inverse ? 0xFF : 0x00;

        return imagecolorallocate($this->img, $r ^ $R, $g ^ $R, $b ^ $R);
    }


    #-- generate JPEG output

    /**
     * @return false|string
     */
    public function output()
    {
        ob_start();
        ob_implicit_flush(0);
        imagejpeg($this->img, null, $this->quality);
        $jpeg = ob_get_contents();
        ob_end_clean();
        imagedestroy($this->img);
        unset($this->img);

        return ($jpeg);
    }
}
