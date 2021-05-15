<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Label\LabelCreatorInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddLabelAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_label';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private LabelCreatorInterface $labelCreator;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        LabelCreatorInterface $labelCreator
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->labelCreator    = $labelCreator;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Must be at least a content manager or edit upload enabled
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === false ||
            !Core::form_verify('add_label')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        // Remove unauthorized defined values from here
        if (filter_has_var(INPUT_POST, 'user')) {
            unset($_POST['user']);
        }
        if (filter_has_var(INPUT_POST, 'creation_date')) {
            unset($_POST['creation_date']);
        }

        $label_id = $this->labelCreator->create($_POST);
        if ($label_id === null) {
            require_once Ui::find_template('show_add_label.inc.php');
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Label has been added'),
                sprintf(
                    '%s/browse.php?action=label',
                    $this->configContainer->getWebPath()
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
