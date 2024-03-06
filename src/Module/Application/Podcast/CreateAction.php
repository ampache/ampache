<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Exception\FeedNotLoadableException;
use Ampache\Module\Podcast\Exception\InvalidCatalogException;
use Ampache\Module\Podcast\Exception\InvalidFeedUrlException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private PodcastCreatorInterface $podcastCreator;

    private RequestParserInterface $requestParser;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        PodcastCreatorInterface $podcastCreator,
        RequestParserInterface $requestParser
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->podcastCreator  = $podcastCreator;
        $this->requestParser   = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true ||
            !$this->requestParser->verifyForm('add_podcast')
        ) {
            throw new AccessDeniedException();
        }

        $data = $request->getParsedBody();

        $catalog = Catalog::create_from_id((int) ($data['catalog'] ?? 0));
        if ($catalog === null) {
            AmpError::add('catalog', T_('Catalog not found'));
        } else {
            try {
                $this->podcastCreator->create(
                    $data['feed'] ?? '',
                    $catalog
                );
            } catch (InvalidFeedUrlException $e) {
                AmpError::add('feed', T_('Feed URL is invalid'));
            } catch (InvalidCatalogException $e) {
                AmpError::add('catalog', T_('Wrong target Catalog type'));
            } catch (FeedNotLoadableException $e) {
                AmpError::add('feed', T_('Can not read the feed'));
            }
        }

        $this->ui->showHeader();
        if (AmpError::occurred()) {
            $this->ui->show(
                'show_add_podcast.inc.php',
                [
                    'catalog_id' => (int)($data['catalog'] ?? 0),
                    'feed' => ($data['feed'] ?? '')
                ]
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Subscribed to the Podcast'),
                sprintf(
                    '%s/browse.php?action=podcast',
                    $this->configContainer->getWebPath()
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
