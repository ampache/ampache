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

namespace Ampache\Module\Api;

use function in_array;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Shared request-body parsing for the API application entry points.
 *
 * Parameters for a write request may be supplied in the query string, a form-encoded body, or a JSON body.
 * php only fills $_POST (and with it getParsedBody()) for POST, so the REST verbs read their form body from
 * the raw stream instead. GET (and other bodyless methods) return nothing.
 */
trait RequestParserTrait
{
    /**
     * Read the plain fields out of a multipart body. Uploads do not belong on these routes, so parts carrying
     * a filename are skipped rather than handled.
     *
     * @return array<array-key, mixed>
     */
    private function parseMultipartBody(string $body, string $contentType): array
    {
        if (!preg_match('/boundary=(?:"([^"]+)"|([^;,\s]+))/i', $contentType, $matches)) {
            return [];
        }

        $boundary = ($matches[1] !== '') ? $matches[1] : $matches[2];

        $result = [];
        foreach (explode('--' . $boundary, $body) as $part) {
            $segments = explode("\r\n\r\n", ltrim($part, "\r\n"), 2);
            if (
                count($segments) !== 2
                || stripos($segments[0], 'filename=') !== false
                || !preg_match('/name=(?:"([^"]*)"|([^;\r\n]+))/i', $segments[0], $nameMatch)
            ) {
                continue;
            }

            $name  = trim(($nameMatch[1] !== '') ? $nameMatch[1] : ($nameMatch[2] ?? ''));
            $value = rtrim($segments[1], "\r\n");
            if ($name === '') {
                continue;
            }

            if (!str_ends_with($name, '[]')) {
                $result[$name] = $value;

                continue;
            }

            $key = substr($name, 0, -2);
            if (!isset($result[$key]) || !is_array($result[$key])) {
                $result[$key] = [];
            }

            $result[$key][] = $value;
        }

        return $result;
    }

    /**
     * Extract request parameters carried in the body (form-encoded or application/json).
     *
     * @return array<array-key, mixed>
     */
    private function parseRequestBody(ServerRequestInterface $request): array
    {
        if (!in_array(strtoupper($request->getMethod()), ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            return [];
        }

        $contentType = $request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) $request->getBody(), true);

            return is_array($decoded) ? $decoded : [];
        }

        $parsed = (array) $request->getParsedBody();
        if ($parsed !== []) {
            return $parsed;
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str((string) $request->getBody(), $result);

            return $result;
        }

        if (str_contains($contentType, 'multipart/form-data')) {
            return $this->parseMultipartBody((string) $request->getBody(), $contentType);
        }

        return [];
    }
}
