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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;

/**
 * @see DeletionUrlResolverInterface
 */
final readonly class DeletionUrlResolver implements DeletionUrlResolverInterface
{
    public function __construct(
        private ConfigContainerInterface $configContainer,
    ) {}

    public function resolveBurl(?string $encodedBurl): string
    {
        if ($encodedBurl === null || $encodedBurl === '') {
            return '';
        }

        $decoded = base64_decode($encodedBurl, true);
        if ($decoded === false || $decoded === '') {
            return '';
        }

        if (preg_match('/[\x00-\x20"\'<>`\\\\]/', $decoded) === 1) {
            return '';
        }

        $scheme = strtolower((string) parse_url($decoded, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return '';
        }

        $webPath = $this->configContainer->getWebPath();
        if (!str_starts_with($decoded, $webPath . '/')) {
            return '';
        }

        $path = strtolower((string) parse_url($decoded, PHP_URL_PATH));
        if (!str_ends_with($path, '.php') && !str_ends_with($path, '/')) {
            return '';
        }

        return $decoded;
    }

    public function resolveContinueUrl(
        string $burl,
        string $selfIdParam,
        int $selfIdValue,
        string $parentUrl,
        string $fallbackUrl,
    ): string {
        if ($burl === '') {
            return ($parentUrl !== '') ? $parentUrl : $fallbackUrl;
        }

        parse_str((string) parse_url($burl, PHP_URL_QUERY), $params);

        $selfId = $params[$selfIdParam] ?? null;
        if (
            $selfIdValue > 0
            && is_scalar($selfId)
            && (int) $selfId === $selfIdValue
        ) {
            return ($parentUrl !== '') ? $parentUrl : $fallbackUrl;
        }

        return $burl;
    }
}
