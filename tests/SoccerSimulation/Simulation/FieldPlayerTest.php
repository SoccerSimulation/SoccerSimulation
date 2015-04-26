<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;

class FieldPlayerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldPlayer
     */
    private $fieldPlayer;

    public function setUp()
    {
        $factory = $this->getMockBuilder('SoccerSimulation\Simulation\FieldPlayerFactory')->setMethods(['getMaxSpeedWithBall', 'getMaxSpeedWithoutBall'])->getMock();
        $factory->method('getMaxSpeedWithBall')->willReturn(Prm::PlayerMaxSpeedWithBallMin);
        $factory->method('getMaxSpeedWithoutBall')->willReturn(Prm::PlayerMaxSpeedWithoutBallMin);

        $pitch = new SoccerPitch();

        $team = new SoccerTeam($pitch->getBlueGoal(), $pitch->getRedGoal(), $pitch, 'Blue');
        $opponent = new SoccerTeam($pitch->getRedGoal(), $pitch->getBlueGoal(), $pitch, 'Red');
        $team->SetOpponent($opponent);

        $this->fieldPlayer = $factory->create($team, 0, PlayerBase::PLAYER_ROLE_ATTACKER);
    }

    public function testIsInHotRegion()
    {
        $this->assertFalse($this->fieldPlayer->isInHotRegion());

        $this->fieldPlayer->placeAtPosition(new Vector2D(10, 400));
        $this->assertTrue($this->fieldPlayer->isInHotRegion());
    }

    public function testMaxSpeed()
    {
        $this->assertEquals(Prm::PlayerMaxSpeedWithoutBallMin, $this->fieldPlayer->getMaxSpeed());

        $this->fieldPlayer->getBall()->placeAtPosition($this->fieldPlayer->getPosition());
        $this->fieldPlayer->getTeam()->setControllingPlayer($this->fieldPlayer);
        $this->assertEquals(Prm::PlayerMaxSpeedWithBallMin, $this->fieldPlayer->getMaxSpeed());
    }
}
