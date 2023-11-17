<?php

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

declare(strict_types=0);

namespace Ampache\Module\Authentication\Authenticator;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Authentication\Openid;
use Ampache\Repository\UserRepositoryInterface;
use Auth_OpenID;
use Auth_OpenID_PAPE_Request;
use Auth_OpenID_SRegRequest;
use Auth_OpenID_SRegResponse;

final class OpenIdAuthenticator implements AuthenticatorInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function auth(string $username, string $password): array
    {
        unset($password);
        $results = array();
        // Username contains the openid url. We don't care about password here.
        $website = $username;
        if (strpos($website, 'http://') === 0 || strpos($website, 'https://') === 0) {
            $consumer = Openid::get_consumer();
            if ($consumer) {
                $auth_request = $consumer->begin($website);
                if ($auth_request) {
                    $sreg_request = Auth_OpenID_SRegRequest::build(// Required
                        array('nickname'), // Optional
                        array('fullname', 'email'));
                    if ($sreg_request) {
                        $auth_request->addExtension($sreg_request);
                    }
                    $pape_request = new Auth_OpenID_PAPE_Request(Openid::get_policies());
                    if ($pape_request) {
                        $auth_request->addExtension($pape_request);
                    }

                    // Redirect the user to the OpenID server for authentication.
                    // Store the token for this authentication so we can verify the response.

                    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
                    // form to send a POST request to the server.
                    if ($auth_request->shouldSendRedirect()) {
                        $redirect_url = $auth_request->redirectURL(AmpConfig::get('web_path'),
                            Openid::get_return_url());
                        if (Auth_OpenID::isFailure($redirect_url)) {
                            $results['success'] = false;
                            $results['error']   = 'Could not redirect to server: ' . $redirect_url->message;
                        } else {
                            // Send redirect.
                            debug_event(__CLASS__, 'OpenID 1: redirecting to ' . $redirect_url, 5);
                            header("Location: " . $redirect_url);
                        }
                    } else {
                        // Generate form markup and render it.
                        $form_id   = 'openid_message';
                        $form_html = $auth_request->htmlMarkup(AmpConfig::get('web_path'), Openid::get_return_url(),
                            false, array('id' => $form_id));

                        if (Auth_OpenID::isFailure($form_html)) {
                            $results['success'] = false;
                            $results['error']   = 'Could not render authentication form.';
                        } else {
                            debug_event(__CLASS__, 'OpenID 2: javascript redirection code to OpenID form.', 5);
                            // First step is a success, UI interaction required.
                            $results['success']     = false;
                            $results['ui_required'] = $form_html;
                        }
                    }
                } else {
                    debug_event(__CLASS__, $website . ' is not a valid OpenID.', 3);
                    $results['success'] = false;
                    $results['error']   = 'Not a valid OpenID.';
                }
            } else {
                debug_event(__CLASS__, 'Cannot initialize OpenID resources.', 3);
                $results['success'] = false;
                $results['error']   = 'Cannot initialize OpenID resources.';
            }
        } else {
            debug_event(__CLASS__, 'Skipped OpenID authentication: missing scheme in ' . $website . '.', 3);
            $results['success'] = false;
            $results['error']   = 'Missing scheme in OpenID.';
        }

        return $results;
    }

    public function postAuth(): ?array
    {
        $result         = [];
        $result['type'] = 'openid';
        $consumer       = Openid::get_consumer();
        if ($consumer) {
            $response = $consumer->complete(Openid::get_return_url());

            if ($response->status == Auth_OpenID_CANCEL) {
                $result['success'] = false;
                $result['error']   = 'OpenID verification cancelled.';
            } else {
                if ($response->status == Auth_OpenID_FAILURE) {
                    $result['success'] = false;
                    $result['error']   = 'OpenID authentication failed: ' . $response->message;
                } else {
                    if ($response->status == Auth_OpenID_SUCCESS) {
                        // Extract the identity URL and Simple Registration data (if it was returned).
                        $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
                        $sreg      = $sreg_resp->contents();

                        $result['website'] = $response->getDisplayIdentifier();
                        if (@$sreg['email']) {
                            $result['email'] = $sreg['email'];
                        }

                        if (@$sreg['nickname']) {
                            $result['username'] = $sreg['nickname'];
                        }

                        if (@$sreg['fullname']) {
                            $result['name'] = $sreg['fullname'];
                        }

                        $users = $this->userRepository->findByWebsite($result['website']);
                        if (count($users) > 0) {
                            if (count($users) == 1) {
                                $user                = new User($users[0]);
                                $result['success']   = true;
                                $result['username']  = $user->username;
                            } else {
                                // Several users for the same website/openid? Allowed but stupid, try to get a match on username.
                                // Should we make website field unique?
                                foreach ($users as $user_id) {
                                    $user = new User($user_id);
                                    if ($user->username == $result['username']) {
                                        $result['success']  = true;
                                        $result['username'] = $user->username;
                                    }
                                }
                            }
                        } else {
                            // Don't return success if a user already exists for this username but don't have this openid identity as website
                            $user = User::get_from_username($result['username']);
                            if ($user->id) {
                                $result['success'] = false;
                                $result['error']   = 'No user associated to this OpenID and username already taken.';
                            } else {
                                $result['success'] = true;
                                $result['error']   = 'No user associated to this OpenID.';
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
