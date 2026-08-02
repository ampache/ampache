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

namespace Ampache\Module\Application\Update;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Preference;
use Ampache\Module\System\Update\Exception\UpdateFailedException;
use Ampache\Module\System\Update\Exception\VersionNotUpdatableException;
use Ampache\Module\System\Update\UpdaterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\RFC\RFC7231;

final readonly class UpdateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'update';

    public function __construct(
        private TalFactoryInterface $talFactory,
        private GuiFactoryInterface $guiFactory,
        private ResponseFactoryInterface $responseFactory,
        private ConfigContainerInterface $configContainer,
        private StreamFactoryInterface $streamFactory,
        private UpdaterInterface $updater,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        try {
            $hasPendingUpdates = $this->updater->hasPendingUpdates();
        } catch (QueryFailedException) {
            // there is no readable update_info to update from, so send them somewhere that explains what is missing
            return $this->responseFactory
                ->createResponse(RFC7231::FOUND)
                ->withHeader('Location', $this->configContainer->getWebPath() . '/test.php');
        }

        if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) === 'sources') {
            if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false) {
                throw new AccessDeniedException();
            }

            set_time_limit(300);
            $success = AutoUpdate::update_files();
            if ($success) {
                $success = AutoUpdate::update_dependencies($this->configContainer);
            }

            Preference::translate_db();
            Preference::set_defaults();

            // a failed update has already printed the command output, so stay on the page rather than redirect
            if (!$success) {
                return $this->responseFactory->createResponse();
            }

            $target = $this->getReturnPath($request);

            // the commands flush their output as they run, so a Location header would be dropped
            if (headers_sent()) {
                echo '<script>window.location.href = ' . (string) json_encode(
                    $target,
                    JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                ) . ';</script>';
                echo '<p><a href="' . scrub_out($target) . '">' . T_('Continue') . '</a></p>';

                return $this->responseFactory->createResponse();
            }

            return $this->responseFactory
                ->createResponse(RFC7231::FOUND)
                ->withHeader('Location', $target);
        } elseif ($hasPendingUpdates) {
            try {
                $this->updater->update();
            } catch (UpdateFailedException) {
                AmpError::add('general', T_('Update failed. Please check the logs for further information.'));
            } catch (VersionNotUpdatableException) {
                echo '<p class="database-update">Database version too old, please upgrade to <a href="https://github.com/ampache/ampache/releases/download/3.8.2/ampache-3.8.2_all.zip">Ampache-3.8.2</a> first</p>';
            }
        }

        $result = $this->talFactory->createTalView()
            ->setTemplate('update.xhtml')
            ->setContext(
                'UPDATE',
                $this->guiFactory->createUpdateViewAdapter()
            )
            ->render();

        return $this->responseFactory
            ->createResponse()
            ->withBody(
                $this->streamFactory->createStream($result)
            );
    }

    /**
     * Return to the page the update was started from, falling back to the web root.
     */
    private function getReturnPath(ServerRequestInterface $request): string
    {
        $fallback = $this->configContainer->getWebPath();
        $referer  = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return $fallback;
        }

        $parts = parse_url($referer);
        if (
            $parts === false
            || !isset($parts['path'])
            || (isset($parts['host']) && $parts['host'] !== $request->getUri()->getHost())
            || str_contains($parts['path'], 'update.php')
        ) {
            return $fallback;
        }

        return $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }
}
