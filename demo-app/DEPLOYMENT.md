# Server Deployment Guide for Demo App

## Important: Dynamic URLs Configuration

The demo application is already configured to work on any domain/server. All routes use Laravel's dynamic URL helpers.

## Pre-Deployment Checklist

### 1. Environment Configuration

Copy `.env.example` to `.env` and update:

```bash
cd demo-app
cp .env.example .env
```

Update these values in `.env`:

```env
# Update to your server's domain
APP_URL=https://yourdomain.com

# Or for local development
APP_URL=http://localhost:8000

# Database settings
DB_HOST=your_database_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. Run Migrations

```bash
php artisan migrate --force
```

### 4. Clear and Cache Configuration

```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## URL Helpers Used (All Dynamic)

The application uses Laravel's built-in URL helpers throughout:

### ✅ Routes
```blade
{{ route('discount-demo.index') }}           // Generates full URL dynamically
{{ route('discount-demo.user-status', $id) }} // With parameters
```

### ✅ Assets
```blade
{{ asset('css/app.css') }}  // Uses APP_URL from .env
{{ url('/path') }}          // Full URL
```

### ✅ AJAX Calls
```javascript
$.post('{{ route("discount-demo.calculate") }}', ...)  // Dynamic routes
```

## Apache Configuration

If deploying on Apache, create `.htaccess` in `public/` directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

## Nginx Configuration

Example Nginx server block:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/demo-app/public;

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
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Permissions

Set correct permissions:

```bash
cd demo-app
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Common Issues & Solutions

### Issue: "Route not found" or 404 errors

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: URLs pointing to wrong domain

**Solution:**
1. Update `APP_URL` in `.env`
2. Clear config cache:
```bash
php artisan config:clear
php artisan config:cache
```

### Issue: CSRF token mismatch

**Solution:**
1. Check `APP_URL` matches your actual domain (including https/http)
2. Clear session:
```bash
php artisan session:clear
```

### Issue: Assets not loading

**Solution:**
1. Verify `APP_URL` is correct in `.env`
2. Run: `php artisan config:clear`
3. If using CDN (Tailwind, jQuery), ensure server has internet access

## Testing After Deployment

Visit these URLs on your server:

1. **Main Dashboard**: `https://yourdomain.com/discount-demo`
2. **Debug Route**: `https://yourdomain.com/discount-demo/debug`
3. **Audit Trail**: `https://yourdomain.com/discount-demo/audits`

All routes should work dynamically based on your domain.

## Production Optimization

For production servers:

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set environment to production
# In .env:
APP_ENV=production
APP_DEBUG=false
```

## Security Recommendations

1. Set `APP_DEBUG=false` in production
2. Use HTTPS (update `APP_URL` to https://)
3. Set strong `DB_PASSWORD`
4. Regenerate `APP_KEY` on server
5. Restrict database access to localhost if possible

---

**Note**: The application is fully portable and will work on any domain once `APP_URL` is set correctly in `.env`.
