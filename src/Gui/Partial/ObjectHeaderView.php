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

namespace Ampache\Gui\Partial;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\Ui;
use Override;

/**
 * The shared heading of a library item page: art, identity, facts, ratings and actions.
 */
final class ObjectHeaderView extends AbstractView
{
    /**
     * @param list<HeaderChip> $chips
     * @param list<HeaderChip> $tags
     * @param list<string> $actions rendered `<li>` contents
     */
    public function __construct(
        private readonly string $kind,
        private readonly string $title,
        private readonly string $art = '',
        private readonly string $breadcrumb = '',
        private readonly array $chips = [],
        private readonly array $tags = [],
        private readonly string $rating = '',
        private readonly string $userflag = '',
        private readonly string $ratingKey = '',
        private readonly string $note = '',
        private readonly string $links = '',
        private readonly string $primaryAction = '',
        private readonly array $actions = [],
        private readonly bool $wideArt = false,
    ) {}

    /**
     * The mobile-only toggle that brings the action labels back.
     */
    public static function actionsHelp(): string
    {
        return '<li class="actions-help"><input id="actions_help" type="checkbox">'
            . '<label for="actions_help">' . Ui::get_material_symbol('help', T_('Help')) . '</label></li>';
    }

    /**
     * @return list<string>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getArt(): string
    {
        return $this->art;
    }

    public function getBreadcrumb(): string
    {
        return $this->breadcrumb;
    }

    /**
     * @return list<HeaderChip>
     */
    public function getChips(): array
    {
        return $this->chips;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getLinks(): string
    {
        return $this->links;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getPrimaryAction(): string
    {
        return $this->primaryAction;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    /**
     * `<id>_<type>`, the suffix the ajax reply targets when a vote is cast.
     */
    public function getRatingKey(): string
    {
        return $this->ratingKey;
    }

    /**
     * @return list<HeaderChip>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUserflag(): string
    {
        return $this->userflag;
    }

    public function hasWideArt(): bool
    {
        return $this->wideArt;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/object_header.phtml');
    }
}
