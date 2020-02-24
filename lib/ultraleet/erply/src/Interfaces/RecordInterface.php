<?php

namespace Ultraleet\Erply\Interfaces;

/**
 * Interface ProductInterface
 *
 * Represents an Erply product.
 *
 * @package Ultraleet\Erply\Interfaces
 */
interface RecordInterface
{
    /**
     * Create an object from record data received from Erply API.
     *
     * @param array $record
     * @param array $args Arguments that might be needed for specific record type.
     * @return RecordInterface
     */
    public static function createFromRecord(array $record, array $args = []): RecordInterface;

    /**
     * Get field values as an array.
     *
     * @return array
     */
    public function toArray(): array;
}
