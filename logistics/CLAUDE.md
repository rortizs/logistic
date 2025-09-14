# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a complete Laravel 12 logistics management system with Filament v3 admin panel. Features include truck fleet tracking, driver management, routes, trips, and maintenance scheduling. The application uses MySQL database with stored procedures for complex operations.

**Admin Panel Access:**
- URL: `/admin`
- Email: admin@logistics.com
- Password: password123

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

### Controllers & Resources
- `CamionController`: CRUD with maintenance verification and status management
- `PilotoController`: CRUD with availability checking and performance analytics
- `RutaController`: CRUD with travel time calculations and route optimization
- `ViajeController`: Enhanced with trip lifecycle management and stored procedure integration
- `MantenimientoController`: CRUD with scheduling and overdue maintenance tracking

### Filament Resources
- Complete admin panel with 5 comprehensive resources
- Advanced forms with validation and smart field interactions
- Dynamic tables with color-coded badges and status indicators
- Custom actions for business workflows (start/complete trips, schedule maintenance)
- Dashboard widgets with key metrics and statistics

### Models
- Complete Laravel Eloquent models with relationships and business logic
- Camion: Truck management with mileage tracking and maintenance alerts
- Piloto: Driver management with availability and experience tracking
- Ruta: Route management with efficiency calculations
- Viaje: Trip management with status tracking and stored procedure integration
- Mantenimiento: Maintenance scheduling and completion tracking

### Frontend Stack
- **Filament v3**: Modern admin panel with comprehensive CRUD operations
- **Vite**: Asset bundling and hot-reload development
- **TailwindCSS**: Utility-first CSS framework
- **Laravel Blade**: Server-side templating
- **Livewire**: For reactive components in Filament

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