# VPS Deployment Guide for Laravel Application

This guide will help you deploy your Laravel application on a VPS so it's accessible from external connections.

## Quick Start (Development Mode)

### Option 1: Simple External Access
```bash
# Start server accessible from external connections
./start-server.sh

# Or manually:
php artisan serve --host=0.0.0.0 --port=8000
```

### Option 2: Production-Ready Scripts
```bash
# Start server in background
./start-production.sh

# Check status
./status-server.sh

# Stop server
./stop-server.sh
```

## Production Deployment with Systemd

### 1. Install the Service
```bash
# Copy service file to systemd directory
sudo cp laravel-server.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable laravel-server

# Start the service
sudo systemctl start laravel-server

# Check status
sudo systemctl status laravel-server
```

### 2. Service Management Commands
```bash
# Start service
sudo systemctl start laravel-server

# Stop service
sudo systemctl stop laravel-server

# Restart service
sudo systemctl restart laravel-server

# View logs
sudo journalctl -u laravel-server -f

# Check status
sudo systemctl status laravel-server
```

## Firewall Configuration

Make sure port 8000 is open in your VPS firewall:

### UFW (Ubuntu/Debian)
```bash
sudo ufw allow 8000
sudo ufw status
```

### iptables
```bash
sudo iptables -A INPUT -p tcp --dport 8000 -j ACCEPT
sudo iptables-save
```

## Access Your Application

Once deployed, your application will be accessible at:
- **External URL**: `http://YOUR_VPS_IP:8000`
- **Local URL**: `http://localhost:8000`

Replace `YOUR_VPS_IP` with your actual VPS IP address.

## Security Considerations

### 1. Environment Configuration
Make sure your `.env` file has proper production settings:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_VPS_IP:8000
```

### 2. Database Configuration
Ensure your database is properly configured for external access if needed.

### 3. SSL/HTTPS (Recommended)
For production use, consider setting up SSL with Let's Encrypt or similar.

## Troubleshooting

### Check if server is running
```bash
# Check process
ps aux | grep "php artisan serve"

# Check port usage
netstat -tlnp | grep :8000

# Check logs
tail -f laravel-server.log
```

### Common Issues
1. **Permission denied**: Make sure the user running the service has access to the project directory
2. **Port already in use**: Check if another service is using port 8000
3. **Firewall blocking**: Ensure port 8000 is open in your VPS firewall

## Alternative: Using Apache/Nginx

For production environments, consider using Apache or Nginx instead of PHP's built-in server:

### Apache Configuration Example
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /home/xserver-monitor/public
    
    <Directory /home/xserver-monitor/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /home/xserver-monitor/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Next Steps

1. Choose your deployment method (scripts or systemd)
2. Configure firewall rules
3. Test external access
4. Set up SSL if needed
5. Configure monitoring and logging
6. Set up backup procedures 