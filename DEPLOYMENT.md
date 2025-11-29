# Production Deployment Guide

## Prerequisites
- PHP 8.2+ with extensions: intl, zip, pdo_mysql, mbstring, xml, curl
- MySQL/MariaDB database
- Composer
- Node.js & NPM
- Web server (Apache/Nginx)

## Deployment Steps

### 1. Clone Repository
```bash
git clone https://github.com/Karim129/APP_test.git
cd APP_test
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
php artisan migrate --force
```

### 5. Create Admin User
```bash
php artisan tinker
```
Then run:
```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@yourdomain.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'admin'
]);
exit
```

### 6. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 7. Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8. Configure Web Server

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/APP_test/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration (.htaccess already included)
Enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 9. SSL Certificate (Recommended)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### 10. Queue Worker (Optional but Recommended)
Create supervisor config `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/APP_test/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/APP_test/storage/logs/worker.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Post-Deployment

### Access Admin Dashboard
Visit: `https://yourdomain.com/admin`
- Email: admin@yourdomain.com
- Password: (the one you set)

### Regular Maintenance
```bash
# Clear caches after updates
php artisan optimize:clear

# Re-optimize
php artisan optimize

# Run migrations
php artisan migrate --force
```

## Security Checklist
- ✅ Set `APP_DEBUG=false` in production
- ✅ Use strong database passwords
- ✅ Enable SSL/HTTPS
- ✅ Set proper file permissions
- ✅ Keep dependencies updated
- ✅ Enable firewall
- ✅ Regular backups

## Troubleshooting

### Clear all caches
```bash
php artisan optimize:clear
```

### Fix permissions
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### View logs
```bash
tail -f storage/logs/laravel.log
```
