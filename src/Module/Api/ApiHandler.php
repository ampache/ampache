<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Method\LostPasswordMethod;
use Ampache\Module\Api\Method\RegisterMethod;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Module\Util\RequestParserInterface;
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
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ApiHandler implements ApiHandlerInterface
{
    private RequestParserInterface $requestParser;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    private NetworkCheckerInterface $networkChecker;

    private ContainerInterface $dic;

    private UserRepositoryInterface $userRepository;

    /** @var string[] */
    private array $deprecated = [
        'tag_albums',
        'tag_artists',
        'tag',
        'tag_songs',
        'tags',
    ];

    public function __construct(
        RequestParserInterface $requestParser,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        NetworkCheckerInterface $networkChecker,
        UserRepositoryInterface $userRepository,
        ContainerInterface $dic
    ) {
        $this->requestParser   = $requestParser;
        $this->streamFactory   = $streamFactory;
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
        $this->networkChecker  = $networkChecker;
        $this->dic             = $dic;
        $this->userRepository  = $userRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ApiOutputInterface $output
    ): ?ResponseInterface {
        $gatekeeper = new Gatekeeper(
            $this->userRepository,
            $request,
            $this->logger
        );
        // block html and visual output
        define('API', true);

        $action       = $this->requestParser->getFromRequest('action');
        $is_handshake = $action == HandshakeMethod::ACTION;
        $is_ping      = $action == PingMethod::ACTION;
        $is_register  = $action == RegisterMethod::ACTION;
        $is_forgotten = $action == LostPasswordMethod::ACTION;
        $is_public    = ($is_handshake || $is_ping || $is_register || $is_forgotten);
        $post         = ($request->getMethod() === 'POST')
            ? (array)$request->getParsedBody()
            : [];
        $input        = array_merge($request->getQueryParams(), $post);
        $header_auth  = false;
        if (!isset($input['auth'])) {
            $header_auth   = true;
            $input['auth'] = $gatekeeper->getAuth();
        }

        $api_format = $input['api_format'];
        $version    = (isset($input['version']))
            ? $input['version']
            : Api::$version;

        $user = (!empty($input['auth']))
            ? $gatekeeper->getUser()
            : null;
        $userId      = $user?->id ?? -1;
        $api_version = (int)Preference::get_by_user($userId, 'api_force_version');
        if (!in_array($api_version, Api::API_VERSIONS)) {
            $api_session = Session::get_api_version($input['auth']);
            $api_version = ($is_public || (isset($input['version']) && $header_auth))
                ? (int)substr($version, 0, 1)
                : $api_session;
            // Downgrade version 7 calls to 6. (You shouldn't use 7 but let it slide if you do.)
            if ($api_version == 7) {
                $api_version = 6;
            }
            // roll up the version if you haven't enabled the older versions
            if ($api_version == 3 && !Preference::get_by_user($userId, 'api_enable_3')) {
                $api_version = 4;
            }
            if ($api_version == 4 && !Preference::get_by_user($userId, 'api_enable_4')) {
                $api_version = 5;
            }
            if ($api_version == 5 && !Preference::get_by_user($userId, 'api_enable_5')) {
                $api_version = 6;
            }
            // if you haven't enabled any api versions then don't keep going
            if ($api_version == 6 && !Preference::get_by_user($userId, 'api_enable_6')) {
                $this->logger->warning(
                    'No API version available; check your options!',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
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
        // If you call api3 from json you don't get anything so change back to the latest version
        if ($api_format == 'json' && $api_version == 3) {
            $api_version = 6;
        }
        // send the version to API calls (this is used to determine return data for api4/api5)
        $input['api_version'] = $api_version;

        /*
         * Create a simplified session for header authenticated sessions
         * If you are sending a handshake, then return a valid auth session.
         * If you are doing anything else, you hide the session behind an MD5 hash of the username
         */
        if (
            $header_auth &&
            $user instanceof User
        ) {
            $data             = [];
            $data['username'] = $user->username;
            $data['value']    = $api_version;
            if ($is_handshake || $is_ping) {
                // for a handshake there needs to be a valid auth response (ping when sent needs one)
                if (
                    $input['auth'] !== md5((string)$user->username) &&
                    !Session::read($input['auth'])
                ) {
                    $data['type']  = 'api';
                    $input['auth'] = Session::create($data);
                }
            } else {
                $data['type']   = 'header';
                $data['apikey'] = md5((string)$user->username);
                // Session might not exist or has expired
                if (!Session::read($data['apikey'])) {
                    Session::destroy($data['apikey']);
                    Session::create($data);
                }

                // Continue with the new session string to hide your header token
                $input['auth'] = $data['apikey'];
            }

            if (in_array($api_version, Api::API_VERSIONS)) {
                Session::write($input['auth'], $api_version, $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PERPETUAL_API_SESSION));
            }
        }

        // If it's not a handshake then we can allow it to take up lots of time
        if (!$is_handshake) {
            set_time_limit(0);
        }

        // If we don't even have access control on then we can't use this!
        if (!$this->configContainer->get('access_control')) {
            ob_end_clean();

            $this->logger->warning(
                'Error Attempted to use the API with Access Control turned off',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                501,
                                T_('Access Control not Enabled')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                501,
                                T_('Access Control not Enabled')
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                                T_('Access Denied'),
                                $action,
                                'system'
                            )
                        )
                    );
                case 6:
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
            !$is_public &&
            (
                !$user instanceof User || // User is required for non-public methods
                (
                    !$header_auth &&
                    $input['auth'] === md5((string)$user->username)
                ) || // require header auth for simplified session
                $gatekeeper->sessionExists($input['auth']) === false // no valid session
            )
        ) {
            $this->logger->warning(
                sprintf('Invalid Session attempt to API [%s]', $action),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                401,
                                T_('Session Expired')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                401,
                                T_('Session Expired')
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                ErrorCodeEnum::INVALID_HANDSHAKE,
                                T_('Session Expired'),
                                $action,
                                'account'
                            )
                        )
                    );
                case 6:
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

        if (!$this->networkChecker->check(AccessTypeEnum::API, $userId, AccessLevelEnum::GUEST)) {
            $this->logger->warning(
                sprintf('Unauthorized access attempt to API [%s]', filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                403,
                                T_('Unauthorized access attempt to API - ACL Error')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                403,
                                T_('Unauthorized access attempt to API - ACL Error')
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                ErrorCodeEnum::FAILED_ACCESS_CHECK,
                                T_('Unauthorized access attempt to API - ACL Error'),
                                $action,
                                'account'
                            )
                        )
                    );
                case 6:
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
            !$is_public &&
            $user instanceof User
        ) {
            /**
             * @todo get rid of implicit user registration and pass the user explicitly
             */
            Session::createGlobalUser($user);

            // make sure the prefs are set too
            Preference::init();
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
                                405,
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
                                405,
                                T_('Invalid Request')
                            )
                        )
                    );
                }
                break;
            case 5:
                if (in_array($action, $this->deprecated)) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                ErrorCodeEnum::DEPRECATED,
                                T_('Deprecated'),
                                $action,
                                'removed'
                            )
                        )
                    );
                }
                $handlerClassName = Api5::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                ErrorCodeEnum::MISSING,
                                T_('Invalid Request'),
                                $action,
                                'system'
                            )
                        )
                    );
                }
                break;
            case 6:
            default:
                if (in_array($action, $this->deprecated)) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                ErrorCodeEnum::DEPRECATED,
                                T_('Deprecated'),
                                $action,
                                'removed'
                            )
                        )
                    );
                }
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
        }

        $debugHandler = $this->configContainer->get('api_debug_handler');
        if ($debugHandler) {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $this->_executeDebugHandler(
                $gatekeeper,
                $is_public,
                $action,
                $handlerClassName,
                $input,
                $user,
                $response,
                $output
            );
        }

        return $this->_executeHandler(
            $gatekeeper,
            $api_version,
            $is_public,
            $action,
            $handlerClassName,
            $input,
            $user,
            $response,
            $output
        );
    }

    /**
     * Run the default API handler with exception handling
     */
    private function _executeHandler(
        Gatekeeper $gatekeeper,
        int $api_version,
        bool $is_public,
        string $action,
        string $handlerClassName,
        array $input,
        ?User $user,
        ResponseInterface $response,
        ApiOutputInterface $output
    ): ?ResponseInterface {
        try {
            /**
             * This condition allows the `new` approach and the legacy one to co-exist.
             * After implementing the MethodInterface in all api methods, the condition will be removed
             *
             * @todo cleanup
             */
            $this->logger->notice(
                sprintf('API function [%s]', $handlerClassName),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            if (
                $user instanceof User &&
                $this->dic->has($handlerClassName) &&
                $this->dic->get($handlerClassName) instanceof MethodInterface
            ) {
                /** @var MethodInterface $handler */
                $handler = $this->dic->get($handlerClassName);

                $response = $handler->handle(
                    $gatekeeper,
                    $response,
                    $output,
                    $input,
                    $user
                );

                $gatekeeper->extendSession($input['auth']);

                return $response;
            } else {
                $params = [$input];

                /** @var callable $callback */
                $callback = [$handlerClassName, $action];

                if (!$is_public) {
                    $params[] = $user;
                }

                call_user_func_array(
                    $callback,
                    $params
                );

                $gatekeeper->extendSession($input['auth']);

                return null;
            }
        } catch (ApiException $error) {
            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                $error->getCode(),
                                $error->getMessage()
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                $error->getCode(),
                                $error->getMessage()
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                $error->getCode(),
                                $error->getMessage(),
                                $action,
                                $error->getType()
                            )
                        )
                    );
                case 6:
                default:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $error->getCode(),
                                $error->getMessage(),
                                $action,
                                $error->getType()
                            )
                        )
                    );
            }
        } catch (Throwable $error) {
            $this->logger->error(
                $error->getMessage(),
                [
                    LegacyLogger::CONTEXT_TYPE => self::class,
                    'method' => $action
                ]
            );

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error3(
                                405,
                                T_('Invalid Request')
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error4(
                                405,
                                T_('Invalid Request')
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error5(
                                ErrorCodeEnum::GENERIC_ERROR,
                                'Generic error',
                                $action,
                                'system'
                            )
                        )
                    );
                case 6:
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
     * Run the DEBUG API handler with NO exception handling!
     * @throws ApiException|Throwable
     */
    private function _executeDebugHandler(
        Gatekeeper $gatekeeper,
        bool $is_public,
        string $action,
        string $handlerClassName,
        array $input,
        ?User $user,
        ResponseInterface $response,
        ApiOutputInterface $output
    ): ?ResponseInterface {
        /**
         * This condition allows the `new` approach and the legacy one to co-exist.
         * After implementing the MethodInterface in all api methods, the condition will be removed
         *
         * @todo cleanup
         */
        $this->logger->notice(
            sprintf('DebugHandler: API function [%s]', $handlerClassName),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        if (
            $user instanceof User &&
            $this->dic->has($handlerClassName) &&
            $this->dic->get($handlerClassName) instanceof MethodInterface
        ) {
            /** @var MethodInterface $handler */
            $handler = $this->dic->get($handlerClassName);

            $response = $handler->handle(
                $gatekeeper,
                $response,
                $output,
                $input,
                $user
            );

            $gatekeeper->extendSession($input['auth']);

            return $response;
        } else {
            $params = [$input];

            /** @var callable $callback */
            $callback = [$handlerClassName, $action];

            if (!$is_public) {
                $params[] = $user;
            }

            call_user_func_array(
                $callback,
                $params
            );

            $gatekeeper->extendSession($input['auth']);

            return null;
        }
    }
}
