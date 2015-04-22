<?php

namespace SoccerSimulation\Common\Game;

use SoccerSimulation\Simulation\BaseGameEntity;

/**
 * Desc:   Singleton class to handle the  management of Entities.          
 */
class EntityManager {
    //provide easy access

    public static $EntityMgr;

    //to facilitate quick lookup the entities are stored in a std::map, in which
    //pointers to entities are cross referenced by their identifying number
    private $m_EntityMap = array();

    private function __construct() {
    }

//--------------------------- Instance ----------------------------------------
//   this class is a singleton
//-----------------------------------------------------------------------------
    public static function getInstance() {
        if (self::$EntityMgr === null) {
            self::$EntityMgr = new EntityManager();
        }
        return self::$EntityMgr;
    }

    /**
     * this method stores a pointer to the entity in the std::vector
     * m_Entities at the index position indicated by the entity's ID
     * (makes for faster access)
     */
    public function RegisterEntity(BaseGameEntity $NewEntity) {
        $this->m_EntityMap[$NewEntity->getId()] = $NewEntity;
    }

    /**
     * @param int
     *
     * @return BaseGameEntity
     *
     * a pointer to the entity with the ID given as a parameter
     */
    public function getEntityFromId($id) {
        //find the entity
        $ent = $this->m_EntityMap[$id];

        return $ent;
    }

    /**
     * this method removes the entity from the list
     */
    public function RemoveEntity(BaseGameEntity $pEntity) {
        unset($this->m_EntityMap[$pEntity->getId()]);
    }

    /**
     * clears all entities from the entity map
     */
    public function Reset() {
        $this->m_EntityMap = array();
    }
}
