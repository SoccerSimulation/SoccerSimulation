<?php

namespace SoccerSimulation\Common\Time;

/**
 *  Desc:   Use this class to regulate code flow (for an update function say)
 *          Instantiate the class with the frequency you would like your code
 *          section to flow (like 10 times per second) and then only allow
 *          the program flow to continue if Ready() returns true
 */
class Regulator
{
    //the time period between updates
    private $m_dUpdatePeriod;
    //the next time the regulator allows code flow
    private $m_dwNextUpdateTime;

    public function __construct($NumUpdatesPerSecondRqd)
    {
        $this->m_dwNextUpdateTime = microtime() + lcg_value() * 1000;

        if ($NumUpdatesPerSecondRqd > 0) {
            $this->m_dUpdatePeriod = 1000.0 / $NumUpdatesPerSecondRqd;
        } else {
            if (0 == $NumUpdatesPerSecondRqd) {
                $this->m_dUpdatePeriod = 0.0;
            } else {
                if ($NumUpdatesPerSecondRqd < 0) {
                    $this->m_dUpdatePeriod = -1;
                }
            }
        }
    }

    //the number of milliseconds the update period can vary per required
    //update-step. This is here to make sure any multiple clients of this class
    //have their updates spread evenly
    private static $UpdatePeriodVariator = 10.0;

    /**
     * @return true if the current time exceeds m_dwNextUpdateTime
     */
    public function isReady()
    {
        //if a regulator is instantiated with a zero freq then it goes into
        //stealth mode (doesn't regulate)
        if (0.0 == $this->m_dUpdatePeriod) {
            return true;
        }

        //if the regulator is instantiated with a negative freq then it will
        //never allow the code to flow
        if ($this->m_dUpdatePeriod < 0) {
            return false;
        }

        $CurrentTime = microtime() + lcg_value() * 1000;

        if ($CurrentTime >= $this->m_dwNextUpdateTime) {
            $this->m_dwNextUpdateTime = ($CurrentTime + $this->m_dUpdatePeriod + rand(-self::$UpdatePeriodVariator,
                    self::$UpdatePeriodVariator));

            return true;
        }

        return false;
    }
}
