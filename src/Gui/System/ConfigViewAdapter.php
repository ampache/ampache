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

namespace Ampache\Gui\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;

final class ConfigViewAdapter implements ConfigViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function isUserFlagsEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_FLAGS);
    }

    public function isWaveformEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WAVEFORM);
    }

    public function isDirectplayEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DIRECTPLAY);
    }

    public function isLicensingEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LICENSING);
    }

    public function isShowLicenseEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_LICENSE);
    }

    public function isShowSkippedTimesEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_SKIPPED_TIMES);
    }

    public function isShowPlayedTimesEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_PLAYED_TIMES);
    }

    public function isRatingEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS);
    }
}
