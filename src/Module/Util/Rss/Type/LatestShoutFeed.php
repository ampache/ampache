<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Generator;

final readonly class LatestShoutFeed extends AbstractGenericRssFeed
{
    public function __construct(
        private ShoutRepositoryInterface $shoutRepository,
        private ShoutObjectLoaderInterface $shoutObjectLoader
    ) {
    }

    protected function getTitle(): string
    {
        return T_('Recently Shouts');
    }

    protected function getItems(): Generator
    {
        $shouts = $this->shoutRepository->getTop(10);

        /** @var Shoutbox $shout */
        foreach ($shouts as $shout) {
            $object = $this->shoutObjectLoader->loadByShout($shout);

            if ($object !== null) {
                $object->format();
                $user = new User($shout->getUserId());
                if ($user->isNew()) {
                    continue;
                }
                $user->format();

                yield array(
                    'title' => $user->getUsername() . ' ' . T_('on') . ' ' . $object->get_fullname(),
                    'link' => $object->get_link(),
                    'description' => $shout->getText(),
                    'image' => (string)Art::url($shout->getObjectId(), (string)$shout->getObjectType()->value, null, 2),
                    'comments' => '',
                    'pubDate' => $shout->getDate()->format(DATE_ATOM)
                );
            }
        }
    }
}
