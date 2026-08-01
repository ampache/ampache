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

namespace Ampache\Module\Label\Deletion;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final readonly class LabelDeleter implements LabelDeleterInterface
{
    public function __construct(
        private ShoutRepositoryInterface $shoutRepository,
        private LabelRepositoryInterface $labelRepository,
        private UserActivityRepositoryInterface $userActivityRepository,
        private ArtCleanupInterface $artCleanup,
        private FolderRepositoryInterface $folderRepository,
        private CatalogCounterInterface $catalogCounter,
    ) {}

    public function delete(
        Label $label,
    ): void {
        $labelId = $label->getId();

        $this->labelRepository->delete($labelId);
        $this->artCleanup->collectGarbageForObject('label', $labelId);
        Userflag::garbage_collection('label', $labelId);
        Rating::garbage_collection('label', $labelId);
        $this->shoutRepository->collectGarbage('label', $labelId);
        $this->userActivityRepository->collectGarbage('label', $labelId);
        $this->folderRepository->collectGarbage();
        $this->catalogCounter->count(CountableTableEnum::LABEL);
    }
}
