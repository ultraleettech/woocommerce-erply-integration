<?php

namespace Ultraleet\Erply\Records;

use Ultraleet\Erply\Interfaces\RecordInterface;
use Ultraleet\Erply\Exceptions\UndefinedFieldException;
use Ultraleet\Erply\Exceptions\InvalidArgumentException;

/**
 * Class AbstractRecord
 *
 * Base class for all other Erply record classes.
 *
 * @package Ultraleet\Erply\Records
 */
abstract class AbstractRecord implements RecordInterface
{
    /**
     * @var array Arguments this record instance was initialized with.
     */
    protected $args = [];

    /**
     * @var array Field values set for this record.
     */
    protected $values = [];

    /**
     * Create the record instance.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->args = array_merge($this->defaultArgs(), $args);
    }

    /**
     * Field names available for this record type.
     *
     * @return array
     */
    protected function fields(): array
    {
        return [];
    }

    /**
     * Fields that have translated values.
     *
     * @return array
     */
    protected function translatedFields(): array
    {
        return [];
    }

    /**
     * Default argument values to use in instance creation.
     *
     * @return array
     */
    protected function defaultArgs(): array
    {
        return [
            'language' => '',
            'defaultLanguage' => 'ENG',
        ];
    }

    /**
     * Get field values as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Get a field value.
     *
     * @param string $name
     * @return mixed|null
     * @throws UndefinedFieldException
     */
    public function getField(string $name)
    {
        if (!in_array($name, $this->fields())) {
            throw new UndefinedFieldException();
        }
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return $this->getValue($name);
    }

    /**
     * Set a field value.
     *
     * @param string $name
     * @param $value
     * @param bool $ignoreUndefinedFields
     * @return AbstractRecord
     * @throws UndefinedFieldException
     */
    public function setField(string $name, $value, bool $ignoreUndefinedFields = false): self
    {
        if (!in_array($name, $this->fields())) {
            if ($ignoreUndefinedFields) {
                return $this;
            } else {
                throw new UndefinedFieldException();
            }
        }
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->setValue($name, $value);
        }
        return $this;
    }

    /**
     * Set fields from the record in bulk.
     *
     * @param array $record
     * @throws UndefinedFieldException
     */
    public function setFields(array $record)
    {
        foreach ($record as $field => $value) {
            if (in_array($field, $this->translatedFields()) && $language = $this->getArg('language')) {
                $fieldName = $field . $language;
                $fieldName = !empty($record[$fieldName]) ? $fieldName : $field . $this->getArg('defaultLanguage');
                $value = empty($record[$fieldName]) ? $value : $record[$fieldName];
            }
            $this->setField($field, $value, true);
        }
    }

    /**
     * Used internally to get raw field value.
     *
     * @param string $name
     * @return mixed|null
     */
    protected function getValue(string $name)
    {
        return $this->values[$name] ?? null;
    }

    /**
     * Used internally to set a raw field value.
     *
     * @param string $name
     * @param $value
     */
    protected function setValue(string $name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * Get instance argument value.
     *
     * @param string $name
     * @return mixed
     */
    protected function getArg(string $name)
    {
        if (!array_key_exists($name, $this->args)) {
            throw new InvalidArgumentException("'$name' is not a valid argument for this record.");
        }
        return $this->args[$name];
    }

    /**
     * Wrapper for getField().
     *
     * @param string $name
     * @return mixed|null
     * @throws UndefinedFieldException
     */
    public function __get(string $name)
    {
        return $this->getField($name);
    }

    /**
     * Wrapper for setField().
     *
     * @param string $name
     * @param $value
     * @throws UndefinedFieldException
     */
    public function __set(string $name, $value)
    {
        $this->setField($name, $value);
    }
}
