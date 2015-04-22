<?php

namespace SoccerSimulation\Common\D2;

class Vector2DTest extends \PHPUnit_Framework_TestCase
{
    public function testPerp()
    {
        $vector = new Vector2D(10, 5);
        $perp = $vector->getPerpendicular();

        $this->assertEquals(10, $vector->x);
        $this->assertEquals(5, $vector->y);
        $this->assertEquals(-5, $perp->x);
        $this->assertEquals(10, $perp->y);
    }

    public function testNormalize()
    {
        $vector = new Vector2D(10, 0);
        $vector->normalize();

        $this->assertEquals(1, $vector->x);
        $this->assertEquals(0, $vector->y);
    }

    public function testTruncate()
    {
        $vector = new Vector2D(10, 0);
        $vector->truncate(5);

        $this->assertEquals(5, $vector->x);
        $this->assertEquals(0, $vector->y);
    }

    public function testDot()
    {
        $vector1 = new Vector2D(10, 5);
        $vector2 = new Vector2D(8, 7);

        $this->assertEquals(115, $vector1->dot($vector2));
    }
}
