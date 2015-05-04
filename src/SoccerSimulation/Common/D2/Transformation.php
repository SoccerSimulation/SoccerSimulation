<?php

namespace SoccerSimulation\Common\D2;

/**
 *  Desc:   Functions for converting 2D vectors between World and Local
 *          space.
 *
 */
class Transformation
{
    //--------------------------- WorldTransform -----------------------------
    //
    //  given a std::vector of 2D vectors, a position, orientation and scale,
    //  this function transforms the 2D vectors into the object's world space
    //------------------------------------------------------------------------
    public static function WorldTransform(
        array $points,
        Vector2D $pos,
        Vector2D $forward,
        Vector2D $side,
        Vector2D $scale = null
    ) {
        //copy the original vertices into the buffer about to be transformed
        $TranVector2Ds = array();
        foreach ($points as $point) {
            $TranVector2Ds[] = clone $point;
        }

        //create a transformation matrix
        $matTransform = new C2DMatrix();

        //scale
        if ($scale !== null && (($scale->x != 1.0) || ($scale->y != 1.0))) {
            $matTransform->Scale($scale->x, $scale->y);
        }

        //rotate
        $matTransform->RotateVectors($forward, $side);

        //and translate
        $matTransform->Translate($pos->x, $pos->y);

        //now transform the object's vertices
        $matTransform->TransformVector2DsArray($TranVector2Ds);

        return $TranVector2Ds;
    }

//--------------------- PointToWorldSpace --------------------------------
//
//  Transforms a point from the agent's local space into world space
//------------------------------------------------------------------------
    public static function PointToWorldSpace(
        Vector2D $point,
        Vector2D $AgentHeading,
        Vector2D $AgentSide,
        Vector2D $AgentPosition
    ) {
        //make a copy of the point
        $TransPoint = Vector2D::createByVector2D($point);

        //create a transformation matrix
        $matTransform = new C2DMatrix();

        //rotate
        $matTransform->rotate($AgentHeading, $AgentSide);

        //and translate
        $matTransform->Translate($AgentPosition->x, $AgentPosition->y);

        //now transform the vertices
        $matTransform->transformVector2Ds($TransPoint);

        return $TransPoint;
    }

//--------------------- VectorToWorldSpace --------------------------------
//
//  Transforms a vector from the agent's local space into world space
//------------------------------------------------------------------------
    public static function VectorToWorldSpace(
        Vector2D $vec,
        Vector2D $AgentHeading,
        Vector2D $AgentSide
    ) {
        //make a copy of the point
        $TransVec = Vector2D::createByVector2D($vec);

        //create a transformation matrix
        $matTransform = new C2DMatrix();

        //rotate
        $matTransform->rotate($AgentHeading, $AgentSide);

        //now transform the vertices
        $matTransform->transformVector2Ds($TransVec);

        return $TransVec;
    }

//--------------------- PointToLocalSpace --------------------------------
//
//------------------------------------------------------------------------
    public static function PointToLocalSpace(
        Vector2D $point,
        Vector2D $AgentHeading,
        Vector2D $AgentSide,
        Vector2D $AgentPosition
    ) {

        //make a copy of the point
        $TransPoint = Vector2D::createByVector2D($point);

        //create a transformation matrix
        $matTransform = new C2DMatrix();

        $Tx = -$AgentPosition->dot($AgentHeading);
        $Ty = -$AgentPosition->dot($AgentSide);

        //create the transformation matrix
        $matTransform->_11($AgentHeading->x);
        $matTransform->_12($AgentSide->x);
        $matTransform->_21($AgentHeading->y);
        $matTransform->_22($AgentSide->y);
        $matTransform->_31($Tx);
        $matTransform->_32($Ty);

        //now transform the vertices
        $matTransform->transformVector2Ds($TransPoint);

        return $TransPoint;
    }

//--------------------- VectorToLocalSpace --------------------------------
//
//------------------------------------------------------------------------
    public static function VectorToLocalSpace(
        Vector2D $vec,
        Vector2D $AgentHeading,
        Vector2D $AgentSide
    ) {

        //make a copy of the point
        $TransPoint = Vector2D::createByVector2D($vec);

        //create a transformation matrix
        $matTransform = new C2DMatrix();

        //create the transformation matrix
        $matTransform->_11($AgentHeading->x);
        $matTransform->_12($AgentSide->x);
        $matTransform->_21($AgentHeading->y);
        $matTransform->_22($AgentSide->y);

        //now transform the vertices
        $matTransform->transformVector2Ds($TransPoint);

        return $TransPoint;
    }

//-------------------------- Vec2DRotateAroundOrigin --------------------------
//
//  rotates a vector ang rads around the origin
//-----------------------------------------------------------------------------
    public static function vectorRotateAroundOrigin(Vector2D $v, $ang)
    {
        //create a transformation matrix
        $mat = new C2DMatrix();

        //rotate
        $mat->rotate($ang);

        //now transform the object's vertices
        $mat->transformVector2Ds($v);
    }

//------------------------ CreateWhiskers ------------------------------------
//
//  given an origin, a facing direction, a 'field of view' describing the 
//  limit of the outer whiskers, a whisker length and the number of whiskers
//  this method returns a vector containing the end positions of a series
//  of whiskers radiating away from the origin and with equal distance between
//  them. (like the spokes of a wheel clipped to a specific segment size)
//----------------------------------------------------------------------------
    public static function CreateWhiskers(
        $NumWhiskers,
        $WhiskerLength,
        $fov,
        Vector2D $facing,
        Vector2D $origin
    ) {
        //this is the magnitude of the angle separating each whisker
        $SectorSize = $fov / (double)($NumWhiskers - 1);

        $whiskers = array();
        $angle = -$fov * 0.5;

        for ($w = 0; $w < $NumWhiskers; ++$w) {
            //create the whisker extending outwards at this angle
            $temp = $facing;
            Transformation::vectorRotateAroundOrigin($temp, $angle);
            $whiskers[] = Vector2D::staticAdd($origin, Vector2D::staticMul($temp, $WhiskerLength));

            $angle += $SectorSize;
        }

        return $whiskers;
    }
}
