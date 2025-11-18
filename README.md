# Event Booking System

A comprehensive REST API for event booking management built with Laravel 12, featuring role-based access control, ticket management, booking system, and payment processing simulation.

## Features

- **User Authentication & Authorization**: Sanctum-based authentication with role-based access control (Admin, Organizer, Customer)
- **Event Management**: Create, update, delete, and search events with pagination and filtering
- **Ticket Management**: Manage ticket types, prices, and quantities for events
- **Booking System**: Book tickets with double-booking prevention and availability checks
- **Payment Processing**: Simulated payment processing with success/failure handling and refunds
- **Notifications**: Queue-based email notifications for booking confirmations
- **Caching**: Redis-based caching for frequently accessed event lists
- **Comprehensive Testing**: Feature and unit tests with 85%+ coverage

## Requirements

- PHP 8.2+
- Composer
- Docker & Docker Compose (recommended)
- MySQL 8.0+ (or SQLite for local development)
- Redis (for caching and queues)

## Installation

### Quick Start (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd laravel_test
   ```

2. **Set up environment variables** (if not already done)
   ```bash
   cp .env.example .env
   ```
   
   Update the `.env` file with your database and Redis configuration:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=sail
   DB_PASSWORD=password

   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis
   CACHE_STORE=redis

   WWWUSER=1000
   WWWGROUP=1000
   MYSQL_EXTRA_OPTIONS=
   ```

3. **Start the project** (builds, migrates, and seeds automatically)
   ```bash
   ./start.sh
   ```

   This script will:
   - Build Docker containers
   - Start all services (MySQL, Redis, phpMyAdmin)
   - Generate application key
   - Run database migrations
   - Seed the database with sample data
   - Clear all caches

4. **Stop the project**
   ```bash
   ./stop.sh
   ```

5. **Clean the project** (remove caches, logs, temporary files)
   ```bash
   ./clean.sh
   ```
   
   This script will:
   - Clear all Laravel caches (config, cache, route, view, event)
   - Remove log files
   - Remove compiled cache files
   - Remove framework cache files (sessions, views)
   - Remove PHPUnit cache files
   - Remove frontend build files
   - Optionally remove `node_modules`, `vendor`, and lock files (with confirmation)

### Manual Installation

If you prefer to set up manually:

1. **Install dependencies**
   ```bash
   docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install
   ```

2. **Start Docker containers**
   ```bash
   docker compose up -d --build
   ```

3. **Generate application key**
   ```bash
   docker compose exec laravel.test php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   docker compose exec laravel.test php artisan migrate
   ```

5. **Seed the database** (optional, creates sample data)
   ```bash
   docker compose exec laravel.test php artisan db:seed
   ```

   This will create:
   - 2 administrators
   - 3 organizers
   - 10 customers
   - 5 events
   - 15 tickets
   - 20 bookings

### Installation Without Docker

If you prefer to install and run the project without Docker:

#### Prerequisites

Install the following on your system:

- **PHP 8.2+** with extensions:
  - `php-cli`
  - `php-fpm` (or PHP built-in server)
  - `php-mysql` (PDO MySQL extension)
  - `php-redis` (for Redis cache and queues)
  - `php-mbstring`
  - `php-xml`
  - `php-bcmath`
  - `php-curl`
  - `php-zip`
  - `php-openssl`

- **Composer** (PHP package manager)
- **MySQL 8.0+** or MariaDB
- **Redis** (for caching and queues)
- **Node.js** and **npm** (optional, for frontend assets)

#### Installation Steps

1. **Install PHP dependencies**
   ```bash
   composer install
   ```

2. **Create environment file**
   ```bash
   cp .env.example .env
   ```

3. **Configure `.env` file**
   
   Update the following variables in `.env`:
   ```env
   APP_NAME="Event Booking System"
   APP_URL=http://localhost:8000
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=root
   DB_PASSWORD=your_password
   
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Create database**
   
   Create a MySQL database:
   ```sql
   CREATE DATABASE laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed the database** (optional, creates sample data)
   ```bash
   php artisan db:seed
   ```
   
   This will create:
   - 2 administrators (password: `password`)
   - 3 organizers
   - 10 customers
   - 5 events
   - 15 tickets
   - 20 bookings

8. **Clear and cache configuration**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

#### Running the Application

1. **Start the development server**
   ```bash
   php artisan serve
   ```
   The application will be available at `http://localhost:8000`

