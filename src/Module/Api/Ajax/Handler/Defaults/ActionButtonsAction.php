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

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Defaults;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ActionButtonsAction implements ActionInterface
{
    private ConfigContainerInterface $config;

    public function __construct(
        ConfigContainerInterface $config
    ) {
        $this->config = $config;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $result      = '';
        $queryParams = $request->getQueryParams();

        $objectId   = $queryParams['object_id'] ?? 0;
        $objectType = $queryParams['object_type'] ?? '';

        if ($this->config->isFeatureEnabled(ConfigurationKeyEnum::RATINGS)) {
            $result .= sprintf(
                ' <span id=\'rating_%s_%s\'>',
                $objectId,
                $objectType
            );
            $result .= Rating::show($objectId, $objectType);
            $result .= '</span> |';
        }

        if ($this->config->isFeatureEnabled(ConfigurationKeyEnum::USER_FLAGS)) {
            $result .= sprintf(
                ' <span id=\'userflag_%s_%s\'>',
                $objectId,
                $objectType
            );
            $result .= Userflag::show($objectId, $objectType);
            $result .= '</span>';
        }

        return [
            'action_buttons' => $result
        ];
    }
}
