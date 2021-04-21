<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=0);

namespace Ampache\Gui;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\LegacyLogger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides some kind of CSRF for web forms
 */
final class FormVerificator implements FormVerificatorInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * This registers a form with a SID, inserts it into the session
     * variables and then returns a string for use in the HTML form
     */
    public function register(string $name, string $type = 'post'): string
    {
        // Make ourselves a nice little sid
        $sid    = md5(uniqid((string)rand(), true));
        $window = AmpConfig::get('session_length');
        $expire = time() + $window;

        // Register it
        $_SESSION['forms'][$sid] = array('name' => $name, 'expire' => $expire);
        if (!isset($_SESSION['forms'][$sid])) {
            $this->logger->error(
                sprintf('Form %s not found in session, failed to register!', $sid),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        } else {
            $this->logger->debug(
                sprintf(
                    'Registered %s form %s with SID %s and expiration %d (%d seconds from now)',
                    $type,
                    $name,
                    $sid,
                    $expire,
                    $window
                ),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        switch ($type) {
            case 'get':
                $string = $sid;
                break;
            case 'post':
            default:
                $string = '<input type="hidden" name="form_validation" value="' . $sid . '" />';
                break;
        }

        return $string;
    }

    /**
     * This takes a form name and then compares it with the posted sid, if
     * they don't match then it returns false and doesn't let the person
     * continue
     */
    public function verify(
        ServerRequestInterface $request,
        string $name,
        string $type = 'post'
    ): bool {
        switch ($type) {
            case 'post':
                $sid = $_POST['form_validation'];
                break;
            case 'get':
                $sid = $_GET['form_validation'];
                break;
            case 'cookie':
                $sid = $_COOKIE['form_validation'];
                break;
            case 'request':
                $sid = $_REQUEST['form_validation'];
                break;
            default:
                return false;
        }

        if (!isset($_SESSION['forms'][$sid])) {
            $this->logger->error(
                sprintf('Form %s not found in session, rejecting request', $sid),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        $form = $_SESSION['forms'][$sid];
        unset($_SESSION['forms'][$sid]);

        if ($form['name'] == $name) {
            $this->logger->debug(
                sprintf('Verified SID %s for %s form %s', $sid, $type, $name),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            if ($form['expire'] < time()) {
                $this->logger->error(
                    sprintf('Form %s is expired, rejecting request', $sid),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                return false;
            }

            return true;
        }

        // OMG HAX0RZ
        $this->logger->error(
            sprintf('%s form %s failed consistency check, rejecting request', $type, $sid),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        return false;
    }
}
