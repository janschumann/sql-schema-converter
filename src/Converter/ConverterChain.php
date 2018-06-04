<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 01.06.18
 * Time: 21:10
 */

namespace SchumannIt\DBAL\Schema\Converter;

use Doctrine\DBAL\Schema\Schema;
use SchumannIt\DBAL\Schema\Converter;
use SchumannIt\DBAL\Schema\Mapping;

class ConverterChain implements \Iterator
{
    /**
     * @var Converter[]
     */
    private $converter = [];
    /**
     * @var integer
     */
    private $pos;
    /**
     * @var Converter
     */
    private $current;
    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @param Mapping $mapping
     */
    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @return Mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param Converter
     */
    public function add(Converter $converter)
    {
        array_push($this->converter, $converter);
    }

    /**
     * @return Converter
     */
    public function current()
    {
        $this->current->setSchemaMapping($this->mapping);
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
        $this->mapping->resolve();

        $this->pos++;
        if (array_key_exists($this->pos, $this->converter)) {
            $this->current = $this->converter[$this->pos];
        }
        else {
            $this->current = null;
        }
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->pos;
    }

    /**
     * Checks if next position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return !is_null($this->current);
    }

    public function rewind()
    {
        $this->mapping->reset();
        $this->pos = -1;
        $this->next();
    }
}
