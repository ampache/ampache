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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Shout\ShoutRowView;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Override;

/**
 * The manage-shoutbox browse.
 *
 * The shouts are loaded here from the ids in the context, so `Browse` no longer has to hydrate them for
 * a template and lend the loader alongside.
 */
final class ShoutboxListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly ShoutRepositoryInterface $shoutRepository,
        private readonly ShoutObjectLoaderInterface $shoutObjectLoader,
    ) {}

    public function getAdminPath(): string
    {
        return $this->configContainer->getWebPath('/admin');
    }

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_object', 'label' => T_('Object')],
            ['class' => 'cel_username', 'label' => T_('User')],
            ['class' => 'cel_sticky', 'label' => T_('Sticky')],
            ['class' => 'cel_comment', 'label' => T_('Comment')],
            ['class' => 'cel_date', 'label' => T_('Date Added')],
            ['class' => 'cel_action', 'label' => T_('Action')],
        ];
    }

    /**
     * A shout whose target no longer loads, or whose author has gone, is skipped rather than half-rendered.
     *
     * @return list<array{shout: Shoutbox, objectLink: string, client: User}>
     */
    public function getRows(): array
    {
        $rows = [];
        foreach ($this->getObjectIds() as $shoutId) {
            $shout = $this->shoutRepository->findById($shoutId);
            if ($shout === null) {
                continue;
            }

            $client = $shout->getUser();
            $object = $this->shoutObjectLoader->loadByShout($shout);
            if ($client === null || !$object instanceof displayable_item) {
                continue;
            }

            $rows[] = ['shout' => $shout, 'objectLink' => $object->get_f_link(), 'client' => $client];
        }

        return $rows;
    }

    /**
     * @param array{shout: Shoutbox, objectLink: string, client: User} $row
     */
    public function renderRow(array $row): string
    {
        return new ShoutRowView(
            $this->getAdminPath(),
            $row['shout'],
            $row['objectLink'],
            $row['client']
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/manage_shoutbox.phtml');
    }
}
