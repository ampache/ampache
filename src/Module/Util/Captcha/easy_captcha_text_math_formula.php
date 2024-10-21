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
 * Class easy_captcha_text_math_formula
 * arithmetic riddle
 */
class easy_captcha_text_math_formula extends easy_captcha
{
    public $question = "1+1";
    public $solution = "2";

    // set up
    public function __construct()
    {
        $this->question = sprintf(self::CAPTCHA_WHATIS_TEXT, $this->create_formula());
        $this->solution = $this->calculate_formula($this->question);
        // we could do easier with iterated formula+result generation here, of course
        // but I had this code handy already ;) and it's easier to modify
    }

    // simple IS-EQUAL check

    /**
     * @param $input
     * @return bool
     */
    public function solved($input = null)
    {
        return (int)$this->solution == (int)$input;
    }

    // make new captcha formula string

    /**
     * create_formula
     */
    public function create_formula(): string
    {
        $formula = [
            rand(20, 100) . " / " . rand(2, 10),
            rand(50, 150) . " - " . rand(2, 100),
            rand(2, 100) . " + " . rand(2, 100),
            rand(2, 15) . " * " . rand(2, 12),
            rand(5, 10) . " * " . rand(5, 10) . " - " . rand(1, 20),
            rand(30, 100) . " + " . rand(5, 99) . " - " . rand(1, 50),
            //    rand(20,100) . " / " . rand(2,10) . " + " . rand(1,50),
        ];

        return $formula[rand(0, count($formula) - 1)];
    }

    // remove non-arithmetic characters

    /**
     * @param string $string
     * @return string|string[]|null
     */
    public function clean($string)
    {
        return preg_replace("/[^-+*\/\d]/", "", $string);
    }

    // "solve" simple calculations

    /**
     * @param $formula
     * @return int
     */
    public function calculate_formula($formula)
    {
        // FIXME $uu is undefined
        preg_match("#^(\d+)([-+/*])(\d+)([-+/*])?(\d+)?$#", $this->clean($formula), $uu);
        list($uu, $X, $op1, $Y, $op2, $Z) = $uu;
        if ($Y) {
            $calc = [
                '/' => $X / $Y,
                // PHP+ZendVM catches division by zero already, and CAPTCHA "attacker" would get no advantage herefrom anyhow
                "*" => $X * $Y,
                "+" => $X + $Y,
                "-" => $X - $Y,
                "*-" => $X * $Y - $Z,
                "+-" => $X + $Y - $Z,
                "/+" => $X / $Y + $Z,
            ];
        }

        return (isset($calc) && $calc[$op1 . $op2])
            ? $calc[$op1 . $op2]
            : rand(0, 1 << 23);
    }
}
