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

namespace Ampache\Gui\Form;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Override;

/**
 * Builds the login view, and owns the decisions the login template used to make for itself.
 */
final readonly class LoginFormViewFactory implements LoginFormViewFactoryInterface
{
    #[Override]
    public function create(): ?LoginFormView
    {
        $webPath      = AmpConfig::get_web_path();
        $authMethods  = AmpConfig::get_array('auth_methods');
        $oidcEnabled  = in_array('oidc', $authMethods, true);
        $mailEnabled  = Mailer::is_mail_enabled();
        $miniUrl      = $webPath . '/m/';
        $referrer     = $this->resolveReferrer($webPath);

        if (
            $oidcEnabled
            && AmpConfig::get('oidc_auto_redirect')
            && !isset($_GET['force_display'])
            && !AmpError::occurred()
            && !headers_sent()
        ) {
            header('Location: ' . $webPath . '/login.php?action=oidc');

            return null;
        }

        // The login page is its own document, so the page-level table markup must not be emitted around it
        if (!defined('TABLE_RENDERED')) {
            define('TABLE_RENDERED', 1);
        }

        if (!AmpConfig::get('disable_xframe_sameorigin', false)) {
            header('X-Frame-Options: SAMEORIGIN');
        }

        $_SESSION['login'] = true;

        $logoUrl = (string) AmpConfig::get('custom_login_logo');
        if ($logoUrl === '') {
            $logoUrl = Ui::get_logo_url();
        }

        $language = (string) AmpConfig::get('lang', 'en_US');

        return new LoginFormView(
            $webPath,
            str_replace('_', '-', $language),
            is_rtl($language) ? 'rtl' : 'ltr',
            (string) AmpConfig::get('site_charset', 'UTF-8'),
            (string) AmpConfig::get('site_title'),
            $logoUrl,
            Core::get_request('username'),
            $referrer,
            (string) AmpConfig::get('login_message'),
            $miniUrl,
            (string) AmpConfig::get('oidc_button_text', T_('Sign in with OpenID Connect')),
            $this->isMobileSession(),
            AmpConfig::get('session_length', 3600) >= AmpConfig::get('remember_length', 604800),
            $referrer !== '' && (str_starts_with($referrer, $miniUrl) || rtrim($referrer, '/') === rtrim($miniUrl, '/')),
            (bool) AmpConfig::get('allow_public_registration') && ($mailEnabled || (bool) AmpConfig::get('user_no_email_confirm', false)),
            $mailEnabled && (bool) AmpConfig::get('allow_lost_password', true),
            (bool) AmpConfig::get('show_mini_player', true),
            $oidcEnabled,
            (bool) AmpConfig::get('cookie_disclaimer')
        );
    }

    private function isMobileSession(): bool
    {
        $userAgent = Core::get_server('HTTP_USER_AGENT');

        return str_contains($userAgent, 'Mobile')
            && (
                str_contains($userAgent, 'Android')
                || str_contains($userAgent, 'iPhone')
                || str_contains($userAgent, 'iPad')
            );
    }

    /**
     * `Init::redirect()` hands us the page you actually asked for; fall back to the browser referrer.
     *
     * `$_POST` comes first so a failed attempt re-renders the form with the destination still attached,
     * otherwise a wrong password would quietly drop you back to the index page after the retry. Only ever
     * emit our own urls; the login action validates this again before redirecting to it.
     */
    private function resolveReferrer(string $webPath): string
    {
        $referrer = (string) ($_POST['referrer'] ?? $_GET['referrer'] ?? Core::get_server('HTTP_REFERER'));

        if (
            $referrer !== ''
            && (
                !str_starts_with($referrer, $webPath)
                // HTTP_REFERER is the login page itself on a retry; that isn't somewhere to send anyone
                || str_contains($referrer, 'login.php')
            )
        ) {
            return '';
        }

        return $referrer;
    }
}
