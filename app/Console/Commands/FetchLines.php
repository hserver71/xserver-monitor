<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Line;
use App\Models\Client;
use Illuminate\Support\Facades\Http;

class FetchLines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
                $this->info("Fetching lines for client {$client->name}");
                $lines = $this->fetchLines($client);
                $this->info("Fetched {$lines->count()} lines for client {$client->name}");
        }
        return 0;
    }
    private function fetchLines($client) {
        try {
            // Fetch lines from client's domain
            $url = 'http://' . $client->ip . '/block_actions.php?action=line';
            $response = Http::get($url);
            
            if ($response->successful()) {
                $apiLines = $response->json();
                $storedCount = 0;
                $updatedCount = 0;
                
                foreach ($apiLines as $apiLine) {
                    // Check if line already exists
                    $existingLine = Line::where('username', $apiLine['username'])
                                      ->where('client_id', $client->id)
                                      ->first();
                    
                    if ($existingLine) {
                        // Update existing line
                        $existingLine->update([
                            'status' => $apiLine['status'] ?? 'unknown',
                            'password' => $apiLine['password'] ?? $existingLine->password
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new line
                        Line::create([
                            'username' => $apiLine['username'],
                            'password' => $apiLine['password'],
                            'status' => $apiLine['status'] ?? 'unknown',
                            'client_id' => $client->id
                        ]);
                        $storedCount++;
                    }
                }
                return $storedCount;
            } else {
                $this->error('Failed to fetch lines from API');
            }
        } catch (\Exception $e) {
            $this->error('Error fetching lines: ' . $e->getMessage());
            return 0;
        }
    }
}
