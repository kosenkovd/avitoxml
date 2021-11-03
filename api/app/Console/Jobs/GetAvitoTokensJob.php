<?php


namespace App\Console\Jobs;


use App\Configuration\Spreadsheet\SheetNames;
use App\Helpers\SpreadsheetHelper;
use App\Models\TableHeader;
use App\Models\TableLaravel;
use App\Services\Interfaces\IAvitoService;
use App\Services\Interfaces\ISpreadsheetClientService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GetAvitoTokensJob extends JobBase {
    const avitoClientIdCol = 0;
    const avitoClientSecretCol = 2;
    const avitoUserIdCol = 4;
    
    private SheetNames $sheetNames;
    
    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        SheetNames $sheetNames,
        string $logChannel = 'avitoTokens'
    )
    {
        parent::__construct($spreadsheetClientService);
        
        $this->sheetNames = $sheetNames;
        $this->logChannel = $logChannel;
    }
    
    /**
     * @param TableLaravel $table
     *
     * @throws Exception
     */
    public function start(TableLaravel $table): void
    {
        $this->processSheet($table);
    }
    
    /**
     * @param TableLaravel $table
     *
     * @throws Exception
     */
    private function processSheet(TableLaravel $table): void
    {
        $tokensStartCell = 'G7';
        $tokensEndCell = 'K7';
        
        $sheetName = $this->sheetNames->getInformation();
        $range = $sheetName.'!'.$tokensStartCell.':'.$tokensEndCell;
        
        $res = $this->spreadsheetClientService->getSpreadsheetCellsRange(
            $table->googleSheetId,
            $range
        );
        
        $tokens = $res[0] ?? null;
        if (is_null($tokens)) {
            return;
        }
        
        $avitoClientId = trim($tokens[self::avitoClientIdCol] ?? null);
        $avitoClientSecret = trim($tokens[self::avitoClientSecretCol] ?? null);
        $avitoUserId = trim($tokens[self::avitoUserIdCol] ?? null);
        
        if ($avitoClientId || $avitoClientSecret || $avitoUserId) {
            
            $this->updateTableTokens($table, $avitoClientId, $avitoClientSecret, $avitoUserId);
            
            $message = "Table '".$table->googleSheetId."' writing values to table...";
            Log::channel($this->logChannel)->info($message);
            
            $clearTokens = [];
            $clearTokens[0][self::avitoClientIdCol] = '';
            $clearTokens[0][1] = $tokens[1];
            $clearTokens[0][self::avitoClientSecretCol] = '';
            $clearTokens[0][3] = $tokens[3];
            $clearTokens[0][self::avitoUserIdCol] = '';
            
            try {
                $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                    $table->googleSheetId,
                    $range,
                    $clearTokens,
                    [
                        'valueInputOption' => 'RAW'
                    ]
                );
            } catch (\Exception $exception) {
                $message = "Error on '".$table->googleSheetId."' while writing to table".PHP_EOL.
                    $exception->getMessage();
                Log::channel($this->logChannel)->error($message);
        
                throw $exception;
            }
        }
    }
    
    private function updateTableTokens(
        TableLaravel $table,
        string $avitoClientId,
        string $avitoClientSecret,
        string $avitoUserId
    ): void
    {
        $tokens = new Collection();
        
        $tokens->put('avitoClientId', $avitoClientId);
        $tokens->put('avitoClientSecret', $avitoClientSecret);
        
        if ($avitoUserId !== '') {
            $avitoUserId = preg_replace('/\s/i', "", $avitoUserId);
        }
        $tokens->put('avitoUserId', $avitoUserId);
        
        $table->update($tokens->filter()->all());
    }
}
