<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class SharesMethod implements MethodInterface
{
    public const ACTION = 'shares';

    private ConfigContainerInterface $configContainer;

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->streamFactory   = $streamFactory;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Get information about shared media this user is allowed to manage.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE) === false) {
            throw new FunctionDisabledException(T_('Enable: share'));
        }

        $browse = $this->modelFactory->createBrowse();
        $browse->reset_filters();
        $browse->set_type('share');
        $browse->set_sort('title', 'ASC');

        $method = ($input['exact'] ?? '') ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, ($input['filter'] ?? ''), $browse);
        Api::set_filter('add', ($input['add'] ?? ''), $browse);
        Api::set_filter('update', ($input['update'] ?? ''), $browse);


        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shares(
                    array_map('intval', $browse->get_objects()),
                    true,
                    (int) ($input['limit'] ?? 0),
                    (int) ($input['offset'] ?? 0)
                )
            )
        );
    }
}
