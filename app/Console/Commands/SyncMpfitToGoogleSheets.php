<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MpfitService;
use App\Services\GoogleSheetsService;

class SyncMpfitToGoogleSheets extends Command
{
    protected $signature = 'mpfit:sync';
    protected $description = 'Sync MPFit data to Google Sheets';

    public function handle(MpfitService $mpfit, GoogleSheetsService $sheets)
    {
        $this->info('Starting synchronization...');

        // 1. Products
        $this->info('Syncing products...');
        $products = $mpfit->getProducts(200, 0, [])['result']['data'] ?? [];
        $this->syncData($sheets, 'Товары', $products, [
            ['ID', 'Артикул', 'Название', 'Тип', 'Дата создания', 'Дата обновления'],
        ], function ($item) {
            return [
                $item['id'] ?? '',
                $item['article'] ?? '',
                $item['name'] ?? '',
                $item['product_type'] ?? '',
                $item['created_at'] ?? '',
                $item['updated_at'] ?? '',
            ];
        });

        // 2. Stocks
        $this->info('Syncing stocks...');
        $stocks = $mpfit->getStocks(200, 0, [])['result']['data'] ?? [];
        $this->syncData($sheets, 'Остатки', $stocks, [
            ['ID товара', 'ID компании', 'Склад ID', 'Доступно', 'Можно собрать'],
        ], function ($item) {
            $rows = [];
            foreach ($item['stocks'] ?? [] as $stock) {
                $rows[] = [
                    $item['product_id'] ?? '',
                    $item['company_id'] ?? '',
                    $stock['warehouse_id'] ?? '',
                    $stock['free'] ?? '',
                    $stock['can_collect'] ?? '',
                ];
            }
            return $rows;
        });

        // 3. Arrivals
        $this->info('Syncing arrivals...');
        $arrivals = $mpfit->getArrivals(200, 0, [])['result']['data'] ?? [];
        $this->syncData($sheets, 'Приемки', $arrivals, [
            ['ID', 'Склад', 'Статус', 'Дата', 'Принято', 'Товар ID', 'Артикул', 'Название', 'Кол-во'],
        ], function ($item) {
            $rows = [];
            foreach ($item['items'] ?? [] as $arrivalItem) {
                $rows[] = [
                    $item['id'] ?? '',
                    $item['to_warehouse_id'] ?? '',
                    $item['status'] ?? '',
                    $item['date'] ?? '',
                    $item['accepted_at'] ?? '',
                    $product['id'] ?? '', // Проверка на существование product
                    $product['article'] ?? '',
                    $product['name'] ?? '',
                    $arrivalItem['quantity'] ?? '',
                ];
            }
            return $rows;
        });

        $this->info('Synchronization completed!');
    }

    /**
     * Универсальный метод синхронизации данных
     */
    protected function syncData(
        GoogleSheetsService $sheets,
        string $sheetName,
        array $data,
        array $headers,
        callable $rowMapper
    ): void {
        $sheets->clearSheet($sheetName);

        $rows = [];
        foreach ($data as $item) {
            $mappedRows = $rowMapper($item);
            if (isset($mappedRows[0][0])) { // Если массив строк
                $rows = array_merge($rows, $mappedRows);
            } else { // Если одна строка
                $rows[] = $mappedRows;
            }
        }

        $sheets->appendData($sheetName, array_merge($headers, $rows));
    }
}
