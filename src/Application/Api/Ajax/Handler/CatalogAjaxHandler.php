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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

final readonly class CatalogAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser
    ) {
    }

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        if ($action === 'flip_state') {
            if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                debug_event('catalog.ajax', ($user->username ?? T_('Unknown')) . ' attempted to change the state of a catalog', 1);

                return;
            }

            $catalog = Catalog::create_from_id((int)$this->requestParser->getFromRequest('catalog_id'));
            if ($catalog === null) {
                return;
            }

            $new_enabled = !$catalog->enabled;
            Catalog::update_enabled($new_enabled, $catalog->id);
            $catalog->enabled = $new_enabled;
            // Return the new Ajax::button
            $id = 'button_flip_state_' . $catalog->id;
            if ($new_enabled) {
                $button     = 'unpublished';
                $buttontext = T_('Disable');
            } else {
                $button     = 'check_circle';
                $buttontext = T_('Enable');
            }

            $results[$id] = Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $catalog->id, $button, $buttontext, 'flip_state_' . $catalog->id);
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
