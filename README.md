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

### Using Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd laravel_test
   ```

2. **Install dependencies**
   ```bash
   docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install
   ```

3. **Set up environment variables**
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
   ```

4. **Start Docker containers**
   ```bash
   docker compose up -d
   ```

5. **Generate application key**
   ```bash
   docker compose exec laravel.test php artisan key:generate
   ```

6. **Run migrations**
   ```bash
   docker compose exec laravel.test php artisan migrate
   ```

7. **Seed the database** (optional, creates sample data)
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

### Manual Installation

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure database** in `.env`

4. **Run migrations**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start queue worker** (for notifications)
   ```bash
   php artisan queue:work
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

### Queue Worker
Start the queue worker to process notifications:
```bash
docker compose exec laravel.test php artisan queue:work
```

Or manually:
```bash
php artisan queue:work
```

### Mail Testing
The project uses Mailpit for local email testing (accessible at `http://localhost:8025` when using Docker).

### Code Quality
- PSR-12 coding standards
- PHPDoc documentation for all methods
- Type hints for all method parameters and return types

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
