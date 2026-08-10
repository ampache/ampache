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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Gui\Share\ShareRowView;
use Ampache\Repository\Model\Share;
use Override;

/**
 * The shared-objects browse.
 */
final class ShareListRenderer extends AbstractBrowseListRenderer
{
    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_object essential', 'label' => T_('Object'), 'sort' => 'name'],
            ['class' => 'cel_object_type optional', 'label' => T_('Object Type'), 'sort' => 'object_type'],
            ['class' => 'cel_user optional', 'label' => T_('User'), 'sort' => 'user'],
            ['class' => 'cel_creation_date optional', 'label' => T_('Creation Date'), 'sort' => 'creation_date'],
            ['class' => 'cel_lastvisit_date optional', 'label' => T_('Last Visit'), 'sort' => 'lastvisit_date'],
            ['class' => 'cel_counter optional', 'label' => T_('Counter'), 'sort' => 'counter'],
            ['class' => 'cel_max_counter optional', 'label' => T_('Max Counter'), 'sort' => 'max_counter'],
            ['class' => 'cel_allow_stream optional', 'label' => T_('Allow Stream'), 'sort' => 'allow_stream'],
            ['class' => 'cel_allow_download optional', 'label' => T_('Allow Download'), 'sort' => 'allow_download'],
            ['class' => 'cel_expire optional', 'label' => T_('Expiry Days'), 'sort' => 'expire'],
            ['class' => 'cel_public_url essential', 'label' => T_('Public URL'), 'sort' => null],
            ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null],
        ];
    }

    /**
     * A share whose target has been deleted is skipped rather than rendering an empty row.
     *
     * @return list<Share>
     */
    public function getShares(): array
    {
        $shares = [];
        foreach ($this->getObjectIds() as $objectId) {
            $share = new Share($objectId);
            if ($share->hasObject()) {
                $shares[] = $share;
            }
        }

        return $shares;
    }

    public function renderRow(Share $share): string
    {
        return (new ShareRowView($share))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/shared_objects.phtml');
    }
}
