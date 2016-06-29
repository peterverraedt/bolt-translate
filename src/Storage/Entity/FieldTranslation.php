<?php

namespace Bolt\Extension\Verraedt\Translate\Storage\Entity;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for TemplateFields.
 */
class FieldTranslation extends Entity
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $contenttype;
    /** @var int */
    protected $content_id;
    /** @var string */
    protected $locale;
    /** @var string */
    protected $fieldname;
    /** @var string */
    protected $fieldtype;

    /** @var string */
    protected $value_string;
    /** @var string */
    protected $value_text;
    /** @var integer */
    protected $value_integer;
    /** @var double */
    protected $value_float;
    /** @var integer */
    protected $value_decimal;
    /** @var \DateTime */
    protected $value_date;
    /** @var \DateTime */
    protected $value_datetime;
    /** @var array */
    protected $value_json_array = [];

    /**
     * @return mixed
     */
    public function getValue($typeCol = NULL)
    {
        if (is_null($typeCol)) {
            $typeCols = array(
                'string', 
                'text', 
                'integer', 
                'float', 
                'decimal', 
                'date', 
                'datetime', 
                'json_array',
            );
            foreach ($typeCols as $typeCol) {
                $param = 'value_' . $typeCol;
                if (!is_null($this->$param)) {
                    return $this->$param;
                }
            }
        }
        else {
            $param = 'value_' . $typeCol;
            return $this->$param;
        }
    }

    /**
     * @param mixed $value
     */
    public function setValue($value, $typeCol)
    {
        $this->value_string = NULL;
        $this->value_text = NULL;
        $this->value_integer = NULL;
        $this->value_float = NULL;
        $this->value_decimal = NULL;
        $this->value_date = NULL;
        $this->value_datetime = NULL;
        $this->value_json_array = [];

        $param = 'value_' . $typeCol;
        $this->$param = $value;
    }

    public function __toString()
    {
        return (string) $this->getValue();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
