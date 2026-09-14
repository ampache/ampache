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

namespace Ampache\Module\Api\Subsonic;

use SimpleXMLElement;

/**
 * Response formatting/output plumbing shared by every legacy Subsonic endpoint handler.
 */
interface SubsonicResponseHandlerInterface
{
    /**
     * @return array{'subsonic-response': array{'status': string, 'version': string}}
     */
    public function addJsonResponse(string $function): array;

    public function addXmlResponse(string $function): SimpleXMLElement;

    /**
     * @param array<string, mixed> $input
     */
    public function checkParameter(array $input, string $parameter, string $function): mixed;

    /**
     * @param array<string, mixed> $input
     */
    public function errorOutput(array $input, int $errorCode, string $function): void;

    /**
     * @param array{'subsonic-response': array<string, mixed>} $json
     */
    public function jsonOutput(array $json): void;

    /**
     * @param array{'subsonic-response': array<string, mixed>} $json
     */
    public function jsonpOutput(array $json, string $callback): void;

    /**
     * @param array<string, mixed> $input
     * @param array{'subsonic-response': array<string, mixed>}|SimpleXMLElement|null $response
     */
    public function responseOutput(array $input, string $function, array|SimpleXMLElement|null $response = null): void;

    public function xmlOutput(SimpleXMLElement $xml): void;
}
