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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Art\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginGatherArtsInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\playlist_object;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class ArtCollector implements ArtCollectorInterface
{
    /**
     * @const ART_SEARCH_LIMIT
     */
    public const int ART_SEARCH_LIMIT = 15;

    public function __construct(
        private ContainerInterface $dic,
        private LoggerInterface $logger,
        private ConfigContainerInterface $configContainer,
        private LibraryItemLoaderInterface $libraryItemLoader,
    ) {}

    /**
     * This tries to get the art in question
     * @param array{
     *     type?: string,
     *     mb_albumid?: string,
     *     artist?: string,
     *     album?: string,
     *     cover?: ?string,
     *     file?: string,
     *     year_filter?: string,
     *     search_limit?: int,
     * } $options
     * @return array<int, array{
     *     'raw'?: string,
     *     'db'?: int,
     *     'url'?: string,
     *     'mosaic'?: array{object_id: int, limit: int},
     *     'title'?: string,
     *     'mime'?: string
     * }>
     */
    public function collect(
        Art $art,
        array $options = [],
        int $limit = 0,
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

        $artOrder = $this->configContainer->getArray('art_order');

        /* If it's not set */
        if (empty($artOrder)) {
            // They don't want art!
            $this->logger->warning(
                'art_order is empty, skipping art gathering',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [];
        }

        $this->logger->notice(
            'Searching using:' . json_encode($artOrder),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        if ($limit === 0) {
            $search_limit = $this->configContainer->get('art_search_limit');
            $limit        = ($search_limit === null) ? self::ART_SEARCH_LIMIT : (int) $search_limit;
        }

        // playlists, smartlists and collections all build their art out of what they hold, so none of the
        // configured providers can say anything useful about them
        $itemType = LibraryItemEnum::tryFrom($type);
        $libitem  = ($itemType === null)
            ? null
            : $this->libraryItemLoader->load($itemType, $art->object_id);

        if ($libitem instanceof playlist_object) {
            $this->logger->notice(
                'Method used: ' . $type,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            // This returns before the configured methods run, so the art it already has would never be
            // offered back. Keep it at the front of the list the way the `db` method would.
            $results = [];
            if ($art->has_db_info() && $art->id) {
                $results[] = [
                    'db' => $art->id,
                    'title' => T_('Art'),
                    'mime' => $art->raw_mime,
                ];
            }

            return array_merge($results, $libitem->gather_art($limit));
        }

        $user = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')
            : new User(-1);

        $plugin_names = Plugin::get_plugins(PluginTypeEnum::ART_RETRIEVER);
        foreach ($artOrder as $method) {
            $data = [];
            if (in_array(strtolower((string) $method), $plugin_names)) {
                $plugin = new Plugin($method);
                if (
                    $plugin->_plugin instanceof PluginGatherArtsInterface
                    && Plugin::get_plugin_version($plugin->_plugin->name) > 0
                    && $plugin->load($user)
                ) {
                    $data = $plugin->_plugin->gather_arts($type, $options, $limit);
                }
            } else {
                $handlerClassName = ArtCollectorTypeEnum::TYPE_CLASS_MAP[$method] ?? null;
                if ($handlerClassName !== null) {
                    $this->logger->notice(
                        'Method used: ' . $method,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    try {
                        /** @var CollectorModuleInterface $handler */
                        $handler = $this->dic->get($handlerClassName);
                    } catch (ContainerExceptionInterface $error) {
                        debug_event(self::class, 'createShare: Dependency injection error: ' . $error->getMessage(), 1);
                        continue;
                    }

                    $data = $handler->collectArt(
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
            $results = array_merge($results, (array) $data);
        }

        $this->logger->notice(
            'found ' . count($results) . ' results',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return $results;
    }
}
