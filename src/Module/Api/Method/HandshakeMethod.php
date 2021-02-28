<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\Exception\HandshakeException;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Authentication\HandshakeInterface;
use Ampache\Module\Api\Method\Lib\ServerDetailsRetrieverInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Module\Util\EnvironmentInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class HandshakeMethod implements MethodInterface
{
    public const ACTION = 'handshake';

    private StreamFactoryInterface $streamFactory;

    private HandshakeInterface $handshake;

    private EnvironmentInterface $environment;

    private ServerDetailsRetrieverInterface $serverDetailsRetriever;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        HandshakeInterface $handshake,
        EnvironmentInterface $environment,
        ServerDetailsRetrieverInterface $serverDetailsRetriever
    ) {
        $this->streamFactory          = $streamFactory;
        $this->handshake              = $handshake;
        $this->environment            = $environment;
        $this->serverDetailsRetriever = $serverDetailsRetriever;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * auth      = (string) $passphrase
     * user      = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version   = (string) $version //optional
     *
     * @return ResponseInterface
     *
     * @throws Exception\HandshakeFailedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $passphrase = $gatekeeper->getAuth();

        $version   = (isset($input['version'])) ? $input['version'] : Api::$version;
        $timestamp = (int) preg_replace('/[^0-9]/', '', $input['timestamp'] ?? time());

        try {
            $user = $this->handshake->handshake(
                trim((string) $input['user']),
                trim($passphrase),
                $timestamp,
                $version,
                $this->environment->getClientIp()
            );
        } catch (HandshakeException $e) {
            throw new Exception\HandshakeFailedException($e->getMessage());
        }

        // Create the session
        $data             = [];
        $data['username'] = $user->username;
        $data['type']     = 'api';
        $data['apikey']   = $user->apikey;
        $data['value']    = $timestamp;
        if (isset($input['client'])) {
            $data['agent'] = $input['client'];
        }
        if (isset($input['geo_latitude'])) {
            $data['geo_latitude'] = $input['geo_latitude'];
        }
        if (isset($input['geo_longitude'])) {
            $data['geo_longitude'] = $input['geo_longitude'];
        }
        if (isset($input['geo_name'])) {
            $data['geo_name'] = $input['geo_name'];
        }
        //Session might not exist or has expired
        if (!Session::read($data['apikey'])) {
            Session::destroy($data['apikey']);
            $token = Session::create($data);
        } else {
            Session::extend($data['apikey']);
            $token = $data['apikey'];
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->dict(
                    $this->serverDetailsRetriever->retrieve($token)
                )
            )
        );
    }
}
