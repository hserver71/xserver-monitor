<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use Exception;
use Illuminate\Support\Facades\Log;

class SSHService
{
    private $ssh;
    private $host;
    private $username;
    private $password;

    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->ssh = new SSH2($host);
    }

    /**
     * Connect to the SSH server
     */
    public function connect()
    {
        try {
            if (!$this->ssh->login($this->username, $this->password)) {
                throw new Exception('SSH login failed for ' . $this->username . '@' . $this->host);
            }
            
            Log::info('SSH connection established', [
                'host' => $this->host,
                'username' => $this->username
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('SSH connection failed', [
                'host' => $this->host,
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Execute a command on the remote server
     */
    public function execute($command)
    {
        try {
            if (!$this->ssh->isConnected()) {
                $this->connect();
            }

            $output = $this->ssh->exec($command);
            $exitCode = $this->ssh->getExitStatus();
            
            Log::info('SSH command executed', [
                'host' => $this->host,
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output
            ]);

            return [
                'success' => $exitCode === 0,
                'output' => $output,
                'exit_code' => $exitCode
            ];
        } catch (Exception $e) {
            Log::error('SSH command execution failed', [
                'host' => $this->host,
                'command' => $command,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Install nginx as a proxy
     */
    public function installNginxProxy()
    {
        try {
            $commands = [
                // Update package list
                'apt update',
                
                // Install nginx
                'apt install -y nginx',
                
                // Start and enable nginx
                'systemctl start nginx',
                'systemctl enable nginx',
                
                // Create basic proxy configuration
                'cat > /etc/nginx/sites-available/proxy << "EOF"
server {
    listen 80;
    server_name _;
    
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF',
                
                // Enable the proxy site
                'ln -sf /etc/nginx/sites-available/proxy /etc/nginx/sites-enabled/',
                
                // Remove default nginx site
                'rm -f /etc/nginx/sites-enabled/default',
                
                // Test nginx configuration
                'nginx -t',
                
                // Reload nginx
                'systemctl reload nginx'
            ];

            $results = [];
            foreach ($commands as $command) {
                $result = $this->execute($command);
                $results[] = [
                    'command' => $command,
                    'success' => $result['success'],
                    'output' => $result['output']
                ];
                
                if (!$result['success']) {
                    throw new Exception('Command failed: ' . $command . ' - ' . $result['output']);
                }
            }

            Log::info('Nginx proxy installation completed', [
                'host' => $this->host,
                'results' => $results
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('Nginx proxy installation failed', [
                'host' => $this->host,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if nginx is installed and running
     */
    public function checkNginxStatus()
    {
        try {
            $status = $this->execute('systemctl is-active nginx');
            $enabled = $this->execute('systemctl is-enabled nginx');
            
            return [
                'installed' => true,
                'running' => $status['success'] && trim($status['output']) === 'active',
                'enabled' => $enabled['success'] && trim($enabled['output']) === 'enabled'
            ];
        } catch (Exception $e) {
            return [
                'installed' => false,
                'running' => false,
                'enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get server information
     */
    public function getServerInfo()
    {
        try {
            $commands = [
                'uname -a' => 'system_info',
                'cat /etc/os-release' => 'os_info',
                'free -h' => 'memory_info',
                'df -h' => 'disk_info'
            ];

            $info = [];
            foreach ($commands as $command => $key) {
                $result = $this->execute($command);
                $info[$key] = $result['success'] ? trim($result['output']) : 'Failed to get info';
            }

            return $info;
        } catch (Exception $e) {
            Log::error('Failed to get server info', [
                'host' => $this->host,
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Close SSH connection
     */
    public function disconnect()
    {
        if ($this->ssh && $this->ssh->isConnected()) {
            $this->ssh->disconnect();
            Log::info('SSH connection closed', ['host' => $this->host]);
        }
    }

    /**
     * Destructor to ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Configure nginx with domain configuration array
     * @param array $domainConfigs Array of configs with main_domain and proxy
     */
    public function configureNginxWithDomainConfigs($domainConfigs)
    {
        try {
            $nginxConfig = '';
            
            foreach ($domainConfigs as $config) {
                $mainDomain = $config['main_domain'];
                $proxyTarget = $config['proxy'];
                
                $nginxConfig .= "server {
    listen 80;
    server_name {$mainDomain};
    
    location / {
        proxy_pass http://{$proxyTarget};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-Host \$host;
        proxy_set_header X-Forwarded-Port \$server_port;
    }
}
";
            }

            $commands = [
                // Create nginx configuration file
                "cat > /etc/nginx/sites-available/domain-configs << 'EOF'\n{$nginxConfig}\nEOF",
                
                // Enable the site
                'ln -sf /etc/nginx/sites-available/domain-configs /etc/nginx/sites-enabled/',
                
                // Test nginx configuration
                'nginx -t',
                
                // Reload nginx
                'systemctl reload nginx'
            ];

            $results = [];
            foreach ($commands as $command) {
                $result = $this->execute($command);
                $results[] = [
                    'command' => $command,
                    'success' => $result['success'],
                    'output' => $result['output']
                ];
                
                if (!$result['success']) {
                    throw new Exception('Command failed: ' . $command . ' - ' . $result['output']);
                }
            }

            Log::info('Nginx domain configurations completed', [
                'host' => $this->host,
                'domain_configs' => $domainConfigs,
                'results' => $results
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('Nginx domain configurations failed', [
                'host' => $this->host,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
