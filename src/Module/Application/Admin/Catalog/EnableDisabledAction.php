<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Song;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EnableDisabledAction extends AbstractCatalogAction
{
    public const REQUEST_KEY = 'enable_disabled';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct($ui);
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    /**
     * @param int[] $catalogIds
     */
    protected function handle(
        ServerRequestInterface $request,
        array $catalogIds
    ): ?ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $songs = $_REQUEST['song'] ?? [];
        if (count($songs)) {
            foreach ($songs as $song_id) {
                Song::update_enabled(true, $song_id);
            }
            $body = count($songs) . ' ' . nT_('Song has been enabled', 'Songs have been enabled', count($songs));
        } else {
            $body = T_("You didn't select any disabled Songs");
        }
        $url   = sprintf('%s/admin/catalog.php', $this->configContainer->getWebPath(false));
        $title = T_('Finished Processing Disabled Songs');
        $this->ui->showConfirmation($title, $body, $url);

        return null;
    }
}
