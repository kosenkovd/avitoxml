<?php


namespace App\Services;


use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CronLockService
{
    public function checkAndCreate(string $scriptName, int $minutes, string $scriptId = null): bool
    {
        $lock = $this->find($scriptName);
        
        if (is_null($lock)) {
            $this->create($scriptName, $scriptId);
            return false;
        }
    
        // Check if lock already exists and refresh if timeout
        if (time() > ($lock->created_at + ($minutes * 60))) {
            $this->refresh($scriptName, $scriptId);
            return false;
        }
        
        return true;
    }
    
    public function checkWhileProcessing(string $scriptName, int $minutes, string $scriptId): bool
    {
        $lock = $this->find($scriptName);

        if (is_null($lock)) {
            return false;
        }
    
        if (strcmp($lock->scriptId, $scriptId) !== 0) {
            return false;
        }

        // Check if lock alive
        if (time() > ($lock->created_at + ($minutes * 60))) {
            return false;
        }

        return true;
    }
    
    public function clear(string $scriptName): void
    {
        DB::table('cron_lock')
            ->where('name', $scriptName)
            ->delete();
    }
    
    private function find(string $scriptName): ?object
    {
        return DB::table('cron_lock')
            ->where('name', $scriptName)
            ->first();
    }
    
    private function create(string $scriptName, string $scriptId = null): void
    {
        try {
            DB::table('cron_lock')->insert([
                'scriptId' => $scriptId,
                'name' => $scriptName,
                'created_at' => time()
            ]);
        } catch (Exception $exception) {
            Log::channel('fatal')->error("Script '".$scriptName."' can't create lock.".PHP_EOL.
                $exception->getMessage());
        }
    }
    
    private function refresh(string $scriptName, string $scriptId): void
    {
        DB::table('cron_lock')
            ->where('name', $scriptName)
            ->update([
                'scriptId' => $scriptId,
                'created_at' => time()
            ]);
    }
}
