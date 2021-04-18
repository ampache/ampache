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

final class ExpirationDateCalculator implements ExpirationDateCalculatorInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function calculate(?int $days = null): int
    {
        if ($days !== null) {
            $expires = $days;
            // no limit expiry
            if ($expires == 0) {
                $expire_days = 0;
            } else {
                // Parse as a string to work on 32-bit computers
                if (strlen((string)$expires) > 3) {
                    $expires = (int)(substr((string) $expires, 0, -3));
                }
                $expire_days = round(($expires - time()) / 86400, 0, PHP_ROUND_HALF_EVEN);
            }
        } else {
            // fall back to config defaults
            $expire_days = $this->configContainer->get(ConfigurationKeyEnum::SHARE_EXPIRE);
        }

        return (int)$expire_days;
    }
}
