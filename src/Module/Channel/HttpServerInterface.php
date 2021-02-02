<?php

namespace Ampache\Module\Channel;

use Ahc\Cli\IO\Interactor;
use Ampache\Model\Channel;

interface HttpServerInterface
{
    public function serve(
        Interactor $interactor,
        Channel $channel,
        array &$client_socks,
        array &$stream_clients,
        array &$read_socks,
        $sock
    ): void;

    public function disconnect(
        Interactor $interactor,
        Channel $channel,
        array &$client_socks,
        array &$stream_clients,
        $sock
    ): void;
}
