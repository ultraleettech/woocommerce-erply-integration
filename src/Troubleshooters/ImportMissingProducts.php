<?php

namespace Ultraleet\WcErply\Troubleshooters;

use Ultraleet\WcErply\Synchronizers\ImportProducts;

class ImportMissingProducts extends ImportProducts
{
    public static function getDirection(): string
    {
        return AbstractTroubleshooter::DIRECTION_TROUBLESHOOTER;
    }

    public static function getTitle(): string
    {
        return __('Import Missing Products', 'wcerply');
    }

    public function hasCron(): bool
    {
        return false;
    }

    public function generateQueue()
    {
        $records = [];
        $page = 0;
        do {
            $response = $this->getPage(++$page);
            foreach ($response['records'] as $record) {
                if ($this->needsImporting($record['code'])) {
                    $records[] = $record;
                } elseif (isset($record['variationList'])) {
                    foreach ($record['variationList'] as $variation) {
                        if ($this->needsImporting($variation['code'])) {
                            $records[] = $record;
                            break;
                        }
                    }
                }
            }
        } while (!empty($response['records']));
        $this->addQueue('import', $records);
    }

    private function needsImporting(string $sku): bool
    {
        global $wpdb;
        $sql = "SELECT COUNT(element_id) FROM {$wpdb->prefix}icl_translations INNER JOIN {$wpdb->posts} ON ID=element_id AND element_type=CONCAT('post_', post_type) INNER JOIN {$wpdb->postmeta} ON post_id=ID AND meta_key='_sku' WHERE meta_value=%s";
        $count = $wpdb->get_var($wpdb->prepare($sql, $sku));
        $need = 1;
        return $count < $need;
    }
}
