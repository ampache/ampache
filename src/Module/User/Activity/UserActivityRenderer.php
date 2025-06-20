<?php

declare(strict_types=1);

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

namespace Ampache\Module\User\Activity;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Useractivity;

final readonly class UserActivityRenderer implements UserActivityRendererInterface
{
    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private LibraryItemLoaderInterface $libraryItemLoader,
    ) {
    }

    /**
     * Show the activity entry.
     */
    public function show(
        Useractivity $useractivity
    ): string {
        // If user flags aren't enabled don't do anything
        if (!$this->configContainer->get('ratings') || !$useractivity->id) {
            return '';
        }

        $user    = $this->modelFactory->createUser($useractivity->user);
        $libitem = $this->libraryItemLoader->load(
            LibraryItemEnum::from($useractivity->object_type),
            $useractivity->object_id
        );

        if ($libitem === null) {
            return '';
        }

        $descr = $user->get_f_link() . ' ';
        $descr .= match ($useractivity->action) {
            'shout' => T_('commented on'),
            'upload' => T_('uploaded'),
            'play' => T_('played'),
            'userflag' => T_('favorited'),
            'follow' => T_('started to follow'),
            'rating' => T_('rated'),
            default => T_('did something on'),
        };
        $link = (!empty($libitem->get_f_parent_link()))
            ? $libitem->get_f_link() . '&nbsp;-&nbsp;' . $libitem->get_f_parent_link()
            : $libitem->get_f_link();

        return sprintf(
            '<div>%s %s %s</div>',
            get_datetime($useractivity->activity_date),
            $descr,
            $link
        );
    }
}
