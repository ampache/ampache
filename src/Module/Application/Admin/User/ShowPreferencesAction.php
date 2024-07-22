<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PreferenceRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders the users preferences
 */
final class ShowPreferencesAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'show_preferences';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private PreferenceRepositoryInterface $preferenceRepository;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        PreferenceRepositoryInterface $preferenceRepository
    ) {
        $this->ui                   = $ui;
        $this->modelFactory         = $modelFactory;
        $this->preferenceRepository = $preferenceRepository;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $userId = (int) ($request->getQueryParams()['user_id'] ?? 0);
        $user   = $this->modelFactory->createUser($userId);

        if ($user->isNew()) {
            throw new ObjectNotFoundException($userId);
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_user_preferences.inc.php',
            [
                'ui' => $this->ui,
                'preferences' => $this->preferenceRepository->getAll($user),
                'client' => $user
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