2. **Start the queue worker** (required for notifications)
   
   Open a new terminal and run:
   ```bash
   php artisan queue:work
   ```
   
   Or use supervisor/PM2 for production:
   ```bash
   php artisan queue:work --daemon --tries=3
   ```

#### Testing Without Docker

Run tests with:
```bash
php artisan test
```

For coverage report (requires Xdebug or PCOV):
```bash
php artisan test --coverage
```

#### Production Setup

For production deployment:

1. **Optimize application**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

2. **Set up a web server** (Nginx/Apache)
   - Point document root to `public/` directory
   - Configure proper PHP-FPM settings

3. **Set up queue worker** (Supervisor/PM2 recommended)
   ```bash
   php artisan queue:work --daemon --tries=3 --timeout=90
   ```

4. **Set proper file permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

## API Documentation

### Base URL
```
http://localhost/api
```
(Or your configured `APP_URL/api`)

### Authentication

All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

### Import Postman Collection

A complete Postman collection is available in the repository:
- `Event_Booking_System_API.postman_collection.json`

Import this file into Postman to get all API endpoints pre-configured.

### API Endpoints

#### Authentication
- `POST /api/register` - Register a new user (returns token)
- `POST /api/login` - Login user (returns token)
- `POST /api/logout` - Logout user (revokes token)
- `GET /api/me` - Get authenticated user information

#### Events
- `GET /api/events` - List events (with pagination, search, date filtering)
- `GET /api/events/{id}` - Get single event
- `POST /api/events` - Create event (Admin/Organizer only)
- `PUT /api/events/{id}` - Update event (Admin/Organizer only)
- `DELETE /api/events/{id}` - Delete event (Admin/Organizer only)

**Query Parameters for List Events:**
- `per_page` - Items per page (default: 15)
- `page` - Page number
- `search` - Search by title or location
- `date_from` - Filter events from date (YYYY-MM-DD)
- `date_to` - Filter events until date (YYYY-MM-DD)
- `sort_by` - Sort field (default: date)
- `sort_order` - Sort order: asc or desc (default: asc)

#### Tickets
- `GET /api/tickets` - List tickets (with filtering)
- `GET /api/tickets/{id}` - Get single ticket
- `POST /api/tickets` - Create ticket (Admin/Organizer only)
- `PUT /api/tickets/{id}` - Update ticket (Admin/Organizer only)
- `DELETE /api/tickets/{id}` - Delete ticket (Admin/Organizer only)

**Query Parameters for List Tickets:**
- `per_page` - Items per page (default: 15)
- `page` - Page number
- `event_id` - Filter by event ID
- `type` - Filter by ticket type
- `available_only` - Show only tickets with available quantity > 0 (true/false)

#### Bookings
- `GET /api/bookings` - List bookings (role-based filtering)
- `GET /api/bookings/{id}` - Get single booking
- `POST /api/bookings` - Create booking (Customer only)
- `PUT /api/bookings/{id}` - Update booking
- `POST /api/bookings/{id}/cancel` - Cancel booking
- `DELETE /api/bookings/{id}` - Delete booking (Admin only)

**Query Parameters for List Bookings:**
- `per_page` - Items per page (default: 15)
- `page` - Page number
- `status` - Filter by status (pending, confirmed, cancelled)

## Role-Based Access Control

### Admin
- Full access to all events, tickets, and bookings
- Can create, update, and delete any resource
- Can view all bookings across all events

### Organizer
- Can create and manage their own events
- Can create and manage tickets for their events
- Can view bookings for their events
- Cannot modify other organizers' events or tickets

### Customer
- Can view events and tickets
- Can create bookings (one active booking per ticket)
- Can view and cancel their own bookings
- Cannot create or modify events or tickets

## Testing

Run the test suite:
```bash
docker compose exec laravel.test php artisan test
```

Or manually:
```bash
php artisan test
```

Run with coverage (requires Xdebug or PCOV):
```bash
php artisan test --coverage
```

### Test Coverage
- **Feature Tests**: Authentication, Events, Tickets, Bookings
- **Unit Tests**: Models (relationships, accessors, helpers), Policies, PaymentService
- **Coverage Goal**: 85%+ across controllers and services
- **Current Status**: 127 tests passing (362 assertions)

## Database Schema

### Users
- `id`, `name`, `email`, `password`, `phone` (nullable), `role` (admin/organizer/customer)
- Default role: `customer`

### Events
- `id`, `title`, `description` (nullable), `date`, `location`, `created_by` (foreign key to users)

