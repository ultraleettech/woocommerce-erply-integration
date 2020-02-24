<?php

namespace Ultraleet\Erply\Records;

use Ultraleet\Erply\Exceptions;
use Ultraleet\Erply\Interfaces\RecordInterface;

/**
 * Class Product
 *
 * Base class for all Erply products.
 *
 * @package Ultraleet\Erply\Records
 */
class Product extends AbstractRecord implements RecordInterface
{
    protected function fields(): array
    {
        return [
            'productID',
            'type',
            'name',
            'code',
            'code2',
            'groupID',
            'price',
            'priceWithVat',
            'priceListPrice',
            'priceListPriceWithVat',
            'displayedInWebshop',
            'vatrateID',
            'length',
            'width',
            'height',
            'description',
            'longdesc',
            'added',
            'lastModified',
            'vatrate',
            'brandName',
            'nonStockProduct',
            'active',
            'status',
            'images',
        ];
    }

    protected function translatedFields(): array
    {
        return [
            'name',
            'description',
            'longdesc',
        ];
    }

    /**
     * Create a product from record data received from Erply API.
     *
     * @param array $record
     * @param array $args
     * @return RecordInterface
     * @throws Exceptions\UndefinedFieldException
     */
    public static function createFromRecord(array $record, array $args = []): RecordInterface
    {
        switch ($record['type']) {
            case 'MATRIX':
                $product = new MatrixProduct($args);
                break;

            default:
                $product = new Product($args);
        }
        $product->setFields($record);
        return $product;
    }

    /**
     * @param array $fieldData
     */
    public function setImages(array $fieldData)
    {
        $images = [];
        foreach ($fieldData as $image) {
            $images[] = $image['fullURL'];
        }
        $this->setValue('images', $images);
    }
}
