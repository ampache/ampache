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
 * Class easy_captcha_fuzzy
 *
 * checks the supplied solution, allows differences (incorrectly guessed letters)
 */
class easy_captcha_fuzzy extends easy_captcha
{
    #-- ratio of letters that may differ between solution and real password
    public $fuzzy = easy_captcha::CAPTCHA_FUZZY;

    #-- compare

    /**
     * @param $input
     * @return boolean
     */
    public function solved($input = null)
    {
        if ($input) {
            $pw      = strtolower($this->solution);
            $input   = strtolower($input);
            $diff    = levenshtein($pw, $input);
            $maxdiff = strlen($pw) * (1 - $this->fuzzy);

            return ($pw == $input) || ($diff <= $maxdiff);  // either matches, or allows around 2 divergent letters
        }

        return false;
    }
}
