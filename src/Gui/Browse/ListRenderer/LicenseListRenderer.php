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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\License\LicenseRowView;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\License;
use Override;

/**
 * The manage-licenses browse, for both the visible and the hidden list.
 *
 * The two differ only in the link that toggles between them, so the browse type decides that rather than
 * there being two copies of the same table. Its empty-state cell used to span six of four columns.
 */
final class LicenseListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly LicenseRepositoryInterface $licenseRepository,
    ) {}

    public function getAdminPath(): string
    {
        return $this->configContainer->getWebPath('/admin');
    }

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_name', 'label' => T_('Name')],
            ['class' => 'cel_description', 'label' => T_('Description')],
            ['class' => 'cel_order', 'label' => T_('Order')],
            ['class' => 'cel_action', 'label' => T_('Action')],
        ];
    }

    public function getCreateUrl(): string
    {
        return $this->getAdminPath() . '/license.php?action=show_create';
    }

    /**
     * @return list<License>
     */
    public function getLicenses(): array
    {
        $licenses = [];
        foreach ($this->getObjectIds() as $objectId) {
            $license = $this->licenseRepository->findById($objectId);
            if ($license !== null) {
                $licenses[] = $license;
            }
        }

        return $licenses;
    }

    /**
     * The hidden list links back to the visible one, and vice versa.
     *
     * @return array{url: string, label: string}
     */
    public function getToggle(): array
    {
        return ($this->getBrowse()->get_type() === 'license_hidden')
            ? ['url' => $this->getAdminPath() . '/license.php', 'label' => T_('Media Licenses')]
            : ['url' => $this->getAdminPath() . '/license.php?action=show_hidden', 'label' => T_('Hidden')];
    }

    public function renderRow(License $license): string
    {
        return new LicenseRowView($this->getAdminPath(), $license)->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/manage_license.phtml');
    }
}
