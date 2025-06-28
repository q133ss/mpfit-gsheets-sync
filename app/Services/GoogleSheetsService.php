<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsService
{
    protected Sheets $service;
    protected string $spreadsheetId;

    public function __construct()
    {
        $this->spreadsheetId = env('GOOGLE_SHEETS_ID');

        $client = new Client();
        $client->setAuthConfig(storage_path('app/togotop-711e9e2a9023.json'));
        $client->addScope(Sheets::SPREADSHEETS);

        $this->service = new Sheets($client);
    }

    /**
     * Записывает данные в указанный лист
     */
    public function appendData(string $sheetName, array $data): void
    {
        $range = "$sheetName!A:Z"; // Записываем во все колонки

        $body = new ValueRange([
            'values' => $data,
        ]);

        $params = [
            'valueInputOption' => 'RAW',
            'insertDataOption' => 'INSERT_ROWS',
        ];

        $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    /**
     * Очищает лист перед записью новых данных
     */
    public function clearSheet(string $sheetName): void
    {
        $range = "$sheetName!A:Z";
        $this->service->spreadsheets_values->clear(
            $this->spreadsheetId,
            $range,
            new \Google\Service\Sheets\ClearValuesRequest()
        );
    }
}