### Tickets
- `id`, `type`, `price`, `quantity`, `event_id` (foreign key to events)
- Accessor: `available_quantity` (calculates available tickets)

### Bookings
- `id`, `user_id`, `ticket_id`, `quantity`, `status` (pending/confirmed/cancelled)
- Accessor: `total_amount` (price × quantity)

### Payments
- `id`, `booking_id` (unique foreign key), `amount`, `status` (success/failed/refunded)

## Key Features Implementation

### Double Booking Prevention
Middleware (`PreventDoubleBooking`) prevents users from creating multiple active bookings for the same ticket.

### Payment Processing
`PaymentService` simulates payment processing:
- Automatic payment creation when booking is confirmed
- Automatic refund when booking is cancelled
- Manual payment creation via `createConfirmedPayment()` for admin/organizer confirmations

### Notifications
Queue-based email notifications sent when:
- Booking status changes to "confirmed"

### Caching
- Event lists are cached for 60 minutes
- Cache is automatically invalidated when events or tickets are created/updated/deleted

### Database Transactions
All booking and payment operations use database transactions with row-level locking to prevent race conditions and ensure data integrity.

## Development

### Service URLs
After starting the project with `./start.sh`, you can access:
- **Application**: http://localhost
- **API**: http://localhost/api
- **phpMyAdmin**: http://localhost:8080

### Queue Worker
Start the queue worker to process notifications:
```bash
docker compose exec laravel.test php artisan queue:work
```

Or manually:
```bash
php artisan queue:work
```

### Code Quality

The project uses Laravel Pint and Larastan (PHPStan) for code quality:

#### Laravel Pint (Code Formatter)

Laravel Pint is a zero-dependency PHP code style fixer built on top of PHP-CS-Fixer.

**Format code:**
```bash
# Format all code (auto-fix)
composer format

# Check code style without fixing
composer format:test
```

**Using Docker:**
```bash
docker compose exec laravel.test vendor/bin/pint
docker compose exec laravel.test vendor/bin/pint --test
```

#### Larastan (Static Analysis)

Larastan is a static analysis tool that helps find bugs in your code before they happen.

**Run static analysis:**
```bash
composer analyse
```

**Using Docker:**
```bash
docker compose exec laravel.test vendor/bin/phpstan analyse
```

#### Lint (Format + Analyse)

Run both formatting check and static analysis:
```bash
composer lint
```

#### Configuration

- **Pint**: Configuration in `pint.json` (uses PSR-12 preset)
- **PHPStan**: Configuration in `phpstan.neon` (level 5)
  - Analyzes: `app/`, `config/`, `database/`, `routes/`
  - Excludes: `bootstrap/`, `storage/`, `vendor/`

#### Standards

- PSR-12 coding standards (enforced by Pint)
- PHPDoc documentation for all methods
- Type hints for all method parameters and return types
- Level 5 static analysis (medium strictness)

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── BookingController.php
│   │       ├── EventController.php
│   │       ├── TicketController.php
│   │       └── UserController.php
│   ├── Middleware/
│   │   ├── EnsureRole.php
│   │   └── PreventDoubleBooking.php
│   ├── Requests/
│   │   └── Auth/
│   │       ├── LoginRequest.php
│   │       └── RegisterRequest.php
│   └── Resources/
│       ├── BookingResource.php
│       ├── EventResource.php
│       ├── PaymentResource.php
│       ├── TicketResource.php
│       └── UserResource.php
├── Models/
│   ├── Booking.php
│   ├── Event.php
│   ├── Payment.php
│   ├── Ticket.php
│   └── User.php
├── Notifications/
│   └── BookingConfirmedNotification.php
├── Policies/
│   ├── BookingPolicy.php
│   ├── EventPolicy.php
│   └── TicketPolicy.php
├── Services/
│   └── PaymentService.php
└── Traits/
    ├── ApiResponseTrait.php
    └── CommonQueryScopes.php
```

## Skills Evaluated

This project demonstrates skills in:

✅ **Database Design** - Normalized schema with proper foreign keys and indexes  
✅ **Eloquent Models & Relationships** - 5 models with 10+ relationships, accessors, and helpers  
✅ **REST API Development** - Complete CRUD APIs with pagination, filtering, and search  
✅ **Authentication & Authorization** - Sanctum auth with role-based access control  
✅ **Middleware, Services, Traits** - Custom middleware, service layer, and reusable traits  
✅ **Queues & Notifications** - Redis queues with asynchronous email notifications  
✅ **Caching & Optimization** - Redis caching with automatic invalidation  

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
