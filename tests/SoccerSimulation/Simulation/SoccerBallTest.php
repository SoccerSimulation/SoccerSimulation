<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;

class SoccerBallTest extends \PHPUnit_Framework_TestCase
{
    public function testKick()
    {
        $position = new Vector2D(10, 5);
        $ball = new SoccerBall($position, 5, 1, -0.015, array());

        $this->assertEquals(0, $ball->getVelocity()->x);
        $this->assertEquals(0, $ball->getVelocity()->y);

        $direction = new Vector2D(5, 0);
        $ball->kick($direction, 0.8);

        $this->assertEquals(0.8, $ball->getVelocity()->x);
        $this->assertEquals(0, $ball->getVelocity()->y);
    }

    public function testUpdate()
    {
        $position = new Vector2D(10, 5);
        $ball = new SoccerBall($position, 5, 1, -0.015, array());
        $ball->getVelocity()->x = 5;
        $ball->getVelocity()->y = 0;

        $this->assertEquals(5, $ball->getVelocity()->x);
        $this->assertEquals(0, $ball->getVelocity()->y);
        $this->assertEquals(10, $ball->getPosition()->x);
        $this->assertEquals(5, $ball->getPosition()->y);

        $ball->update();

        $this->assertEquals(4.985, $ball->getVelocity()->x);
        $this->assertEquals(0, $ball->getVelocity()->y);
        $this->assertEquals(14.985, $ball->getPosition()->x);
        $this->assertEquals(5, $ball->getPosition()->y);
    }
}
