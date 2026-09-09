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
use Override;

/**
 * A block of free text supplied by a feed or a user: a biography, a summary, a description.
 */
final class ProseBlockView extends AbstractView
{
    private const int FOLD_ABOVE = 600;

    public function __construct(
        private readonly string $html,
        private readonly string $id,
    ) {}

    /**
     * Plain text as stored: entities decoded once, then escaped, line breaks kept.
     */
    public static function fromText(string $text, string $id): self
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return new self(nl2br(htmlspecialchars($text, ENT_QUOTES)), $id);
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Long texts open folded, so the page below them stays reachable.
     */
    public function isFolded(): bool
    {
        return mb_strlen(strip_tags($this->html)) > self::FOLD_ABOVE;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/prose_block.phtml');
    }
}
