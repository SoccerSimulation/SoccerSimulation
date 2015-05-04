<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\EnterStateEvent;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\FieldPlayer;
use SoccerSimulation\Simulation\Prm;

class ReceiveBall extends State
{
    /**
     * @var ReceiveBall
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ReceiveBall();
        }

        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player)
    {
        //let the team know this player is receiving the ball
        $player->getTeam()->setReceiver($player);

        //this player is also now the controlling player
        $player->getTeam()->setControllingPlayer($player);

        //there are two types of receive behavior. One uses arrive to direct
        //the receiver to the position sent by the passer in its telegram. The
        //other uses the pursuit behavior to pursue the ball. 
        //This statement selects between them dependent on the probability
        //ChanceOfUsingArriveTypeReceiveBehavior, whether or not an opposing
        //player is close to the receiving player, and whether or not the receiving
        //player is in the opponents 'hot region' (the third of the pitch closest
        //to the opponent's goal
        $PassThreatRadius = 70.0;

        // @todo change player::isInHotRegion() to player::isInPenaltyBox
        if (($player->isInHotRegion() || lcg_value() < Prm::ChanceOfUsingArriveTypeReceiveBehavior) && !$player->getTeam()->isOpponentWithinRadius($player->getPosition(),
                $PassThreatRadius)
        ) {
            $player->getSteering()->activateArrive();
        } else {
            $player->getSteering()->activatePursuit();
        }
        if (Define::PLAYER_STATE_INFO_ON) {
            $this->raise(new EnterStateEvent($this, $player));
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player)
    {
        //if the ball comes close enough to the player or if his team lose control
        //he should change state to chase the ball
        if ($player->isBallWithinReceivingRange() || !$player->getTeam()->isInControl()) {
            $player->getStateMachine()->changeState(ChaseBall::getInstance());

            return;
        }

        if ($player->getSteering()->isPursuitActive()) {
            $player->getSteering()->setTarget($player->getBall()->getPosition());
        }

        //if the player has 'arrived' at the steering target he should wait and
        //turn to face the ball
        if ($player->isAtTarget()) {
            $player->getSteering()->deactivateArrive();
            $player->getSteering()->deactivatePursuit();
            $player->trackBall();
            $player->setVelocity(new Vector2D(0, 0));
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function quit($player)
    {
        $player->getSteering()->deactivateArrive();
        $player->getSteering()->deactivatePursuit();

        $player->getTeam()->resetReceiver();
    }
}
