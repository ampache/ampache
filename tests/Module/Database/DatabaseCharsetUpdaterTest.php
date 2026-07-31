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
 */

namespace Ampache\Module\Database;

use Ampache\Config\ConfigContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatabaseCharsetUpdaterTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseCharsetUpdater $subject;

    public function testUpdateReadsDatabaseNameAndSiteCharsetFromConfig(): void
    {
        // NOTE: the rest of update()'s behavior (the actual ALTER statements) are driven by static Dba calls.
        $this->configContainer->expects(static::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['database_name', 'test_db'],
                ['site_charset', 'utf-8'],
            ]);

        $this->subject->update();

        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new DatabaseCharsetUpdater($this->configContainer);
    }
}
