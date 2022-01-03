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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Api\Authentication\Gatekeeper;
use Ampache\Module\Api\Exception\ApiException;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\HandshakeMethod;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Method\PingMethod;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UserRepositoryInterface;
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

    private ContainerInterface $dic;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        NetworkCheckerInterface $networkChecker,
        ContainerInterface $dic
    ) {
        $this->streamFactory   = $streamFactory;
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
        $this->networkChecker  = $networkChecker;
        $this->dic             = $dic;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ApiOutputInterface $output
    ): ?ResponseInterface {
        $gatekeeper = new Gatekeeper(
            $request,
            $this->logger
        );

        $action        = (string)Core::get_request('action');
        $is_handshake  = $action == HandshakeMethod::ACTION;
        $is_ping       = $action == PingMethod::ACTION;
        $input         = $request->getQueryParams();
        $input['auth'] = $gatekeeper->getAuth();
        $api_format    = $input['api_format'];
        $version       = (isset($input['version'])) ? $input['version'] : Api::$version;
        $user          = $gatekeeper->getUser();
        $userId        = $user->id ?? -1;
        $api_version   = (int)Preference::get_by_user($userId, 'api_force_version');
        if ($api_version == 0) {
            $api_session = Session::get_api_version($input['auth']);
            $api_version = ($is_handshake || $is_ping)
                ? (int)substr($version, 0, 1)
                : $api_session;
            // roll up the version if you haven't enabled the older versions
            if ($api_version == 3 && !Preference::get_by_user($userId, 'api_enable_3')) {
                $api_version = 4;
            }
            if ($api_version == 4 && !Preference::get_by_user($userId, 'api_enable_4')) {
                $api_version = 5;
            }
            // if you haven't enabled any api versions then don't keep going
            if ($api_version == 5 && !Preference::get_by_user($userId, 'api_enable_5')) {
                $this->logger->warning(
                    'No API version available; check your options!',
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
        }
        // If you call api3 from json you don't get anything so change back to 5
        if ($api_format == 'json' && $api_version == 3) {
            $api_version = 5;
        }
        // send the version to API calls (this is used to determine return data for api4/api5)
        $input['api_version'] = $api_version;

        // If it's not a handshake then we can allow it to take up lots of time
        if (!$is_handshake) {
            set_time_limit(0);
        }

        // If we don't even have access control on then we can't use this!
        if (!$this->configContainer->get('access_control')) {
            ob_end_clean();

            $this->logger->warning(
                'Error Attempted to use the API with Access Control turned off',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                '501',
                                T_('Access Control not Enabled')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                '501',
                                T_('Access Control not Enabled')
                            )
                        )
                    );
                case 5:
                default:
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
        }

        /**
         * Verify the existence of the Session they passed in we do allow them to
         * login via this interface so we do have an exception for action=login
         */
        if (
            $gatekeeper->sessionExists() === false &&
            !$is_handshake &&
            !$is_ping
        ) {
            $this->logger->warning(
                sprintf('Invalid Session attempt to API [%s]', $action),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            ob_end_clean();

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                '401',
                                T_('Session Expired')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                '401',
                                T_('Session Expired')
                            )
                        )
                    );
                case 5:
                default:
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
        }

        if (!$this->networkChecker->check(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)) {
            $this->logger->warning(
                sprintf('Unauthorized access attempt to API [%s]', Core::get_server('REMOTE_ADDR')),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            ob_end_clean();

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                '403',
                                T_('Unauthorized access attempt to API - ACL Error')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                '403',
                                T_('Unauthorized access attempt to API - ACL Error')
                            )
                        )
                    );
                case 5:
                default:
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
        }

        if (
            !$is_handshake && !$is_ping
        ) {
            /**
             * @todo get rid of implicit user registration and pass the user explicitly
             */
            $GLOBALS['user'] = $user;
        }

        // Make sure beautiful url is disabled as it is not supported by most Ampache clients
        AmpConfig::set('stream_beautiful_url', false, true);

        // Retrieve the api method handler from the list of known methods
        switch ($api_version) {
            case 3:
                $handlerClassName = Api3::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null || $api_format == 'json') {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                '405',
                                T_('Invalid Request')
                            )
                        )
                    );
                }
                break;
            case 4:
                $handlerClassName = Api4::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                '405',
                                T_('Invalid Request')
                            )
                        )
                    );
                }
                break;
            case 5:
            default:
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
                break;
        }

        try {
            /**
             * This condition allows the `new` approach and the legacy one to co-exist.
             * After implementing the MethodInterface in all api methods, the condition will be removed
             *
             * @todo cleanup
             */
            $this->logger->info(
                sprintf('API function [%s]', $handlerClassName),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            if ($this->dic->has($handlerClassName) && $this->dic->get($handlerClassName) instanceof MethodInterface) {
                /** @var MethodInterface $handler */
                $handler = $this->dic->get($handlerClassName);

                $response = $handler->handle(
                    $gatekeeper,
                    $response,
                    $output,
                    $input
                );

                $gatekeeper->extendSession();

                return $response;
            } else {
                call_user_func(
                    [$handlerClassName, $action],
                    $input
                );

                $gatekeeper->extendSession();

                return null;
            }
        } catch (ApiException $e) {
            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                $e->getCode(),
                                $e->getMessage()
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                $e->getCode(),
                                $e->getMessage()
                            )
                        )
                    );
                case 5:
                default:
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
            }
        } catch (Throwable $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    LegacyLogger::CONTEXT_TYPE => __CLASS__,
                    'method' => $action
                ]
            );

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                '405',
                                T_('Invalid Request')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                '405',
                                T_('Invalid Request')
                            )
                        )
                    );
                case 5:
                default:
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

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
