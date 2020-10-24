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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Model\User;
use Ampache\Module\Api\Exception\ApiException;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\ApiMethodException;
use Ampache\Module\Api\Method\HandshakeMethod;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Method\PingMethod;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ApiHandler implements ApiHandlerInterface
{
    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    private ContainerInterface $dic;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        ContainerInterface $dic
    ) {
        $this->streamFactory = $streamFactory;
        $this->logger        = $logger;
        $this->dic           = $dic;
    }

    public function handle(
        ResponseInterface $response,
        ApiOutputInterface $output
    ): ?ResponseInterface {
        $action = (string) Core::get_request('action');

        // If it's not a handshake then we can allow it to take up lots of time
        if ($action != HandshakeMethod::ACTION) {
            set_time_limit(0);
        }

        // If we don't even have access control on then we can't use this!
        if (!AmpConfig::get('access_control')) {
            ob_end_clean();
            debug_event('api', 'Error Attempted to use the API with Access Control turned off', 3);

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                        T_('Access Denied'),
                        Core::get_request('action'),
                        'system'
                    )
                )
            );
        }

        /**
         * Verify the existence of the Session they passed in we do allow them to
         * login via this interface so we do have an exception for action=login
         */
        if (
            !Session::exists('api', Core::get_request('auth')) &&
            $action !== HandshakeMethod::ACTION &&
            $action != PingMethod::ACTION
        ) {
            debug_event('Access Denied', 'Invalid Session attempt to API [' . $action . ']', 3);
            ob_end_clean();

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        ErrorCodeEnum::INVALID_HANDSHAKE,
                        T_('Session Expired'),
                        $action,
                        'account'
                    )
                )
            );
        }

        // If the session exists then let's try to pull some data from it to see if we're still allowed to do this
        $username = ($action == HandshakeMethod::ACTION) ? $_REQUEST['user'] : Session::username($_REQUEST['auth']);

        if (!Access::check_network('init-api', $username, 5)) {
            debug_event('Access Denied', 'Unauthorized access attempt to API [' . Core::get_server('REMOTE_ADDR') . ']', 3);
            ob_end_clean();

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        ErrorCodeEnum::FAILED_ACCESS_CHECK,
                        T_('Unauthorized access attempt to API - ACL Error'),
                        $action,
                        'account'
                    )
                )
            );
        }

        if (
            $action != HandshakeMethod::ACTION && $action != PingMethod::ACTION
        ) {
            if (isset($_REQUEST['user'])) {
                $GLOBALS['user'] = User::get_from_username(Core::get_request('user'));
            } else {
                debug_event('api', 'API session [' . Core::get_request('auth') . ']', 3);
                $GLOBALS['user'] = User::get_from_username(Session::username(Core::get_request('auth')));
            }
        }

        // Make sure beautiful url is disabled as it is not supported by most Ampache clients
        AmpConfig::set('stream_beautiful_url', false, true);

        // Retrieve the api method handler from the list of known methods
        $handlerClassName = Api::METHOD_LIST[$action] ?? null;
        if ($handlerClassName === null) {
            ob_end_clean();

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        ErrorCodeEnum::MISSING,
                        T_('Invalid Request'),
                        $action,
                        'system'
                    )
                )
            );
        }

        try {
            /**
             * This condition allows the `new` approach and the legacy one to co-exist.
             * After implementing the MethodInterface in all api methods, the condition will be removed
             *
             * @todo cleanup
             */
            if ($this->dic->has($handlerClassName) && $this->dic->get($handlerClassName) instanceof MethodInterface) {
                /** @var MethodInterface $handler */
                $handler = $this->dic->get($handlerClassName);

                return $handler->handle($response, $_GET);
            } else {
                call_user_func([$handlerClassName, $action], $_GET);

                return null;
            }
        } catch (ApiMethodException $e) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $e->getCode(),
                        $e->getMessage(),
                        $action,
                        $e->getType()
                    )
                )
            );
        } catch (ApiException | Throwable $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    LegacyLogger::CONTEXT_TYPE => __CLASS__,
                    'method' => $action
                ]
            );

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        ErrorCodeEnum::GENERIC_ERROR,
                        'Generic error',
                        $action,
                        'system'
                    )
                )
            );
        }
    }
}
