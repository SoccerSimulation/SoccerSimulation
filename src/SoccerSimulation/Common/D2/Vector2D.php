<?php

namespace SoccerSimulation\Common\D2;

class Vector2D
{
    /**
     * @var float
     */
    public $x;

    /**
     * @var float
     */
    public $y;

    /**
     * @param float $x
     * @param float $y
     */
    public function __construct($x = 0.0, $y = 0.0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->x . '|' . $this->y;
    }

    /**
     * @param Vector2D $v
     *
     * @return Vector2D
     */
    static public function createByVector2D(Vector2D $v)
    {
        return clone $v;
    }

    /**
     * @param Vector2D $v
     *
     * @return Vector2D
     */
    public function set(Vector2D $v)
    {
        $this->x = $v->x;
        $this->y = $v->y;

        return $this;
    }

    /**
     * sets x and y to zero
     */
    public function Zero()
    {
        $this->x = 0.0;
        $this->y = 0.0;
    }

    /**
     * @return bool
     */
    public function isZero()
    {
        return $this->x + $this->y == 0;
    }

    /**
     * @return float
     *
     * returns the length of a 2D vector
     */
    public function getLength()
    {
        return sqrt($this->x * $this->x + $this->y * $this->y);
    }

    /**
     * @return float
     *
     * returns the squared length of the vector (thereby avoiding the sqrt)
     */
    public function LengthSq()
    {
        return ($this->x * $this->x + $this->y * $this->y);
    }

    /**
     * normalizes a 2D Vector
     */
    public function normalize()
    {
        $vector_length = $this->getLength();

        if ($vector_length > 0) {
            $this->x /= $vector_length;
            $this->y /= $vector_length;
        }
    }

    /**
     * @param Vector2D $v2
     *
     * @return float
     *
     * calculates the dot product
     */
    public function dot(Vector2D $v2)
    {
        return $this->x * $v2->x + $this->y * $v2->y;
    }

    public static $clockwise = 1;

    public static $anticlockwise = -1;

    /**
     * @param Vector2D $v2
     *
     * @return int
     *
     * returns positive if v2 is clockwise of this vector,
     * negative if anticlockwise (assuming the Y axis is pointing down,
     * X axis to right like a Window app)
     */
    public function sign(Vector2D $v2)
    {
        if ($this->y * $v2->x > $this->x * $v2->y) {
            return self::$anticlockwise;
        } else {
            return self::$clockwise;
        }
    }

    /**
     * @return Vector2D
     *
     * returns the vector that is perpendicular to this one.
     */
    public function getPerpendicular()
    {
        return new Vector2D(-$this->y, $this->x);
    }

    /**
     * @param float $max
     *
     * adjusts x and y so that the length of the vector does not exceed max
     * truncates a vector so that its length does not exceed max
     */
    public function truncate($max)
    {
        if ($this->getLength() > $max) {
            $this->normalize();
            $this->mul($max);
        }
    }

    /**
     * @param Vector2D $v2
     *
     * @return float
     *
     * calculates the euclidean distance between two vectors
     */
    public function Distance(Vector2D $v2)
    {
        $ySeparation = $v2->y - $this->y;
        $xSeparation = $v2->x - $this->x;

        return sqrt($ySeparation * $ySeparation + $xSeparation * $xSeparation);
    }

    /**
     * @param Vector2D $v2
     *
     * @return float
     *
     * squared version of distance.
     *
     * calculates the euclidean distance squared between two vectors
     */
    public function DistanceSq(Vector2D $v2)
    {
        $ySeparation = $v2->y - $this->y;
        $xSeparation = $v2->x - $this->x;

        return $ySeparation * $ySeparation + $xSeparation * $xSeparation;
    }

    /**
     * @param Vector2D $norm
     *
     * given a normalized vector this method reflects the vector it
     * is operating upon. (like the path of a ball bouncing off a wall)
     */
    public function Reflect(Vector2D $norm)
    {
        $this->add($norm->GetReverse()->mul(2.0 * $this->dot($norm)));
    }

    /**
     * @return Vector2D
     *
     * the vector that is the reverse of this vector
     */
    public function GetReverse()
    {
        return new Vector2D(-$this->x, -$this->y);
    }

