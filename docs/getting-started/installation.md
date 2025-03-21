# Installation

This guide will help you install the Titan Framework for your next project.

## Requirements

Before installing Titan, make sure your server meets the following requirements:

- PHP >= 8.2
- BCMath PHP Extension
- Ctype PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension

## Installing Titan

Titan utilizes [Composer](https://getcomposer.org/) to manage its dependencies. Make sure you have Composer installed on your machine before proceeding.

### Via Composer Create-Project

The simplest way to create a new Titan project is via Composer's `create-project` command:

```bash
composer create-project titan/titan your-project-name
```

This command will create a new Titan project in a directory called `your-project-name`, install all of Titan's dependencies, and set up the basic project structure.

### Manual Installation

Alternatively, you can install Titan manually:

1. Clone the GitHub repository:

```bash
git clone https://github.com/titan/framework.git your-project-name
```

2. Navigate to the project directory:

```bash
cd your-project-name
```

3. Install dependencies:

```bash
composer install
```

4. Copy the environment file:

```bash
cp .env.example .env
```

5. Generate an application key:

```bash
php titan key:generate
```

## Configuration

After installing Titan, you need to configure a few things:

### Environment Configuration

The `.env` file contains the environment-specific configuration for your application. This includes database credentials, the application URL, and other configuration options.

```bash
APP_NAME=Titan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Make sure to update the database configuration with your own credentials.

### Web Server Configuration

#### Apache

Titan includes a `.htaccess` file in the `public` directory that handles URL rewriting for Apache web servers. Make sure that `mod_rewrite` is enabled on your Apache server.

If you're using Apache, make sure the `DocumentRoot` is set to the `public` directory:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your-project-name/public

    <Directory "/path/to/your-project-name/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

If you're using Nginx, use the following configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your-project-name/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
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
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Be sure to replace `your-domain.com` and `/path/to/your-project-name` with your actual domain and project path.

## Directory Permissions

After installing Titan, you may need to configure some permissions. The `storage` and `bootstrap/cache` directories should be writable by your web server:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Verification

To verify that your Titan installation is working correctly, start the built-in development server:

```bash
php titan serve
```

Then, open your web browser and navigate to `http://localhost:8000`. You should see the Titan welcome page.

## Next Steps

Now that you have successfully installed Titan, you can:

- [Learn about the directory structure](directory-structure.md)
- [Configure your application](configuration.md)
- [Build your first project](first-project.md)

If you encounter any issues during installation, please check the [troubleshooting guide](troubleshooting.md) or [ask for help in the community](https://titan-framework.com/community).
