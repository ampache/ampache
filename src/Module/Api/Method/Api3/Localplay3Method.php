<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Playback\Localplay\LocalPlay;

/**
 * Class Localplay3Method
 */
final class Localplay3Method
{
    public const ACTION = 'localplay';

    /**
     * localplay
     * This is for controling localplay
     * @param array $input
     */
    public static function localplay(array $input)
    {
        // Load their localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        if (!$localplay->connect()) {
            echo Xml3_Data::error('405', T_('Invalid Request'));

            return false;
        }

        switch ($input['command']) {
            case 'next':
                $result = $localplay->next();
                break;
            case 'prev':
                $result = $localplay->prev();
                break;
            case 'stop':
                $result = $localplay->stop();
                break;
            case 'play':
                $result = $localplay->play();
                break;
            default:
                // They are doing it wrong
                echo Xml3_Data::error('405', T_('Invalid Request'));

                return false;
        } // end switch on command

        $xml_array     = array('localplay' => array('command' => array($input['command'] => make_bool($result))));
        echo Xml3_Data::keyed_array($xml_array);
    } // localplay
}
