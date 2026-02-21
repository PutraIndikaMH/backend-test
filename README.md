# Laravel 12 E-Commerce API

A simple REST API for an e-commerce system built with Laravel 12 and Sanctum.

---

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- SQLite (default Laravel 12 database)
- Pest (Testing)

---

## Requirements

- PHP >= 8.2
- Composer
- SQLite (bundled with PHP, no separate installation needed)

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/PutraIndikaMH/backend-test.git
cd backend-test

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate
```

Verify `.env` contains:

```env
DB_CONNECTION=sqlite
```

```bash
# 5. Run migrations and seed the database
php artisan migrate:fresh --seed

# 6. Start the development server
php artisan serve
```

The API will be available at `http://localhost:8000`.

---

## Default Seeded Accounts

| Role  | Email             | Password |
|-------|-------------------|----------|
| admin | admin@example.com | password |
| user  | user@example.com  | password |

5 active products and 2 inactive products are seeded automatically.

---

## API Endpoints

### Auth

| Method | Endpoint     | Access        |
|--------|--------------|---------------|
| POST   | /api/login   | Public        |
| POST   | /api/logout  | Authenticated |

### Products

| Method | Endpoint           | Access     |
|--------|--------------------|------------|
| GET    | /api/products      | Public     |
| GET    | /api/products/{id} | Public     |
| POST   | /api/products      | Admin only |
| PUT    | /api/products/{id} | Admin only |
| DELETE | /api/products/{id} | Admin only |

### Orders

| Method | Endpoint         | Access     |
|--------|------------------|------------|
| POST   | /api/orders      | Public     |
| GET    | /api/orders      | Admin only |
| GET    | /api/orders/{id} | Admin only |

---

## Response Format

All responses return consistent JSON.

```json
// Success
{ "data": { ... } }

// Error
{ "message": "Validation failed", "errors": { ... } }
```

---

## Running Tests

```bash
php artisan test
```

24 tests, 130 assertions across 4 feature test files:

| File                      | Tests |
|---------------------------|-------|
| AdminMiddlewareTest.php   | 3     |
| AuthControllerTest.php    | 5     |
| ProductControllerTest.php | 10    |
| OrderControllerTest.php   | 6     |

---

## Postman Collection

`postman_collection.json` is included in the root of the project.

**Import steps:**
1. Open Postman
2. Click `File -> Import`
3. Select `postman_collection.json`

**Usage:**
1. Run `POST /api/login` first — the `{{token}}` collection variable is automatically set via a Postman test script
2. All authenticated requests will use `{{token}}` automatically
3. The base URL is pre-configured as `http://localhost:8000`

**Request order for full testing:**
1. Login (saves token)
2. Get Products (public)
3. Create Product (admin)
4. Create Order (public)
5. Get Orders (admin)
6. Logout
