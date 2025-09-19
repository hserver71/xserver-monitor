<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use App\Models\Server;

class FetchServers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:fetch-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'client:fetch-servers';

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
        $clients = Client::all();
        foreach ($clients as $client) {
            $this->info("Fetching servers for client {$client->name}");
            $servers = $this->fetchServers($client);
            $this->info("Fetched {$servers->count()} servers for client {$client->name}");
        }
        return 0;
    }
    private function fetchServers($client) {
        $url = "http://" . $client->ip . "/block_actions.php?action=server";
        $response = Http::get($url);
        if ($response->successful()) {
            $servers = $response->json();
            foreach ($servers as $server) {
                $this->info("Fetching servers for client {$client->name}");
                $server = Server::where('ip', $server['ip'])->first();
                if ($server) {
                    $server->update([
                        'name' => $server['name'],
                        'ip' => $server['ip'],
                        'client_id' => $client->id
                    ]);
                }
                $this->info("Fetched {$servers->count()} servers for client {$client->name}");
            }
            return $servers;
        } else {
            $this->error('Failed to fetch servers from API');
            return 0;
        }
    }
}
