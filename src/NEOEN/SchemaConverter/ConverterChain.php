<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 01.06.18
 * Time: 21:10
 */

namespace NEOEN\SchemaConverter;


class ConverterChain implements \Iterator
{
    /**
     * @var array[ConverterInterface]
     */
    private $converter;
    /**
     * @var integer
     */
    private $pos;
    /**
     * @var ConverterInterface
     */
    private $current;


    /**
     * @param ConverterInterface
     */
    public function add(ConverterInterface $converter)
    {
        $this->converter[] = $converter;
    }

    /**
     * @return array[ConverterInterface]
     */
    public function getConverters()
    {
        return $this->converter;
    }

    /**
     * @return ConverterInterface
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->converter);
    }

    public function next()
    {
        $this->pos++;
        $this->current = $this->converter[$this->pos];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->pos;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->pos < count($this->converter);
    }

    public function rewind()
    {
        $this->pos = 0;
    }
}
