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

/**
 * Works out where a deletion confirmation page should send the browser next.
 *
 * Deletion links carry a `burl` parameter holding the base64 encoded address of the page the user was
 * looking at when they clicked delete. That value is user supplied, so it is validated before it is ever
 * used as a form target, and the page it points at may be the very page the deletion is about to destroy.
 */
interface DeletionUrlResolverInterface
{
    /**
     * Decode and validate a `burl` parameter, returning the empty string when it is missing or unusable.
     *
     * Anything that is not an absolute http(s) address inside this Ampache install is rejected, which keeps
     * the value safe to render as a form action.
     */
    public function resolveBurl(?string $encodedBurl): string;

    /**
     * Pick the address to continue to once an object has been deleted.
     *
     * When the caller came from the deleted object's own page that page no longer exists, so the parent is
     * used instead (and the fallback when there is no parent); any other origin page is returned unchanged.
     *
     * @param string $burl Validated origin address, as returned by resolveBurl()
     * @param string $selfIdParam Query parameter naming the object on its own detail page, e.g. `song_id`
     * @param int $selfIdValue Id of the object being deleted
     * @param string $parentUrl Absolute address of the parent object, or an empty string when there is none
     * @param string $fallbackUrl Absolute address used when neither the origin page nor a parent can be used
     */
    public function resolveContinueUrl(
        string $burl,
        string $selfIdParam,
        int $selfIdValue,
        string $parentUrl,
        string $fallbackUrl,
    ): string;
}
