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

namespace Ampache\Module\Label;

use Ampache\Module\Label\Deletion\LabelDeleterInterface;
use Ampache\Repository\LabelRepositoryInterface;

/**
 * Clears out placeholder labels that earlier scans imported from tags
 *
 * Skipping them at scan time only stops new ones arriving, so the ones already in the table are
 * swept here, together with the art, ratings and shouts that hang off them.
 */
final readonly class LabelGarbageCollector implements LabelGarbageCollectorInterface
{
    public function __construct(
        private LabelRepositoryInterface $labelRepository,
        private LabelNameFilterInterface $labelNameFilter,
        private LabelDeleterInterface $labelDeleter,
    ) {}

    public function collect(): void
    {
        foreach ($this->labelRepository->getAll() as $labelId => $labelName) {
            if (!$this->labelNameFilter->isIgnored($labelName)) {
                continue;
            }

            $label = $this->labelRepository->findById($labelId);
            if ($label === null) {
                continue;
            }

            // a label someone entered by hand is theirs to remove, however odd the name looks;
            // the imported ones carry no owner (or user 0, from the scanner)
            if ($label->user !== null && $label->user > 0) {
                continue;
            }

            debug_event(self::class, 'Removing placeholder label {' . $labelName . '}', 4);

            $this->labelDeleter->delete($label);
        }
    }
}
