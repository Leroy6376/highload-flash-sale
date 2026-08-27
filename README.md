<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Docker

Create a local Docker configuration and set a unique application key:

```bash
cp .env.docker.example .env.docker
make key
```

Copy the printed `base64:` value to `APP_KEY` in `.env.docker`, then start the
FPM profile:

```bash
make up
```

The development application is available at `http://localhost:8080`, Vite HMR
at `http://localhost:5173`, and PostgreSQL for an IDE at `localhost:5432`.
Run migrations with:

```bash
make migrate
```

Use `make help` for all commands. Development uses bind-mounted sources, Vite
HMR, Composer dev dependencies, and Xdebug. Xdebug is enabled in the dev image
but starts an IDE session only with a browser trigger/cookie or
`XDEBUG_SESSION=1` for CLI. Configure the IDE to listen on port `9003` and map
`/var/www` to the project root.

### Laravel Boost on Windows + WSL

Laravel Boost is configured to run in the project's Docker PHP container. This
ensures that every MCP client uses PHP 8.5 and PostgreSQL rather than a locally
installed PHP version.

On a new computer:

1. Install Docker Desktop and enable its WSL integration for the distribution
   that contains the project.
2. Clone the repository into the WSL filesystem and open that directory in
   PhpStorm.
3. Create `.env.docker` and set an application key as described above, then run
   `make up`.
4. Run `make boost-check`. It must report PHP 8.5 and `Database ... pgsql`.
5. Restart the MCP client (for example, Codex or Junie) after it reads the
   repository's shared MCP configuration.

In PhpStorm, configure the PHP CLI interpreter as Docker Compose service `php`
using the `docker-compose exec` lifecycle. This makes IDE tools, Artisan, tests,
and debugging use the same PHP container as Laravel Boost.

For an immutable local production-like stack, run `make prod-up`.

PostgreSQL data is stored in the `postgres-data` Docker volume. Future services
such as RoadRunner/Octane, Redis, and RabbitMQ can be added as separate Compose
profiles without changing the FPM stack.

Docker images are separated by responsibility: `docker/php/Dockerfile` builds
the PHP-FPM application (including the `app-dev` target with Xdebug), while
`docker/nginx/Dockerfile` builds Nginx from the PHP image's `public` directory.
This lets each runtime gain its own configuration and extensions independently.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
