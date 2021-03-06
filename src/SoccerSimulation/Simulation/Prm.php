<?php

namespace SoccerSimulation\Simulation;

class Prm
{
    const GoalWidth = 73.2;

    //use to set up the sweet spot calculator
    const NumSupportSpotsX = 13;
    const NumSupportSpotsY = 6;

    //these values tweak the various rules used to calculate the support spots
    const Spot_CanPassScore = 2.0;
    const Spot_CanScoreFromPositionScore = 1.0;
    const Spot_DistFromControllingPlayerScore = 2.0;
    const Spot_ClosenessToSupportingPlayerScore = 0.0;
    const Spot_AheadOfAttackerScore = 0.0;

    //how many times per second the support spots will be calculated
    const SupportSpotUpdateFreq = 1;

    //the chance a player might take a random pot shot at the goal
    const ChancePlayerAttemptsPotShot = 0.005;

    //this is the chance that a player will receive a pass using the arrive
    //steering behavior, rather than Pursuit
    const ChanceOfUsingArriveTypeReceiveBehavior = 0.5;

    const BallSize = 2.2;
    const BallMass = 1.0;
    const Friction = -0.015;

    //the goalkeeper has to be this close to the ball to be able to interact with it
    const KeeperInBallRange = 10.0;
    const PlayerInTargetRange = 10.0;

    //the number of times a player can kick the ball per second
    const PlayerKickFrequency = 0;

    const PlayerMassMin = 0.5;
    const PlayerMassMax = 5.5;
    const PlayerMaxForceMin = 0.5;
    const PlayerMaxForceMax = 1.5;
    const PlayerMaxSpeedWithBallMin = 1.0;
    const PlayerMaxSpeedWithBallMax = 1.4;
    const PlayerMaxSpeedWithoutBallMin = 1.4;
    const PlayerMaxSpeedWithoutBallMax = 1.8;
    const PlayerMaxTurnRate = 0.4;
    const PlayerScale = 1.0;

    //when an opponents comes within this range the player will attempt to pass
    //the ball. Players tend to pass more often, the higher the value
    const PlayerComfortZone = 60.0;

    //in the range zero to 1.0. adjusts the amount of noise added to a kick,
    //the lower the value the worse the players get.
    const PlayerKickingAccuracy = 0.99;

    //the number of times the SoccerTeam::CanShoot method attempts to find
    //a valid shot
    const NumAttemptsToFindValidStrike = 5;

    const MaxDribbleForce = 1.5;
    const MaxShootingForce = 6.0;
    const MaxPassingForce = 3.0;

    //the minimum distance a receiving player must be from the passing player
    const MinPassDist = 120.0;
    //the minimum distance a player must be from the goalkeeper before it will
    //pass the ball
    const GoalkeeperMinPassDist = 50.0;

    //this is the distance the keeper puts between the back of the net
    //and the ball when using the interpose steering behavior
    const GoalKeeperTendingDistance = 20.0;

    //when the ball becomes within this distance of the goalkeeper he
    //changes state to intercept the ball
    const GoalKeeperInterceptRange = 100.0;

    //how close the ball must be to a receiver before he starts chasing it
    const BallWithinReceivingRange = 10.0;

    //simple soccer's physics are calculated using each tick as the unit of time
    //so changing this will adjust the speed
    const FrameRate = 30;


    //--------------------------------------------steering behavior stuff
    const SeparationCoefficient = 10.0;

    //how close a neighbour must be to be considered for separation
    const ViewDistance = 30.0;

    //player has to be this close to the ball to be able to kick it. The higher
    //the value this gets, the easier it gets to tackle.
    static public function PlayerKickingDistance()
    {
        return 6.0 + self::BallSize;
    }
}
