<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\D2\Wall2D;
use SoccerSimulation\Common\Game\Region;
use SoccerSimulation\Simulation\TeamStates\PrepareForKickOff;

/**
 *  Desc:   A SoccerPitch is the main game object. It owns instances of
 *          two soccer teams, two goals, the playing area, the ball
 *          etc. This is the root class for all the game updates and
 *          renders etc
 */
class SoccerPitch implements \JsonSerializable
{
    const REGIONS_HORIZONTAL = 12;
    const REGIONS_VERTICAL = 7;

    const PITCH_WIDTH = 1050;
    const PITCH_HEIGHT = 680;
    const PITCH_OFFSET_HEIGHT = 20;
    const PITCH_OFFSET_WIDTH = 60;

    /**
     * @var SoccerBall
     */
    private $ball;

    /**
     * @var SoccerTeam
     */
    private $redTeam;

    /**
     * @var SoccerTeam
     */
    private $blueTeam;

    /**
     * @var Goal
     */
    private $redGoal;

    /**
     * @var Goal
     */
    private $blueGoal;

    /**
     * @var Wall2D[]
     */
    private $walls = array();

    /**
     * @var Region
     */
    private $playingArea;

    /**
     * @var Region[]
     *
     * the playing field is broken up into regions that the team
     * can make use of to implement strategies.
     */
    private $regions = array();

    /**
     * @var bool
     *
     * true if a goal keeper has possession
     */
    private $goalKeeperHasBall;

    /**
     * @var bool
     *
     * true if the game is in play. Set to false whenever the players
     * are getting ready for kickoff
     */
    private $gameIsActive;

    public function __construct()
    {
        $this->goalKeeperHasBall = false;
        for ($i = 0; $i < self::REGIONS_HORIZONTAL * self::REGIONS_VERTICAL; $i++) {
            $this->regions[] = new Region();
        }
        $this->gameIsActive = true;
        // define the playing area
        $this->playingArea = new Region(self::PITCH_OFFSET_WIDTH, self::PITCH_OFFSET_HEIGHT, self::PITCH_WIDTH + self::PITCH_OFFSET_WIDTH, self::PITCH_HEIGHT + self::PITCH_OFFSET_HEIGHT);

        // create the regions
        $this->createRegions($this->getPlayingArea()->getWidth() / self::REGIONS_HORIZONTAL, $this->getPlayingArea()->getHeight() / self::REGIONS_VERTICAL);

        // create the goals
        $this->redGoal = new Goal(new Vector2D(self::PITCH_OFFSET_WIDTH, $this->getCenterOfPitch()->y), new Vector2D(1, 0));
        $this->blueGoal = new Goal(new Vector2D(self::PITCH_OFFSET_WIDTH + self::PITCH_WIDTH, $this->getCenterOfPitch()->y), new Vector2D(-1, 0));

        // create the walls
        $topLeft = new Vector2D($this->playingArea->getLeft(), $this->playingArea->getTop());
        $topRight = new Vector2D($this->playingArea->getRight(), $this->playingArea->getTop());
        $bottomRight = new Vector2D($this->playingArea->getRight(), $this->playingArea->getBottom());
        $bottomLeft = new Vector2D($this->playingArea->getLeft(), $this->playingArea->getBottom());

        $this->walls[] = new Wall2D($bottomLeft, $this->redGoal->getRightPost());
        $this->walls[] = new Wall2D($this->redGoal->getLeftPost(), $topLeft);
        $this->walls[] = new Wall2D($topLeft, $topRight);
        $this->walls[] = new Wall2D($topRight, $this->blueGoal->getLeftPost());
        $this->walls[] = new Wall2D($this->blueGoal->getRightPost(), $bottomRight);
        $this->walls[] = new Wall2D($bottomRight, $bottomLeft);

        // create the soccer ball
        $this->ball = new SoccerBall($this->getCenterOfPitch(), Prm::BallSize, Prm::BallMass, Prm::Friction, $this->walls);

        // create the teams
        $this->redTeam = new SoccerTeam($this->redGoal, $this->blueGoal, $this, SoccerTeam::COLOR_RED);
        $this->blueTeam = new SoccerTeam($this->blueGoal, $this->redGoal, $this, SoccerTeam::COLOR_BLUE);

        // make sure each team knows who their opponents are
        $this->redTeam->SetOpponent($this->blueTeam);
        $this->blueTeam->SetOpponent($this->redTeam);
    }

    /**
     * this instantiates the regions the players utilize to  position
     * themselves
     *
     * @param float $width
     * @param float $height
     */
    private function createRegions($width, $height)
    {
        // index into the vector
        $idx = count($this->regions) - 1;

        for ($col = 0; $col < self::REGIONS_HORIZONTAL; ++$col) {
            for ($row = 0; $row < self::REGIONS_VERTICAL; ++$row) {
                $this->regions[$idx] = new Region($col * $width + self::PITCH_OFFSET_WIDTH, $row * $height + self::PITCH_OFFSET_HEIGHT, ($col + 1) * $width + self::PITCH_OFFSET_WIDTH, ($row + 1) * $height + self::PITCH_OFFSET_HEIGHT, $idx);
                --$idx;
            }
        }
    }

    static $tick = 0;

    /**
     *  this demo works on a fixed frame rate (60 by default) so we don't need
     *  to pass a time_elapsed as a parameter to the game entities
     */
    public function update()
    {
        // update the balls
        $this->ball->update();

        // update the teams
        $this->redTeam->update();
        $this->blueTeam->update();

        // if a goal has been detected reset the pitch ready for kickoff
        if ($this->blueGoal->hasScored($this->ball) || $this->redGoal->hasScored($this->ball)) {
            $this->gameIsActive = false;

            // reset the ball
            $this->ball->placeAtPosition($this->getCenterOfPitch());

            // get the teams ready for kickoff
            $this->redTeam->getStateMachine()->changeState(PrepareForKickOff::getInstance());
            $this->blueTeam->getStateMachine()->changeState(PrepareForKickOff::getInstance());
        }
    }

    /**
     * @return bool
     */
    public function hasGoalKeeperBall()
    {
        return $this->goalKeeperHasBall;
    }

    /**
     * @param bool $b
     */
    public function setGoalKeeperHasBall($b)
    {
        $this->goalKeeperHasBall = $b;
    }

    /**
     * @return Region
     */
    public function getPlayingArea()
    {
        return $this->playingArea;
    }

    public function getBall()
    {
        return $this->ball;
    }

    public function getRegionFromIndex($idx)
    {
        return $this->regions[$idx];
    }

    public function isGameActive()
    {
        return $this->gameIsActive;
    }

    public function setGameIsActive()
    {
        $this->gameIsActive = true;
    }

    public function render()
    {
        throw new \Exception('dont use render');
    }

    public function jsonSerialize()
    {
        return [
            'ball' => $this->ball,
            'teamRed' => $this->redTeam,
            'teamBlue' => $this->blueTeam,
            'goalRed' => $this->redGoal,
            'goalBlue' => $this->blueGoal,
            'regions' => $this->regions
        ];
    }

    /**
     * @return Goal
     */
    public function getRedGoal()
    {
        return $this->redGoal;
    }

    /**
     * @return Goal
     */
    public function getBlueGoal()
    {
        return $this->blueGoal;
    }

    /**
     * @return Vector2D
     */
    public function getCenterOfPitch()
    {
        return new Vector2D(self::PITCH_WIDTH / 2 + self::PITCH_OFFSET_WIDTH, self::PITCH_HEIGHT / 2 + self::PITCH_OFFSET_HEIGHT);
    }
}
