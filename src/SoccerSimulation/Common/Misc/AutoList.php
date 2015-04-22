<?php

namespace SoccerSimulation\Common\Misc;

// Unfortunatelly this class is not "Auto"
// You have to call add()/remove() method in your class
class AutoList
{
    static private $m_Members = array();

    public function add($o) {
        self::$m_Members[] = $o;
    }

    public function GetAllMembers() {
        return self::$m_Members;
    }
}
