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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Preference;
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
    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository,
        RequestParserInterface $requestParser
    ) {
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
        $this->userRepository  = $userRepository;
        $this->requestParser   = $requestParser;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        if (!$this->requestParser->verifyForm('edit_user')) {
            throw new AccessDeniedException();
        }

        $body = (array)$request->getParsedBody();

        $this->ui->showHeader();

        /* Clean up the variables */
        $user_id              = (int) ($body['user_id'] ?? 0);
        $username             = scrub_in(htmlspecialchars($body['username'] ?? '', ENT_NOQUOTES));
        $fullname             = scrub_in(htmlspecialchars($body['fullname'] ?? '', ENT_NOQUOTES));
        $email                = scrub_in((string) filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $website              = (isset($body['website']))
            ? filter_var(urldecode($body['website']), FILTER_VALIDATE_URL) ?: ''
            : '';
        $access               = (int) ($body['access'] ?? 0);
        $catalog_filter_group = (int) ($body['catalog_filter_group'] ?? 0);
        $pass1                = Core::get_post('password_1');
        $pass2                = Core::get_post('password_2');
        $state                = scrub_in(htmlspecialchars($body['state'] ?? '', ENT_NOQUOTES));
        $city                 = scrub_in(htmlspecialchars($body['city'] ?? '', ENT_NOQUOTES));
        $fullname_public      = isset($_POST['fullname_public']);

        /* Setup the temp user */
        $client = $this->modelFactory->createUser($user_id);

        // option to reset user preferences to default
        $preset = (string)($body['preset'] ?? '');
        // you must explicitly disable the option on the edit page to reset user preferences admin can't be changed
        $prevent_override = ($client->access === 100)
            ? 1
            : (int)($body['prevent_override'] ?? 0);

        /* Verify Input */
        if (empty($username)) {
            AmpError::add('username', T_("A Username is required"));
        } elseif ($username != $client->username && $this->userRepository->idByUsername($username) > 0) {
            AmpError::add('username', T_("That Username already exists"));
        }
        if ($pass1 !== $pass2 && !empty($pass1)) {
            AmpError::add('password', T_("Your Passwords don't match"));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('You entered an invalid e-mail address'));
        }

        // Check the website for a valid site.
        if (
            isset($body['website']) &&
            strlen($body['website']) > 6 &&
            $website === ''
        ) {
            AmpError::add('website', T_('Error'));
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
        if ($catalog_filter_group != $client->catalog_filter_group) {
            $client->update_catalog_filter_group($catalog_filter_group);
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
        // reset preferences if allowed
        if (
            $prevent_override === 0 &&
            in_array($preset, ['system', 'default', 'minimalist', 'community'])
        ) {
            Preference::set_preset($client->getUsername(), $preset);
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
                sprintf(
                    T_('Please check your image is within the minimum %1$s and maximum %2$s dimensions'),
                    $mindimension,
                    $maxdimension
                ),
                sprintf('%s/users.php', $this->configContainer->getWebPath('/admin'))
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                sprintf(T_('%s (%s) updated'), scrub_out($client->username), scrub_out($client->fullname)),
                sprintf('%s/users.php', $this->configContainer->getWebPath('/admin'))
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
