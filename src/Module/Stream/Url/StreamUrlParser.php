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

declare(strict_types=1);

namespace Ampache\Module\Stream\Url;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;

/**
 * Splits a stream url (http://some.tld/play/...) into its components
 */
final class StreamUrlParser implements StreamUrlParserInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * Takes an url and parses out all the chewy goodness.
     */
    public function parse(string $url): array
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::STREAM_BEAUTIFUL_URL) === true) {
            $posargs = strpos($url, '/play/');
            if ($posargs !== false) {
                $argsstr = substr($url, $posargs + 6);
                $url     = substr($url, 0, $posargs + 6) . 'index.php?';
                $args    = explode('/', $argsstr);
                $a_count = count($args);
                for ($i = 0; $i < $a_count; $i += 2) {
                    if ($i > 0) {
                        $url .= '&';
                    }
                    $url .= $args[$i] . '=' . $args[$i + 1];
                }
            }
        }

        $query    = (string)parse_url($url, PHP_URL_QUERY);
        $elements = explode('&', $query);
        $results  = array();

        $results['base_url'] = $url;

        foreach ($elements as $element) {
            [$key, $value] = explode('=', $element);
            switch ($key) {
                case 'oid':
                    $key = 'id';
                    break;
                case 'video':
                    if (make_bool($value)) {
                        $results['type'] = 'video';
                    }
                    break;
            }
            $results[$key] = $value;
        }

        return $results;
    }
}
