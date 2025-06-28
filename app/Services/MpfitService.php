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

    /**
     * Отправляет запрос к MPFit API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('MPFit API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('MPFit API Exception', ['error' => $e->getMessage()]);
            return null;
        }
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
