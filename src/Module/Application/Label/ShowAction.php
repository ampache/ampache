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
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Label;
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

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        PrivilegeCheckerInterface $privilegeChecker,
        LabelRepositoryInterface $labelRepository
    ) {
        $this->configContainer  = $configContainer;
        $this->ui               = $ui;
        $this->logger           = $logger;
        $this->privilegeChecker = $privilegeChecker;
        $this->labelRepository  = $labelRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LABEL)) {
            throw new AccessDeniedException('Access Denied: label features are not enabled.');
        }

        $this->ui->showHeader();

        $input = $request->getQueryParams();

        // lookup by ID
        $label_id = (isset($input['label'])) ? (int)$input['label'] : null;
        $label    = (is_int($label_id))
            ? $this->labelRepository->findById($label_id)
            : null;
        // lookup by name if ID didn't work
        $label_name = (isset($input['name'])) ? urldecode((string)$input['name']) : null;
        if (!$label && $label_name !== null) {
            $label_id = $this->labelRepository->lookup($label_name);
            $label    = ($label_id > 0)
                ? $this->labelRepository->findById($label_id)
                : null;
        }

        if ($label_id !== null && $label === null) {
            $this->logger->warning(
                'Requested a label that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
            $this->ui->showFooter();

            return null;
        } elseif ($label instanceof Label) {
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

        // if you didn't set a label_id or name, show the add label form
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === true
        ) {
            $this->ui->show(
                'show_add_label.inc.php'
            );
        } else {
            throw new AccessDeniedException();
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
            AccessTypeEnum::INTERFACE,
            AccessLevelEnum::CONTENT_MANAGER
        );
    }
}
