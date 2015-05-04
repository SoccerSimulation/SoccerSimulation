<?php

namespace SoccerSimulation\Simulation\GoalKeeperStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\MessageDispatcher;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\GoalKeeper;
use SoccerSimulation\Simulation\MessageTypes;
use SoccerSimulation\Simulation\PlayerBase;
use SoccerSimulation\Simulation\Prm;

class PutBallBackInPlay extends State
{
    /**
     * @var PutBallBackInPlay
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new PutBallBackInPlay();
        }
        return self::$instance;
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function enter($keeper) {
        //let the team know that the keeper is in control
        $keeper->getTeam()->setControllingPlayer($keeper);

        //send all the players home
        $keeper->getTeam()->getOpponent()->returnAllFieldPlayersToHome();
        $keeper->getTeam()->returnAllFieldPlayersToHome();
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function execute($keeper)
    {
        $receiver = null;
        $ballTarget = new Vector2D();

        //test if there are players further forward on the field we might
        //be able to pass to. If so, make a pass.
        $pass = $keeper->getTeam()->findPass($keeper, $ballTarget, Prm::MaxPassingForce, Prm::GoalkeeperMinPassDist);
        if ($pass['found']) {
            /** @var PlayerBase $receiver */
            $receiver = clone $pass['receiver'];
            //make the pass   
            $keeper->getBall()->kick(Vector2D::vectorNormalize(Vector2D::staticSub($ballTarget, $keeper->getBall()->getPosition())),
                    Prm::MaxPassingForce);

            //goalkeeper no longer has ball 
            $keeper->getPitch()->setGoalKeeperHasBall(false);

            //let the receiving player know the ball's comin' at him
            MessageDispatcher::getInstance()->dispatch($keeper, $receiver, new MessageTypes(MessageTypes::Msg_ReceiveBall), $ballTarget);

            //go back to tending the goal   
            $keeper->getStateMachine()->changeState(TendGoal::getInstance());

            return;
        }

        $keeper->setVelocity(new Vector2D());
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function quit($keeper)
    {
    }

    /**
     * @param GoalKeeper $e
     * @param Telegram $t
     *
     * @return bool
     */
    public function onMessage($e, Telegram $t)
    {
        return false;
    }
}
