<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

/**
 * #####################################################################
 * #                               Warning                             #
 * #                               #######                             #
 * # This external file is Ampache-adapted and probably unsynced with  #
 * # origin because abandoned by its original authors.                #
 * #                                                                   #
 * #####################################################################
 *
 * Code origin from http://oauth.googlecode.com/svn/code/php/OAuth.php
 */

declare(strict_types=0);

namespace Ampache\Module\Util\OAuth;

/**
 * Class OAuthConsumer
 */
class OAuthConsumer
{
    public $key;
    public $secret;
    public $callback_url;

    /**
     * OAuthConsumer constructor.
     * @param $key
     * @param $secret
     * @param $callback_url
     */
    public function __construct($key, $secret, $callback_url = null)
    {
        $this->key          = $key;
        $this->secret       = $secret;
        $this->callback_url = $callback_url;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}
