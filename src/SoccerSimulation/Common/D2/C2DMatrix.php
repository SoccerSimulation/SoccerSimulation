<?php

namespace SoccerSimulation\Common\D2;

/**
 * Desc:   2D Matrix class
 */
class C2DMatrix
{
    /**
     * @var Matrix
     */
    private $m_Matrix;

    public function __construct()
    {
        $this->m_Matrix = new Matrix();
        //initialize the matrix to an identity matrix
        $this->Identity();
    }

    //accessors to the matrix elements
    public function _11($val)
    {
        $this->m_Matrix->_11 = $val;
    }

    public function _12($val)
    {
        $this->m_Matrix->_12 = $val;
    }

    public function _13($val)
    {
        $this->m_Matrix->_13 = $val;
    }

    public function _21($val)
    {
        $this->m_Matrix->_21 = $val;
    }

    public function _22($val)
    {
        $this->m_Matrix->_22 = $val;
    }

    public function _23($val)
    {
        $this->m_Matrix->_23 = $val;
    }

    public function _31($val)
    {
        $this->m_Matrix->_31 = $val;
    }

    public function _32($val)
    {
        $this->m_Matrix->_32 = $val;
    }

    public function _33($val)
    {
        $this->m_Matrix->_33 = $val;
    }

//multiply two matrices together
    private function MatrixMultiply(Matrix $mIn)
    {
        $mat_temp = new Matrix();

        //first row
        $mat_temp->_11 = ($this->m_Matrix->_11 * $mIn->_11) + ($this->m_Matrix->_12 * $mIn->_21) + ($this->m_Matrix->_13 * $mIn->_31);
        $mat_temp->_12 = ($this->m_Matrix->_11 * $mIn->_12) + ($this->m_Matrix->_12 * $mIn->_22) + ($this->m_Matrix->_13 * $mIn->_32);
        $mat_temp->_13 = ($this->m_Matrix->_11 * $mIn->_13) + ($this->m_Matrix->_12 * $mIn->_23) + ($this->m_Matrix->_13 * $mIn->_33);

        //second
        $mat_temp->_21 = ($this->m_Matrix->_21 * $mIn->_11) + ($this->m_Matrix->_22 * $mIn->_21) + ($this->m_Matrix->_23 * $mIn->_31);
        $mat_temp->_22 = ($this->m_Matrix->_21 * $mIn->_12) + ($this->m_Matrix->_22 * $mIn->_22) + ($this->m_Matrix->_23 * $mIn->_32);
        $mat_temp->_23 = ($this->m_Matrix->_21 * $mIn->_13) + ($this->m_Matrix->_22 * $mIn->_23) + ($this->m_Matrix->_23 * $mIn->_33);

        //third
        $mat_temp->_31 = ($this->m_Matrix->_31 * $mIn->_11) + ($this->m_Matrix->_32 * $mIn->_21) + ($this->m_Matrix->_33 * $mIn->_31);
        $mat_temp->_32 = ($this->m_Matrix->_31 * $mIn->_12) + ($this->m_Matrix->_32 * $mIn->_22) + ($this->m_Matrix->_33 * $mIn->_32);
        $mat_temp->_33 = ($this->m_Matrix->_31 * $mIn->_13) + ($this->m_Matrix->_32 * $mIn->_23) + ($this->m_Matrix->_33 * $mIn->_33);

        $this->m_Matrix = $mat_temp;
    }

//applies a 2D transformation matrix to a single Vector2D
    public function transformVector2Ds(Vector2D $vPoint)
    {

        $tempX = ($this->m_Matrix->_11 * $vPoint->x) + ($this->m_Matrix->_21 * $vPoint->y) + ($this->m_Matrix->_31);
        $tempY = ($this->m_Matrix->_12 * $vPoint->x) + ($this->m_Matrix->_22 * $vPoint->y) + ($this->m_Matrix->_32);
        $vPoint->x = $tempX;
        $vPoint->y = $tempY;
    }

//applies a 2D transformation matrix to a std::vector of Vector2Ds
    public function TransformVector2DsArray(array $vPoint)
    {
        foreach ($vPoint as $i) {
            $this->transformVector2Ds($i);
        }
    }

//create an identity matrix
    public function Identity()
    {
        $this->m_Matrix->_11 = 1;
        $this->m_Matrix->_12 = 0;
        $this->m_Matrix->_13 = 0;
        $this->m_Matrix->_21 = 0;
        $this->m_Matrix->_22 = 1;
        $this->m_Matrix->_23 = 0;
        $this->m_Matrix->_31 = 0;
        $this->m_Matrix->_32 = 0;
        $this->m_Matrix->_33 = 1;
    }

//create a transformation matrix
    public function Translate($x, $y)
    {
        $mat = new Matrix();

        $mat->_11 = 1;
        $mat->_12 = 0;
        $mat->_13 = 0;
        $mat->_21 = 0;
        $mat->_22 = 1;
        $mat->_23 = 0;
        $mat->_31 = $x;
        $mat->_32 = $y;
        $mat->_33 = 1;

        //and multiply
        $this->MatrixMultiply($mat);
    }

//create a scale matrix
    public function Scale($xScale, $yScale)
    {
        $mat = new Matrix();

        $mat->_11 = $xScale;
        $mat->_12 = 0;
        $mat->_13 = 0;
        $mat->_21 = 0;
        $mat->_22 = $yScale;
        $mat->_23 = 0;
        $mat->_31 = 0;
        $mat->_32 = 0;
        $mat->_33 = 1;

        //and multiply
        $this->MatrixMultiply($mat);
    }

//create a rotation matrix
    public function rotate($rot)
    {
        $mat = new Matrix();

        $Sin = sin($rot);
        $Cos = cos($rot);

        $mat->_11 = $Cos;
        $mat->_12 = $Sin;
        $mat->_13 = 0;
        $mat->_21 = -$Sin;
        $mat->_22 = $Cos;
        $mat->_23 = 0;
        $mat->_31 = 0;
        $mat->_32 = 0;
        $mat->_33 = 1;

        //and multiply
        $this->MatrixMultiply($mat);
    }

//create a rotation matrix from a 2D vector
// @todo duplicate method - use just one or rename
    public function RotateVectors(Vector2D $fwd, Vector2D $side)
    {
        $mat = new Matrix();

        $mat->_11 = $fwd->x;
        $mat->_12 = $fwd->y;
        $mat->_13 = 0;
        $mat->_21 = $side->x;
        $mat->_22 = $side->y;
        $mat->_23 = 0;
        $mat->_31 = 0;
        $mat->_32 = 0;
        $mat->_33 = 1;

        //and multiply
        $this->MatrixMultiply($mat);
    }
}
