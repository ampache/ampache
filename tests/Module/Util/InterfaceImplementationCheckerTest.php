<?php

declare(strict_types=1);

namespace Ampache\Module\Util;

use Channel;
use Ampache\Model\library_item;
use Ampache\Model\Media;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Ampache\Model\playable_item;
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
        $instance = Mockery::mock(Channel::class, Media::class);

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
