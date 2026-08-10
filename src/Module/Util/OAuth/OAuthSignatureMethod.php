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

namespace Ampache\Module\Util\OAuth;

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class OAuthSignatureMethod
{
    /**
     * Build up the signature
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in OAuthRequest when the final
     * request is serialized
     */
    abstract public function build_signature(OAuthRequest $request, OAuthConsumer $consumer, OAuthToken $token): string;

    /**
     * Verifies that a given signature is correct
     */
    public function check_signature(OAuthRequest $request, OAuthConsumer $consumer, OAuthToken $token, string $signature): bool
    {
        $built = $this->build_signature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if ($built === '' || $signature === '') {
            return false;
        }

        if (strlen($built) !== strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($count = 0; $count < strlen($signature); $count++) {
            $result |= ord($built[$count]) ^ ord($signature[$count]);
        }

        return $result === 0;
    }

    /**
     * Needs to return the name of the Signature Method (ie HMAC-SHA1)
     */
    abstract public function get_name(): string;
}
