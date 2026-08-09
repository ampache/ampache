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

namespace Ampache\Gui\Search;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\Util\Ui;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * The random-selection form and the results it enqueued.
 *
 * It built a `length_*` local through a variable-variable that nothing then read.
 */
final class RandomFormView extends AbstractView
{
    private const array COUNTS = [1, 5, 10, 20, 30, 50, 100, 500, 1000];

    private const array LENGTHS = [15, 30, 60, 120, 240, 480, 960];

    private const array SIZES = [64, 128, 256, 512, 1024];

    /**
     * @param array<int> $objectIds
     */
    public function __construct(
        private readonly array $objectIds,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly string $webPath,
    ) {}

    public function createBrowse(): Browse
    {
        return $this->browseFactory->create();
    }

    /**
     * A video selection browses videos; everything else browses the songs it picked.
     */
    public function getBrowseType(): string
    {
        return ($this->getCurrentType() === 'video') ? 'video' : 'song';
    }

    /**
     * @return list<array{type: string, label: string}>
     */
    public function getCategories(): array
    {
        $categories = [
            ['type' => 'song', 'label' => T_('Songs')],
            ['type' => 'album', 'label' => T_('Albums')],
            ['type' => 'artist', 'label' => T_('Artists')],
        ];

        if (AmpConfig::get('allow_video') && $this->videoRepository->getItemCount()) {
            $categories[] = ['type' => 'video', 'label' => T_('Videos')];
        }

        return $categories;
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function getCounts(): array
    {
        $counts = [];
        foreach (self::COUNTS as $count) {
            $counts[] = ['value' => $count, 'label' => (string) $count];
        }

        $counts[] = ['value' => -1, 'label' => T_('All')];

        return $counts;
    }

    public function getCurrentType(): ?string
    {
        $type = (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);

        return (in_array($type, Random::VALID_TYPES, true)) ? $type : null;
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function getLengths(): array
    {
        $lengths = [['value' => 0, 'label' => T_('Unlimited')]];
        foreach (self::LENGTHS as $value) {
            $lengths[] = [
                'value' => $value,
                'label' => ($value < 60)
                    ? sprintf(nT_('%d minute', '%d minutes', $value), $value)
                    : sprintf(nT_('%d hour', '%d hours', (int) ($value / 60)), (int) ($value / 60)),
            ];
        }

        return $lengths;
    }

    /**
     * @return array<int>
     */
    public function getObjectIds(): array
    {
        return $this->objectIds;
    }

    public function getSelectedCount(): int
    {
        return (int) ($_POST['limit'] ?? 1);
    }

    public function getSelectedLength(): int
    {
        return (int) ($_POST['length'] ?? 0);
    }

    public function getSelectedSize(): int
    {
        return (int) ($_POST['size_limit'] ?? 0);
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function getSizes(): array
    {
        $sizes = [['value' => 0, 'label' => T_('Unlimited')]];
        foreach (self::SIZES as $value) {
            $sizes[] = ['value' => $value, 'label' => Ui::format_bytes($value * 1048576)];
        }

        return $sizes;
    }

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('search/random.phtml');
    }
}
