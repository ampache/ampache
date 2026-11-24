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

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Crypto\SymmetricEncrypterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateUserAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'update_user';

    public function __construct(
        private PreferencesViewFactoryInterface $preferencesViewFactory,
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private SymmetricEncrypterInterface $symmetricEncrypter,
        private UserRepositoryInterface $userRepository,
        private Stats $stats,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // each of these refuses on its own, so an anonymous visitor cannot slip past a combined check
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || (int) (Core::get_global('user')?->getId()) === 0
            || $gatekeeper->isDemoMode()
            || !$this->requestParser->verifyForm('update_user')
        ) {
            throw new AccessDeniedException();
        }

        // block updates from simple users
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SIMPLE_USER_MODE)) {
            throw new AccessDeniedException();
        }

        $user = Core::get_global('user');
        if (!$user instanceof User) {
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // Remove the value
        unset($_SESSION['forms']['account']);

        $mandatory_fields = $this->configContainer->getArray(ConfigurationKeyEnum::REGISTRATION_MANDATORY_FIELDS);
        if (in_array('fullname', $mandatory_fields) && !$_POST['fullname']) {
            AmpError::add('fullname', T_("Please fill in your full name (first name, last name)"));
        }

        if (in_array('website', $mandatory_fields) && !$_POST['website']) {
            AmpError::add('website', T_("Please fill in your website"));
        }

        if (in_array('state', $mandatory_fields) && !$_POST['state']) {
            AmpError::add('state', T_("Please fill in your state"));
        }

        if (in_array('city', $mandatory_fields) && !$_POST['city']) {
            AmpError::add('city', T_("Please fill in your city"));
        }

        $password = (string) ($_POST['password1'] ?? '');
        if ($password !== '' && $password !== (string) ($_POST['password2'] ?? '')) {
            AmpError::add('password', T_("Passwords do not match"));
        }

        // stored reversibly encrypted rather than hashed, so it is applied apart once the rest of the form is accepted
        $subsonicPassword = (string) ($_POST['subsonic_password1'] ?? '');
        if ($subsonicPassword !== '' && $subsonicPassword !== (string) ($_POST['subsonic_password2'] ?? '')) {
            AmpError::add('subsonic_password', T_("Passwords do not match"));
        }

        $this->ui->showHeader();
        if (AmpError::occurred()) {
            AmpError::add('general', T_('Update failed'));
        } else {
            // only what this form sends, so the admin-only fields (access, username, public full name) stay as they are
            foreach (['fullname', 'email', 'website', 'state', 'city'] as $field) {
                if (!isset($_POST[$field])) {
                    continue;
                }

                $value = scrub_in((string) $_POST[$field]);
                if ($value !== (string) $user->$field) {
                    $user->{'update_' . $field}($value);
                }
            }

            if ($password !== '') {
                $user->update_password($password);
            }

            if (isset($_POST['clear_stats'])) {
                $this->stats->clear($user->getId());
            }

            $user->upload_avatar();
            $this->updateSubsonicSecret($user, $subsonicPassword);
            display_notification(T_('User updated successfully'));
        }

        $user = $gatekeeper->getUser();
        if ($user instanceof User) {
            echo $this->preferencesViewFactory->create(
                $gatekeeper,
                PreferenceSubject::ownPreferences($user),
                $user,
                (string) (((array) $request->getParsedBody())['tab'] ?? '')
            )->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    /**
     * Encrypts and stores a newly chosen Subsonic password. An empty value leaves the existing secret alone; clearing
     * it is a separate, confirmed admin action so a blank field can never silently revoke Subsonic access.
     */
    private function updateSubsonicSecret(User $user, string $subsonicPassword): void
    {
        if ($subsonicPassword === '') {
            return;
        }

        $secret = $this->symmetricEncrypter->encrypt($subsonicPassword);
        if ($secret === null) {
            display_notification(T_('Could not store the Subsonic password. Check that secret_key is set in your Ampache config'));

            return;
        }

        $this->userRepository->updateSubsonicSecret($user->getId(), $secret);
    }
}
