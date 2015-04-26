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
        $factory = new FieldPlayerFactory();

        $pitch = new SoccerPitch();

        $team = new SoccerTeam($pitch->getBlueGoal(), $pitch->getRedGoal(), $pitch, 'Blue');

        $this->fieldPlayer = $factory->create($team, 0, PlayerBase::PLAYER_ROLE_ATTACKER);
    }

    public function testIsInHotRegion()
    {
        $this->assertFalse($this->fieldPlayer->isInHotRegion());

        $this->fieldPlayer->placeAtPosition(new Vector2D(10, 400));
        $this->assertTrue($this->fieldPlayer->isInHotRegion());
    }

    public function testIsInPenaltyArea()
    {
        $this->assertFalse($this->fieldPlayer->isInPenaltyArea());

        $this->fieldPlayer->placeAtPosition(new Vector2D(10, 400));
        $this->assertTrue($this->fieldPlayer->isInPenaltyArea());
    }

    public function testIsInOwnPenaltyArea()
    {
        $this->assertFalse($this->fieldPlayer->isInOwnPenaltyArea());

        $this->fieldPlayer->placeAtPosition(new Vector2D(10, 400));
        $this->assertTrue($this->fieldPlayer->isInOwnPenaltyArea());
    }

    public function testIsInOpponentsPenaltyArea()
    {
        $this->assertFalse($this->fieldPlayer->isInOpponentsPenaltyArea());

        $this->fieldPlayer->placeAtPosition(new Vector2D(10, 400));
        $this->assertTrue($this->fieldPlayer->isInOpponentsPenaltyArea());
    }
}
