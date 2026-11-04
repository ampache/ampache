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
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\StreamControlIpActivityRepositoryInterface;
use Override;

class AmpacheStreamHits extends AmpachePlugin implements PluginStreamControlInterface
{
    #[Override]
    public string $categories = 'stream_control';

    #[Override]
    public string $description = 'Control hits per user';

    #[Override]
    public string $max_ampache = '999999';

    #[Override]
    public string $min_ampache = '370024';

    #[Override]
    public string $name = 'Stream Hits';

    #[Override]
    public string $url = '';

    #[Override]
    public string $version = '000001';

    private int $hits_days;
    private int $hits_ip_max;
    private int $hits_max;

    // These are internal settings used by this class, run this->load to fill them out
    private int $user_id;

    /**
     * Constructor
     */
    public function __construct(
        private readonly Graph $graph,
        private readonly StreamControlIpActivityRepositoryInterface $streamControlIpActivityRepository,
    ) {
        $this->description = T_('Control hits per user');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('stream_control_hits_max', T_('Stream control maximal hits'), -1, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('stream_control_hits_days', T_('Stream control hits history (days)'), 30, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('stream_control_hits_ip_max', T_('Stream control maximal hits per IP'), -1, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name);
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->user_id   = $user->id;
        $this->hits_max  = (int) ($data['stream_control_hits_max']) ?: -1;
        $this->hits_days = ((int) ($data['stream_control_hits_days']) > 0)
            ? (int) ($data['stream_control_hits_days'])
            : 30;

        $this->hits_ip_max = (int) ($data['stream_control_hits_ip_max']) ?: -1;

        return true;
    }

    /**
     * Check stream control
     * @param array<int, array{object_type: string|LibraryItemEnum, object_id: int}> $media_ids
     */
    public function stream_control(array $media_ids, ?string $ip = null, bool $isGuest = false): bool
    {
        // Nothing is configured to check (both limits unlimited, = -1)
        if ($this->hits_max < 0 && $this->hits_ip_max < 0) {
            return true;
        }

        // if using free software only you can't use this plugin
        if (!AmpConfig::get('statistical_graphs')) {
            debug_event('streamhits.plugin', 'Stream control not enforced, statistical graphs disabled.', 1);

            return true;
        }

        $hit_count  = count($media_ids);
        $end_date   = time();
        $start_date = $end_date - ($this->hits_days * 86400);

        if ($this->hits_max >= 0) {
            $account_total = $hit_count + $this->graph->get_total_hits($this->user_id, $start_date, $end_date);
            debug_event('streamhits.plugin', 'Next stream hits will be ' . $account_total . ' / ' . $this->hits_max, 3);
            if ($account_total > $this->hits_max) {
                return false;
            }
        }

        // a cap on the source IP, only used when the request has no identified account to cap instead (eg. a public share)
        if ($isGuest && $ip !== null && $this->hits_ip_max >= 0) {
            $ip_total = $hit_count + $this->streamControlIpActivityRepository->sumHits($ip, $start_date, $end_date);
            debug_event('streamhits.plugin', 'Next IP stream hits will be ' . $ip_total . ' / ' . $this->hits_ip_max, 3);
            if ($ip_total > $this->hits_ip_max) {
                return false;
            }
        }

        return true;
    }

    public function tracksIp(): bool
    {
        return $this->hits_ip_max >= 0;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('stream_control_hits_max')
            && Preference::delete('stream_control_hits_days')
            && Preference::delete('stream_control_hits_ip_max')
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
