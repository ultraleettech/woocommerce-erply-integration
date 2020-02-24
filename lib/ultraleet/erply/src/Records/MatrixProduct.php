<?php

namespace Ultraleet\Erply\Records;

/**
 * Class MatrixProduct
 *
 * Deals with matrix products that have variation data in addition to regular product info.
 *
 * @package Ultraleet\Erply\Records
 */
class MatrixProduct extends Product
{
    /**
     * @var ProductVariation[]
     */
    protected $variations = [];

    /**
     * Matrix products have a list of variations.
     *
     * @return array
     */
    protected function fields(): array
    {
        return array_merge(parent::fields(), [
            'variationList',
        ]);
    }

    /**
     * Create instances for all matrix variations.
     *
     * @param array $variations
     */
    public function setVariationList(array $variations)
    {
        $this->variations = [];
        foreach ($variations as $data) {
            $this->variations[] = ProductVariation::createFromRecord($data);
        }
    }

    /**
     * Get array of matrix variation instances.
     *
     * @return ProductVariation[]
     */
    public function getVariations(): array
    {
        return $this->variations;
    }

    /**
     * Add variation data to other product fields.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['variations'] = [];
        foreach ($this->variations as $variation) {
            $data['variations'][] = $variation->toArray();
        }
        return $data;
    }
}
