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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\User;
use Override;

/**
 * The label edit dialog.
 *
 * Its MusicBrainz id reached two `value=` attributes unescaped, and its twelve categories were twelve
 * hand-written options each repeating the same comparison.
 */
final class LabelEditFormRenderer extends AbstractEditFormRenderer
{
    public function getAddress(): string
    {
        return (string) $this->getItem()->address;
    }

    /**
     * @return array<string, string>
     */
    public function getCategories(): array
    {
        return [
            'personal' => T_('Personal'),
            'association' => T_('Association'),
            'company' => T_('Company'),
            'imprint' => T_('Imprint'),
            'production' => T_('Production'),
            'original production' => T_('Original Production'),
            'bootleg production' => T_('Bootleg Production'),
            'reissue production' => T_('Reissue Production'),
            'distributor' => T_('Distributor'),
            'holding' => T_('Holding'),
            'rights society' => T_('Rights Society'),
            'tag_generated' => T_('Tag Generated'),
        ];
    }

    /**
     * A label with no category set reads as personal, which is what the form has always defaulted to.
     */
    public function getCategory(): string
    {
        return ($this->getItem()->category === null || $this->getItem()->category === '')
            ? 'personal'
            : $this->getItem()->category;
    }

    public function getCountry(): string
    {
        return (string) $this->getItem()->country;
    }

    public function getEmail(): string
    {
        return (string) $this->getItem()->email;
    }

    public function getLabelId(): int
    {
        return $this->getItem()->getId();
    }

    public function getMbid(): string
    {
        return (string) $this->getItem()->mbid;
    }

    public function getName(): string
    {
        return (string) $this->getItem()->name;
    }

    public function getSummary(): string
    {
        return (string) $this->getItem()->summary;
    }

    public function getWebsite(): string
    {
        return (string) $this->getItem()->website;
    }

    public function isActive(): bool
    {
        return $this->getItem()->active;
    }

    public function mayEditMbid(): bool
    {
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            return false;
        }

        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->getId())
            || $user->getId() === $this->getItem()->get_user_owner();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/label.phtml');
    }

    private function getItem(): Label
    {
        /** @var Label $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
