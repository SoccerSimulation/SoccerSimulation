<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Transformation;
use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\FSM\StateMachine;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Common\Time\Regulator;
use Cunningsoft\MatchBundle\SimpleSoccer\Render\Player;
use SoccerSimulation\Simulation\FieldPlayerStates\GlobalPlayerState;

/**
 *   Desc:   Derived from a PlayerBase, this class encapsulates a player
 *           capable of moving around a soccer pitch, kicking, dribbling,
 *           shooting etc
 */
class FieldPlayer extends PlayerBase
{
    /**
     * @var StateMachine
     *
     * an instance of the state machine class
     */
    private $stateMachine;

    /**
     * @var Regulator
     *
     * limits the number of kicks a player may take per second
     */
    private $kickLimiter;

    /**
     * @param SoccerTeam $homeTeam
     * @param int $homeRegion
     * @param State $startState
     * @param Vector2D $heading
     * @param Vector2D $velocity
     * @param float $mass
     * @param float $maxForce
     * @param float $maxSpeed
     * @param float $maxTurnRate
     * @param float $scale
     * @param string $role
     */
    public function __construct(SoccerTeam $homeTeam,
            $homeRegion,
            State $startState,
            Vector2D $heading,
            Vector2D $velocity,
            $mass,
            $maxForce,
            $maxSpeed,
            $maxTurnRate,
            $scale,
            $role) {
        parent::__construct($homeTeam,
                $homeRegion,
                $heading,
                $velocity,
                $mass,
                $maxForce,
                $maxSpeed,
                $maxTurnRate,
                $scale,
                $role);

        //set up the state machine
        $this->stateMachine = new StateMachine($this);

        if ($startState != null) {
            $this->stateMachine->setCurrentState($startState);
            $this->stateMachine->setPreviousState($startState);
            $this->stateMachine->setGlobalState(GlobalPlayerState::getInstance());

            $this->stateMachine->getCurrentState()->enter($this);
        }

        $this->steering->activateSeparation();

        //set up the kick regulator
        $this->kickLimiter = new Regulator(Prm::PlayerKickFrequency);
    }


    /**
     * call this to update the player's position and orientation
     */
    public function update() {
        parent::update();

        //run the logic for the current state
        $this->stateMachine->update();

        //calculate the combined steering force
        $this->steering->calculate();

        //if no steering force is produced decelerate the player by applying a
        //braking force
        if ($this->steering->getForce()->isZero()) {
            $brakingRate = 0.8;

            $this->velocity->mul($brakingRate);
        }

        //the steering force's side component is a force that rotates the 
        //player about its axis. We must limit the rotation so that a player
        //can only turn by PlayerMaxTurnRate rads per update.
        $turningForce = $this->steering->getSideComponent();
        $turningForce = max(min(Prm::PlayerMaxTurnRate, $turningForce), -Prm::PlayerMaxTurnRate);

        //rotate the heading vector
        Transformation::vectorRotateAroundOrigin($this->heading, $turningForce);

        //make sure the velocity vector points in the same direction as
        //the heading vector
        $this->velocity = Vector2D::staticMul($this->heading, $this->velocity->getLength());
        
        //and recreate m_vSide
        $this->side = $this->heading->getPerpendicular();

        //now to calculate the acceleration due to the force exerted by
        //the forward component of the steering force in the direction
        //of the player's heading
        $accel = Vector2D::staticMul($this->heading, $this->steering->getForwardComponent()/ $this->mass);

        $this->velocity->add($accel);

        //make sure player does not exceed maximum velocity
        $this->velocity->truncate($this->maxSpeed);
        //update the position
        $this->position->add($this->velocity);
    }

    public function render() {
        $player = new Player();

        if (Prm::HighlightIfThreatened && ($this->getTeam()->getControllingPlayer() == $this) && $this->isThreatened()) {
            $player->isThreatened = true;
        }

        $player->posX = $this->getPosition()->x;
        $player->posY = $this->getPosition()->y;
        $player->lookX = $this->getHeading()->x;
        $player->lookY = $this->getHeading()->y;

        if (Prm::ViewIDs) {
            $player->id = $this->getId();
        }
        if (Prm::ViewStates) {
            $player->state = $this->stateMachine->getNameOfCurrentState();
        }
        if (Prm::ViewTargets) {
            $player->targetX = $this->getSteering()->getTarget()->x;
            $player->targetY = $this->getSteering()->getTarget()->y;
        }
        if (Define::SHOW_STEERING_FORCE) {
            $steering = $this->getSteering()->render();
            $player->steeringX = $steering->x;
            $player->steeringY = $steering->y;
        }
        if (Define::SHOW_DEBUG_MESSAGES) {
            $player->debug = $this->getDebugMessages();
        }

        return $player;
    }

    /**
     * routes any messages appropriately
     */
    public function handleMessage(Telegram $message) {
        return $this->stateMachine->handleMessage($message);
    }

    public function getStateMachine() {
        return $this->stateMachine;
    }

    public function isReadyForNextKick() {
        return $this->kickLimiter->isReady();
    }
}
