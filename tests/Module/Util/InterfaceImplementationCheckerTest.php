<?php

declare(strict_types=1);

namespace Ampache\Module\Util;

use Channel;
use library_item;
use media;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use playable_item;
use Song_Preview;

class InterfaceImplementationCheckerTest extends MockeryTestCase
{
    public function testIsPlayableItemReturnsTrueIfImplemented(): void
    {
        $instance = Mockery::mock(Song_Preview::class, playable_item::class);

        $this->assertTrue(
            InterfaceImplementationChecker::is_playable_item(get_class($instance))
        );
    }

    public function testIsPlayableItemReturnsFalseIfNotImplemented(): void
    {
        $instance = Mockery::mock(Song_Preview::class);

        $this->assertFalse(
            InterfaceImplementationChecker::is_playable_item(get_class($instance))
        );
    }

    public function testIsLibraryItemReturnsTrueIfImplemented(): void
    {
        $instance = Mockery::mock(Channel::class, library_item::class);

        $this->assertTrue(
            InterfaceImplementationChecker::is_library_item(get_class($instance))
        );
    }

    public function testIsLibraryItemReturnsFalseIfNotImplemented(): void
    {
        $instance = Mockery::mock(Song_Preview::class);

        $this->assertFalse(
            InterfaceImplementationChecker::is_library_item(get_class($instance))
        );
    }

    public function testIsMediaReturnsTrueIfImplemented(): void
    {
        $instance = Mockery::mock(Channel::class, media::class);

        $this->assertTrue(
            InterfaceImplementationChecker::is_media(get_class($instance))
        );
    }

    public function testIsMediaReturnsFalseIfNotImplemented(): void
    {
        $instance = Mockery::mock(Song_Preview::class);

        $this->assertFalse(
            InterfaceImplementationChecker::is_media(get_class($instance))
        );
    }
}
