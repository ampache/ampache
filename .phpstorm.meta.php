<?php
namespace PHPSTORM_META
{
    override(\Psr\Container\ContainerInterface::get(0), map([
        '' => '@',
    ]));
    override(\DI\Container::get(0), map([
        '' => '@',
    ]));
    override(\Ampache\MockeryTestCase::mock(0), type(0));
    override(\Mockery::mock(0), type(0));
    override(\Mockery::spy(0), type(0));
    override(\Mockery::namedMock(0), type(0));
    override(\Mockery::instanceMock(0), type(0));
    override(\mock(0), type(0));
    override(\spy(0), type(0));
    override(\namedMock(0), type(0));
}
