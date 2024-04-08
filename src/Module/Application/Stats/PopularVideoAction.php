<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Application\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PopularVideoAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'popular_video';

    public function __construct(
        private UiInterface $ui,
        private ModelFactoryInterface $modelFactory,
        private ConfigContainerInterface $configContainer,
        private VideoRepositoryInterface $videoRepository
    ) {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $thresh_value = $this->configContainer->get(ConfigurationKeyEnum::STATS_THRESHOLD);

        $this->ui->showHeader();
        $this->ui->show('show_form_popular.inc.php');
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) &&
            $this->videoRepository->getItemCount(Video::class)
        ) {
            $objects = Stats::get_top('video', -1, $thresh_value, 0, $gatekeeper->getUser());
            $browse  = $this->modelFactory->createBrowse();
            $browse->set_threshold($thresh_value);
            $browse->set_type('video');
            $browse->show_objects($objects);
            $browse->store();
        }

        $this->ui->showBoxBottom();

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
