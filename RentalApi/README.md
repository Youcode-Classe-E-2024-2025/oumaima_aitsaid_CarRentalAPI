# Car Rental API

A robust REST API built with Laravel for managing car rentals, user authentication, and payments.

## Features

- User authentication with Laravel Sanctum
- Car management (CRUD operations)
- Rental bookings and management
- Payment processing with Stripe
- API documentation with Swagger/OpenAPI
- Request validation and error handling
- Database migrations and seeders

## Requirements

- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Stripe account for payments

## Installation

1. Clone the repository:
```bash
git clone https://github.com/Youcode-Classe-E-2024-2025/oumaima_aitsaid_CarRentalAPI.git
```

```markdown:README.md
2. Install dependencies:
```bash
composer install
```

```markdown:README.md
3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

```markdown:README.md
4. Set up database:
```bash
php artisan migrate
php artisan db:seed
```

## API Documentation

Access the API documentation at `/api/documentation` after starting the server.

### Main Endpoints

- Authentication
  - POST `/api/register`
  - POST `/api/login`
  - POST `/api/logout`

- Cars
  - GET `/api/cars`
  - POST `/api/cars`
  - GET `/api/cars/{id}`
  - PUT `/api/cars/{id}`
  - DELETE `/api/cars/{id}`

- Rentals
  - GET `/api/rentals`
  - POST `/api/rentals`
  - GET `/api/rentals/{id}`
  - PUT `/api/rentals/{id}`

- Payments
  - POST `/api/payments/intent`
  - POST `/api/payments/confirm`

## Testing

Run tests using PHPUnit:
```bash
php artisan test
```

## Security

- Authentication using Laravel Sanctum
- Input validation
- CSRF protection
- SQL injection prevention

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License.
```

This README provides clear instructions for installation, available endpoints, testing procedures, and contribution guidelines. The documentation is professional and follows standard README conventions.