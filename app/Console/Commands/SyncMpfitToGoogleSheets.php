<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\MpfitService;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Log;

class SyncMpfitToGoogleSheets extends Command
{
    protected $signature = 'mpfit:sync';
    protected $description = 'Sync MPFit data to Google Sheets';

    public function handle(MpfitService $mpfit, GoogleSheetsService $sheets)
    {
        $this->info('=== Starting synchronization ===');
        $startTime = microtime(true);

        try {
            // 1. Синхронизация товаров
            $this->info('[1/3] Syncing products...');
            $products = $mpfit->getAllProducts();

            if (empty($products)) {
                $this->error('No product data received from API');
            } else {
                $this->info("Received " . count($products) . " products");
                $this->processProducts($sheets, $products);
            }

            // 2. Синхронизация остатков
            $this->info('[2/3] Syncing stocks...');
            $stocks = $mpfit->getAllStocks();

            if (empty($stocks)) {
                $this->error('No stock data received from API');
            } else {
                $this->info("Received " . count($stocks) . " stock records");
                $this->processStocks($sheets, $stocks);
            }

            // 3. Синхронизация приемок
            $this->info('[3/3] Syncing arrivals...');
            $arrivals = $mpfit->getAllArrivals();

            if (empty($arrivals)) {
                $this->error('No arrival data received from API. Check logs for details.');
                Log::error('Empty arrivals data', [
                    'possible_cause' => 'API returned empty result or parsing failed'
                ]);
            } else {
                $this->info("Received " . count($arrivals) . " arrival items");
                $this->processArrivals($sheets, $arrivals);
            }

        } catch (\Exception $e) {
            $this->error("SYNC FAILED: " . $e->getMessage());
            Log::error("Sync failed", ['error' => $e->getMessage()]);
            return 1;
        }

        $executionTime = round(microtime(true) - $startTime, 2);
        $this->info("=== Synchronization completed in {$executionTime}s ===");

        // Добавляем ссылку на логи для удобства
        $this->info("Check detailed logs in: storage/logs/laravel.log");

        return 0;
    }

    protected function processStocks(GoogleSheetsService $sheets, array $stocks)
    {
        try {
            $headers = [['Parse DateTime', 'product_id', 'company_id', 'warehouse_id', 'free', 'can_collect']];
            $rows = [];

            foreach ($stocks as $stock) {
                $currentDateTime = now()->format('Y-m-d H:i:s');
                $rows[] = [
                    $currentDateTime,
                    $stock['product_id'] ?? '',
                    $stock['company_id'] ?? '',
                    $stock['warehouse_id'] ?? '',
                    $stock['free'] ?? 0,
                    $stock['can_collect'] ?? 0
                ];
            }

            $sheets->clearSheet('Остатки');
            $sheets->appendData('Остатки', array_merge($headers, $rows));
            $this->info("Successfully updated Остатки with " . count($rows) . " rows");

        } catch (\Exception $e) {
            $this->error("Failed to process stocks: " . $e->getMessage());
            throw $e;
        }
    }

    protected function processArrivals(GoogleSheetsService $sheets, array $arrivals)
    {
        try {
            $headers = [
                [
                    'Parse DateTime',
                    'arrival_id', 'to_warehouse_id', 'status', 'date',
                    'accepted_at', 'created_at', 'updated_at',
                    'product_id', 'article', 'product_name',
                    'quantity', 'fact_quantity', 'item_id'
                ]
            ];

            $rows = [];

            foreach ($arrivals as $arrival) {
                $currentDateTime = now()->format('Y-m-d H:i:s');
                $rows[] = [
                    $currentDateTime,
                    $arrival['arrival_id'],
                    $arrival['to_warehouse_id'],
                    $arrival['status'],
                    $this->formatDate($arrival['date']),
                    $this->formatDate($arrival['accepted_at']),
                    $this->formatDate($arrival['created_at']),
                    $this->formatDate($arrival['updated_at']),
                    $arrival['product_id'],
                    $arrival['article'],
                    $arrival['product_name'],
                    $arrival['quantity'],
                    $arrival['fact_quantity'],
                    $arrival['item_id']
                ];
            }

            $this->info("Preparing to sync " . count($rows) . " arrival items");

            $sheets->clearSheet('Приемки');
            $sheets->appendData('Приемки', array_merge($headers, $rows));

            $this->info("Successfully updated Приемки with " . count($rows) . " rows");

        } catch (\Exception $e) {
            $this->error("Failed to process arrivals: " . $e->getMessage());
            Log::error("Arrivals processing error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'first_item' => $arrivals[0] ?? null
            ]);
            throw $e;
        }
    }

    protected function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d.m.Y H:i:s');
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$date}");
            return $date;
        }
    }

    protected function processProducts(GoogleSheetsService $sheets, array $products)
    {
        try {
            $headers = [['Parse DateTime', 'ID', 'article', 'name', 'product_type', 'created_at', 'updated_at']];
            $rows = [];

            foreach ($products as $product) {
                $currentDateTime = now()->format('Y-m-d H:i:s');
                $rows[] = [
                    $currentDateTime,
                    $product['id'] ?? '',
                    $product['article'] ?? '',
                    $product['name'] ?? '',
                    $product['product_type'] ?? '',
                    $this->formatDate($product['created_at'] ?? null),
                    $this->formatDate($product['updated_at'] ?? null)
                ];
            }

            $sheets->clearSheet('Товары');
            $sheets->appendData('Товары', array_merge($headers, $rows));
            $this->info("Successfully updated Товары with " . count($rows) . " rows");

        } catch (\Exception $e) {
            $this->error("Failed to process products: " . $e->getMessage());
            throw $e;
        }
    }
}
