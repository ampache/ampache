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

namespace Ampache\Gui\Pow;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Pow\PowChallenge;
use Override;

/**
 * The markup a page needs in order to solve a challenge: the hidden fields the answer travels in,
 * a status line, and the solver script.
 *
 * Rendered inside the registration form and inside the standalone challenge page, so the two stay
 * in step without either of them knowing how the solver works.
 */
final class PowWidgetView extends AbstractView
{
    public function __construct(
        private readonly PowChallenge $challenge,
        private readonly string $webPath,
    ) {}

    public function getChallengeId(): string
    {
        return $this->challenge->id;
    }

    public function getDifficulty(): int
    {
        return $this->challenge->difficulty;
    }

    public function getExpire(): int
    {
        return $this->challenge->expire;
    }

    public function getScriptUrl(): string
    {
        return $this->webPath . '/lib/javascript/pow.js';
    }

    /**
     * The terms travel with the client, so they travel signed.
     */
    public function getSignature(): string
    {
        return $this->challenge->signature;
    }

    /** Served as a file, not a blob: `worker-src` falls back to `script-src 'self'`. */
    public function getWorkerUrl(): string
    {
        return $this->webPath . '/lib/javascript/pow.worker.js';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('pow/widget.phtml');
    }
}
