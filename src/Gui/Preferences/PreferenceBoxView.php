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

namespace Ampache\Gui\Preferences;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Preference;
use Override;

/**
 * One category of preferences, as a table, each row carrying its shipped default and the server value
 */
final class PreferenceBoxView extends AbstractView
{
    /** The category whose preferences are global (`user = -1` only), so neither control below applies to it */
    private const string SYSTEM_CATEGORY = 'system';

    /** @var ?list<PreferenceItem> */
    private ?array $ordered = null;

    /**
     * @param list<PreferenceItem> $items
     */
    public function __construct(
        private readonly array $items,
        private readonly PreferenceSubject $subject,
        private readonly PreferenceInputRenderer $renderer,
        private readonly string $category,
    ) {}

    /**
     * A reference value reads exactly as the control beside it would, label included.
     */
    public function displayValue(PreferenceItem $item, ?string $value): string
    {
        return $this->renderer->label($item, $value);
    }

    public function formatSubcategory(?string $subcategory): string
    {
        return ($subcategory === null)
            ? T_('Other')
            : T_(Preference::format_subcategory($subcategory));
    }

    public function getColumnCount(): int
    {
        return ($this->showsAdminControls()) ? 6 : 4;
    }

    /**
     * Every preference of the tab, those belonging to no section pushed to the end
     *
     * @return list<PreferenceItem>
     */
    public function getItems(): array
    {
        if ($this->ordered !== null) {
            return $this->ordered;
        }

        $sectioned = [];
        $loose     = [];
        foreach ($this->items as $item) {
            if ($item->subcategory === null) {
                $loose[] = $item;
            } else {
                $sectioned[] = $item;
            }
        }

        return $this->ordered = [...$sectioned, ...$loose];
    }

    /**
     * @return array<int, string>
     */
    public function getLevels(): array
    {
        return AccessLevelEnum::selectableDescriptions();
    }

    /**
     * The sections of this tab, as anchor => heading, for the jump list at the top of the page.
     *
     * @return array<string, string>
     */
    public function getSubcategories(): array
    {
        $sections = [];
        foreach ($this->getItems() as $item) {
            $sections[$this->subcategoryAnchor($item->subcategory)] = $this->formatSubcategory($item->subcategory);
        }

        return $sections;
    }

    public function renderControl(PreferenceItem $item): string
    {
        return $this->renderer->render($item, $this->subject);
    }

    /**
     * What the row is matched against when the visitor types in the filter box.
     */
    public function searchText(PreferenceItem $item): string
    {
        return strtolower(trim($item->name . ' ' . $item->description . ' ' . ($item->help->text ?? '')));
    }

    /**
     * Level and "apply to all" act on the shared per-user row, so neither means anything for a system
     * preference, which only ever has the server's own `user = -1` row.
     */
    public function showsAdminControls(): bool
    {
        return $this->subject->isServer && $this->category !== self::SYSTEM_CATEGORY;
    }

    public function subcategoryAnchor(?string $subcategory): string
    {
        return ($subcategory === null)
            ? 'pref-section-other'
            : 'pref-section-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($subcategory));
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('preferences/preference_box.phtml');
    }
}
