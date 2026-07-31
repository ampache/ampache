<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Video;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class DeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'delete';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            return null;
        }

        $queryParams = $request->getQueryParams();
        $videoId     = (int) ($queryParams['video_id'] ?? 0);
        $burlParam   = (string) ($queryParams['burl'] ?? '');

        $this->ui->showHeader();
        if ($videoId < 1) {
            $this->logger->warning(
                'Requested a video that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $webPath = $this->configContainer->getWebPath('/client');

            $this->ui->showConfirmationWithReturn(
                T_('Are You Sure?'),
                T_('The Video will be deleted'),
                sprintf(
                    '%s/video.php?action=confirm_delete&video_id=%d&burl=%s',
                    $webPath,
                    $videoId,
                    rawurlencode($burlParam)
                ),
                $this->deletionUrlResolver->resolveBurl($burlParam) ?: sprintf(
                    '%s/video.php?action=show_video&video_id=%d',
                    $webPath,
                    $videoId
                ),
                'delete_video'
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
