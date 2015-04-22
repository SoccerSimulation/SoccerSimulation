<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\Messaging\Telegram;

/**
 * Desc: Base class to define a common interface for all game
 *       entities
 */
abstract class BaseGameEntity
{
    /**
     * @var int
     */
    public static $default_entity_type = -1;

    /**
     * @var int
     *
     * each entity has a unique ID
     */
    private $id;

    /**
     * @var int
     *
     * every entity has a type associated with it (health, troll, ammo etc)
     */
    private $type;

    /**
     * @var int
     *
     * this is the next valid ID. Each time a BaseGameEntity is instantiated
     * this value is updated
     */
    private static $nextValidId = 0;

    /**
     * @var Vector2D
     *
     * its location in the environment
     */
    protected $position;

    /**
     * @var Vector2D
     */
    protected $scale;

    /**
     * @var double
     *
     * the magnitude of this object's bounding radius
     */
    protected $boundingRadius;

    /**
     * @param int $id
     */
    protected function __construct($id)
    {
        $this->boundingRadius = 0;
        $this->scale = new Vector2D(1, 1);
        $this->type = self::$default_entity_type;
        $this->position = new Vector2D();
        $this->setId($id);
    }

    /**
     *  this must be called within each constructor to make sure the ID is set
     *  correctly. It verifies that the value passed to the method is greater
     *  or equal to the next valid ID, before setting the ID and incrementing
     *  the next valid ID
     */
    private function setId($val)
    {

        $this->id = $val;

        self::$nextValidId = $this->id + 1;
    }

    public function update()
    {
    }

    abstract public function render();

    /**
     * @param Telegram $message
     *
     * @return bool
     */
    public function handleMessage(Telegram $message)
    {
        return false;
    }

    /**
     * @return int
     *
     * entities should be able to read/write their data to a stream
     * virtual void Write(std::ostream&  os)const{}
     * virtual void Read (std::ifstream& is){}
     * use this to grab the next valid ID
     */
    public static function getNextValidId()
    {
        return self::$nextValidId;
    }

    /**
     * @return Vector2D
     */
    public function getPosition()
    {
        return Vector2D::createByVector2D($this->position);
    }

    /**
     * @param Vector2D $position
     */
    public function setPosition(Vector2D $position)
    {
        $this->position = Vector2D::createByVector2D($position);
    }

    /**
     * @return float
     */
    public function getBoundingRadius()
    {
        return $this->boundingRadius;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
