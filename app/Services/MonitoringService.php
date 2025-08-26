<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Vps;
use App\Models\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LogFacade;

class MonitoringService
{
    protected $baseUrl;

    public function __construct()
    {
        // You can configure this in your .env file
        $this->baseUrl = config('monitoring.api_base_url', 'https://your-domain.com');
    }

    /**
     * Main monitoring method that runs every 10 hours
     */
    public function runMonitoring()
    {
        try {
            LogFacade::info('Starting monitoring job');

            // Get all VPS records
            $vpsList = Vps::with(['client', 'server'])->get();

            foreach ($vpsList as $vps) {
                $this->monitorVps($vps);
            }

            LogFacade::info('Monitoring job completed successfully');
        } catch (\Exception $e) {
            LogFacade::error('Monitoring job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Monitor a specific VPS
     */
    protected function monitorVps(Vps $vps)
    {
        try {
            // Get line information from API
            $lineData = $this->getLineFromApi($vps->line_id);
            
            if (!$lineData) {
                LogFacade::warning("Could not get line data for VPS ID: {$vps->id}");
                return;
            }

            // Update VPS with line information
            $this->updateVpsWithLineData($vps, $lineData);

            // Check uptime and create log
            $this->checkUptimeAndLog($vps, $lineData);

            // Add admin note via API
            $this->addAdminNote($vps, $lineData);

        } catch (\Exception $e) {
            LogFacade::error("Error monitoring VPS ID {$vps->id}: " . $e->getMessage());
        }
    }

    /**
     * Get line information from API
     */
    protected function getLineFromApi($lineId)
    {
        try {
            $response = Http::get("{$this->baseUrl}/block_actions.php", [
                'action' => 'line',
                'line_id' => $lineId
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            LogFacade::error("Error getting line data from API: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update VPS with line data
     */
    protected function updateVpsWithLineData(Vps $vps, $lineData)
    {
        $updateData = [];

        if (isset($lineData['linename'])) {
            $updateData['linename'] = $lineData['linename'];
        }

        if (isset($lineData['serverdomain'])) {
            $updateData['serverdomain'] = $lineData['serverdomain'];
        }

        if (!empty($updateData)) {
            $vps->update($updateData);
        }
    }

    /**
     * Check uptime and create log entry
     */
    protected function checkUptimeAndLog(Vps $vps, $lineData)
    {
        // Simple uptime check - you can enhance this based on your needs
        $uptimeStatus = $this->checkUptime($vps);
        
        // Create log entry
        Log::create([
            'line_id' => $vps->line_id,
            'client_id' => $vps->client_id,
            'vps_id' => $vps->id,
            'line_name' => $lineData['linename'] ?? null,
            'client_domain' => $vps->client->domain ?? null,
            'uptime_status' => $uptimeStatus,
            'check_details' => json_encode([
                'vps_name' => $vps->name,
                'vps_ip' => $vps->ip,
                'check_method' => 'ping'
            ]),
            'checked_at' => now()
        ]);
    }

    /**
     * Simple uptime check using ping
     */
    protected function checkUptime(Vps $vps)
    {
        if (empty($vps->ip)) {
            return false;
        }

        try {
            // Simple ping check - you might want to use a more sophisticated method
            $pingResult = exec("ping -n 1 -w 1000 " . escapeshellarg($vps->ip), $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            LogFacade::error("Error checking uptime for VPS {$vps->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add admin note via API
     */
    protected function addAdminNote(Vps $vps, $lineData)
    {
        try {
            $note = "Monitoring using client's domain: {$vps->client->domain}";
            
            $response = Http::post("{$this->baseUrl}/block_actions.php", [
                'action' => 'add_note',
                'line' => $vps->line_id,
                'note' => $note
            ]);

            if (!$response->successful()) {
                LogFacade::warning("Failed to add admin note for VPS ID: {$vps->id}");
            }
        } catch (\Exception $e) {
            LogFacade::error("Error adding admin note for VPS {$vps->id}: " . $e->getMessage());
        }
    }
} 