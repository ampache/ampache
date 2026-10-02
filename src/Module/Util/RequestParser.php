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

use Ampache\Module\System\LegacyLogger;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Provides utility methods related to frontend request parsing
 */
final readonly class RequestParser implements RequestParserInterface
{
    public function __construct(private LoggerInterface $logger) {}

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

    #[Override]
    public function verifyForm(string $formName): bool
    {
        return $this->consumeFormToken($formName, (string) ($_POST['form_validation'] ?? ''));
    }

    #[Override]
    public function verifyFormFromQuery(string $formName): bool
    {
        return $this->consumeFormToken($formName, (string) ($_GET['form_validation'] ?? ''));
    }

    /**
     * Reads the token once, whatever carried it: a second read must never succeed
     */
    private function consumeFormToken(string $formName, string $sid): bool
    {
        if (!isset($_SESSION['forms'][$sid])) {
            $this->logger->error(
                sprintf('Form %s not found in session, rejecting request', $formName),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return false;
        }

        /**
         * @var array{
         *     name: string,
         *     expire: int
         * } $form
         */
        $form = $_SESSION['forms'][$sid];
        unset($_SESSION['forms'][$sid]);

        if ($form['name'] === $formName) {
            $this->logger->debug(
                sprintf('Verified SID %s for form %s', $sid, $formName),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            if ($form['expire'] < time()) {
                $this->logger->error(
                    sprintf('Form %s is expired, rejecting request', $formName),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return false;
            }

            return true;
        }

        // OMG HAX0RZ
        $this->logger->error(
            sprintf('form %s failed consistency check, rejecting request', $formName),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return false;
    }
}
