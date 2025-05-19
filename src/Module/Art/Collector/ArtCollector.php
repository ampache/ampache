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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ArtCollector implements ArtCollectorInterface
{
    /**
     * @const ART_SEARCH_LIMIT
     */
    public const ART_SEARCH_LIMIT = 15;

    private ContainerInterface $dic;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ContainerInterface $dic,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        $this->dic             = $dic;
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
    }

    /**
     * This tries to get the art in question
     * @param Art $art
     * @param array{
     *      type?: string,
     *      mb_albumid?: string,
     *      artist?: string,
     *      album?: string,
     *      cover?: ?string,
     *      file?: string,
     *      year_filter?: string,
     *      search_limit?: int,
     *  } $options
     * @param int $limit
     * @return array
     */
    public function collect(
        Art $art,
        array $options = [],
        int $limit = 0
    ): array {
        // Define vars
        $results = [];

        $type = $options['type'] ?? $art->object_type;

        if ($options === []) {
            $this->logger->warning(
                'No options for art search, skipped.',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [];
        }
        $artOrder = $this->configContainer->get('art_order');

        /* If it's not set */
        if (empty($artOrder)) {
            // They don't want art!
            $this->logger->warning(
                'art_order is empty, skipping art gathering',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [];
        } elseif (!is_array($artOrder)) {
            $artOrder = [$artOrder];
        }

        $this->logger->notice(
            'Searching using:' . json_encode($artOrder),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        if ($limit == 0) {
            $search_limit = $this->configContainer->get('art_search_limit');
            $limit        = (is_null($search_limit))
                ? self::ART_SEARCH_LIMIT
                : $search_limit;
        }

        if ($type == 'playlist') {
            $this->logger->notice(
                "Method used: playlist",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            $playlist = new Playlist($art->object_id);

            return $playlist->gather_art($limit);
        }
        /** @var User $user */
        $user = (!empty(Core::get_global('user')))
            ? Core::get_global('user')
            : new User(-1);

        $plugin_names = Plugin::get_plugins(PluginTypeEnum::ART_RETRIEVER);
        foreach ($artOrder as $method) {
            $data = [];
            if (in_array(strtolower($method), $plugin_names)) {
                $plugin            = new Plugin($method);
                $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
                if ($installed_version > 0) {
                    if ($plugin->_plugin !== null && $plugin->load($user)) {
                        $data = $plugin->_plugin->gather_arts($type, $options, $limit);
                    }
                }
            } else {
                $handlerClassName = ArtCollectorTypeEnum::TYPE_CLASS_MAP[$method] ?? null;
                if ($handlerClassName !== null) {
                    $this->logger->notice(
                        "Method used: $method",
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    /** @var CollectorModuleInterface $handler */
                    $handler = $this->dic->get($handlerClassName);

                    $data = $handler->collect(
                        $art,
                        $limit,
                        $options
                    );
                } else {
                    $this->logger->error(
                        $method . ' not defined',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }

            // Add the results we got to the current set
            $results = array_merge($results, (array)$data);
        }
        $this->logger->notice(
            'found ' . count($results) . ' results',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return $results;
    }
}
