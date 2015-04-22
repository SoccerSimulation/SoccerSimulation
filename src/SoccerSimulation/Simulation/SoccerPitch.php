<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\D2\Wall2D;
use SoccerSimulation\Common\Game\Region;
use Cunningsoft\MatchBundle\SimpleSoccer\Render\Pitch;
use SoccerSimulation\Simulation\TeamStates\PrepareForKickOff;

/**
 *  Desc:   A SoccerPitch is the main game object. It owns instances of
 *          two soccer teams, two goals, the playing area, the ball
 *          etc. This is the root class for all the game updates and
 *          renders etc
 */
class SoccerPitch
{
    /**
     * @var int
     */
    public static $NumRegionsHorizontal = 12;

    /**
     * @var int
     */
    public static $NumRegionsVertical = 7;

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

    /**
     * @var bool
     *
     * set true to pause the motion
     */
    private $isPaused;

    /**
     * @var int
     *
     * local copy of client window dimensions
     */
    private $clientWidth;

    /**
     * @var int
     *
     * local copy of client window dimensions
     */
    private $clientHeight;

    public function __construct($clientWidth, $clientHeight)
    {
        $this->clientWidth = $clientWidth;
        $this->clientHeight = $clientHeight;
        $this->isPaused = false;
        $this->goalKeeperHasBall = false;
        for ($i = 0; $i < self::$NumRegionsHorizontal * self::$NumRegionsVertical; $i++) {
            $this->regions[] = new Region();
        }
        $this->gameIsActive = true;
        //define the playing area
        $this->playingArea = new Region(20, 20, $clientWidth - 20, $clientHeight - 20);

        //create the regions  
        $this->createRegions($this->getPlayingArea()->getWidth() / self::$NumRegionsHorizontal,
            $this->getPlayingArea()->getHeight() / self::$NumRegionsVertical);

        //create the goals
        $this->redGoal = new Goal(new Vector2D($this->playingArea->getLeft(), ($clientHeight - Prm::GoalWidth) / 2),
                new Vector2D($this->playingArea->getLeft(), $clientHeight - ($clientHeight - Prm::GoalWidth) / 2),
                new Vector2D(1, 0));

        $this->blueGoal = new Goal(new Vector2D($this->playingArea->getRight(), ($clientHeight - Prm::GoalWidth) / 2),
                new Vector2D($this->playingArea->getRight(), $clientHeight - ($clientHeight - Prm::GoalWidth) / 2),
                new Vector2D(-1, 0));

        //create the walls
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

        //create the soccer ball
        $x = $this->clientWidth / 2.0;
        $y = $this->clientHeight / 2.0;
        $this->ball = new SoccerBall(new Vector2D($x, $y), Prm::BallSize, Prm::BallMass, Prm::Friction, $this->walls);


        //create the teams 
        $this->redTeam = new SoccerTeam($this->redGoal, $this->blueGoal, $this, SoccerTeam::COLOR_RED);
        $this->blueTeam = new SoccerTeam($this->blueGoal, $this->redGoal, $this, SoccerTeam::COLOR_BLUE);

        //make sure each team knows who their opponents are
        $this->redTeam->SetOpponent($this->blueTeam);
        $this->blueTeam->SetOpponent($this->redTeam);
    }

    /**
     ** this instantiates the regions the players utilize to  position
     ** themselves
     */
    private function createRegions($width, $height)
    {
        //index into the vector
        $idx = count($this->regions) - 1;

        for ($col = 0; $col < self::$NumRegionsHorizontal; ++$col) {
            for ($row = 0; $row < self::$NumRegionsVertical; ++$row) {
                $this->regions[$idx] = new Region($this->getPlayingArea()->getLeft() + $col * $width,
                    $this->getPlayingArea()->getTop() + $row * $height,
                    $this->getPlayingArea()->getLeft() + ($col + 1) * $width,
                    $this->getPlayingArea()->getTop() + ($row + 1) * $height,
                    $idx);
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
        if ($this->isPaused) {
            return;
        }

        //update the balls
        $this->ball->update();

        //update the teams
        $this->redTeam->update();
        $this->blueTeam->update();

        //if a goal has been detected reset the pitch ready for kickoff
        if ($this->blueGoal->hasScored($this->ball) || $this->redGoal->hasScored($this->ball)) {
            $this->gameIsActive = false;

            //reset the ball                                                      
            $this->ball->placeAtPosition(new Vector2D($this->clientWidth / 2.0, $this->clientHeight / 2.0));

            //get the teams ready for kickoff
            $this->redTeam->getStateMachine()->changeState(PrepareForKickOff::getInstance());
            $this->blueTeam->getStateMachine()->changeState(PrepareForKickOff::getInstance());
        }
    }

    public function render()
    {
        $pitch = new Pitch();

        //render regions
        if (Prm::ViewRegions) {
            for ($r = 0; $r < count($this->regions); ++$r) {
                $pitch->regions[] = $this->regions[$r]->render(true);
            }
        }

        //render the goals
        $pitch->goalRed = new \Cunningsoft\MatchBundle\SimpleSoccer\Render\Goal();
        $pitch->goalRed->x = $this->playingArea->getLeft();
        $pitch->goalRed->y = ($this->clientHeight - Prm::GoalWidth) / 2;
        $pitch->goalRed->width = 40;
        $pitch->goalRed->height = Prm::GoalWidth;

        $pitch->goalBlue = new \Cunningsoft\MatchBundle\SimpleSoccer\Render\Goal();
        $pitch->goalBlue->x = $this->playingArea->getRight() - 40;
        $pitch->goalBlue->y = ($this->clientHeight - Prm::GoalWidth) / 2;
        $pitch->goalBlue->width = 40;
        $pitch->goalBlue->height = Prm::GoalWidth;

        //the ball
        $pitch->ball = $this->ball->render();

        //Render the teams
        $pitch->teamRed = $this->redTeam->render();
        $pitch->teamBlue = $this->blueTeam->render();

        //show the score
        $pitch->scoreRed = $this->blueGoal->getNumberOfGoalsScored();
        $pitch->scoreBlue = $this->redGoal->getNumberOfGoalsScored();

        return $pitch;
    }

    /**
     * @return bool
     */
    public function hasGoalKeeperBall() {
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
    public function getPlayingArea() {
        return $this->playingArea;
    }

    public function getBall() {
        return $this->ball;
    }

    public function getRegionFromIndex($idx) {
        return $this->regions[$idx];
    }

    public function isGameActive() {
        return $this->gameIsActive;
    }

    public function setGameIsActive() {
        $this->gameIsActive = true;
    }
}
