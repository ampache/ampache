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

namespace Ampache\Module\Application\Login;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\EnvironmentInterface;
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
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teapot\StatusCode;

final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'default';

    private ConfigContainerInterface $configContainer;

    private AuthenticationManagerInterface $authenticationManager;

    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;

    private NetworkCheckerInterface $networkChecker;

    private EnvironmentInterface $environment;

    public function __construct(
        ConfigContainerInterface $configContainer,
        AuthenticationManagerInterface $authenticationManager,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger,
        NetworkCheckerInterface $networkChecker,
        EnvironmentInterface $environment
    ) {
        $this->configContainer       = $configContainer;
        $this->authenticationManager = $authenticationManager;
        $this->responseFactory       = $responseFactory;
        $this->logger                = $logger;
        $this->networkChecker        = $networkChecker;
        $this->environment           = $environment;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Avoid form login if still connected
        if ($this->configContainer->get('use_auth') && !filter_has_var(INPUT_GET, 'force_display')) {
            $auth = false;
            if (Session::exists('interface', $_COOKIE[$this->configContainer->getSessionName()])) {
                $auth = true;
            } else {
                if (Session::auth_remember()) {
                    $auth = true;
                }
            }
            if ($auth) {
                return $this->responseFactory
                    ->createResponse(StatusCode::FOUND)
                    ->withHeader(
                        'Location',
                        $this->configContainer->get('web_path')
                    );
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
            if (!$this->networkChecker->check(AccessLevelEnum::TYPE_INTERFACE, null, AccessLevelEnum::LEVEL_GUEST)) {
                throw new AccessDeniedException(
                    sprintf(
                        'Access denied: %s is not in the Interface Access list',
                        (string) filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)
                    )
                );
            }
        } // access_control is enabled

        /* Clean Auth values */
        unset($auth);

        if (empty($_REQUEST['step'])) {
            /* Check for posted username and password, or appropriate environment variable if using HTTP auth */
            if (($_POST['username']) ||
                (in_array('http', $this->configContainer->get(ConfigurationKeyEnum::AUTH_METHODS)) &&
                    (filter_has_var(INPUT_SERVER, 'REMOTE_USER') || filter_has_var(INPUT_SERVER, 'HTTP_REMOTE_USER')))) {
                /* If we are in demo mode let's force auth success */
                if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
                    $auth                         = array();
                    $auth['success']              = true;
                    $auth['info']['username']     = 'Admin - DEMO';
                    $auth['info']['fullname']     = 'Administrative User';
                    $auth['info']['offset_limit'] = 25;
                } else {
                    if (Core::get_post('username') !== '') {
                        $username = (string) scrub_in(Core::get_post('username'));
                        $password = Core::get_post('password');
                    } else {
                        if (filter_has_var(INPUT_SERVER, 'REMOTE_USER')) {
                            $username = (string) Core::get_server('REMOTE_USER');
                        } elseif (filter_has_var(INPUT_SERVER, 'HTTP_REMOTE_USER')) {
                            $username = (string) Core::get_server('HTTP_REMOTE_USER');
                        } else {
                            $username = '';
                        }
                        $password = '';
                    }

                    $auth = $this->authenticationManager->login($username, $password, true);

                    if ($auth['success']) {
                        $username = $auth['username'];
                    } elseif ($auth['ui_required']) {
                        echo $auth['ui_required'];

                        return null;
                    } else {
                        $this->logger->warning(
                            sprintf(
                                '%s From %s attempted to login and failed',
                                scrub_out($username),
                                filter_input(
                                    INPUT_SERVER,
                                    'REMOTE_ADDR',
                                    FILTER_SANITIZE_STRING,
                                    FILTER_FLAG_NO_ENCODE_QUOTES
                                )
                            ),
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                        AmpError::add('general', T_('Incorrect username or password'));
                    }
                }
            }
        } elseif (Core::get_request('step') == '2') {
            $auth_mod = $_REQUEST['auth_mod'];

            $auth = $this->authenticationManager->postAuth($auth_mod);

            /**
             * postAuth may return null, so this has to be considered in here
             */

            if ($auth['success']) {
                $username = $auth['username'];
            } else {
                $this->logger->error(
                    'Second step authentication failed',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                AmpError::add('general', $auth['error']);
            }
        }

        if (!empty($username) && isset($auth)) {
            $user = User::get_from_username($username);

            if ($user->disabled) {
                // if user disabled
                $auth['success'] = false;
                AmpError::add('general', T_('Account is disabled, please contact the administrator'));
                $this->logger->warning(
                    sprintf('%s is disabled and attempted to login', scrub_out($username)),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            } elseif (AmpConfig::get('prevent_multiple_logins')) {
                // if logged in multiple times
                $session_ip = $user->is_logged_in();
                $current_ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                if ($current_ip && ($current_ip != $session_ip)) {
                    $auth['success'] = false;
                    AmpError::add('general', T_('User is already logged in'));

                    $this->logger->info(
                        sprintf(
                            '%s is already logged in from %s and attempted to login from %s',
                            scrub_out($username),
                            (string) $session_ip,
                            $current_ip
                        ),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            } elseif ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AUTO_CREATE) && $auth['success'] && ! $user->username) {
                // This is run if we want to autocreate users who don't exist (useful for non-mysql auth)
                $access   = User::access_name_to_level($this->configContainer->get(ConfigurationKeyEnum::AUTO_USER) ?? 'guest');
                $fullname = array_key_exists('name', $auth) ? $auth['name'] : '';
                $email    = array_key_exists('email', $auth) ? $auth['email'] : '';
                $website  = array_key_exists('website', $auth) ? $auth['website'] : '';
                $state    = array_key_exists('state', $auth) ? $auth['state'] : '';
                $city     = array_key_exists('city', $auth) ? $auth['city'] : '';

                // Attempt to create the user
                if (User::create($username, $fullname, $email, $website, hash('sha256', mt_rand()), $access, $state, $city) > 0) {
                    $user = User::get_from_username($username);

                    if (array_key_exists('avatar', $auth)) {
                        $user->update_avatar($auth['avatar']['data'], $auth['avatar']['mime']);
                    }
                } else {
                    $auth['success'] = false;
                    AmpError::add('general', T_('Unable to create a local account'));
                }
            } // end if auto_create

            // This allows stealing passwords validated by external means such as LDAP
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AUTH_PASSWORD_SAVE) && $auth['success'] && isset($password)) {
                $user->update_password($password);
            }
        }

        /* If the authentication was a success */
        if (isset($auth) && $auth['success'] && isset($user)) {
            // $auth->info are the fields specified in the config file
            //   to retrieve for each user
            Session::create($auth);

            // Not sure if it was me or php tripping out,
            //   but naming this 'user' didn't work at all
            $_SESSION['userdata'] = $auth;

            // You really don't want to store the avatar
            //   in the SESSION.
            unset($_SESSION['userdata']['avatar']);

            // Record the IP of this person!
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::TRACK_USER_IP)) {
                $user->insert_ip_history();
            }

            if (isset($username)) {
                Session::create_user_cookie($username);
                if ($_POST['rememberme']) {
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

            $GLOBALS['user'] = $user;
            // If an admin, check for update
            if (
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AUTOUPDATE) &&
                $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ) {
                AutoUpdate::is_update_available();
            }
            // fix preferences that are missing for user
            User::fix_preferences($user->id);

            /* Make sure they are actually trying to get to this site and don't try
             * to redirect them back into an admin section
             */
            $web_path = $this->configContainer->getWebPath();
            if ((substr($_POST['referrer'], 0, strlen((string) $web_path)) == $web_path) &&
                strpos($_POST['referrer'], 'install.php') === false &&
                strpos($_POST['referrer'], 'login.php') === false &&
                strpos($_POST['referrer'], 'logout.php') === false &&
                strpos($_POST['referrer'], 'update.php') === false &&
                strpos($_POST['referrer'], 'activate.php') === false &&
                strpos($_POST['referrer'], 'admin') === false) {
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

        require Ui::find_template('show_login_form.inc.php');

        return null;
    }
}
