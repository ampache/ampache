<?php
/*
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

declare(strict_types=0);

namespace Ampache\Module\Util\Captcha;

/**
 * Class easy_captcha_graphic_image_disturbed
 *
 * colorful captcha image I
 */
class easy_captcha_graphic_image_disturbed extends easy_captcha_graphic
{
    /**
     * returns jpeg file stream with unscannable letters encoded
     * in front of colorful disturbing background
     * @return false|string
     */
    public function jpeg()
    {
        // step by step
        $this->create();
        $this->background_lines();
        $this->background_letters();
        $this->text();

        return $this->output();
    }

    // initialize in-memory image with gd library
    public function create()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $this->random_color(222, 255));

        // encolour bg
        $wd = 20;
        $x  = 0;
        while ($x < $this->width) {
            imagefilledrectangle($this->img, $x, 0, $x += $wd, $this->height, $this->random_color(222, 255));
            $wd += max(10, rand(0, 20) - 10);
        }
    }

    // make interesting background I, lines
    public function background_lines()
    {
        $c1 = rand(150, 185);
        $c2 = rand(195, 230);
        $wd = 4;
        $w1 = 0;
        $w2 = 0;
        for ($x = 0; $x < $this->width; $x += (int)$wd) {
            if ($x < $this->width) {   // verical
                imageline($this->img, $x + $w1, 0, $x + $w2, $this->height - 1, $this->random_color($c1++, $c2));
            }
            if ($x < $this->height) {  // horizontally ("y")
                imageline($this->img, 0, $x - $w2, $this->width - 1, $x - $w1, $this->random_color($c1, $c2--));
            }
            $wd += rand(0, 8) - 4;
            if ($wd < 1) {
                $wd = 2;
            }
            $w1 += rand(0, 8) - 4;
            $w2 += rand(0, 8) - 4;
            if (($x > $this->height) && ($y > $this->height)) {
                // FIXME $y is undefined
                break;
            }
        }
    }

    // more disturbing II, random letters
    public function background_letters()
    {
        $limit = rand(30, 90);
        for ($n = 0; $n < $limit; $n++) {
            $letter = '';
            do {
                $letter .= chr(rand(31, 125)); // random symbol
            } while (rand(0, 1));
            $size     = rand(5, $this->height / 2);
            $half     = (int)($size / 2);
            $x        = rand(-$half, $this->width + $half);
            $y        = rand(+$half, $this->height);
            $rotation = rand(60, 300);
            imagettftext($this->img, $size, $rotation, $x, $y, $this->random_color(130, 240), $this->font(), $letter);
        }
    }

    // add the real text to it
    public function text()
    {
        $phrase = $this->solution;
        $len    = strlen($phrase);
        $w1     = 10;
        $w2     = $this->width / ($len + 1);
        for ($p = 0; $p < $len; $p++) {
            $letter = $phrase[$p];
            $size   = rand(18, $this->height / 2.2);
            //$half     = (int) $size / 2;
            $rotation = rand(-33, 33);
            $y        = rand($size + 3, $this->height - 3);
            $x        = $w1 + $w2 * $p;
            $w1 += rand(-$this->width / 90, $this->width / 40); // @BUG: last char could be +30 pixel outside of image
            $font            = $this->font();
            list($r, $g, $b) = [rand(30, 99), rand(30, 99), rand(30, 99)];
            imagettftext($this->img, $size, $rotation, $x + 1, $y, $this->rgb($r * 2, $g * 2, $b * 2), $font, $letter);
            imagettftext($this->img, $size, $rotation, $x, $y - 1, $this->rgb($r, $g, $b), $font, $letter);
        }
    }
}
