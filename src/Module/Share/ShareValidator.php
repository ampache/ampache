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
 */

declare(strict_types=1);

namespace Ampache\Module\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ShareInterface;
use Psr\Log\LoggerInterface;

final class ShareValidator implements ShareValidatorInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function isValid(
        ShareInterface $share,
        string $secret,
        string $action
    ): bool {
        $shareId = $share->getId();

        if (!$shareId) {
            $this->logger->error(
                'Access Denied: Invalid share.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            $this->logger->error(
                'Access Denied: share feature disabled.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        $expireDays = $share->getExpireDays();

        if ($expireDays > 0 && ($share->getCreationDate() + ($expireDays * 86400)) < time()) {
            $this->logger->error(
                'Access Denied: share expired.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        $maxCounter = $share->getMaxCounter();

        if ($maxCounter > 0 && $share->getCounter() >= $maxCounter) {
            $this->logger->error(
                'Access Denied: max counter reached.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        $shareSecret = $share->getSecret();

        if (!empty($shareSecret) && $secret != $shareSecret) {
            $this->logger->error(
                sprintf('Access Denied: secret requires to access share %s', $shareId),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        if (
            $action == 'download' &&
            (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DOWNLOAD) || !$share->getAllowDownload())
        ) {
            $this->logger->error(
                'Access Denied: download unauthorized.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        if ($action == 'stream' && !$share->getAllowStream()) {
            $this->logger->error(
                'Access Denied: stream unauthorized.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        return true;
    }
}
