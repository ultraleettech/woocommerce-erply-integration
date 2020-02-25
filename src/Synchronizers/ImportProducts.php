<?php

namespace Ultraleet\WcErply\Synchronizers;

use WC_Product;
use WC_Data_Exception;
use Ultraleet\WcErply;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Product_Attribute;
use Ultraleet\Erply\Records\Product;
use Ultraleet\Erply\Exceptions\UndefinedFieldException;

class ImportProducts extends AbstractSynchronizer
{
    protected $productsAdded = 0;
    protected $productsUpdated = 0;
    protected $variationsAdded = 0;
    protected $variationsUpdated = 0;
    protected $productsDeleted = 0;
    protected $originalProductId;
    protected $removeUniqueSkuFilter = false;
    protected $publish = false;

    public static function getDirection(): string
    {
        return static::DIRECTION_FROM;
    }

    public static function getTitle(): string
    {
        return __('Products', 'wcerply');
    }

    public static function getPrerequisites()
    {
        return [
            'import_categories',
        ];
    }

    /**
     * @param array $config
     */
    public static function addSettingsConfig(array &$config)
    {
        $config['products'] = [
            'title' => __('Products', 'wcerply'),
            'sections' => [
                'backorders' => [
                    'fields' => [
                        'header' => [
                            'type' => 'section_header',
                            'title' => __('Allow backorders', 'wcerply'),
                            'text' => __('Configure the default backorder setting for new and updated products that are imported from Erply.', 'wcerply'),
                        ],
                        'default' => [
                            'type' => 'select',
                            'label' => __('Default setting', 'wcerply'),
                            'options' => 'wc_get_product_backorder_options',
                            'default' => 'no',
                        ],
                    ],
                ],
            ]
        ];
    }

