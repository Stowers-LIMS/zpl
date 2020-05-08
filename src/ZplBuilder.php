<?php

namespace Zpl;

class ZplBuilder extends AbstractBuilder
{
    const CONTROL_CHAR_HEX_MAPPINGS = [
        '^' => '_5e',
        '~' => '_7e',
        '_' => '_5f',
    ];

    /**
     * ZPL commands
     *
     * @var array
     */
    protected $commands = array();

    /**
     * Commands to be inserted before beginning of ZPL document (^XA)
     *
     * @var array
     */
    protected $preCommands = array();

    /**
     * Commands to be inserted after end of ZPL document (^XZ)
     *
     * @var array
     */
    protected $postCommands = array();

    /**
     * Resolution of the printer in DPI
     *
     * @var int
     */
    protected $resolution = 203;

    /**
     * @var Fonts\AbstractMapper
     */
    protected $fontMapper;

    const PAGE_SEPARATOR = '%PAGE_SEPARATOR%';

    /**
     *
     * @param string  $unit
     * @param int     $resolution Resolution of the document
     *
     * @throws BuilderException
     */
    public function __construct(string $unit = 'dots', int $resolution = 203)
    {
        parent::__construct($unit);
        $this->resolution = $resolution;
    }

    public function setMediaWidth(float $width)
    {
        $this->commands[] = "^PW" . floor($width * $this->resolution);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::setFont()
     */
    public function setFont(string $font, float $size) : void
    {
        $fontMapper = $this->fontMapper;
        $mapper = $fontMapper::$mapper;
        if (isset($mapper[$font])) {
            $font = $mapper[$font];
        }
        $size = $this->fontSizeToDots($size);
        $this->commands[] = '^CF' . $font . ',' . $size;
    }

    /**
     * Value from 0 to 36.
     *
     * @param int $code
     */
    public function setEncoding(int $code) : void
    {
        $this->commands[] = '^CI' . $code;
    }

    public function setOrientation(string $orientation = 'N', int $justification = 0)
    {
        $this->commands[] = '^FW' . $orientation . ',' . $justification;
    }

    public function setHome(float $x, float $y)
    {
        $this->commands[] = sprintf('^LH%f,%f', $this->toDots($x), $this->toDots($y));
    }

    public function drawDot(float $x, float $y)
    {
        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y) . '^GB2^FS';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawText()
     */
    public function drawText(
        float $x,
        float $y,
        string $text,
        ?string $orientation = 'N',
        ?int $justify = self::JUSTIFY_LEFT,
        ?float $width = null,
        ?float $fontSize = 12,
        ?bool $invert = false
    ) : void
    {
        $this->commands[] = '^FW' . $orientation;
        $this->commands[] = '^FT' . $this->toDots($x) . ',' . $this->toDots($y) . ',' . $justify;
        if ($width) {
            $this->commands[] = '^TB,' . $this->toDots($width) . ',' . $this->fontSizeToDots($fontSize);
        }
        if ($invert) {
            $this->commands[] = '^FR';
        }
        $this->commands[] = '^FH^FD' . strtr($text, self::CONTROL_CHAR_HEX_MAPPINGS) . '^FS';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawLine()
     */
    public function drawLine(float $x1, float $y1, float $x2, float $y2, float $thickness = 0) : void
    {
        $this->drawRect($this->x, $this->y, $x2-$x1, $y2-$y1, $thickness);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawRect()
     */
    public function drawRect(
        float $x,
        float $y,
        float $width,
        float $height,
        float $thickness = 0,
        string $color = 'B',
        float $round = 0
    ) : void {
        $thickness = $thickness === 0 ? 3 : $this->toDots($thickness);
        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y)
            . '^GB' . $this->toDots($width) . ',' . $this->toDots($height) . ',' . $thickness . ',' . $color . ',' . $round
            . '^FS';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawCircle()
     */
    public function drawCircle(
        float $x,
        float $y,
        float $diameter,
        float $thickness = 0,
        string $color = 'B'
    ) : void {
        $thickness = $thickness === 0 ? 3 : $this->toDots($thickness);
        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y)
            . '^GC' . $this->toDots($diameter) . ',' . $thickness . ',' . $color
            . '^FS';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawCell()
     */
    public function drawCell(
        float $width,
        float $height,
        string $text,
        bool $border = false,
        bool $ln = false,
        string $align = ''
    ) : void {
        $x = $this->getX();
        $y = $this->getY();
        if ($border === true) {
            $this->drawRect($x, $y, $width, $height);
        }
        if ($text !== '') {
            $offsetX = 10;
            $offsetY = $this->toDots($height) / 4;
            $this->commands[] = '^FO' . ($this->toDots($x) + $offsetX) . ',' . ($this->toDots($y) + $offsetY);
            if ($align !== '') {
                $this->commands[] = '^FB' . ($this->toDots($width) - $offsetX) . ',' . ($this->toDots($height) - $offsetY) . ',0,' . $align;
            }
            $this->commands[] = '^FD' . $text . '^FS';
        }
        if ($ln === true) {
            $this->setY($y + $height) ;
            $this->setX($this->getMargin());
        } else {
            $this->setX($x + $width);
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawCode39()
     */
    public function drawCode39(float $x, float $y, float $height, string $data, int $size = 2, bool $printData = false) : void
    {
        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y);
        $this->commands[] = '^BY' . $size;
        $this->commands[] = '^B3N,N,' . $this->toDots($height) . ',' . ($printData === true ? 'Y' : 'N');
        $this->commands[] = '^FD' . $data . '^FS';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawCode128()
     */
    public function drawCode128(float $x, float $y, float $height, string $data, int $size = 2, bool $printData = false, string $orientation = 'N') : void
    {
        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y);
        $this->commands[] = '^BY' . $size;
        $this->commands[] = '^BC' . $orientation . ',' . $this->toDots($height) . ',' . ($printData === true ? 'Y' : 'N');
        $this->commands[] = '^FD' . $data . '^FS';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::drawQrCode()
     */
    public function drawQrCode(float $x, float $y, string $data, int $size = 14) : void
    {
        $scale =  round($this->toDots($size)/28);

        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y);
        $this->commands[] = '^BQN,2,' . $scale;
        $this->commands[] = '^FDMA,' . $data . '^FS';
    }

    /**
     *
     * @param string $command
     */
    public function addPreCommand(string $command) : void
    {
        $this->preCommands[] = $command;
    }

    /**
     * @param float $x
     * @param float $y
     * @param GdDecoder $decoder
     * @param int|null $width Width in user units
     * @param int|null $height Height in user units, leave -1 to maintain aspect ratio
     */
    public function drawImage(float $x, float $y, GdDecoder $decoder, ?int $width = null, ?int $height = -1)
    {
        if ($width) {
            $decoder->scaleImage($this->toDots($width), $this->toDots($height));
        }

        $image = new Image($decoder);
        $bytesPerRow = $image->width();
        $byteCount = $fieldCount = $bytesPerRow * $image->height();
        $this->commands[] = '^FO' . $this->toDots($x) . ',' . $this->toDots($y);
        $this->commands[] = '^GFA,' . $byteCount . ',' . $fieldCount . ',' . $bytesPerRow . ',' . $image->toAscii();
    }

    /**
     *
     * @param array $commands
     */
    public function setPreCommands(array $commands) : void
    {
        $this->preCommands = $commands;
    }

    /**
     *
     * @param string $command
     */
    public function addPostCommand(string $command) : void
    {
        $this->postCommands[] = $command;
    }

    /**
     *
     * @param array $commands
     */
    public function setPostCommands(array $commands) : void
    {
        $this->postCommands = $commands;
    }

    /**
     * Adds a new label
     *
     * {@inheritDoc}
     * @see \Zpl\AbstractBuilder::newPage()
     */
    public function newPage() : void
    {
        $this->commands[] = '^XZ';
        $this->commands[] = self::PAGE_SEPARATOR;
        $this->commands[] = '^XA';
        $this->setY(0);
        $this->setX($this->getMargin());
    }

    /**
     * Converts the $size from $this->unit to dots
     *
     * @param float $size
     *
     * @return float The size in dots
     */
    protected function toDots(float $size) : float
    {
        switch ($this->unit) {
            case 'mm':
                //1 inch = 25.4 mm
                $sizeInDots = $size * $this->resolution / 25.4;
                break;
            default:
                $sizeInDots = $size;
                break;
        }
        return $sizeInDots;
    }

    /**
     * Converts the font $size from points to dots
     *
     * @param float $size
     *
     * @return float The size in dots
     */
    protected function fontSizeToDots(float $size) : float
    {
        return $size * ($this->resolution * 0.014);
    }

    public function setFontMapper(Fonts\AbstractMapper $mapper) : void
    {
        $this->fontMapper = $mapper;
    }

    /**
     * Convert instance to ZPL.
     *
     * @return string
     */
    public function toZpl() : string
    {
        $preCommands = array_merge($this->preCommands, array('^XA'));
        $postCommands = array_merge(array('^XZ'), $this->postCommands, array(''));

        $zpl = implode("\n", array_merge($preCommands, $this->commands, $postCommands));
        $commands = implode("\n", array_merge($this->postCommands, $this->preCommands));
        $zpl = str_replace(self::PAGE_SEPARATOR, $commands, $zpl);
        return $zpl;
    }

    /**
     * Reset the command queue
     *
     * @return void
     */
    public function reset() : void
    {
        $this->commands = [];
        $this->preCommands = [];
        $this->postCommands = [];
    }

    /**
     * Convert instance to string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toZpl();
    }
}
