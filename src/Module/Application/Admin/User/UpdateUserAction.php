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

final class UpdateUserAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'update_user';

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

        if (!Core::form_verify('edit_user')) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        /* Clean up the variables */
        $user_id         = (int) filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $username        = (string) scrub_in(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $fullname        = (string) scrub_in(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $email           = (string) scrub_in(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $website         = scrub_in(filter_input(INPUT_POST, 'website', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $access          = scrub_in(filter_input(INPUT_POST, 'access', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $pass1           = filter_input(INPUT_POST, 'password_1', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $pass2           = filter_input(INPUT_POST, 'password_2', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $state           = scrub_in(filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $city            = scrub_in(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $fullname_public = isset($_POST['fullname_public']);

        /* Setup the temp user */
        $client = new User($user_id);

        /* Verify Input */
        if (empty($username)) {
            AmpError::add('username', T_("A Username is required"));
        } else {
            if ($username != $client->username) {
                if ($this->userRepository->findByUsername($username) !== null) {
                    AmpError::add('username', T_("That Username already exists"));
                }
            }
        }
        if ($pass1 !== $pass2 && !empty($pass1)) {
            AmpError::add('password', T_("Your Passwords don't match"));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('You entered an invalid e-mail address'));
        }

        /* If we've got an error then show edit form! */
        if (AmpError::occurred()) {
            require_once Ui::find_template('show_edit_user.inc.php');

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        if ($access != $client->access) {
            $client->update_access($access);
        }
        if ($email != $client->email) {
            $client->update_email($email);
        }
        if ($website != $client->website) {
            $client->update_website($website);
        }
        if ($username != $client->username) {
            $client->update_username($username);
        }
        if ($fullname != $client->fullname) {
            $client->update_fullname($fullname);
        }
        if ($fullname_public != $client->fullname_public) {
            $client->update_fullname_public($fullname_public);
        }
        if ($pass1 == $pass2 && strlen($pass1)) {
            $client->update_password($pass1);
        }
        if ($state != $client->state) {
            $client->update_state($state);
        }
        if ($city != $client->city) {
            $client->update_city($city);
        }
        if (!$client->upload_avatar()) {
            $mindimension = sprintf(
                '%dx%d',
                (int) $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_MIN_WIDTH),
                (int) $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_MIN_HEIGHT)
            );
            $maxdimension = sprintf(
                '%dx%d',
                (int) $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_MAX_WIDTH),
                (int) $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_MAX_HEIGHT)
            );
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                /* HINT: %1 Minimum are dimensions (200x300), %2 Maximum Art dimensions (2000x3000) */
                sprintf(T_('Please check your image is within the minimum %1$s and maximum %2$s dimensions'),
                    $mindimension, $maxdimension),
                sprintf('%s/admin/users.php', $this->configContainer->getWebPath())
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                sprintf(T_('%s (%s) updated'), $client->username, $client->fullname),
                sprintf('%s/admin/users.php', $this->configContainer->getWebPath())
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