    public function hasCron(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getQueueActions(): array
    {
        return [
            'product' => 'importProduct',
            'variation' => 'importVariation',
            'publish' => 'publishProduct',
            'sync' => 'syncVariableProduct',
        ];
    }

    /**
     * @throws \Exception
     */
    public function generateQueue()
    {
        $records = [];
        $page = 0;
        do {
            $response = $this->getPage(++$page);
            if ($page == 1) {
                $timestamp = $response['status']['requestUnixTime'];
            }
            foreach ($response['records'] as $record) {
                $records[] = $this->createProductRecords($record);
            }
        } while ($response['status']['recordsTotal'] > count($records));
        $this->saveTimestamp($timestamp);
        $this->addQueue('product', $records);
    }

    /**
     * Fetch a page of products from Erply.
     *
     * @param int $page
     * @return array
     * @throws \Exception
     */
    public function getPage(int $page): array
    {
        $params = [
            'changedSince' => $this->getTimestamp(),
            'orderBy' => 'changed',
            'orderByDir' => 'asc',
            'getPriceListPrices' => 1,
            'includeMatrixVariations' => 0,
            'getMatrixVariations' => 1,
            'recordsOnPage' => 1000,
            'pageNo' => $page,
        ];
        $response = $this->api->request('getProducts', $params);
        return $response;
    }

    /**
     * @param array $record
     * @return array
     * @throws UndefinedFieldException
     */
    protected function createProductRecords(array $record): array
    {
        $records = [
            'record' => Product::createFromRecord($record)->toArray(),
        ];
        $records['publish'] = 'publish';
        return $records;
    }

    /**
     * Process an Erply product record from the queue.
     *
     * @param array $data
     * @throws WC_Data_Exception
     */
    public function importProduct(array $data)
    {
        $this->resetProperties();
        $this->loadTermMap();
        if (isset($data['record'])) {
            $product = $data;
            $record = $product['record'];
            $this->originalProductId = $product['originalProductId'] ?? $record['productID'];
        } else {
            $product = array_shift($data);
            $record = $product['record'];
        }
        $this->publish = isset($product['publish']);
        $this->processRecord($record);
    }

    protected function resetProperties()
    {
        $this->originalProductId = null;
        $this->publish = false;
    }

    /**
     * Process a product variation from queue.
     *
     * @param array $record
     * @throws WC_Data_Exception
     */
    public function importVariation(array $record)
    {
        $this->resetProperties();
        $parent = wc_get_product($record['parentId']);
        $variation = $this->getVariationByErplyId($record['parentId'], $record['productID']);
        if (! $this->processProductVariation($variation, $parent, $record)) {
            $this->logger->debug("Skipped variation", $record);
        }
    }

    /**
     * Fetch or initialize product variation.
     *
     * @param int $productId Parent product ID.
     * @param int $erplyId Erply ID of variation.
     * @return WC_Product_Variation|WC_Product
     */
    protected function getVariationByErplyId(int $productId, int $erplyId): WC_Product_Variation
    {
        global $wpdb;
        $query = "SELECT ID from {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_erply_product_id' WHERE p.post_type = 'product_variation' AND p.post_parent=%d AND pm.meta_value = %s";
        $id = $wpdb->get_var($wpdb->prepare($query, $productId, $erplyId));
        return $id ? wc_get_product($id) : new WC_Product_Variation();
    }

    /**
     * Publish product (and translations if applicable) after import.
     *
     * @param int $productId
     */
    public function publishProduct(int $productId)
    {
        $ids = [$productId];
        $originalProductId = $productId;
        foreach ($ids as $id) {
            wp_publish_post($id);
        }
        $this->logger->debug("Product #$originalProductId published.", $ids);
    }

    /**
     * Sync variable product when all variations have been processed.
     *
     * @param $productId
     */
    public function syncVariableProduct($productId)
    {
        $this->logger->debug("Syncing variable product #$productId...");
        wc_delete_product_transients($productId);
        WC_Product_Variable::sync($productId);
    }

    /**
     * Process a single Erply product record.
     *
     * @param array $record
     * @throws WC_Data_Exception
     */
    protected function processRecord(array $record)
    {
        $context = [];
        $this->logger->debug(sprintf('Processing Erply product %d...', $record['productID']), $context);
        $id = wc_get_product_id_by_sku($record['code']);
        if ($id) {
            if ($record['status'] !== 'ACTIVE') {
                $this->deleteProduct($record['code']);
            } else {
                $this->updateProduct($id, $record);
            }
        } elseif ($record['status'] === 'ACTIVE') {
            $id = $this->addProduct($record);
        } elseif ($products = $this->getAllProductsBySku($record['code'])) {
            /**
             * Fix to delete all translations of archived products.
             *
             * This function should never succeed in deleting any products.
             * If it does, it indicates, that there are for some reason orphaned translations of products
             * still in the database.
             *
             * @deprecated 2019-09-29
             */
            $this->deleteProduct($products);
            if ($this->productsDeleted > 0) {
                $this->logger->debug('DEPRECATION BLOCKER: orphaned translation delete succeeded', [
                    'class' => __CLASS__,
                    'method' => __METHOD__,
                    'file' => __FILE__,
                    'line' => __LINE__,
                ]);
            }
            $this->productsDeleted = 0;
        }
    }

    /**
     * Add a new product from Erply record.
     *
     * @param array $record
     * @return int|null
     * @throws WC_Data_Exception
     */
    protected function addProduct(array $record): int
    {
        if ($record['type'] == 'MATRIX') {
            $product = new WC_Product_Variable();
        } elseif ($record['type'] == 'PRODUCT') {
            $product = new WC_Product();
        } else {
            // ignore other product types for now.
            return 0;
        }
        $product->add_meta_data('_erply_product_id', $record['productID']);
        $id = $this->processProduct($product, $record);
        $this->logger->debug("Product #$id created.");

        $this->incrementTotalProducts();
        $this->productsAdded++;
        return $id;
    }

    private function incrementTotalProducts()
    {
        $total = get_option('wcerply_stats_total_products', 0);
        update_option('wcerply_stats_total_products', ++$total);
    }

    /**
     * Update a product from Erply record.
     *
     * @param int $id
     * @param array $record
     * @throws WC_Data_Exception
     */
    protected function updateProduct(int $id, array $record)
    {
        $product = wc_get_product($id);
        $id = $this->processProduct($product, $record);
        $logMessage = sprintf(__('Product #%d updated.', 'wcerply'), $id);
        $this->logger->debug($logMessage);
        $this->productsUpdated++;
    }

    /**
     * Process a product record.
     *
     * @param WC_Product $product
     * @param array $record
     * @return mixed
     * @throws WC_Data_Exception
     */
    protected function processProduct($product, array $record)
    {
        if ($record['type'] === 'MATRIX' && $product->get_type() !== 'variable') {
            $product = new WC_Product_Variable($product->get_id());
        }
        $termMap = $this->termMap;
        $name = $record['name'];
        if ($product->get_name('edit') !== $name) {
            $product->set_slug(sanitize_title($name));
            $product->set_name($name);
        }
        if (!$product->get_id()) {
            $product->set_sku($record['code']);
            $product->set_status('draft');
            $product->set_date_created($record['added']);
            $product->set_sold_individually(false);
        }
        $product->set_date_modified($record['lastModified']);
        $product->set_catalog_visibility($record['displayedInWebshop'] ? 'visible' : 'hidden');
        $product->set_description(wp_filter_post_kses($record['longdesc']));
        $product->set_short_description($record['description']);
        $product->set_manage_stock(!$record['nonStockProduct'] && 'MATRIX' !== $record['type']);
        $product->set_backorders($this->settings->getSettingValue('default', 'backorders', 'products'));
        $product->set_reviews_allowed(get_option('woocommerce_enable_reviews'));
        $product->set_weight($record['netWeight'] ?? 0);
        $product->set_length($record['length'] ?? 0);
        if (($groupId = $record['groupID'] ?? 0) && isset($termMap[$groupId])) {
            $product->set_category_ids([$termMap[$groupId]]);
        }
        if ('MATRIX' !== $record['type']) {
            $this->setProductPrice($product, $record);
        }

        // import product images
        if ($record['images'] ?? []) {
            $productImagesIDs = [];
            foreach ($record['images'] as $url) {
                if ($mediaID = $this->fetchImage($url)) {
                    $productImagesIDs[] = $mediaID;
                }
            }
            if ($productImagesIDs) {
                $product->set_image_id($productImagesIDs[0]);
                if (count($productImagesIDs) > 1) {
                    $product->set_gallery_image_ids(array_slice($productImagesIDs, 1));
                }
            }
        }

        // save product
        $id = $product->save();

        // product brand
        if ($this->isBrandsSupported() && $record['brandName']) {
            wp_set_object_terms($id, [$record['brandName']], 'product_brand');
        }

        // queue variations, translations, and publish
        $canPublishNow = true;
        if (isset($record['variations'])) {
            $this->queueVariations($record['variations'], $id, $record);
            $canPublishNow = false;
        }
        if ($this->publish) {
            $publishId = $this->originalProductId ?: $id;
            $canPublishNow ? $this->publishProduct($publishId) : $this->addQueue('publish', [$publishId]);
        }

        return $id;
    }

    /**
     * @param WC_Product $product
     * @param array $record
     */
    protected function setProductPrice(WC_Product $product, array $record): void
    {
        $savePriceWithVat = wc_string_to_bool(get_option('woocommerce_prices_include_tax'));
        $price = $savePriceWithVat ? $record['priceWithVat'] : $record['price'];
        $priceListPrice = $savePriceWithVat ? $record['priceListPriceWithVat'] : $record['priceListPrice'];
        $isSalePrice = round($priceListPrice, 2) < round($price, 2);
        $product->set_regular_price($price);
        $product->set_sale_price($isSalePrice ? $priceListPrice : '');
    }

    /**
     * Add product variations to import queue.
     *
     * @param array $records
     * @param int $productId
     * @param array $parentRecord
     */
    protected function queueVariations(array $records, int $productId, array $parentRecord)
    {
        foreach ($records as $record) {
            $prepend = ['parentId' => $productId];
            $append = array_filter($parentRecord, function ($key) {
                return in_array($key, [
                    'price',
                    'priceWithVat',
                    'priceListPrice',
                    'priceListPriceWithVat',
                    'nonStockProduct',
                ]);
            }, ARRAY_FILTER_USE_KEY);
            $this->addQueue('variation', [$prepend + $record + $append]);
        }
        $this->addQueue('sync', [$productId]);
    }

    /**
     * Add/update product variation data.
     *
     * @param WC_Product_Variation $variation
     * @param WC_Product $parent
     * @param array $record
     * @return int|null
     */
    protected function processProductVariation(WC_Product_Variation $variation, WC_Product $parent, array $record)
    {
        // Bail out early in case a variation with this SKU already exists.
        if ($record['code'] !== $variation->get_sku('edit')) {
            try {
                $variation->set_sku($record['code']);
            } catch (WC_Data_Exception $e) {
                return null;
            }
        }
        $erplyId = $record['productID'];
        $parentId = $parent->get_id();
        /** @var WC_Product_Attribute[] $parentAttributes */
        if (! $parentAttributes = $parent->get_attributes('edit')) {
            $parentAttributes = [];
        }
        $saveParentAttributes = false;
        if (! $id = $variation->get_id()) {
            $variation->set_parent_id($parentId);
            $variation->add_meta_data('_erply_product_id', $erplyId, true);
        }
        $variation->set_name($record['name']);
        $variation->set_manage_stock(! $record['nonStockProduct']);
        $this->setProductPrice($variation, $record);
        $attributes = [];
        foreach ($record['dimensions'] as $attribute) {
            if (! $attribute['name']) {
                continue;
            }

            $taxonomy = wc_attribute_taxonomy_name($attribute['name']);
            if (! taxonomy_exists($taxonomy)) {
                $attributeId = wc_create_attribute([
                    'name' => $attribute['name'],
                    'type' => 'text',
                ]);
                $resultRegister = register_taxonomy(
                    $taxonomy,
                    'product',
                    [
                        'hierarchical' => false,
                        'label' => $attribute['name'],
                        'query_var' => true,
                        'rewrite' => ['slug' => ''],
                    ]
                );
                $logMessage = sprintf(
                    'Added new product attribute: %s (taxonomy: %s).',
                    $attribute['name'],
                    $taxonomy
                );
                $this->logger->debug($logMessage);
            } else {
                $attributeId = wc_attribute_taxonomy_id_by_name($taxonomy);
            }

            if (! $term = ulwp_get_term_by_name($attribute['value'], $taxonomy)) {
                $args = (object) ['taxonomy' => $taxonomy];
                $slug = wp_unique_term_slug(sanitize_title($attribute['value']), $args);
                $term = wp_insert_term($attribute['value'], $taxonomy, ['slug' => $slug]);
                $term = get_term($term['term_id']);
                $this->logger->debug("New option created for attribute '{$attribute['name']}'", $term->to_array());
            }
            $attributes[$taxonomy] = $term->slug;

            // update parent product attributes
            if (! isset($parentAttributes[$taxonomy])) {
                $newAttribute = new WC_Product_Attribute();
                $newAttribute->set_id($attributeId);
                $newAttribute->set_name($taxonomy);
                $newAttribute->set_position($attribute['order']);
                $newAttribute->set_visible(true);
                $newAttribute->set_variation(true);

                $parentAttributes[$taxonomy] = $newAttribute;
            }
            $attributeOptions = $parentAttributes[$taxonomy]->get_options();
            if (! in_array($term->term_id, $attributeOptions)) {
                $attributeOptions[] = $term->term_id;
                $parentAttributes[$taxonomy]->set_options($attributeOptions);
                $saveParentAttributes = true;
            }
        }
        if ($saveParentAttributes) {
            /**
             * Make sure attribute changes are registered.
             */
            $parent->set_attributes([]);
            $parent->set_attributes($parentAttributes);
            $parent->save();
        }
        $variation->set_attributes($attributes);
        $variationId = $variation->save();

        if (! $id) {
            $this->logger->debug("Added variation #$variationId to product #$parentId");
            $this->variationsAdded++;
            $this->incrementTotalVariations();
        } else {
            $this->logger->debug("Updated variation #$variationId for product #$parentId");
            $this->variationsUpdated++;
        }
        return $variationId;
    }

    private function incrementTotalVariations()
    {
        $total = get_option('wcerply_stats_total_variations', 0);
        update_option('wcerply_stats_total_variations', ++$total);
    }

    /**
     * Delete a product as well as all variations and translations.
     *
     * @param string|array $sku SKU of the product(s) or an array of products to delete
     */
    protected function deleteProduct($sku)
    {
        if (!is_array($sku)) {
            $this->logger->debug("Deleting products with sku $sku...");
            /** @var WC_Product[] $products */
            $products = $this->getAllProductsBySku($sku);
        } else {
            $this->logger->debug("Deleting orphaned translations...");
            $products = $sku;
        }
        foreach ($products as $product) {
            $productId = $product->get_id();
            $this->logger->debug("Deleting product #$productId");
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
            $product->delete(true);
            $result = !($product->get_id() > 0);
            if (!$result) {
                $this->logger->error("Error deleting product $productId!");
                continue;
            }
            if ($parentId = wp_get_post_parent_id($productId)) {
                wc_delete_product_transients($parentId);
            }
        }
        $this->productsDeleted++;
    }

    /**
     * @param string $sku
     * @return array
     */
    protected function getAllProductsBySku(string $sku): array
    {
        $products = wc_get_products(['sku' => $sku]);
        foreach ($products as $index => $product) {
            if ($product->get_sku() !== $sku) {
                // skip partial matches
                unset($products[$index]);
            }
        }
        return $products;
    }

    // DEPRECATED METHODS

    /**
     * Synchronize products.
     *
     * @throws \Exception
     * @deprecated 2019-09-30
     */
    public function execute()
    {
        echo $this->view->render('sync/products');
    }

    /**
     * Finalize product synchronization.
     *
     * Saves last request time and logs stats about the sync.
     *
     * @param array $stats
     * @deprecated 2019-09-30
     */
    public function syncComplete(array $stats)
    {
        $this->saveTimestamp($stats['timeStamp']);
        $logMessage = sprintf(
            "%d products added, %d updated, %d deleted. %d variations added, %d updated.",
            $stats['productsAdded'],
            $stats['productsUpdated'],
            $stats['productsDeleted'],
            $stats['variationsAdded'],
            $stats['variationsUpdated']
        );
        $this->logger->info($logMessage);
    }
}
