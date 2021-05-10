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
 * along with useractivity program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Ampache\Module\User\Activity;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\UseractivityInterface;

final class UserActivityRenderer implements UserActivityRendererInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * Show the activity entry.
     */
    public function show(
        UseractivityInterface $useractivity
    ): string {
        // If user flags aren't enabled don't do anything
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_FLAGS)) {
            return '';
        }

        $user = $this->modelFactory->createUser($useractivity->getUser());
        $user->format();

        $libitem = $this->modelFactory->mapObjectType(
            $useractivity->getObjectType(),
            $useractivity->getObjectId()
        );
        $libitem->format();

        $descr = $user->f_link . ' ';
        switch ($useractivity->getAction()) {
            case 'shout':
                $descr .= T_('commented on');
                break;
            case 'upload':
                $descr .= T_('uploaded');
                break;
            case 'play':
                $descr .= T_('played');
                break;
            case 'userflag':
                $descr .= T_('favorited');
                break;
            case 'follow':
                $descr .= T_('started to follow');
                break;
            default:
                $descr .= T_('did something on');
                break;
        }

        if (property_exists($libitem, 'f_link')) {
            $link = $libitem->f_link;
        } else {
            $link = $libitem->getLinkFormatted();
        }

        return sprintf(
            '<div>%s %s %s</div>',
            get_datetime($useractivity->getActivityDate()),
            $descr,
            $link
        );
    }
}
