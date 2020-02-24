<?php

namespace Ultraleet\WcErply\Troubleshooters;

class RemoveDuplicateProducts extends AbstractTroubleshooter
{
    public static function getTitle(): string
    {
        return __('Remove Duplicate Products', 'wcerply');
    }

    /**
     * @inheritDoc
     */
    public function getQueueActions(): array
    {
        return [
            'process' => 'processProduct',
        ];
    }

    public function generateQueue()
    {
        global $wpdb;
        $skus = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku'");
        $this->addQueue('process', $skus);
        $this->logger->debug(sprintf('Added %d products to check for duplicates.', count($skus)));
    }

    public function processProduct(string $sku)
    {
        global $wpdb;
        $languages = $this->ml->getActiveLanguages();
        $defaultLanguage = $this->ml->getDefaultLanguage();
        $sql = "SELECT language_code, post_id FROM {$wpdb->postmeta} INNER JOIN {$wpdb->prefix}icl_translations ON element_id = post_id WHERE meta_key='_sku' AND meta_value=%s AND element_type IN ('post_product', 'post_product_variation')";
        $products = $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                $sku
            ),
            ARRAY_A
        );
        $data = [];
        foreach ($products as $product) {
            $data[$product['language_code']][] = $product['post_id'];
        }
        foreach ($languages as $language) {
            $productIds = $data[$language['code']] ?? [];
            if (count($productIds) > 1) {
                $productIds = array_slice($productIds, 0, -1);
                $count = count($productIds);
                $this->logger->debug("$count duplicate products in {$language['english_name']} (sku=$sku)");
                foreach ($productIds as $productId) {
                    if (!$product = wc_get_product($productId)) {
                        continue;
                    }
                    $this->logger->debug("Removing duplicate product #{$productId}");
                    $product->delete(true);
                    if ($product->is_type('variable')) {
                        foreach ($product->get_children() as $childId) {
                            if ($child = wc_get_product($childId)) {
                                $this->logger->debug("Deleting variation #$childId");
                                $child->delete(true);
                            }
                        }
                    } elseif ($product->is_type('grouped')) {
                        foreach ($product->get_children() as $childId) {
                            if ($child = wc_get_product($childId)) {
                                $child->set_parent_id(0);
                                $child->save();
                            }
                        }
                    }
                }
            }
        }
    }
}
