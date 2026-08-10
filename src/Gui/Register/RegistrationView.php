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

namespace Ampache\Gui\Register;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Core;
use Ampache\Module\User\Registration\RegistrationAgreementRendererInterface;
use Ampache\Module\Util\Ui;
use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Override;

/**
 * The public registration form, which is a whole standalone document rather than a page body.
 */
final class RegistrationView extends AbstractView
{
    /**
     * Where the answer to the rendered captcha is kept until the form comes back.
     */
    public const string CAPTCHA_SESSION_KEY = 'registration_captcha';

    public function __construct(
        private readonly string $webPath,
        private readonly RegistrationAgreementRendererInterface $registrationAgreementRenderer,
    ) {}

    /**
     * A fresh captcha, whose answer is stored server-side rather than posted alongside the form.
     */
    public function getCaptchaImage(): string
    {
        $builder = new CaptchaBuilder(null, new PhraseBuilder(10, '23456789ABCDEFGHJKMNPQRSTVWXYZ'));
        $builder->setMaxBehindLines(8);
        $builder->setMaxFrontLines(8);

        try {
            $builder->buildAgainstOCR(280, 128);
        } catch (Exception $error) {
            debug_event(self::class, 'Captcha OCR error: ' . $error->getMessage(), 3);
            $builder->build(280, 128);
        }

        $_SESSION[self::CAPTCHA_SESSION_KEY] = $builder->getPhrase();

        return $builder->inline();
    }

    public function getDocumentLanguage(): string
    {
        return str_replace('_', '-', (string) AmpConfig::get('lang', 'en_US'));
    }

    /**
     * @return array<string, string>
     */
    public function getFieldValues(): array
    {
        return [
            'fullname' => (string) scrub_in(Core::get_request('fullname')),
            'username' => (string) scrub_in(Core::get_request('username')),
            'email' => (string) scrub_in(Core::get_request('email')),
            'website' => (string) scrub_in(Core::get_request('website')),
            'state' => (string) scrub_in(Core::get_request('state')),
            'city' => (string) scrub_in(Core::get_request('city')),
        ];
    }

    public function getLogoUrl(): string
    {
        return (string) (AmpConfig::get('custom_login_logo') ?: Ui::get_logo_url());
    }

    public function getSiteCharset(): string
    {
        return (string) AmpConfig::get('site_charset', 'UTF-8');
    }

    public function getTitle(): string
    {
        return AmpConfig::get('site_title') . ' - ' . T_('Registration');
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    /**
     * A field is only rendered when the install asks for it, and only starred when it is mandatory.
     */
    public function isDisplayed(string $field): bool
    {
        return in_array($field, (array) AmpConfig::get('registration_display_fields'), true);
    }

    public function isMandatory(string $field): bool
    {
        return in_array($field, (array) AmpConfig::get('registration_mandatory_fields'), true);
    }

    public function renderAgreement(): string
    {
        return $this->registrationAgreementRenderer->render();
    }

    public function showAgreement(): bool
    {
        return (bool) AmpConfig::get('user_agreement');
    }

    public function showCaptcha(): bool
    {
        return (bool) AmpConfig::get('captcha_public_reg');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('register/registration.phtml');
    }
}
