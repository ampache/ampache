<?php

declare(strict_types=1);

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

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AccessRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Deletes an ACL item by its id
 */
final class DeleteRecordAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete_record';

    private UiInterface $ui;

    private AccessRepositoryInterface $accessRepository;

    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        AccessRepositoryInterface $accessRepository,
        RequestParserInterface $requestParser
    ) {
        $this->ui               = $ui;
        $this->accessRepository = $accessRepository;
        $this->requestParser    = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false ||
            $this->requestParser->verifyForm('delete_access') === false
        ) {
            throw new AccessDeniedException();
        }

        $this->accessRepository->delete((int) ($request->getQueryParams()['access_id'] ?? 0));

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('Your Access List entry has been removed'),
            'admin/access.php',
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
