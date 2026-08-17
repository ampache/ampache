<?php

declare(strict_types=1);

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

namespace Ampache\Module\Util\Rss;

use Ampache\Config\AmpConfig;

/**
 * Feed urls, in the query form every install understands and in the path form
 * enabled by rss_beautiful_url (see public/rss/.htaccess.dist)
 */
final class RssUrl
{
    /**
     * The url a feed is identified by: the query form, whatever shape it is served under
     *
     * @param array<string, string> $params
     */
    public static function canonical(array $params): string
    {
        return AmpConfig::get_web_path() . '/rss.php?' . http_build_query($params);
    }

    /**
     * The current request's own query params, normalized back to underscores so a feed built from them
     * identifies the same way whether this request arrived beautiful or not
     *
     * @return array<string, string>
     */
    public static function currentQueryParams(): array
    {
        parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $parsed);

        $params = [];
        foreach ($parsed as $key => $value) {
            if (is_scalar($value)) {
                $params[(string) $key] = $value;
            }
        }

        foreach (['type', 'object_type'] as $key) {
            if (isset($params[$key])) {
                $params[$key] = str_replace('-', '_', $params[$key]);
            }
        }

        return $params;
    }

    /**
     * The url a feed is published under, a path when rss_beautiful_url is on
     *
     * @param array<string, string> $params
     */
    public static function published(array $params, string $title = ''): string
    {
        if (!AmpConfig::get('rss_beautiful_url')) {
            return self::canonical($params);
        }

        $type  = str_replace('_', '-', (string) ($params['type'] ?? ''));
        $token = (string) ($params['rsstoken'] ?? '');

        $path = ($type === 'library-item')
            ? str_replace('_', '-', (string) ($params['object_type'] ?? '')) . '/' . ($params['object_id'] ?? '')
            : $type;
        if ($path === '' || str_ends_with($path, '/')) {
            return self::canonical($params);
        }

        $slug = self::slug($title);
        if ($slug !== '' && isset($params['object_id'])) {
            $path .= '/' . $slug;
        }

        return AmpConfig::get_web_path() . '/rss/' . $path . (($token !== '')
            ? '?rsstoken=' . rawurlencode($token)
            : '');
    }

    /**
     * A readable, ascii only path segment. Purely decorative, feeds resolve without it
     */
    public static function slug(string $text): string
    {
        $slug = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $slug));

        return trim($slug, '-');
    }
}
