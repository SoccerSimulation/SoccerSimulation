<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\Time\Regulator;

/**
 *  Desc:   Class to determine the best spots for a supporting soccer
 *          player to move to.
 */
class SupportSpotCalculator
{
    /**
     * @var SoccerTeam
     */
    private $m_pTeam;

    /**
     * @var SupportSpot[]
     */
    private $m_Spots = array();

    /**
     * @var SupportSpot
     *
     * a pointer to the highest valued spot from the last update
     */
    private $m_pBestSupportingSpot;

    /**
     * @var Regulator
     *
     * this will regulate how often the spots are calculated (default is
     * one update per second)
     */
    private $m_pRegulator;

    public function __construct($numX, $numY, SoccerTeam $team)
    {
        $this->m_pBestSupportingSpot = null;
        $this->m_pTeam = $team;
        $PlayingField = $team->getPitch()->getPlayingArea();

        //calculate the positions of each sweet spot, create them and 
        //store them in m_Spots
        $HeightOfSSRegion = $PlayingField->getHeight() * 0.8;
        $WidthOfSSRegion = $PlayingField->getWidth() * 0.9;
        $SliceX = $WidthOfSSRegion / $numX;
        $SliceY = $HeightOfSSRegion / $numY;

        $left = $PlayingField->getLeft() + ($PlayingField->getWidth() - $WidthOfSSRegion) / 2.0 + $SliceX / 2.0;
        $right = $PlayingField->getRight() - ($PlayingField->getWidth() - $WidthOfSSRegion) / 2.0 - $SliceX / 2.0;
        $top = $PlayingField->getTop() + ($PlayingField->getHeight() - $HeightOfSSRegion) / 2.0 + $SliceY / 2.0;

        for ($x = 0; $x < ($numX / 2) - 1; ++$x) {
            for ($y = 0; $y < $numY; ++$y) {
                if ($this->m_pTeam->getColor() == SoccerTeam::COLOR_BLUE) {
                    $this->m_Spots[] = new SupportSpot(new Vector2D($left + $x * $SliceX, $top + $y * $SliceY), 0.0);
                } else {
                    $this->m_Spots[] = new SupportSpot(new Vector2D($right - $x * $SliceX, $top + $y * $SliceY), 0.0);
                }
            }
        }

        //create the regulator
        $this->m_pRegulator = new Regulator(Prm::SupportSpotUpdateFreq);
    }

    /**
     * draws the spots to the screen as a hollow circles. The higher the
     * score, the bigger the circle. The best supporting spot is drawn in
     * bright green.
     */
    public function render()
    {
        throw new \Exception('dont use render');
    }

    /**
     * this method iterates through each possible spot and calculates its
     * score.
     */
    public function DetermineBestSupportingPosition()
    {
        //only update the spots every few frames                              
        if (!$this->m_pRegulator->isReady() && $this->m_pBestSupportingSpot != null) {
            return $this->m_pBestSupportingSpot->m_vPos;
        }

        //reset the best supporting spot
        $this->m_pBestSupportingSpot = null;

        $BestScoreSoFar = 0.0;

        foreach ($this->m_Spots as $curSpot) {
            //first remove any previous score. (the score is set to one so that
            //the viewer can see the positions of all the spots if he has the 
            //aids turned on)
            $curSpot->m_dScore = 1.0;

            //Test 1. is it possible to make a safe pass from the ball's position 
            //to this position?
            if ($this->m_pTeam->isPassSafeFromAllOpponents($this->m_pTeam->getControllingPlayer()->getPosition(),
                $curSpot->m_vPos, Prm::MaxPassingForce)
            ) {
                $curSpot->m_dScore += Prm::Spot_CanPassScore;
            }


            //Test 2. Determine if a goal can be scored from this position.  
            if ($this->m_pTeam->canShoot($curSpot->m_vPos,
                Prm::MaxShootingForce)
            ) {
                $curSpot->m_dScore += Prm::Spot_CanScoreFromPositionScore;
            }


            //Test 3. calculate how far this spot is away from the controlling
            //player. The further away, the higher the score. Any distances further
            //away than OptimalDistance pixels do not receive a score.
            if ($this->m_pTeam->getSupportingPlayer() != null) {

                $OptimalDistance = 400.0;

                $dist = Vector2D::Vec2DDistance($this->m_pTeam->getControllingPlayer()->getPosition(),
                    $curSpot->m_vPos);

                $temp = abs($OptimalDistance - $dist);

                if ($temp < $OptimalDistance) {

                    //normalize the distance and add it to the score
                    $curSpot->m_dScore += Prm::Spot_DistFromControllingPlayerScore
                        * ($OptimalDistance - $temp) / $OptimalDistance;
                }
            }

            //check to see if this spot has the highest score so far
            if ($curSpot->m_dScore > $BestScoreSoFar) {
                $BestScoreSoFar = $curSpot->m_dScore;

                $this->m_pBestSupportingSpot = $curSpot;
            }

        }

        /** @var SupportSpot $bestSupportingSpot */
        $bestSupportingSpot = $this->m_pBestSupportingSpot;

        return $bestSupportingSpot->m_vPos;
    }

    /**
     * returns the best supporting spot if there is one. If one hasn't been
     * calculated yet, this method calls DetermineBestSupportingPosition and
     * returns the result.
     */
    public function GetBestSupportingSpot()
    {
        if ($this->m_pBestSupportingSpot != null) {
            return $this->m_pBestSupportingSpot->m_vPos;
        } else {
            return $this->DetermineBestSupportingPosition();
        }
    }

    /**
     * @return SupportSpot[]
     */
    public function getSupportSpots()
    {
        return $this->m_Spots;
    }
}
