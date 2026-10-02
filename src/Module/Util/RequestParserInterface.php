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

interface RequestParserInterface
{
    /**
     * Return a $_POST variable instead of calling directly
     */
    public function getFromPost(string $variable): string;

    /**
     * Return a $_REQUEST variable instead of calling directly
     */
    public function getFromRequest(string $variable): string;

    /**
     * Verifies a single-use form token carried in the post, consuming it whether or not it matches
     */
    public function verifyForm(string $formName): bool;

    /**
     * Verifies a form token carried back in the query string, as a third-party authorisation callback does
     */
    public function verifyFormFromQuery(string $formName): bool;
}
