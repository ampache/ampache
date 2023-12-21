<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\System\Core;

final class RequestParser implements RequestParserInterface
{
    /**
     * Return a $_REQUEST variable instead of calling directly
     */
    public function getFromRequest(string $variable): string
    {
        $variable = (string) ($_REQUEST[$variable] ?? '');
        if ($variable === '') {
            return '';
        }

        return scrub_in($variable);
    }

    /**
     * Return a $_POST variable instead of calling directly
     */
    public function getFromPost(string $variable): string
    {
        $variable = (string) ($_POST[$variable] ?? '');
        if ($variable === '') {
            return '';
        }

        return scrub_in($variable);
    }

    /**
     * Check if the form-submit is valid
     *
     * If the application expects a form-submit, check if it's actually
     * a valid submit (by validating a session token).
     * This method currently proxies the verification to a static method within
     * the core-class to make it testable.
     *
     * @return bool True, if the form-submit is considered valid
     *
     * @see Core::form_verify()
     */
    public function verifyForm(string $formName): bool
    {
        return Core::form_verify($formName);
    }
}
