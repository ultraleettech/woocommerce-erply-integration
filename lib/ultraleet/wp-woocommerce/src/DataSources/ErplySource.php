<?php

namespace Ultraleet\WP\WooCommerce\DataSources;

use Ultraleet\Erply\Interfaces\RecordInterface;
use Ultraleet\WP\WooCommerce\Components\Product;
use Ultraleet\Erply\Records\Product as ErplyProduct;

class ErplySource
{
    protected function props(): array
    {
        return [
            'code' => 'sku',
            'added' => 'date_created',
            'lastModified' => 'date_modified',
            'displayedInWebshop' => 'catalog_visibility',
            'name' => 'name',
            'description' => [
                'prop' =>'description',
                'function' => 'wp_filter_post_kses',
            ],
            'shortDescription' => 'short_description',
            //'set_manage_stock',
            'netWeight' => 'weight',
            'length' => 'length',
            //'groupID',
            //PRICES
            //'images',
            //'brandName',
        ];
    }


    /**
     * Sets product props from an Erply record.
     *
     * @param ErplyProduct |array $record
     * @param Product $product
     */
    public function setProductProps($record, Product $product)
    {
        if ($record instanceof RecordInterface) {
            /** @var RecordInterface $product */
            $record = $record->toArray();
        }

    }
}
