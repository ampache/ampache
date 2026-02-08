<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginGetLyricsInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * Class GetLyricsMethod
 * @package Lib\ApiMethods
 */
final class GetLyricsMethod
{
    public const ACTION = 'get_lyrics';

    /**
     * get_lyrics
     * MINIMUM_API_VERSION=6.9.0
     *
     * Return Database lyrics or search with plugins by Song id
     *
     * filter  = (string) song id
     * plugins = (int) 0,1, if false disable plugin lookup (Default: 1)
     *
     * @param array{
     *     filter: string,
     *     plugins?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function get_lyrics(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        $object_id = (int) $input['filter'];
        $libitem   = new Song($object_id);

        if ($libitem->isNew()) {
            Api::error(sprintf('Bad Request: %s', $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $results = [
            'object_id' => $object_id,
            'object_type' => 'song',
            'plugin' => [],
        ];

        $database_lyrics = $libitem->get_lyrics(true);
        if (!empty($database_lyrics)) {
            $results['plugin']['database'] = $database_lyrics;
        }

        if ((int)($input['plugins'] ?? 1) === 1) {
            foreach (Plugin::get_plugins(PluginTypeEnum::LYRIC_RETRIEVER) as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin instanceof PluginGetLyricsInterface && $plugin->load($user)) {
                    $lyrics = $plugin->_plugin->get_lyrics($libitem);
                    if (!empty($lyrics)) {
                        // save the lyrics if not set before
                        if (array_key_exists('text', $lyrics) && !empty($lyrics['text'])) {
                            $results['plugin'][$plugin_name] = $lyrics;
                        }
                    }
                }
            }
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::keyed_array($results);
        }

        return true;
    }
}
