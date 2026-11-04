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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Graph;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\User;
use Ampache\Repository\StreamControlIpActivityRepositoryInterface;
use Override;

class AmpacheStreamBandwidth extends AmpachePlugin implements PluginStreamControlInterface
{
    #[Override]
    public string $categories = 'stream_control';

    #[Override]
    public string $description = 'Control bandwidth per user';

    #[Override]
    public string $max_ampache = '999999';

    #[Override]
    public string $min_ampache = '370024';

    #[Override]
    public string $name = 'Stream Bandwidth';

    #[Override]
    public string $url = '';

    #[Override]
    public string $version = '000001';

    private int $bandwidth_days   = 30;
    private int $bandwidth_ip_max = -1;
    private int $bandwidth_max    = 1024;

    // These are internal settings used by this class, run this->load to fill them out
    private int $user_id = 0;

    /**
     * Constructor
     */
    public function __construct(
        private readonly Graph $graph,
        private readonly StreamControlIpActivityRepositoryInterface $streamControlIpActivityRepository,
    ) {
        $this->description = T_('Control bandwidth per user');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('stream_control_bandwidth_max', T_('Stream control maximal bandwidth (month)'), 1024, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('stream_control_bandwidth_days', T_('Stream control bandwidth history (days)'), 30, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('stream_control_bandwidth_ip_max', T_('Stream control maximal bandwidth per IP (month)'), -1, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name);
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->user_id       = $user->id;
        $this->bandwidth_max = (int) ($data['stream_control_bandwidth_max']) ?: 1024;

        if ((int) ($data['stream_control_bandwidth_days']) > 0) {
            $this->bandwidth_days = (int) ($data['stream_control_bandwidth_days']);
        } else {
            $this->bandwidth_days = 30;
        }

        $this->bandwidth_ip_max = (int) ($data['stream_control_bandwidth_ip_max']) ?: -1;

        return true;
    }

    /**
     * Check stream control
     * @param array<int, array{object_type: string|LibraryItemEnum, object_id: int}> $media_ids
     */
    public function stream_control(array $media_ids, ?string $ip = null, bool $isGuest = false): bool
    {
        // Nothing is configured to check (both limits unlimited, = -1)
        if ($this->bandwidth_max < 0 && $this->bandwidth_ip_max < 0) {
            return true;
        }

        // if using free software only you can't use this plugin
        if (!AmpConfig::get('statistical_graphs')) {
            debug_event('streambandwidth.plugin', 'Stream control not enforced, statistical graphs disabled.', 1);

            return true;
        }

        // Calculate all media size
        $next_total = 0;
        foreach ($media_ids as $media_id) {
            $objectType = $media_id['object_type'];
            $className  = ObjectTypeToClassNameMapper::map($objectType instanceof LibraryItemEnum ? $objectType->value : $objectType);
            /** @var Media $media */
            $media = new $className($media_id['object_id']);
            $next_total += $media->size ?? 0;
        }

        $end_date   = time();
        $start_date = $end_date - ($this->bandwidth_days * 86400);

        if ($this->bandwidth_max >= 0) {
            $account_total = $next_total + $this->graph->get_total_bandwidth($this->user_id, $start_date, $end_date);
            $max           = $this->bandwidth_max * 1024 * 1024;
            debug_event('streambandwidth.plugin', 'Next stream bandwidth will be ' . $account_total . ' / ' . $max, 3);
            if ($account_total > $max) {
                return false;
            }
        }

        // a cap on the source IP, only used when the request has no identified account to cap instead (eg. a public share)
        if ($isGuest && $ip !== null && $this->bandwidth_ip_max >= 0) {
            $ip_total = $next_total + $this->streamControlIpActivityRepository->sumBandwidth($ip, $start_date, $end_date);
            $max      = $this->bandwidth_ip_max * 1024 * 1024;
            debug_event('streambandwidth.plugin', 'Next IP stream bandwidth will be ' . $ip_total . ' / ' . $max, 3);
            if ($ip_total > $max) {
                return false;
            }
        }

        return true;
    }

    public function tracksIp(): bool
    {
        return $this->bandwidth_ip_max >= 0;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('stream_control_bandwidth_max')
            && Preference::delete('stream_control_bandwidth_days')
            && Preference::delete('stream_control_bandwidth_ip_max')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    }
}
