<?php

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

namespace Ampache\Module\Application\Login;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teapot\StatusCode;

final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'default';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private AuthenticationManagerInterface $authenticationManager;

    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;

    private NetworkCheckerInterface $networkChecker;

    private UiInterface $ui;

    private UserTrackerInterface $userTracker;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        AuthenticationManagerInterface $authenticationManager,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger,
        NetworkCheckerInterface $networkChecker,
        UiInterface $ui,
        UserTrackerInterface $userTracker
    ) {
        $this->requestParser         = $requestParser;
        $this->configContainer       = $configContainer;
        $this->authenticationManager = $authenticationManager;
        $this->responseFactory       = $responseFactory;
        $this->logger                = $logger;
        $this->networkChecker        = $networkChecker;
        $this->ui                    = $ui;
        $this->userTracker           = $userTracker;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Avoid form login if still connected
        if ($this->configContainer->get('use_auth') && !isset($_GET['force_display'])) {
            $auth = false;
            $name = $this->configContainer->getSessionName();
            if (array_key_exists($name, $_COOKIE) && Session::exists(AccessTypeEnum::INTERFACE->value, $_COOKIE[$this->configContainer->getSessionName()])) {
                $auth = true;
            } elseif (Session::auth_remember()) {
                $auth = true;
            }
            if ($auth) {
                return $this->responseFactory
                    ->createResponse(StatusCode::FOUND)
                    ->withHeader(
                        'Location',
                        $this->configContainer->get('web_path')
                    );
            } elseif (array_key_exists($name, $_COOKIE)) {
                // now auth so unset this cookie
                setcookie($name, '', -1, (string)AmpConfig::get('cookie_path'));
                setcookie($name, '', -1);
            }
        }

        Session::create_cookie();
        Preference::init();

        /**
         * If Access Control is turned on then we don't
         * even want them to be able to get to the login
         * page if they aren't in the ACL
         */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ACCESS_CONTROL)) {
            if (!$this->networkChecker->check(AccessTypeEnum::INTERFACE, null, AccessLevelEnum::GUEST)) {
                throw new AccessDeniedException(
                    sprintf(
                        'Access denied: %s is not in the Interface Access list',
                        Core::get_user_ip()
                    )
                );
            }
        } // access_control is enabled

        /* Clean Auth values */
        unset($auth);

        if (empty($this->requestParser->getFromRequest('step'))) {
            /* Check for posted username and password, or appropriate environment variable if using HTTP auth */
            if (
                (isset($_POST['username'])) ||
                (in_array('http', $this->configContainer->get(ConfigurationKeyEnum::AUTH_METHODS)) && (isset($_SERVER['REMOTE_USER']) || isset($_SERVER['HTTP_REMOTE_USER'])))
            ) {
                /* If we are in demo mode let's force auth success */
                if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
                    $auth                         = [];
                    $auth['success']              = true;
                    $auth['info']['username']     = 'Admin - DEMO';
                    $auth['info']['fullname']     = 'Administrative User';
                    $auth['info']['offset_limit'] = 50;
                } else {
                    if (Core::get_post('username') !== '') {
                        $username = (string)$_POST['username'];
                        $password = $_POST['password'] ?? '';
                    } else {
                        if (isset($_SERVER['REMOTE_USER'])) {
                            $username = (string) Core::get_server('REMOTE_USER');
                        } elseif (isset($_SERVER['HTTP_REMOTE_USER'])) {
                            $username = (string) Core::get_server('HTTP_REMOTE_USER');
                        } else {
                            $username = '';
                        }
                        $password = '';
                    }

                    $auth = $this->authenticationManager->login($username, $password, true);

                    if ($auth['success']) {
                        $username = $auth['username'];
                    } elseif (array_key_exists('ui_required', $auth)) {
                        echo $auth['ui_required'];

                        return null;
                    } else {
                        $this->logger->warning(
                            sprintf(
                                '%s From %s attempted to login and failed',
                                scrub_out($username),
                                Core::get_user_ip()
                            ),
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                        AmpError::add('general', T_('Incorrect username or password'));
                    }
                }
            }
        } elseif ($this->requestParser->getFromRequest('step') == '2') {
            $auth_mod = $this->requestParser->getFromRequest('auth_mod');

            $auth = $this->authenticationManager->postAuth($auth_mod);

            /**
             * postAuth may return null, so this has to be considered in here
             */
            if (isset($auth['success']) && $auth['success']) {
                $username = $auth['username'];
            } else {
                $this->logger->error(
                    'Second step authentication failed',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                AmpError::add('general', $auth['error'] ?? '');
            }
        }

        $user = null;
        if (!empty($username) && isset($auth)) {
            $user = User::get_from_username($username);

            if ($user instanceof User && $user->disabled) {
                // if user disabled
                $auth['success'] = false;
                AmpError::add('general', T_('Account is disabled, please contact the administrator'));
                $this->logger->warning(
                    sprintf('%s is disabled and attempted to login', scrub_out($username)),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            } elseif (AmpConfig::get('prevent_multiple_logins')) {
                // if logged in multiple times
                $session_ip = ($user instanceof User) ? $user->is_logged_in() : false;
                $current_ip = Core::get_user_ip();
                if ($current_ip && ($current_ip != $session_ip)) {
                    $auth['success'] = false;
                    AmpError::add('general', T_('User is already logged in'));

                    $this->logger->notice(
                        sprintf(
                            '%s is already logged in from %s and attempted to login from %s',
                            scrub_out($username),
                            (string) $session_ip,
                            $current_ip
                        ),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            } elseif ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AUTO_CREATE) && $auth['success'] && !$user instanceof User) {
                // This is run if we want to autocreate users who don't exist (useful for non-mysql auth)
                $access   = AccessLevelEnum::fromTextual($this->configContainer->get(ConfigurationKeyEnum::AUTO_USER) ?? 'guest');
                $fullname = array_key_exists('name', $auth) ? $auth['name'] : '';
                $email    = array_key_exists('email', $auth) ? $auth['email'] : '';
                $website  = array_key_exists('website', $auth) ? $auth['website'] : '';
                $state    = array_key_exists('state', $auth) ? $auth['state'] : '';
                $city     = array_key_exists('city', $auth) ? $auth['city'] : '';
                $dfg      = array_key_exists('catalog_filter_group', $auth) ? $auth['catalog_filter_group'] : 0;

                // Attempt to create the user
                $user_id = User::create($username, $fullname, $email, $website, hash('sha256', bin2hex(random_bytes(20))), $access, $dfg, $state, $city);
                if ($user_id > 0) {
                    // tell me you're creating the user
                    $this->logger->notice(
                        sprintf(
                            'Created missing user %s',
                            scrub_out($username)
                        ),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    $user = new User($user_id);

                    if (array_key_exists('avatar', $auth)) {
                        $user->update_avatar($auth['avatar']['data'], $auth['avatar']['mime']);
                    }
                } else {
                    $auth['success'] = false;
                    $this->logger->error(
                        'Unable to create a local account',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    AmpError::add('general', T_('Unable to create a local account'));
                }
            } // end if auto_create

            // This allows stealing passwords validated by external means such as LDAP
            if (
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AUTH_PASSWORD_SAVE) &&
                isset($auth) &&
                $auth['success'] &&
                isset($password) &&
                $user instanceof User
            ) {
                $user->update_password($password);
            }
        }

        /* If the authentication was a success */
        if (isset($auth) && $auth['success'] && $user instanceof User) {
            // $auth->info are the fields specified in the config file
            // to retrieve for each user
            Session::create($auth);

            // Not sure if it was me or php tripping out, but naming this 'user' didn't work at all
            $_SESSION['userdata'] = $auth;

            // You really don't want to store the avatar
            // in the SESSION.
            unset($_SESSION['userdata']['avatar']);

            // Record the IP of this person!
            $this->userTracker->trackIpAddress($user);

            if (isset($username)) {
                Session::create_user_cookie($username);
                if (isset($_POST['rememberme'])) {
                    Session::create_remember_cookie($username);
                }
            }

            // Update data from this auth if ours are empty or if config asks us to
            $external_auto_update = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::EXTERNAL_AUTO_UPDATE);

            if (($external_auto_update || empty($user->fullname)) && !empty($auth['name'])) {
                $user->update_fullname($auth['name']);
            }
            if (($external_auto_update || empty($user->email)) && !empty($auth['email'])) {
                $user->update_email($auth['email']);
            }
            if (($external_auto_update || empty($user->website)) && !empty($auth['website'])) {
                $user->update_website($auth['website']);
            }
            if (($external_auto_update || empty($user->state)) && !empty($auth['state'])) {
                $user->update_state($auth['state']);
            }
            if (($external_auto_update || empty($user->city)) && !empty($auth['city'])) {
                $user->update_city($auth['city']);
            }
            if (($external_auto_update || empty($user->f_avatar)) && !empty($auth['avatar'])) {
                $user->update_avatar($auth['avatar']['data'], $auth['avatar']['mime']);
            }

            Session::createGlobalUser($user);
            // If an admin, check for update
            if (
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AUTOUPDATE) &&
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ) {
                // admins need to know if an update is available
                AutoUpdate::is_update_available();
                // Make sure all default preferences are set
                Preference::set_defaults();
            }
            // fix preferences that are missing for user
            User::fix_preferences($user->id);

            /* Make sure they are actually trying to get to this site and don't try
             * to redirect them back into an admin section
             */
            $web_path = $this->configContainer->getWebPath();
            if (
                (substr($_POST['referrer'], 0, strlen((string) $web_path)) == $web_path) &&
                strpos($_POST['referrer'], 'install.php') === false &&
                strpos($_POST['referrer'], 'login.php') === false &&
                strpos($_POST['referrer'], 'logout.php') === false &&
                strpos($_POST['referrer'], 'update.php') === false &&
                strpos($_POST['referrer'], 'activate.php') === false &&
                strpos($_POST['referrer'], 'admin') === false
            ) {
                return $this->responseFactory
                    ->createResponse(StatusCode::FOUND)
                    ->withHeader(
                        'Location',
                        $_POST['referrer']
                    );
            } // if we've got a referrer

            return $this->responseFactory
                ->createResponse(StatusCode::FOUND)
                ->withHeader(
                    'Location',
                    sprintf('%s/index.php', $this->configContainer->getWebPath())
                );
        } // auth success

        $this->ui->show('show_login_form.inc.php');

        return null;
    }
}
