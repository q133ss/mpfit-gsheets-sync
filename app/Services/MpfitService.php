<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpfitService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = 'https://app.mpfit.ru/api';
        $this->token = env('MPFIT_API_TOKEN');
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}{$endpoint}", $data);

            if (!$response->successful()) {
                Log::error('MPFit API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'request' => $data
                ]);
                return null;
            }

            $responseData = $response->json();

            if (!isset($responseData['result'])) {
                Log::error('Invalid API response structure', [
                    'response' => $responseData
                ]);
                return null;
            }

            return $responseData;

        } catch (\Exception $e) {
            Log::error('MPFit API Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function getAllStocks(): array
    {
        $allStocks = [];
        $lastId = 0;
        $attempts = 0;
        $maxAttempts = 50;

        do {
            $response = $this->makeRequest('POST', '/v1/products/stocks', [
                'limit' => 200,
                'last_id' => $lastId
            ]);

            if (empty($response['result']['data'])) {
                Log::info("No more stock data, last_id: {$lastId}");
                break;
            }

            foreach ($response['result']['data'] as $item) {
                if (empty($item['stocks'])) {
                    continue;
                }

                foreach ($item['stocks'] as $stock) {
                    $allStocks[] = [
                        'product_id' => $item['product_id'] ?? '',
                        'company_id' => $item['company_id'] ?? '',
                        'warehouse_id' => $stock['warehouse_id'] ?? '',
                        'free' => $stock['free'] ?? 0,
                        'can_collect' => $stock['can_collect'] ?? 0
                    ];
                }
            }

            $lastId = $response['result']['last_id'] ?? null;
            $attempts++;

        } while ($lastId !== null && $attempts < $maxAttempts);

        return $allStocks;
    }

    public function getAllArrivals(): array
    {
        $allItems = [];
        $lastId = 0;
        $attempts = 0;
        $maxAttempts = 50;

        do {
            $response = $this->makeRequest('POST', '/v1/arrivals/list', [
                'limit' => 200,
                'last_id' => $lastId
            ]);

            // Заменяем $this->info() на Log::info()
            Log::info('Arrivals API request', [
                'last_id' => $lastId,
                'data_count' => isset($response['result']['data']) ? count($response['result']['data']) : 0
            ]);

            if (empty($response['result']['data'])) {
                Log::info("No more arrival data, last_id: {$lastId}");
                break;
            }

            foreach ($response['result']['data'] as $arrival) {
                if (empty($arrival['items'])) {
                    Log::info("Empty items in arrival ID: " . ($arrival['id'] ?? 'unknown'));
                    continue;
                }

                foreach ($arrival['items'] as $item) {
                    $product = $item['product'] ?? [
                        'id' => $item['id'] ?? '',
                        'article' => 'N/A',
                        'name' => 'N/A'
                    ];

                    $allItems[] = [
                        'arrival_id' => $arrival['id'] ?? '',
                        'to_warehouse_id' => $arrival['to_warehouse_id'] ?? '',
                        'status' => $arrival['status'] ?? '',
                        'date' => $arrival['date'] ?? '',
                        'accepted_at' => $arrival['accepted_at'] ?? '',
                        'created_at' => $arrival['created_at'] ?? '',
                        'updated_at' => $arrival['updated_at'] ?? '',
                        'product_id' => $product['id'],
                        'article' => $product['article'],
                        'product_name' => $product['name'],
                        'quantity' => $item['quantity'] ?? 0,
                        'fact_quantity' => $item['fact_quantity'] ?? 0,
                        'item_id' => $item['id'] ?? ''
                    ];
                }
            }

            $lastId = $response['result']['last_id'] ?? null;
            $attempts++;

            Log::info("Processed arrivals batch", [
                'last_id' => $lastId,
                'total_items' => count($allItems)
            ]);

        } while ($lastId !== null && $attempts < $maxAttempts);

        return $allItems;
    }

    /**
     * Получает список товаров с пагинацией
     */
    public function getProducts(int $limit = 200, int $lastId = 0, array $filter = [])
    {
        return $this->makeRequest('POST', '/v1/products/list', [
            'limit' => $limit,
            'last_id' => $lastId,
            'filter' => $filter,
        ]);
    }

    public function getAllProducts(): array
    {
        $allProducts = [];
        $lastId = 0;
        $attempts = 0;
        $maxAttempts = 50;

        do {
            $response = $this->makeRequest('POST', '/v1/products/list', [
                'limit' => 200,
                'last_id' => $lastId
            ]);

            if (empty($response['result']['data'])) {
                Log::info("No more product data, last_id: {$lastId}");
                break;
            }

            $allProducts = array_merge($allProducts, $response['result']['data']);
            $lastId = $response['result']['last_id'] ?? null;
            $attempts++;

            Log::info("Fetched " . count($response['result']['data']) . " products, last_id: {$lastId}");

        } while ($lastId !== null && $attempts < $maxAttempts);

        return $allProducts;
    }

    /**
     * Получает остатки товаров
     */
    public function getStocks(int $limit = 200, int $lastId = 0, array $filter = [])
    {
        return $this->makeRequest('POST', '/v1/products/stocks', [
            'limit' => $limit,
            'last_id' => $lastId,
            'filter' => $filter,
        ]);
    }

    /**
     * Получает список заказов на приемку
     */
    public function getArrivals(int $limit = 200, int $lastId = 0, array $filter = [])
    {
        return $this->makeRequest('POST', '/v1/arrivals/list', [
            'limit' => $limit,
            'last_id' => $lastId,
            'filter' => $filter,
        ]);
    }

    /**
     * Получает список заказов на отгрузку
     */
    public function getOrders(int $limit = 200, int $lastId = 0, array $filter = [])
    {
        return $this->makeRequest('POST', '/v1/orders/list', [
            'limit' => $limit,
            'last_id' => $lastId,
            'filter' => $filter,
        ]);
    }

    /**
     * Получает список типов отгрузок
     */
    public function getOrderTypes(int $limit = 200, int $lastId = 0, array $filter = [])
    {
        return $this->makeRequest('POST', '/v1/orders/types/list', [
            'limit' => $limit,
            'last_id' => $lastId,
            'filter' => $filter,
        ]);
    }
}
