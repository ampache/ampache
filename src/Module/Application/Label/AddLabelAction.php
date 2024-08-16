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

namespace Ampache\Module\Application\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Label;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddLabelAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_label';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->requestParser   = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Must be at least a content manager or edit upload enabled
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === false &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === false ||
            !$this->requestParser->verifyForm('add_label')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        // Remove unauthorized defined values from here
        if (isset($_POST['user'])) {
            unset($_POST['user']);
        }
        if (isset($_POST['creation_date'])) {
            unset($_POST['creation_date']);
        }

        $label_id = Label::create($_POST);
        if (!$label_id) {
            $this->ui->show('show_add_label.inc.php');
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Label has been added'),
                sprintf(
                    '%s/browse.php?action=label',
                    $this->configContainer->getWebPath('/client')
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
