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

namespace Ampache\Module\Application\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\User\PrivateMessage\Exception\PrivateMessageCreationException;
use Ampache\Module\User\PrivateMessage\PrivateMessageCreatorInterface;
use Ampache\Module\Util\UiInterface;
use PHPMailer\PHPMailer\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddMessageAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_message';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private PrivateMessageCreatorInterface $privateMessageCreator;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        PrivateMessageCreatorInterface $privateMessageCreator,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer       = $configContainer;
        $this->ui                    = $ui;
        $this->privateMessageCreator = $privateMessageCreator;
        $this->modelFactory          = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) === false
        ) {
            throw new AccessDeniedException('Access Denied: sociable features are not enabled.');
        }
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $data = $request->getParsedBody();

        $this->ui->showHeader();

        $subject = trim(strip_tags(filter_var($data['subject'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        $message = trim(strip_tags(filter_var($data['message'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        $to_user = User::get_from_username($data['to_user'] ?? '');

        if (!$to_user) {
            AmpError::add('to_user', T_('Unknown user'));
        }
        if (empty($subject)) {
            AmpError::add('subject', T_('Subject is required'));
        }
        if (AmpError::occurred()) {
            $this->ui->show('show_add_pvmsg.inc.php');
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        try {
            $this->privateMessageCreator->create(
                $to_user,
                $this->modelFactory->createUser($gatekeeper->getUserId()),
                $subject,
                $message
            );

            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Message has been sent'),
                sprintf(
                    '%s/browse.php?action=pvmsg',
                    $this->configContainer->getWebPath()
                )
            );
        } catch (PrivateMessageCreationException | Exception $e) {
            $this->ui->show('show_add_pvmsg.inc.php');
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
