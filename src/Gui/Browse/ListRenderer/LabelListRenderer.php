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
use Ampache\Gui\Label\LabelRowView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use LogicException;
use Override;

/**
 * The record label browse.
 *
 * The label repository is injected here rather than lent by `Browse`, which is the point of the interface.
 */
final class LabelListRenderer extends AbstractView implements BrowseListRendererInterface
{
    private ?BrowseListContext $context = null;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly LabelRepositoryInterface $labelRepository,
    ) {}

    public function getBrowse(): Browse
    {
        return $this->getContext()->browse;
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null],
            ['class' => 'cel_label essential persist', 'label' => T_('Label'), 'sort' => 'name'],
            ['class' => 'cel_category essential', 'label' => T_('Category'), 'sort' => 'category'],
            ['class' => 'cel_artists optional', 'label' => T_('Artists'), 'sort' => null],
            ['class' => 'cel_country optional', 'label' => T_('Country'), 'sort' => 'country'],
            ['class' => 'cel_status optional', 'label' => T_('Status'), 'sort' => 'active'],
            ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null],
        ];
    }

    public function getCoverClass(): string
    {
        return $this->getBrowse()->is_grid_view() ? 'grid_cover' : 'cel_cover';
    }

    public function getCreateUrl(): string
    {
        return $this->configContainer->getWebPath() . '/labels.php?action=show_add_label';
    }

    /**
     * @return list<Label>
     */
    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->getContext()->objectIds as $objectId) {
            $label = $this->labelRepository->findById((int) $objectId);
            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    public function getTableClass(): string
    {
        return $this->getBrowse()->is_grid_view() ? ' gridview' : '';
    }

    public function mayCreate(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            && (bool) $this->configContainer->get('label');
    }

    /**
     * Renderers are shared services, so the previous context is put back afterwards.
     */
    #[Override]
    public function renderList(BrowseListContext $context): string
    {
        $previous      = $this->context;
        $this->context = $context;

        try {
            return $this->render();
        } finally {
            $this->context = $previous;
        }
    }

    public function renderRow(Label $label): string
    {
        $mayInteract = $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);

        return (new LabelRowView(
            $this->configContainer->getWebPath(),
            $label,
            $this->getCoverClass(),
            !$this->configContainer->get('use_auth') || $mayInteract,
            $mayInteract,
            (bool) $this->configContainer->get('sociable'),
            Catalog::can_remove($label)
        ))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/labels.phtml');
    }

    private function getContext(): BrowseListContext
    {
        if ($this->context === null) {
            throw new LogicException('The list renderer was rendered outside renderList()');
        }

        return $this->context;
    }
}