    /**
     * @param Vector2D $rhs
     *
     * @return Vector2D
     *
     * we need some overloaded operators
     */
    public function add(Vector2D $rhs)
    {
        $this->x += $rhs->x;
        $this->y += $rhs->y;

        return $this;
    }

    /**
     * @param Vector2D $rhs
     *
     * @return Vector2D
     */
    public function sub(Vector2D $rhs)
    {
        $this->x -= $rhs->x;
        $this->y -= $rhs->y;

        return $this;
    }

    /**
     * @param float $rhs
     *
     * @return Vector2D
     */
    public function mul($rhs)
    {
        $this->x *= $rhs;
        $this->y *= $rhs;

        return $this;
    }

    /**
     * @param float $rhs
     *
     * @return Vector2D
     */
    public function div($rhs)
    {
        $this->x /= $rhs;
        $this->y /= $rhs;

        return $this;
    }

    /**
     * @param Vector2D $rhs
     *
     * @return bool
     */
    public function isEqual(Vector2D $rhs)
    {
        return $this->x == $rhs->x && $this->y == $rhs->y;
    }

    /**
     * @param Vector2D $rhs
     *
     * @return bool
     */
    public function notEqual(Vector2D $rhs)
    {
        return ($this->x != $rhs->x) || ($this->y != $rhs->y);
    }

    /**
     * @param Vector2D $lhs
     * @param float $rhs
     *
     * @return Vector2D
     */
    static public function staticMul(Vector2D $lhs, $rhs)
    {
        $result = Vector2D::createByVector2D($lhs);
        $result->mul($rhs);

        return $result;
    }

    /**
     * @param Vector2D $lhs
     * @param Vector2D $rhs
     *
     * @return Vector2D
     */
    static public function staticSub(Vector2D $lhs, Vector2D $rhs)
    {
        $result = Vector2D::createByVector2D($lhs);
        $result->x -= $rhs->x;
        $result->y -= $rhs->y;

        return $result;
    }

    /**
     * @param Vector2D $lhs
     * @param Vector2D $rhs
     *
     * @return Vector2D
     */
    static public function staticAdd(Vector2D $lhs, Vector2D $rhs)
    {
        $result = Vector2D::createByVector2D($lhs);
        $result->x += $rhs->x;
        $result->y += $rhs->y;

        return $result;
    }

    /**
     * @param Vector2D $lhs
     * @param float $val
     *
     * @return Vector2D
     */
    static public function staticDiv(Vector2D $lhs, $val)
    {
        $result = Vector2D::createByVector2D($lhs);
        $result->x /= $val;
        $result->y /= $val;

        return $result;
    }

    /**
     * @param Vector2D $v
     *
     * @return Vector2D
     */
    static public function vectorNormalize(Vector2D $v)
    {
        $vec = Vector2D::createByVector2D($v);

        $vector_length = $vec->getLength();

        if ($vector_length > 1) {
            $vec->x /= $vector_length;
            $vec->y /= $vector_length;
        }

        return $vec;
    }

    /**
     * @param Vector2D $v1
     * @param Vector2D $v2
     *
     * @return float
     */
    static public function Vec2DDistance(Vector2D $v1, Vector2D $v2)
    {
        $ySeparation = $v2->y - $v1->y;
        $xSeparation = $v2->x - $v1->x;

        return sqrt($ySeparation * $ySeparation + $xSeparation * $xSeparation);
    }

    public static function vectorDistanceSquared(Vector2D $v1, Vector2D $v2)
    {
        $ySeparation = $v2->y - $v1->y;
        $xSeparation = $v2->x - $v1->x;

        return $ySeparation * $ySeparation + $xSeparation * $xSeparation;
    }

    public static function Vec2DLength(Vector2D $v)
    {
        return sqrt($v->x * $v->x + $v->y * $v->y);
    }

    public static function Vec2DLengthSq(Vector2D $v)
    {
        return ($v->x * $v->x + $v->y * $v->y);
    }

///////////////////////////////////////////////////////////////////////////////
//treats a window as a toroid
    public static function WrapAround(Vector2D $pos, $MaxX, $MaxY)
    {
        if ($pos->x > $MaxX) {
            $pos->x = 0.0;
        }

        if ($pos->x < 0) {
            $pos->x = $MaxX;
        }

        if ($pos->y < 0) {
            $pos->y = $MaxY;
        }

        if ($pos->y > $MaxY) {
            $pos->y = 0.0;
        }
    }
}
