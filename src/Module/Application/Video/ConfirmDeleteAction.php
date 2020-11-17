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

namespace Ampache\Module\Application\Video;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Model\Catalog;
use Ampache\Model\Video;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->logger          = $logger;
    }

    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->ui->showHeader();
        
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            return null;
        }

        $video = Video::create_from_id(
            filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS)
        );
        if (!Catalog::can_remove($video)) {
            $this->logger->critical(
                sprintf('Unauthorized to remove the video `%s`', $video->id),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            Ui::access_denied();

            return null;
        }

        if ($video->remove()) {
            show_confirmation(
                T_('No Problem'),
                T_('Video has been deleted'),
                $this->configContainer->getWebPath()
            );
        } else {
            show_confirmation(
                T_('There Was a Problem'),
                T_('Couldn\'t delete this Video.'),
                $this->configContainer->getWebPath()
            );
        }
        
        $this->ui->showQueryStats();
        $this->ui->showFooter();
        
        return null;
    }
}
