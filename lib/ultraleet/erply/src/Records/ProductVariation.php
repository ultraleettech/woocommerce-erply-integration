<?php

namespace Ultraleet\Erply\Records;

use Ultraleet\Erply\Interfaces\RecordInterface;

/**
 * Class ProductVariation
 *
 * Represent a matrix product variation.
 *
 * @package Ultraleet\Erply\Records
 */
class ProductVariation extends AbstractRecord
{
    /**
     * Matrix variation fields.
     *
     * @return array
     */
    protected function fields(): array
    {
        return [
            'productID',
            'name',
            'code',
            'code2',
            'dimensions',
        ];
    }

    /**
     * Create an object from record data received from Erply API.
     *
     * @param array $record
     * @param array $args Arguments that might be needed for specific record type.
     * @return RecordInterface
     */
    public static function createFromRecord(array $record, array $args = []): RecordInterface
    {
        $variation = new static($args);
        $variation->setFields($record);
        return $variation;
    }

    /**
     * Save relevant variation attribute data.
     *
     * @param array $dimensions
     */
    public function setDimensions(array $dimensions)
    {
        foreach ($dimensions as $index => $data) {
            $dimensions[$index] = [
                'name' => $data['name'],
                'value' => $data['value'],
                'order' => $data['order'],
            ];
        }
        $this->setValue('dimensions', $dimensions);
    }
}
