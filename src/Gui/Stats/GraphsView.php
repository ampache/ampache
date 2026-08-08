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

namespace Ampache\Gui\Stats;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\Graph;
use Override;

/**
 * The statistical graphs page, with the date range form beneath them.
 */
final class GraphsView extends AbstractView
{
    /**
     * @param list<string> $graphTypes
     */
    public function __construct(
        private readonly Graph $graph,
        private readonly string $webPath,
        private readonly array $graphTypes,
        private readonly int $userId,
        private readonly string $objectType,
        private readonly int $objectId,
        private readonly int $startDate,
        private readonly int $endDate,
        private readonly string $zoom,
        private readonly string $formattedStartDate,
        private readonly string $formattedEndDate,
        private readonly string $requestAction,
        private readonly string $requestType,
        private readonly string $breadcrumbLink,
        private readonly bool $geolocationEnabled,
    ) {}

    public function getFormattedEndDate(): string
    {
        return $this->formattedEndDate;
    }

    public function getFormattedStartDate(): string
    {
        return $this->formattedStartDate;
    }

    public function getGraph(): Graph
    {
        return $this->graph;
    }

    /**
     * One entry per graph, each with the url that renders it and the larger pop-out.
     *
     * @return list<array{url: string, largeUrl: string}>
     */
    public function getGraphs(): array
    {
        return array_map(
            function (string $graphType): array {
                $url = sprintf(
                    '%s/graph.php?type=%s&start_date=%d&end_date=%d&zoom=%s&user_id=%d&object_type=%s&object_id=%d',
                    $this->webPath,
                    rawurlencode($graphType),
                    $this->startDate,
                    $this->endDate,
                    rawurlencode($this->zoom),
                    $this->userId,
                    rawurlencode($this->objectType),
                    $this->objectId
                );

                return ['url' => $url, 'largeUrl' => $url . '&width=1400&height=690'];
            },
            $this->graphTypes
        );
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getRequestAction(): string
    {
        return $this->requestAction;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function getTitle(): string
    {
        return ($this->breadcrumbLink === '')
            ? T_('Statistical Graphs')
            : T_('Statistical Graphs') . ' - ' . $this->breadcrumbLink;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getZoom(): string
    {
        return $this->zoom;
    }

    /**
     * @return array<string, string>
     */
    public function getZoomLevels(): array
    {
        return [
            'year' => T_('Year'),
            'month' => T_('Month'),
            'day' => T_('Day'),
            'hour' => T_('Hour'),
        ];
    }

    public function isGeolocationEnabled(): bool
    {
        return $this->geolocationEnabled;
    }

    /**
     * The map helper echoes rather than returns.
     */
    public function renderMap(): string
    {
        ob_start();
        $this->graph->display_map(
            $this->userId,
            $this->objectType,
            $this->objectId,
            $this->startDate,
            $this->endDate,
            $this->zoom
        );

        return (string) ob_get_clean();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('graphs.phtml');
    }
}
