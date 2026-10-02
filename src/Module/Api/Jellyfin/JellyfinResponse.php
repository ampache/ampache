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

namespace Ampache\Module\Api\Jellyfin;

/**
 * A Jellyfin method's result, independent of PSR-7 so a method class stays trivially testable. The front
 * controller (JellyfinApiApplication) is the only place this gets turned into a real HTTP response.
 */
final class JellyfinResponse
{
    /** Sentinel status: the handler already wrote headers/status/body itself (a raw byte stream). */
    private const int ALREADY_SENT_STATUS = 0;

    /** @param array<string, string> $headers */
    private function __construct(
        public readonly int $status,
        public readonly mixed $body,
        public readonly string $contentType,
        public readonly array $headers = [],
    ) {}

    /**
     * For a handler that streams raw bytes (audio, range/HEAD support) and calls
     * header()/http_response_code() itself — emit() must not layer a JSON envelope on top of that.
     */
    public static function alreadySent(): self
    {
        return new self(self::ALREADY_SENT_STATUS, null, 'application/json');
    }

    public static function badRequest(): self
    {
        return new self(400, null, 'application/json');
    }

    public static function forbidden(): self
    {
        return new self(403, null, 'application/json');
    }

    /** An uncaught error in a handler — a real client sees a clean 500 instead of a dropped connection. */
    public static function internalError(): self
    {
        return new self(500, null, 'application/json');
    }

    public static function json(mixed $body, int $status = 200): self
    {
        return new self($status, $body, 'application/json');
    }

    public static function noContent(int $status = 204): self
    {
        return new self($status, null, 'application/json');
    }

    public static function notFound(): self
    {
        return new self(404, null, 'application/json');
    }

    public static function redirect(string $location): self
    {
        return new self(302, null, 'text/html', ['Location' => $location]);
    }

    /** The spec's own "feature disabled" / "server not ready" signal — never 404, never 501. */
    public static function serviceUnavailable(string $message = ''): self
    {
        return new self(503, null, 'text/html', $message === '' ? [] : ['Message' => $message]);
    }

    public static function unauthorized(): self
    {
        return new self(401, null, 'application/json');
    }

    public function isAlreadySent(): bool
    {
        return $this->status === self::ALREADY_SENT_STATUS;
    }
}
