# Mallow Store

Mallow Store is a Laravel application for managing products, customer records, inventory, and customer orders. It includes a browser-based store administration interface and a small JSON API for order entry, customer lookup, order history, and low-stock queries.

## Features

- Create, view, update, and delete products, including unit price, tax rate, and stock quantity.
- Prevent deletion of products already referenced by order items.
- Browse, view, update, and soft-delete customers. Customer email addresses are unique.
- Create orders with one or more products from the billing page or JSON API.
- Calculate line totals, subtotal, tax, and grand total from the stored product prices and tax rates.
- Validate inventory and deduct stock inside a database transaction. Product rows are locked while an order is processed; failed orders roll back stock and customer/order changes.
- View paginated orders, order details, and customer order history.
- Look up customers by email and request products below a configurable stock threshold.
- Queue an order-confirmation job. The current job simulates email delivery by writing a confirmation event to the Laravel log; it does not send email.
- Seed ten example products for local development.

## Technology

- PHP 8.2 or later and Laravel 12
- SQLite by default; MySQL is also configured
- Laravel Blade, Bootstrap 5.3 (loaded from a CDN), custom CSS, and JavaScript
- Vite 7 and Tailwind CSS 4 packages for frontend asset development
- PHPUnit 11 through Laravel's test runner

## Requirements

- PHP 8.2+ with the extensions required by Laravel and the selected database driver
- Composer
- Node.js and npm, for frontend dependency installation and asset builds
- SQLite (the default local database) or a configured MySQL database

## Installation and Setup

From the project directory:

```bash
composer install
cp .env.example .env
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
```

The PHP command creates the SQLite file expected by the default configuration. In PowerShell, `Copy-Item .env.example .env` can be used instead of `cp`.

The default `.env.example` uses SQLite and the database queue. `php artisan migrate --seed` creates the schema, a sample user, and ten products. To seed or refresh only the product catalog later, run:

```bash
php artisan db:seed --class=ProductSeeder
```

The product seeder upserts by product code, so running it repeatedly does not create duplicate products.

### Using MySQL

Create an empty database, then update the database variables in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mallow_store
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Then run `php artisan migrate --seed`.

### Start the Application

Run the web server and queue worker in separate terminals:

```bash
php artisan serve
```

```bash
php artisan queue:listen --tries=1 --timeout=0
```

Open `http://127.0.0.1:8000`. The application navigation provides the new-order, products, customers, and orders pages. The queue worker is needed to process confirmation jobs when `QUEUE_CONNECTION=database` (the default in `.env.example`).

For local development, `composer run dev` is also configured to start the Laravel server, queue listener, log viewer, and Vite development server together. It requires the Composer and npm dependencies to be installed first.

Confirmation-job messages are written through Laravel's logging system. With the default `LOG_CHANNEL=stack` / `LOG_STACK=single`, inspect `storage/logs/laravel.log`.

## Data Model

| Table | Main fields and behavior |
| --- | --- |
| `products` | `name`, unique `code`, `unit_price` (`decimal(10,2)`), `tax_percentage` (`decimal(5,2)`), and integer `stock_on_hand` (defaults to `0`). |
| `customers` | `name`, unique `email`, timestamps, and soft deletes. |
| `orders` | Customer reference, `subtotal`, `tax_amount`, and `grand_total` (decimal amounts). Deleting a customer record permanently cascades to its orders; normal customer deletion is soft deletion. |
| `order_items` | Order and product references, `quantity`, unit-price and tax snapshots, line subtotal, tax amount, and total. Products referenced by an order item cannot be deleted. |

Order totals are calculated using each product's current `unit_price` and `tax_percentage` when the order is created. Those values are also copied to the order item so the order retains the applied price and tax.

## Web Interface

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/` | Create an order; only products with stock are selectable. |
| `POST` | `/orders` | Submit the browser order form. |
| `GET` | `/orders` | Browse orders, paginated ten per page. |
| `GET` | `/orders/{order}` | View an order, customer, and line items. |
| `GET` | `/products` | Browse and manage products. |
| `POST` | `/products` | Create a product. |
| `PUT` | `/products/{product}` | Update a product. |
| `DELETE` | `/products/{product}` | Delete an unused product. |
| `GET` | `/customers` | Browse customers. |
| `GET` | `/customers/{customer}` | View customer details and order history. |
| `GET` | `/customers/{customer}/edit` | Open the customer edit form. |
| `PUT` | `/customers/{customer}` | Update a customer. |
| `DELETE` | `/customers/{customer}` | Soft-delete a customer. |

## JSON API

The routes below are registered under the `/api` prefix. Send `Accept: application/json` (or use a JSON request) to receive JSON validation and order responses.

### Create an Order

`POST /api/orders`

Request fields:

| Field | Requirements |
| --- | --- |
| `customer_name` | Required string, maximum 255 characters. |
| `customer_email` | Required valid email address. A customer is created if that email does not already exist. |
| `products` | Required array containing at least one item. |
| `products[*].product_id` | Required ID of an existing product. |
| `products[*].quantity` | Required integer greater than or equal to 1. |

Example request:

```json
{
	"customer_name": "Asha Patel",
	"customer_email": "asha@example.com",
	"products": [
		{ "product_id": 1, "quantity": 2 },
		{ "product_id": 3, "quantity": 1 }
	]
}
```

Success returns HTTP `201` with a `message` and an `order` object containing the calculated totals, customer, and order items. Insufficient stock returns HTTP `422` with a `message`; invalid request fields return Laravel's JSON validation response with HTTP `422`.

### Look Up a Customer

`GET /api/customers/lookup?email=asha%40example.com`

The `email` query parameter is required and must be a valid email. A successful response is:

```json
{
	"exists": true,
	"name": "Asha Patel"
}
```

For an email that is not in the customer table, the response is `{"exists": false, "name": null}`. Invalid email input returns HTTP `422`.

### Read Customer Order History

`GET /api/customers/{email}/orders`

For example: `/api/customers/asha%40example.com/orders`. A successful response contains `customer` and `orders` objects, including order items and their products. If the customer does not exist, the endpoint returns HTTP `404` with `{"message":"Customer not found"}`.

### Read Low-Stock Products

`GET /api/products/low-stock?threshold=10`

The `threshold` query parameter is optional and defaults to `10`. Products are included when `stock_on_hand` is strictly less than the threshold. The response is a JSON object with a `products` array.

### Product Index Route

`GET /api/products` is currently registered but calls the same controller action as the browser product page, so it returns rendered HTML rather than a JSON product collection. Use `/api/products/low-stock` for the currently implemented JSON product response.

## Testing

Run the complete test suite:

```bash
php artisan test
```

Or use the Composer test script, which clears Laravel's configuration cache before running the suite:

```bash
composer test
```

Run an individual feature test file or filter by test name:

```bash
php artisan test tests/Feature/OrderTest.php
php artisan test --filter=customer_lookup
```

The PHPUnit configuration uses an in-memory SQLite database, so the tests do not use the development database. Coverage includes order totals and stock behavior, transaction rollback, product and customer management, API validation and lookup, low-stock results, the confirmation job, and product seeding.

## Operational Notes

- Application routes currently have no authentication middleware. Add authentication and authorization before exposing the management interface or API to untrusted users.
- The confirmation job currently logs a simulated email event; configure a real mail transport and update the job if actual email delivery is required.
- `QUEUE_CONNECTION=database` requires a running queue worker to process jobs. Set `QUEUE_CONNECTION=sync` for local synchronous execution if desired.