<?php

namespace App\Http\Controllers;

use App\Services\MpfitService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncController extends Controller
{
    protected $mpfitService;
    protected $sheetsService;

    public function __construct(MpfitService $mpfitService, GoogleSheetsService $sheetsService)
    {
        $this->mpfitService = $mpfitService;
        $this->sheetsService = $sheetsService;
    }

    public function showSyncPage()
    {
        return view('sync');
    }

    public function syncProducts(Request $request)
    {
        try {
            $products = $this->mpfitService->getAllProducts();
            $this->processProducts($products);

            return back()->with('success', 'Товары успешно синхронизированы!');
        } catch (\Exception $e) {
            Log::error('Product sync error: ' . $e->getMessage());
            return back()->with('error', 'Ошибка синхронизации товаров: ' . $e->getMessage());
        }
    }

    public function syncStocks(Request $request)
    {
        try {
            $stocks = $this->mpfitService->getAllStocks();
            $this->processStocks($stocks);

            return back()->with('success', 'Остатки успешно синхронизированы!');
        } catch (\Exception $e) {
            Log::error('Stocks sync error: ' . $e->getMessage());
            return back()->with('error', 'Ошибка синхронизации остатков: ' . $e->getMessage());
        }
    }

    public function syncArrivals(Request $request)
    {
        try {
            $arrivals = $this->mpfitService->getAllArrivals();
            $this->processArrivals($arrivals);

            return back()->with('success', 'Приемки успешно синхронизированы!');
        } catch (\Exception $e) {
            Log::error('Arrivals sync error: ' . $e->getMessage());
            return back()->with('error', 'Ошибка синхронизации приемок: ' . $e->getMessage());
        }
    }

    protected function processProducts(array $products)
    {
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

        $this->sheetsService->clearSheet('Товары');
        $this->sheetsService->appendData('Товары', array_merge($headers, $rows));
    }

    protected function processStocks(array $stocks)
    {
        $headers = [['Parse DateTime','product_id', 'company_id', 'warehouse_id', 'free', 'can_collect']];
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

        $this->sheetsService->clearSheet('Остатки');
        $this->sheetsService->appendData('Остатки', array_merge($headers, $rows));
    }

    protected function processArrivals(array $arrivals)
    {
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
                $arrival['arrival_id'] ?? '',
                $arrival['to_warehouse_id'] ?? '',
                $arrival['status'] ?? '',
                $this->formatDate($arrival['date'] ?? null),
                $this->formatDate($arrival['accepted_at'] ?? null),
                $this->formatDate($arrival['created_at'] ?? null),
                $this->formatDate($arrival['updated_at'] ?? null),
                $arrival['product_id'] ?? '',
                $arrival['article'] ?? 'N/A',
                $arrival['product_name'] ?? 'N/A',
                $arrival['quantity'] ?? 0,
                $arrival['fact_quantity'] ?? 0,
                $arrival['item_id'] ?? ''
            ];
        }

        $this->sheetsService->clearSheet('Приемки');
        $this->sheetsService->appendData('Приемки', array_merge($headers, $rows));
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

    public function apiSyncProducts(Request $request)
    {
        try {
            $products = $this->mpfitService->getAllProducts();
            $this->processProducts($products);

            return response()->json([
                'message' => 'Обновлено товаров: ' . count($products),
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function apiSyncStocks(Request $request){
        try {
            $stocks = $this->mpfitService->getAllStocks();
            $this->processStocks($stocks);

            return response()->json([
                'message' => 'Обновлено остатков: ' . count($stocks),
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function apiSyncArrivals(Request $request){
        try {
            $arrivals = $this->mpfitService->getArrivals();
            $this->processStocks($arrivals);

            return response()->json([
                'message' => 'Обновлено: ' . count($arrivals),
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}
