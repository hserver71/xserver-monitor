<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Line;
use App\Models\Client;
use App\Models\Server;
use Carbon\Carbon;

class AssignLinesToFreeVPS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lines:assign-free-vps {--days=1 : Number of days after which to assign lines}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign lines to free VPS';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        $this->info("Looking for VPS with lines assigned before: {$cutoffDate->format('Y-m-d H:i:s')}");
        $line = Line::where('assigned_at', '!=', null)->first();
        $client = Client::where('id', $line->client_id)->first();
        $servers = Server::where('client_id', $client->id)->get();
        $domainConfigs = [];
        $cleanDomain = '';
        $domainConfigs = array();; // single object
        
        foreach ($servers as $server) {
            if ($server->domain) {
                $domains = array_map('trim', explode(',', $server->domain));
                foreach ($domains as $domain) {
                    if ($domain && str_starts_with($domain, '*.')) {
                        $cleanDomain = ltrim($domain, '*.');
                        $domainConfigs[] = [
                            'main_domain' => $domain,
                            'proxy' => $cleanDomain,
                        ];
                    }
                    break 2;
                }
            }
        }
        try {
            $sshService = new \App\Services\SSHService($vps->ip, $vps->username, $vps->password);
            $sshService->connect();

            // Check nginx status
            $nginxStatus = $sshService->checkNginxStatus();
            
            if (!$nginxStatus['installed'] || !$nginxStatus['running']) {
                // Install nginx if not installed or not running
                \Illuminate\Support\Facades\Log::info('Installing nginx on VPS', ['vps_id' => $vps->id, 'ip' => $vps->ip]);
                $sshService->installNginxProxy();
            }

            // Configure nginx with domain configurations
            \Illuminate\Support\Facades\Log::info('Configuring nginx with domain configs', [
                'vps_id' => $vps->id,
                'domain_configs' => $domainConfigs
            ]);
            
            $sshService->configureNginxWithDomainConfigs($domainConfigs);
            
            $sshService->disconnect();
            $line->update([
                'assigned_at' => Carbon::now(),
                'status' => 'monitoring now'
            ]);
            $vps->update([
                'linename' => $line->username,
                'domains' => json_encode($domainConfigs),
                'assigned_at' => Carbon::now()
            ]);
            
        } catch (\Exception $sshException) {
            \Illuminate\Support\Facades\Log::error('SSH/Nginx configuration failed', [
                'vps_id' => $vps->id,
                'error' => $sshException->getMessage()
            ]);
            
            $this->error('Failed to configure nginx: ' . $sshException->getMessage());
            return 1;
        }
        return 0;
    }
}
