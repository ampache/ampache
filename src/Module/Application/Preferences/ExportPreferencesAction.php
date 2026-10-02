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

namespace Ampache\Module\Application\Preferences;

use Ampache\Gui\Preferences\PreferenceExporterInterface;
use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Hands back a configuration as a JSON file.
 */
final readonly class ExportPreferencesAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'export_preferences';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ModelFactoryInterface $modelFactory,
        private PreferenceExporterInterface $exporter,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || $gatekeeper->isDemoMode()
        ) {
            throw new AccessDeniedException();
        }

        $operator = $gatekeeper->getUser();
        if (!$operator instanceof User) {
            throw new AccessDeniedException();
        }

        $subject = $this->resolveSubject($request, $gatekeeper, $operator);
        $body    = json_encode($this->exporter->export($subject, $operator), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $this->exporter->fileName($subject) . '"');
        $response->getBody()->write(($body === false) ? '{}' : $body);

        return $response;
    }

    /**
     * Reading another account, or the server, is the admin's privilege.
     */
    private function resolveSubject(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        User $operator,
    ): PreferenceSubject {
        $params = $request->getQueryParams();
        $userId = (int) ($params['user_id'] ?? 0);
        $server = ($params['method'] ?? '') === 'admin';

        if (!$server && ($userId === 0 || $userId === $operator->getId())) {
            return PreferenceSubject::ownPreferences($operator);
        }

        if (!$gatekeeper->mayAdminister()) {
            throw new AccessDeniedException();
        }

        if ($server) {
            return PreferenceSubject::serverPreferences($operator);
        }

        $target = $this->modelFactory->createUser($userId);
        if ($target->isNew()) {
            throw new AccessDeniedException();
        }

        return PreferenceSubject::otherUser($target, $operator);
    }
}
