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

namespace Ampache\Module\Application\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private PrivilegeCheckerInterface $privilegeChecker;

    private LabelRepositoryInterface $labelRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        PrivilegeCheckerInterface $privilegeChecker,
        LabelRepositoryInterface $labelRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer  = $configContainer;
        $this->ui               = $ui;
        $this->logger           = $logger;
        $this->privilegeChecker = $privilegeChecker;
        $this->labelRepository  = $labelRepository;
        $this->modelFactory     = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $label_id = (int)($request->getQueryParams()['label'] ?? 0);
        if (!$label_id) {
            $name = $_REQUEST['name'] ?? null;
            if ($name !== null) {
                $label_id = $this->labelRepository->lookup((string) $name);
            }
        }
        if ($label_id < 1) {
            $this->logger->warning(
                'Requested a label that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_('You have requested an object that does not exist');
            $this->ui->showFooter();
        } else {
            $label = $this->modelFactory->createLabel($label_id);
            $label->format();

            $this->ui->show(
                'show_label.inc.php',
                [
                    'label' => $label,
                    'object_ids' => $label->get_artists(),
                    'object_type' => 'artist',
                    'isLabelEditable' => $this->isEditable(
                        $gatekeeper->getUserId(),
                        $label
                    )
                ]
            );

            $this->ui->showFooter();

            return null;
        }
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === true
        ) {
            $this->ui->show(
                'show_add_label.inc.php'
            );
        } else {
            echo T_('The Label cannot be found');
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    private function isEditable(
        int $userId,
        Label $label
    ): bool {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === true) {
            if ($label->user !== null && $userId == $label->user) {
                return true;
            }
        }

        return $this->privilegeChecker->check(
            AccessLevelEnum::TYPE_INTERFACE,
            AccessLevelEnum::LEVEL_CONTENT_MANAGER
        );
    }
}
