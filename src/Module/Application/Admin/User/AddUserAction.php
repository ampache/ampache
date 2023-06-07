<?php
/*
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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddUserAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'add_user';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository
    ) {
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
        $this->userRepository  = $userRepository;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        if (!Core::form_verify('add_user')) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $username             = (string) scrub_in(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $fullname             = (string) scrub_in(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $email                = (string) scrub_in(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $website              = (string) scrub_in(filter_input(INPUT_POST, 'website', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $access               = (int) scrub_in(filter_input(INPUT_POST, 'access', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $catalog_filter_group = (int) scrub_in(filter_input(INPUT_POST, 'catalog_filter_group', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $pass1                = Core::get_post('password_1');
        $pass2                = Core::get_post('password_2');
        $state                = (string) scrub_in(filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $city                 = (string) scrub_in(Core::get_get('city'));

        if ($pass1 !== $pass2 || !strlen($pass1)) {
            AmpError::add('password', T_("Your Passwords don't match"));
        }

        if (empty($username)) {
            AmpError::add('username', T_('A Username is required'));
        }

        /* make sure the username doesn't already exist */
        if ($this->userRepository->idByUsername($username) > 0) {
            AmpError::add('username', T_('That Username already exists'));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('You entered an invalid e-mail address'));
        }

        /* If we've got an error then show add form! */
        if (AmpError::occurred()) {
            require_once Ui::find_template('show_add_user.inc.php');

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        /* Attempt to create the user */
        $user_id = User::create($username, $fullname, $email, $website, $pass1, $access, $catalog_filter_group, $state, $city);
        if ($user_id < 1) {
            AmpError::add('general', T_("The new User was not created"));
        }

        $user = $this->modelFactory->createUser($user_id);
        $user->upload_avatar();

        $useraccess = '';
        switch ($access) {
            case 5:
                $useraccess = T_('Guest');
                break;
            case 25:
                $useraccess = T_('User');
                break;
            case 50:
                $useraccess = T_('Content Manager');
                break;
            case 75:
                $useraccess = T_('Catalog Manager');
                break;
            case 100:
                $useraccess = T_('Admin');
        }

        $this->ui->showConfirmation(
            T_('New User Added'),
            /* HINT: %1 Username, %2 Access (Guest, User, Admin) */
            sprintf(T_('%1$s has been created with an access level of %2$s'), $username, $useraccess),
            sprintf('%s/admin/users.php', $this->configContainer->getWebPath())
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
