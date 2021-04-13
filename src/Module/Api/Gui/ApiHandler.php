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

namespace Ampache\Module\Api\Gui;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Gui\Authentication\Gatekeeper;
use Ampache\Module\Api\Gui\Exception\ApiException;
use Ampache\Module\Api\Gui\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Gui\Method\HandshakeMethod;
use Ampache\Module\Api\Gui\Method\MethodInterface;
use Ampache\Module\Api\Gui\Method\PingMethod;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ApiHandler implements ApiHandlerInterface
{
    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    private NetworkCheckerInterface $networkChecker;

    private PrivilegeCheckerInterface $privilegeChecker;

    private ContainerInterface $dic;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        NetworkCheckerInterface $networkChecker,
        PrivilegeCheckerInterface $privilegeChecker,
        ContainerInterface $dic
    ) {
        $this->streamFactory    = $streamFactory;
        $this->logger           = $logger;
        $this->configContainer  = $configContainer;
        $this->networkChecker   = $networkChecker;
        $this->privilegeChecker = $privilegeChecker;
        $this->dic              = $dic;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ApiOutputInterface $output
    ): ?ResponseInterface {
        $gatekeeper = new Gatekeeper(
            $request,
            $this->logger,
            $this->privilegeChecker,
            $this->configContainer
        );

        $action = (string) Core::get_request('action');

        // If it's not a handshake then we can allow it to take up lots of time
        if ($action != HandshakeMethod::ACTION) {
            set_time_limit(0);
        }

        // If we don't even have access control on then we can't use this!
        if (!$this->configContainer->get('access_control')) {
            ob_end_clean();

            $this->logger->warning(
                'Error Attempted to use the API with Access Control turned off',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                        T_('Access Denied'),
                        $action,
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
            $gatekeeper->sessionExists() === false &&
            $action !== HandshakeMethod::ACTION &&
            $action != PingMethod::ACTION
        ) {
            $this->logger->warning(
                sprintf('Invalid Session attempt to API [%s]', $action),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

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

        if ($action === HandshakeMethod::ACTION) {
            $userId = User::get_from_username($_REQUEST['user'])->id;
        } else {
            $userId = $gatekeeper->getUser()->id;
        }

        if (!$this->networkChecker->check(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)) {
            $this->logger->warning(
                sprintf('Unauthorized access attempt to API [%s]', Core::get_server('REMOTE_ADDR')),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
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
            /**
             * @todo get rid of implicit user registration and pass the user explicitly
             */
            $GLOBALS['user'] = $gatekeeper->getUser();
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
            /** @var MethodInterface $handler */
            $handler = $this->dic->get($handlerClassName);

            $response = $handler->handle(
                $gatekeeper,
                $response,
                $output,
                $request->getQueryParams()
            );

            $gatekeeper->extendSession();

            return $response;
        } catch (ApiException $e) {
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
        } catch (Throwable $e) {
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
