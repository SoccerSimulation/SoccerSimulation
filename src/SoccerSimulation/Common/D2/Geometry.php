<?php

namespace SoccerSimulation\Common\D2;

class Geometry
{
    const SPAN_TYPE_PLANE_BACKSIDE = 'span_type_plane_backside';
    const SPAN_TYPE_PLANE_FRONT = 'span_type_plane_front';
    const SPAN_TYPE_PLANE_ON_PLANE = 'span_type_on_plane';

    /**
     * given a plane and a ray this function determins how far along the ray 
     * an interestion occurs. Returns negative if the ray is parallel
     */
    public static function distanceToRayPlaneIntersection(Vector2D $RayOrigin,
            Vector2D $RayHeading,
            Vector2D $PlanePoint, //any point on the plane
            Vector2D $PlaneNormal) {

        $d = -$PlaneNormal->dot($PlanePoint);
        $numer = $PlaneNormal->dot($RayOrigin) + $d;
        $denom = $PlaneNormal->dot($RayHeading);

        // normal is parallel to vector
        if (($denom < 0.000001) && ($denom > -0.000001)) {
            return (-1.0);
        }

        return -($numer / $denom);
    }

//------------------------- WhereIsPoint --------------------------------------

    public static function whereIsPoint(Vector2D $point,
            Vector2D $PointOnPlane, //any point on the plane
            Vector2D $PlaneNormal) {
        $dir = Vector2D::staticSub($PointOnPlane, $point);

        $d = $dir->dot($PlaneNormal);

        if ($d < -0.000001) {
            return self::SPAN_TYPE_PLANE_FRONT;
        } else if ($d > 0.000001) {
            return self::SPAN_TYPE_PLANE_BACKSIDE;
        }

        return self::SPAN_TYPE_PLANE_ON_PLANE;
    }

    public static $pi = 3.14159;// Math.PI

    /**
     *  Given a point P and a circle of radius R centered at C this function
     *  determines the two points on the circle that intersect with the 
     *  tangents from P to the circle. Returns false if P is within the circle.
     *
     *  Thanks to Dave Eberly for this one.
     */
    public static function getTangentPoints(Vector2D $C, $R, Vector2D $P, Vector2D $T1, Vector2D $T2) {
        $PmC = Vector2D::staticSub($P, $C);
        $SqrLen = $PmC->LengthSq();
        $RSqr = $R * $R;
        if ($SqrLen <= $RSqr) {
            // P is inside or on the circle
            return false;
        }

        $InvSqrLen = 1 / $SqrLen;
        $Root = sqrt(abs($SqrLen - $RSqr));

        $T1->x = $C->x + $R * ($R * $PmC->x - $PmC->y * $Root) * $InvSqrLen;
        $T1->y = $C->y + $R * ($R * $PmC->y + $PmC->x * $Root) * $InvSqrLen;
        $T2->x = $C->x + $R * ($R * $PmC->x + $PmC->y * $Root) * $InvSqrLen;
        $T2->y = $C->y + $R * ($R * $PmC->y - $PmC->x * $Root) * $InvSqrLen;

        return true;
    }

    /**
     *	Given 2 lines in 2D space AB, CD this returns true if an 
     *	intersection occurs.
     */
    public static function lineIntersection2D(Vector2D $A,
            Vector2D $B,
            Vector2D $C,
            Vector2D $D) {
        $rTop = ($A->y - $C->y) * ($D->x - $C->x) - ($A->x - $C->x) * ($D->y - $C->y);
        $sTop = ($A->y - $C->y) * ($B->x - $A->x) - ($A->x - $C->x) * ($B->y - $A->y);

        $Bot = ($B->x - $A->x) * ($D->y - $C->y) - ($B->y - $A->y) * ($D->x - $C->x);

        if ($Bot == 0)//parallel
        {
            return false;
        }

        $invBot = 1.0 / $Bot;
        $r = $rTop * $invBot;
        $s = $sTop * $invBot;

        if (($r > 0) && ($r < 1) && ($s > 0) && ($s < 1)) {
            //lines intersect
            return true;
        }

        //lines do not intersect
        return false;
    }
}
