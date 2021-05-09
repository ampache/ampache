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

declare(strict_types=1);

namespace Ampache\Module\Wanted\Gui;

use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;
use Ampache\Repository\WantedRepositoryInterface;

final class WantedUiItem implements WantedUiItemInterface
{
    private WantedRepositoryInterface $wantedRepository;

    private User $user;

    private Wanted $wanted;

    public function __construct(
        WantedRepositoryInterface $wantedRepository,
        User $user,
        Wanted $wanted
    ) {
        $this->wantedRepository = $wantedRepository;
        $this->user             = $user;
        $this->wanted           = $wanted;
    }

    public function isAccepted(): bool
    {
        return $this->wanted->getAccepted();
    }

    public function getName(): string
    {
        return $this->wanted->getName();
    }

    public function getYear(): int
    {
        return $this->wanted->getYear();
    }

    public function getArtistId(): ?int
    {
        return $this->wanted->getArtistId();
    }

    public function getArtistMusicBrainzId(): ?string
    {
        return $this->wanted->getArtistMusicBrainzId();
    }

    public function getLink(): string
    {
        return $this->wanted->getLinkFormatted();
    }

    public function getArtistLink(): string
    {
        return $this->wanted->getArtistLink();
    }

    public function getUserName(): string
    {
        return $this->wanted->getUser()->f_name;
    }

    public function getActionButtons(): string
    {
        $result = '';
        if (!$this->wanted->getAccepted()) {
            if (Core::get_global('user')->has_access('75')) {
                $result .= Ajax::button(
                    '?page=index&action=accept_wanted&mbid=' . $this->wanted->getMusicBrainzId(),
                    'enable',
                    T_('Accept'),
                    'wanted_accept_' . $this->wanted->getMusicBrainzId()
                );
            }
        }
        if (
            $this->user->has_access(AccessLevelEnum::LEVEL_MANAGER) ||
            (
                $this->wantedRepository->find($this->wanted->getMusicBrainzId(), $this->user->getId()) &&
                $this->wanted->getAccepted() != 1
            )
        ) {
            $result .= sprintf(' %s',
                Ajax::button(
                    '?page=index&action=remove_wanted&mbid=' . $this->wanted->getMusicBrainzId(),
                    'disable',
                    T_('Remove'),
                    'wanted_remove_' . $this->wanted->getMusicBrainzId()
                )
            );
        }

        return $result;
    }

    public function getMusicBrainzId(): string
    {
        return $this->wanted->getMusicBrainzId();
    }
}
