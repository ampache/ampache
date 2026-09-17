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

namespace Ampache\Module\Playback;

/**
 * A mutable holder for state a StreamProxy call's callbacks capture, read back once curl_exec() returns; a
 * plain by-reference closure capture reads as always-null/false to static analysis, since the assignment
 * happens in a sibling closure it cannot see.
 *
 * One instance lives for the whole `proxy()` call, so `started`, once set, survives every later attempt: a
 * reconnect after a drop must know the client's response is already committed, and keep writing into it
 * rather than trying to redirect or re-send headers that were already flushed. `location` instead resets
 * per attempt via `resetLocation()` rather than a plain assignment, which is what keeps PHPStan from
 * narrowing it to always-null in the closures below the reset -- the mutation has to stay out of sight
 * behind a method call, not sit in the same scope as the read.
 */
final class StreamRedirect
{
    public ?string $location = null;
    public bool $started     = false;

    public function resetLocation(): void
    {
        $this->location = null;
    }
}
