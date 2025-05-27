<?php

namespace Ampache\Module\System;

use SessionHandlerInterface;

class AmpacheSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        return Session::read($id);
    }

    public function write(string $id, string $data): bool
    {
        return Session::write($id, $data);
    }

    public function destroy(string $id): bool
    {
        return Session::destroy($id);
    }

    public function gc(int $max_lifetime): int|false
    {
        Session::garbage_collection();

        return 0;
    }
}
