<?php

namespace Ultraleet\WcErply\Synchronizers;

class ImportCategories extends AbstractSynchronizer
{
    public static function getDirection(): string
    {
        return static::DIRECTION_FROM;
    }

    public static function getTitle(): string
    {
        return __('Product Categories', 'wcerply');
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
            'import' => 'importRecord',
        ];
    }

    /**
     * @throws \Exception
     *
     * @todo Sync deleted records
     */
    public function generateQueue()
    {
        $params = [
            'changedSince' => $this->getTimestamp(),
        ];
        $response = $this->api->request('getProductGroups', $params);
        $this->saveTimestamp($response);
        $this->addQueue('import', $response['records']);
    }

    /**
     * Import Erply product category from queued record.
     *
     * @param array $record
     */
    public function importRecord(array $record)
    {
        $this->loadTermMap();
        $this->processRecord($record);
        $this->saveTermMap();
    }

    private function processRecord(array $record, int $parentId = 0)
    {
        if (isset($this->termMap[$record['id']])) {
            $termId = $this->termMap[$record['id']];
            $this->updateCategory($termId, $record);
        } elseif ($record['showInWebshop']) {
            $termId = $this->insertCategory($record, $parentId);
            if ($termId) {
                $this->termMap[$record['id']] = $termId;
            }
        }
        foreach ($record['subGroups'] ?? [] as $record) {
            $this->processRecord($record, $termId);
        }
    }

    private function insertCategory(array $record, int $parentId = 0): int
    {
        $result = wp_insert_term($record['name'], 'product_cat', [
            'parent' => $parentId,
        ]);
        $this->logResult("Insert category {$record['name']}", $result);
        if (!is_wp_error($result)) {
            $id = $result['term_id'];
            // TODO: add image
            update_term_meta($id, 'order', $record['positionNo']);
            $this->incrementTotalCategories();
        }
        return $id ?? null;
    }

    private function incrementTotalCategories()
    {
        $total = get_option('wcerply_stats_total_categories', 0);
        update_option('wcerply_stats_total_categories', ++$total);
    }

    private function updateCategory(int $id, array $record)
    {
        if (!$record['showInWebshop']) {
            $this->deleteCategory($id, $record['productGroupID']);
            return;
        }
        $parentId = $this->termMap[$record['parentGroupID']] ?? 0;
        $result = wp_update_term($id, 'product_cat', [
            'name' => $record['name'],
            'parent' => $parentId,
        ]);
        update_term_meta($id, 'order', $record['positionNo']);
        $this->logResult("Update category {$record['name']}", $result);
    }

    private function deleteCategory(int $id, int $erplyId)
    {
        wp_delete_term($id, 'product_cat');
        unset($this->termMap[$erplyId]);
    }

    /**
     * @throws \Exception
     *
     * @deprecated
     */
    public function execute()
    {
        $params = [
            'changedSince' => $this->getTimestamp(),
        ];
        $response = $this->api->request('getProductGroups', $params);

        // TODO: sync deleted records

        echo $this->view->render('sync/categories', [
            'response' => $response,
        ]);
        $this->saveTimestamp($response);
    }
}
