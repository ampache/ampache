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
 * Class easy_captcha_persistent_grant
 * shortcut, allow access for an user if captcha was previously solved
 * (should be identical in each instantiation, cookie is time-bombed)
 */
class easy_captcha_persistent_grant extends easy_captcha
{
    public function __construct($captcha_id = null, $ignore_expiration = 0)
    {
    }

    /**
     * @param integer $input
     * @return boolean
     */
    public function solved($input = 0)
    {
        if (CAPTCHA_PERSISTENT && filter_has_var(INPUT_COOKIE, $this->cookie())) {
            return in_array($_COOKIE[$this->cookie()], array($this->validity_token(), $this->validity_token(-1)));
        }

        return false;
    }

    #-- set captcha persistence cookie
    public function grant()
    {
        if (!headers_sent()) {
            setcookie($this->cookie(), $this->validity_token(), ['expires' => time() + 175 * CAPTCHA_TIMEOUT, 'samesite' => 'Strict']);
            //} else {
            //    // $this->log("::grant", "COOKIES", "too late for cookies");
        }
    }

    #-- pseudo password (time-bombed)

    /**
     * @param integer $deviation
     * @return string
     */
    public function validity_token($deviation = 0)
    {
        return easy_captcha::hash("PERSISTENCE", $deviation, $length = 100);
    }

    /**
     * @return string
     */
    public function cookie()
    {
        return "captcha_pass";
    }
}
