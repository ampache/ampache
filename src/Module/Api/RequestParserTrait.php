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
 * PSR-7 populates getParsedBody() from form/multipart bodies only, so application/json is decoded here from
 * the raw body. GET (and other bodyless methods) return nothing.
 */
trait RequestParserTrait
{
    /**
     * Extract request parameters carried in the body (form-encoded or application/json).
     *
     * @return array<string, mixed>
     */
    private function parseRequestBody(ServerRequestInterface $request): array
    {
        if (!in_array(strtoupper($request->getMethod()), ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            return [];
        }

        if (str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            $decoded = json_decode((string) $request->getBody(), true);

            return is_array($decoded) ? $decoded : [];
        }

        return (array) $request->getParsedBody();
    }
}
