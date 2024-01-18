<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

namespace Ampache\Module\Label\Deletion;

use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final class LabelDeleter implements LabelDeleterInterface
{
    private ShoutRepositoryInterface $shoutRepository;

    private LabelRepositoryInterface $labelRepository;

    private UserActivityRepositoryInterface $useractivityRepository;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository,
        LabelRepositoryInterface $labelRepository,
        UserActivityRepositoryInterface $useractivityRepository
    ) {
        $this->shoutRepository        = $shoutRepository;
        $this->labelRepository        = $labelRepository;
        $this->useractivityRepository = $useractivityRepository;
    }

    public function delete(
        Label $label
    ): bool {
        $labelId = $label->getId();

        $deleted = $this->labelRepository->delete($labelId);
        if ($deleted) {
            Art::garbage_collection('label', $labelId);
            Userflag::garbage_collection('label', $labelId);
            Rating::garbage_collection('label', $labelId);
            $this->shoutRepository->collectGarbage('label', $labelId);
            $this->useractivityRepository->collectGarbage('label', $labelId);
        }

        return $deleted !== false;
    }
}
