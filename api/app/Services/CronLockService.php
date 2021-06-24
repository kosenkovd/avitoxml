<?php


namespace App\Services;


use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CronLockService
{
    public function checkAndCreate(string $scriptName, int $minutes): bool
    {
        $lock = DB::table('cron_lock')
            ->where('name', $scriptName)
            ->first();
        
        if (is_null($lock)) {
            $this->create($scriptName);
            return false;
        }
    
        // Check if lock already exists and refresh if timeout
        if (($lock->created_at + ($minutes * 60)) > time()) {
            $this->refresh($scriptName);
            return false;
        }
        
        return true;
    }
    
    public function checkAndRefreshOrClearIfTimeout(string $scriptName, int $minutes): bool
    {
        $lock = DB::table('cron_lock')
            ->where('name', $scriptName)
            ->first();
        
        if (is_null($lock)) {
            Log::channel('fatal')->error("Script '".$scriptName."' have no lock.");
            $this->create($scriptName);
            return false;
        }
    
        // Check if lock alive and clear if timeout
        if (($lock->created_at + ($minutes * 60)) < time()) {
            $this->clear($scriptName);
            return true;
        }
        
        $this->refresh($scriptName);
        return false;
    }
    
    public function clear(string $scriptName): void
    {
        DB::table('cron_lock')
            ->where('name', $scriptName)
            ->delete();
    }
    
    public function create(string $scriptName): void
    {
        try {
            DB::table('cron_lock')->insert([
                'name' => $scriptName,
                'created_at' => time()
            ]);
        } catch (Exception $exception) {
            Log::channel('fatal')->error("Script '".$scriptName."' already running.".PHP_EOL.
                $exception->getMessage());
        }
    }
    
    public function refresh(string $scriptName): void
    {
        DB::table('cron_lock')
            ->where('name', $scriptName)
            ->update([
                'created_at' => time()
            ]);
    }
}
