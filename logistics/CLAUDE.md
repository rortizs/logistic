# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a Laravel 12 logistics management system with truck fleet tracking, driver management, routes, trips, and maintenance scheduling. The application uses MySQL database with stored procedures for complex operations.

## Development Commands

### Environment Setup
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate
```

### Development Server
```bash
# Run all development services (Laravel server, queue worker, logs, Vite)
composer run dev

# Or run individual services:
php artisan serve           # Laravel development server
php artisan queue:work      # Queue worker
php artisan pail           # Real-time logs
npm run dev                # Vite asset compilation
```

### Testing
```bash
# Run all tests
composer run test
# Equivalent to: php artisan test

# Run PHPUnit directly
vendor/bin/phpunit

# Run specific test suites
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Feature
```

### Code Quality
```bash
# Laravel Pint (code formatting)
vendor/bin/pint

# Clear configuration cache
php artisan config:clear
```

### Asset Building
```bash
# Development build with Vite
npm run dev

# Production build
npm run build
```

## Database Architecture

### Core Tables
- `camiones` (trucks): Vehicle fleet management with maintenance tracking
- `pilotos` (drivers): Driver information (schema incomplete)
- `rutas` (routes): Route definitions (schema incomplete) 
- `viajes` (trips): Trip records linking trucks, drivers, and routes
- `mantemientos` (maintenance): Maintenance scheduling and tracking

### Key Features
- **Stored Procedures**: Uses `ActualizarKilometrajeCamion` procedure to update truck mileage after trip completion
- **Mileage Tracking**: Automatic calculation of kilometers traveled per trip
- **Maintenance Intervals**: Configurable maintenance scheduling based on mileage

## Application Structure

### Controllers
- `ViajeController`: Handles trip completion logic with stored procedure calls for mileage updates

### Models
- Standard Laravel Eloquent models (User model present)
- Missing models for core entities (Camion, Piloto, Ruta, Viaje, Mantenimiento)

### Frontend Stack
- **Vite**: Asset bundling and hot-reload development
- **TailwindCSS**: Utility-first CSS framework
- **Laravel Blade**: Server-side templating

### Key Patterns
- Uses anonymous migration classes (modern Laravel pattern)
- Database operations combine Eloquent ORM with raw stored procedure calls
- Enum fields for status management (e.g., truck status: 'Activo', 'En Taller', 'Inactivo')

## Common Development Tasks

### Adding New Migrations
```bash
php artisan make:migration create_table_name
php artisan migrate
```

### Creating Models/Controllers
```bash
php artisan make:model ModelName -mc  # Model with migration and controller
php artisan make:controller ControllerName
```

### Database Operations
- The app uses both Eloquent ORM and raw SQL/stored procedures
- Critical business logic (like mileage updates) uses stored procedures
- Remember to handle both database approaches when making changes