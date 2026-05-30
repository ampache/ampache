<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\Admin\Access\Lib\AccessListItem;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessListManagerInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Exception\InvalidEndIpException;
use Ampache\Module\Authorization\Exception\InvalidIpRangeException;
use Ampache\Module\Authorization\Exception\InvalidStartIpException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateRecordAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'update_record';

    public function __construct(
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private AccessListManagerInterface $accessListManager,
        private RequestParserInterface $requestParser,
    ) {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false ||
            !$this->requestParser->verifyForm('edit_acl')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $data     = (array)$request->getParsedBody();
        $accessId = (int)($request->getQueryParams()['access_id'] ?? 0);
        try {
            $this->accessListManager->update(
                $accessId,
                $data['start'] ?? '',
                $data['end'] ?? '',
                $data['name'] ?? '',
                (int)($data['user'] ?? -1),
                AccessLevelEnum::from((int)($data['level'] ?? 0)),
                AccessTypeEnum::from($data['type'] ?? 'stream')
            );
        } catch (InvalidIpRangeException) {
            AmpError::add('start', T_('IP Address version mismatch'));
            AmpError::add('end', T_('IP Address version mismatch'));
        } catch (InvalidStartIpException) {
            AmpError::add('start', T_('An Invalid IPv4 / IPv6 Address was entered'));
        } catch (InvalidEndIpException) {
            AmpError::add('end', T_('An Invalid IPv4 / IPv6 Address was entered'));
        }

        if (AmpError::occurred()) {
            $this->ui->show(
                'show_edit_access.inc.php',
                [
                    'access' => new AccessListItem(
                        $this->modelFactory,
                        $this->modelFactory->createAccess($accessId)
                    )
                ]
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Your Access Control List has been updated'),
                sprintf(
                    '%s/access.php',
                    $this->configContainer->getWebPath('/admin')
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
