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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CatalogRepositoryInterface;

final class SseApiApplication implements ApiApplicationInterface
{
    // wait on an already-running scan before giving up and treating it as a fresh trigger.
    private const int MAX_WAIT_SECONDS = 14400;

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui,
        private readonly CatalogRepositoryInterface $catalogRepository,
    ) {
        $this->ui = $ui;
    }

    public function run(): void
    {
        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
            $this->ui->accessDenied();

            return;
        }
        if (AmpConfig::get('demo_mode')) {
            return;
        }

        ob_end_clean();
        set_time_limit(0);

        if (!array_key_exists('html', $_REQUEST)) {
            define('SSE_OUTPUT', true);
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
        }

        $worker = $_REQUEST['worker'] ?? null;
        if (array_key_exists('options', $_REQUEST)) {
            $options = json_decode(urldecode($_REQUEST['options']), true);
        } else {
            $options = null;
        }

        if (array_key_exists('catalogs', $_REQUEST)) {
            $catalogs = array_map(
                'intval',
                (array) json_decode(urldecode($_REQUEST['catalogs']), true)
            );
        } else {
            $catalogs = null;
        }

        // Free the session write lock
        // Warning: Do not change any session variable after this call
        session_write_close();

        if ($worker == 'catalog') {
            if (defined('SSE_OUTPUT')) {
                echo "data: " . json_encode(['fn' => 'toggleVisible', 'args' => ['ajax-loading']]) . "\n\n";
                ob_flush();
                flush();
            }

            $action  = Core::get_request('action');
            $lockKey = $this->buildActionLockKey($action, $catalogs);

            // A reconnect (the browser/a proxy dropped the stream mid-scan) resends the same url
            if (!$this->catalogRepository->tryAcquireActionLock($lockKey)) {
                $this->waitWhileProcessing($lockKey);
            } else {
                try {
                    Catalog::process_action($action, $catalogs, $options);
                } finally {
                    $this->catalogRepository->releaseActionLock($lockKey);
                }
            }

            if (defined('SSE_OUTPUT')) {
                echo "data: " . json_encode(['fn' => 'toggleVisible', 'args' => ['ajax-loading']]) . "\n\n";
                ob_flush();
                flush();

                echo "data: " . json_encode(['fn' => 'stop_sse_worker', 'args' => []]) . "\n\n";
                ob_flush();
                flush();
            } else {
                echo AmpError::display('general');
            }
        }
    }

    /**
     * Identify SSE action calls by action and catalog ids.
     *
     * @param null|int[] $catalogIds
     */
    private function buildActionLockKey(string $action, ?array $catalogIds): string
    {
        $catalogPart = ($catalogIds === null) ? 'null' : implode(',', $catalogIds);

        return hash('sha256', $action . '|' . $catalogPart);
    }

    /**
     * Sends a comment line so the browser/any proxy sees activity and doesn't drop the connection again.
     */
    private function waitWhileProcessing(string $lockKey): void
    {
        $waited = 0;
        while ($this->catalogRepository->isActionProcessing($lockKey) && $waited < self::MAX_WAIT_SECONDS) {
            if (defined('SSE_OUTPUT')) {
                echo ": still processing\n\n";
                ob_flush();
                flush();
            }

            sleep(1);
            ++$waited;
        }
    }
}
