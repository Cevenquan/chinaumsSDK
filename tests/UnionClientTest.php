<?php
/**
 * Created by PhpStorm.
 * User: 30795
 * Date: 2019/6/14
 * Time: 16:35
 */

namespace cevenquan\chinaumsSDK\Tests;
use PHPUnit\Framework\TestCase;
use Cevenquan\ChinaumsSDK\UnionClient;
final class UnionClientTest extends TestCase
{
    public function testCannotBeShow()
    {
        $UnionClient = new UnionClient;

        $this->expectOutputString($UnionClient->show());
        print $UnionClient->show();
    }
}