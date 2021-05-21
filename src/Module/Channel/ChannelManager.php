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
 */

declare(strict_types=0);

namespace Ampache\Module\Channel;

use Ampache\Module\System\Dba;
use Ampache\Repository\ChannelRepositoryInterface;
use Ampache\Repository\Model\ChannelInterface;

final class ChannelManager implements ChannelManagerInterface
{
    private ChannelInterface $channel;

    private ChannelRepositoryInterface $channelRepository;

    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        ChannelInterface $channel
    ) {
        $this->channelRepository = $channelRepository;
        $this->channel           = $channel;
    }

    public function updateListeners(
        int $listeners,
        bool $addition = false
    ): void {
        $sql             = "UPDATE `channel` SET `listeners` = ? ";
        $params          = array($listeners);
        $this->channel->setListeners($listeners);
        if ($listeners > $this->channel->getPeakListeners()) {
            $this->channel->setPeakListeners($listeners);
            $sql .= ", `peak_listeners` = ? ";
            $params[] = $listeners;
        }
        if ($addition) {
            $sql .= ", `connections`=`connections`+1 ";
        }
        $sql .= "WHERE `id` = ?";
        $params[] = $this->channel->getId();
        Dba::write($sql, $params);
    }

    public function updateStart(
        int $start_date,
        string $address,
        int $port,
        int $pid
    ): void {
        $sql = "UPDATE `channel` SET `start_date` = ?, `interface` = ?, `port` = ?, `pid` = ?, `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, array($start_date, $address, $port, $pid, $this->channel->getId()));

        $this->channel->setStartDate($start_date);
        $this->channel->setInterface($address);
        $this->channel->setPort((int) $port);
        $this->channel->setPid($pid);
    }

    public function startChannel(): void
    {
        $path = __DIR__ . '/../../../bin/cli';
        $cmd  = sprintf(
            'env php %s run:channel %d > /dev/null &',
            $path,
            $this->channel->getId()
        );
        exec($cmd);
    }

    public function stopChannel(): void
    {
        $pid = $this->channel->getPid();

        if ($pid) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /PID " . $pid);
            } else {
                exec("kill -9 " . $pid);
            }

            $sql = "UPDATE `channel` SET `start_date` = '0', `listeners` = '0', `pid` = '0' WHERE `id` = ?";
            Dba::write($sql, array($this->channel->getId()));

            $this->channel->setPid(0);
        }
    }

    public function checkChannel(): bool
    {
        $check = false;
        if ($this->channel->getInterface() && $this->channel->getPort()) {
            $connection = @fsockopen($this->channel->getInterface(), $this->channel->getPort());
            if (is_resource($connection)) {
                $check = true;
                fclose($connection);
            }
        }

        return $check;
    }
}
