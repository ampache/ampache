<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * Class easy_captcha_dxy_wave
 *
 * xy-wave deviation (works best for around 200x60)
 * cos(x,y)-idea taken from imagemagick
 */
class easy_captcha_dxy_wave
{

    #-- init params
    /**
     * easy_captcha_dxy_wave constructor.
     * @param $max_x
     * @param $max_y
     */
    public function __construct($max_x, $max_y)
    {
        $this->dist_x = $this->real_rand(2.5, 3.5); // max +-x/y delta distance
        $this->dist_y = $this->real_rand(2.5, 3.5);
        $this->slow_x = $this->real_rand(7.5, 20.0); // =wave-width in pixel/3
        $this->slow_y = $this->real_rand(7.5, 15.0);
    }

    #-- calculate source pixel position with overlapping sinus x/y-displacement

    /**
     * @param $x
     * @param $y
     * @return array
     */
    public function dxy($x, $y)
    {
        #-- adapting params
        $this->dist_x *= 1.000035;
        $this->dist_y *= 1.000015;
        #-- dest pixels (with x+y together in each of the sin() calcs you get more deformation, else just yields y-ripple effect)
        $distortx = $this->dist_x * cos(($x / $this->slow_x) - ($y / 1.1 / $this->slow_y));
        $distorty = $this->dist_y * sin(($y / $this->slow_y) - ($x / 0.9 / $this->slow_x));
        #-- result
        return array($distortx, $distorty);
    }

    #-- array of values with random start/end values

    /**
     * @param $max
     * @param $a
     * @param $b
     * @return array
     */
    public function from_to_rand($max, $a, $b)
    {
        $BEG    = $this->real_rand($a, $b);
        $DIFF   = $this->real_rand($a, $b) - $BEG;
        $result = array();
        for ($count = 0; $count <= $max; $count++) {
            $result[$count] = $BEG + $DIFF * $count / $max;
        }

        return ($result);
    }

    #-- returns random value in given interval

    /**
     * @param $a
     * @param $b
     * @return float|int
     */
    public function real_rand($a, $b)
    {
        $random = rand(0, 1 << 30);

        return ($random / (1 << 30) * ($b - $a) + $a); // base + diff * (0..1)
    }
}
