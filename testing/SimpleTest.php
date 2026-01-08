<?php

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    public function test1()
    {
    	$this->assertEquals(1, 1);
    }
}

?>