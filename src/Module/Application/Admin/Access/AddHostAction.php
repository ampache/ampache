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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AccessRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddHostAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_host';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private AccessRepositoryInterface $accessRepository;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        AccessRepositoryInterface $accessRepository
    ) {
        $this->ui               = $ui;
        $this->configContainer  = $configContainer;
        $this->accessRepository = $accessRepository;
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

        $data = $request->getParsedBody();

        $start   = @inet_pton($data['start'] ?? '');
        $end     = @inet_pton($data['end'] ?? '');
        $type    = $data['type'] ?? '';
        $name    = $data['name'] ?? '';
        $user    = (int) ($data['user'] ?: -1);
        $level   = (int) $data['level'] ?? 0;

        if (Access::_verify_range($data['start'] ?? '', $data['end'] ?? '') === true) {
            // Check existing ACLs to make sure we're not duplicating values here
            if ($this->accessRepository->exists($start, $end, $type, $user) === true) {
                debug_event(
                    'access.class',
                    'Error: An ACL entry equal to the created one already exists. Not adding duplicate: ' . $data['start'] . ' - ' . $data['end'],
                    1
                );
                AmpError::add('general', T_('Duplicate ACL entry defined'));
            } else {
                Access::create($data);

                // Create Additional stuff based on the type
                if (Core::get_post('addtype') == 'stream' ||
                    Core::get_post('addtype') == 'all'
                ) {
                    if ($this->accessRepository->exists($start, $end, 'stream', $user)) {
                        debug_event(
                            'access.class',
                            'Error: An ACL entry equal to the created one already exists. Not adding duplicate: ' . $data['start'] . ' - ' . $data['end'],
                            1
                        );
                        AmpError::add('general', T_('Duplicate ACL entry defined'));
                    } else {
                        $data['type'] = 'stream';
                        Access::create($data);
                    }
                }
                if (Core::get_post('addtype') == 'all') {
                    if ($this->accessRepository->exists($start, $end, 'interface', $user)) {
                        debug_event(
                            'access.class',
                            'Error: An ACL entry equal to the created one already exists. Not adding duplicate: ' . $data['start'] . ' - ' . $data['end'],
                            1
                        );
                        AmpError::add('general', T_('Duplicate ACL entry defined'));
                    } else {
                        $data['type'] = 'interface';
                        Access::create($data);
                    }
                }
            }
        }

        if (!AmpError::occurred()) {
            $url = sprintf(
                '%s/admin/access.php',
                $this->configContainer->getWebPath()
            );
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Your new Access Control List(s) have been created'),
                $url
            );
        } else {
            $this->ui->show(
                'show_add_access.inc.php',
                ['action' => 'show_add_' . Core::get_post('type')]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
