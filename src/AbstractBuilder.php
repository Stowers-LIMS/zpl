<?php

namespace Zpl;

use ReflectionClass;

abstract class AbstractBuilder
{
    const UNIT_DOTS = 'dots';
    const UNIT_MM   = 'mm';
    const JUSTIFY_LEFT = 0;
    const JUSTIFY_RIGHT = 1;
    const JUSTIFY_AUTO = 3;

    /**
     *
     * @var string
     */
    protected $unit = 'dots';

    /**
     * Current position of X coordinate in user unit
     *
     * @var float
     */
    protected $x = 0;

    /**
     * Current position Y coordinate in user unit
     *
     * @var float
     */
    protected $y = 0;

    protected $margin = 0;

    protected $height = 0;

    protected $width = 0;


    /**
     *
     * @param string  $unit
     *
     * @throws BuilderException
     */
    public function __construct(string $unit = 'dots')
    {
        if ($this->verifyUnit($unit) === true) {
            $this->unit = $unit;
        } else {
            throw new BuilderException('Unit ' . $unit . ' not recognized. Please use one of the constants of the class.');
        }
    }

    /**
     *
     * @param string $font The font number on the printer
     * @param float  $size The font's size in pt
     */
    abstract public function setFont(string $font, float $size) : void;

    /**
     * Insert a text into the document.
     *
     * @param float  $x    X position in user units
     * @param float  $y    Y position in user units
     * @param string $text Text to be inserted
     * @param string $orientation The text orientation. Available options:
     *                            N = normal
     *                            R = rotated 90 degrees
     *                            I = inverted 180 degrees
     *                            B = bottom-up 270 degrees, read from bottom up
     * @param int    $justify See self:JUSTIFY_* for options
     * @param float  $width Max width of the text in user units
     * @param float  $fontSize Sets the height of the field based on the font size in points
     * @param bool   $invert Invert the color based on the background behind the text
     */
    abstract public function drawText(
        float $x,
        float $y,
        string $text,
        ?string $orientation = 'N',
        ?int $justify = self::JUSTIFY_LEFT,
        ?float $width = null,
        ?float $fontSize = 12,
        ?bool $invert = false
    ) : void;

    /**
     *
     * @param float $x1        X1 position in user units
     * @param float $y1        Y1 position in user units
     * @param float $x2        X2 position in user units
     * @param float $y2        Y2 position in user units
     * @param float $thickness Thickness in user units
     */
    abstract public function drawLine(float $x1, float $y1, float $x2, float $y2, float $thickness = 0) : void;

    /**
     *
     * @param float  $x         X position in user units
     * @param float  $y         Y position in user units
     * @param float  $width     width of the rectangle in user units
     * @param float  $height    height of the rectangle in user units
     * @param float  $thickness Thickness in user units or 0 for the default thickness
     * @param string $color     'B' for black or 'W' for white
     * @param float  $round     0 (no rounding) to 8 (heaviest rounding)
     */
    abstract public function drawRect(
        float $x,
        float $y,
        float $width,
        float $height,
        float $thickness = 0,
        string $color = 'B',
        float $round = 0
    ) : void;

    /**
     *
     * @param float  $width  width of the cell in user units
     * @param float  $height height of the cell in user units
     * @param string $text   Text to be drawn
     * @param bool   $border Whether the cell have a border or not
     * @param bool   $ln     Whether to advance the X, Y coordinates to the next line
     * @param string $align  Alignment of the text inside the cell (L = left, C = center, R = right, J = justified)
     */
    abstract public function drawCell(
        float $width,
        float $height,
        string $text,
        bool $border = false,
        bool $ln = false,
        string $align = ''
    ) : void;

    /**
     *
     * @param float  $x         X position in user units
     * @param float  $y         Y position in user units
     * @param float  $diameter  diameter of the circle in user units
     * @param float  $thickness Thickness in user units or 0 for the default thickness
     * @param string $color     'B' for black or 'W' for white
     */
    abstract public function drawCircle(
        float $x,
        float $y,
        float $diameter,
        float $thickness = 0,
        string $color = 'B'
    ) : void;

    /**
     * @param float  $x         X position in user units
     * @param float  $y         Y position in user units
     * @param float  $height    height of the barcode in user units
     * @param string $data      Data to draw the barcode
     * @param int    $size      Scale of the barcode (1-9)
     * @param bool   $printData Whether to print the data or not
     */
    abstract public function drawCode39(float $x, float $y, float $height, string $data, int $size = 2, bool $printData = false) : void;

    /**
     * @param float  $x         X position in user units
     * @param float  $y         Y position in user units
     * @param float  $height    height of the barcode in user units
     * @param string $data      Data to draw the barcode
     * @param int    $size      Scale of the barcode (1-9)
     * @param bool   $printData Whether to print the data or not
     * @param string $orientation Orientation of the bar code (N, R, I, B)
     */
    abstract public function drawCode128(float $x, float $y, float $height, string $data, int $size = 2, bool $printData = false, string $orientation = 'N') : void;

    /**
     *
     * @param float  $x      X position in user units
     * @param float  $y      Y position in user units
     * @param string $data   Data to draw the barcode
     * @param int    $size   The size of the QR Code in user units (will round to closest magnification factor)
     */
    abstract public function drawQrCode(float $x, float $y, string $data, int $size = 10) : void;

    abstract public function newPage() : void;

    /**
     * Verify if the $unit is valid
     *
     * @param string $unit
     *
     * @return bool true if the unit is valid, false otherwise.
     */
    protected function verifyUnit(string $unit) : bool
    {
        $r = new ReflectionClass('\Zpl\AbstractBuilder');
        $constants = $r->getConstants();
        $key = array_search($unit, $constants);
        if (preg_match('/UNIT/', $key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param float $x
     * @param float $y
     */
    public function setXY(float $x, float $y) : void
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     *
     * @param float $x
     */
    public function setX(float $x) : void
    {
        $this->x = $x;
    }

    /**
     *
     * @return float
     */
    public function getX() : float
    {
        return $this->x;
    }

    /**
     *
     * @param float $y
     */
    public function setY($y) : void
    {
        $this->y = $y;
    }

    /**
     *
     * @return float
     */
    public function getY() : float
    {
        return $this->y;
    }

    /**
     *
     * @param float $margin
     */
    public function setMargin(float $margin) : void
    {
        $this->margin = $margin;
    }

    /**
     *
     * @return float
     */
    public function getMargin() : float
    {
        return $this->margin;
    }

    public function setHeight(float $height) : void
    {
        $this->height = $height;
    }

    public function setWidth(float $width) : void
    {
        $this->width = $width;
    }

    public function getHeight() : float
    {
        return $this->height;
    }

    public function getWidth() : float
    {
        return $this->width;
    }

    public function setPageSize(float $height, float $width) : void
    {
        $this->setHeight($height);
        $this->setWidth($width);
    }
}
