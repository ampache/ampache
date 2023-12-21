<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessListManagerInterface;
use Ampache\Module\Authorization\Exception\AclItemDuplicationException;
use Ampache\Module\Authorization\Exception\InvalidEndIpException;
use Ampache\Module\Authorization\Exception\InvalidIpRangeException;
use Ampache\Module\Authorization\Exception\InvalidStartIpException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class AddHostAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_host';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private AccessListManagerInterface $accessListManager;

    private LoggerInterface $logger;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        AccessListManagerInterface $accessListManager,
        LoggerInterface $logger
    ) {
        $this->ui                = $ui;
        $this->configContainer   = $configContainer;
        $this->accessListManager = $accessListManager;
        $this->logger            = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Make sure we've got a valid form submission
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false ||
            !Core::form_verify('add_acl')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $data    = $request->getParsedBody();
        $startIp = $data['start'] ?? '';
        $endIp   = $data['end'] ?? '';

        try {
            $this->accessListManager->create(
                $startIp,
                $endIp,
                $data['name'] ?? '',
                (int)($data['user'] ?? -1),
                (int)($data['level'] ?? 0),
                $data['type'] ?? '',
                $data['addtype'] ?? ''
            );
        } catch (InvalidIpRangeException $e) {
            AmpError::add('start', T_('IP Address version mismatch'));
            AmpError::add('end', T_('IP Address version mismatch'));
        } catch (InvalidStartIpException $e) {
            AmpError::add('start', T_('An Invalid IPv4 / IPv6 Address was entered'));
        } catch (InvalidEndIpException $e) {
            AmpError::add('end', T_('An Invalid IPv4 / IPv6 Address was entered'));
        } catch (AclItemDuplicationException $e) {
            $this->logger->critical(
                'Error: An ACL entry equal to the created one already exists. Not adding duplicate: ' . $startIp . ' - ' . $endIp,
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            AmpError::add('general', T_('Duplicate ACL entry defined'));
        }

        if (!AmpError::occurred()) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Your new Access Control List(s) have been created'),
                sprintf(
                    '%s/admin/access.php',
                    $this->configContainer->getWebPath()
                )
            );
        } else {
            $this->ui->show(
                'show_add_access.inc.php',
                [
                    'action' => 'show_add_' . Core::get_post('type'),
                    'add_type' => 'add_host'
                ]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
