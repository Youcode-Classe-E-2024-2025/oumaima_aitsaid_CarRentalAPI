# Car Rental API

A RESTful API for a car rental service built with Laravel.

## Features

- User authentication with Laravel Sanctum
- CRUD operations for cars, rentals, and payments
- Role-based access control
- API documentation with Swagger
- Comprehensive validation and error handling

## Requirements

- PHP 8.1+
- Composer
- Postgres

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/car-rental-api.git
   cd car-rental-api
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Copy the .env.example file:
   ```
   cp .env.example .env
   ```

4. Configure your database in the .env file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=car_rental
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Generate application key:
   ```
   php artisan key:generate
   ```

6. Run migrations and seeders:
   ```
   php artisan migrate --seed
   ```

7. Generate Swagger documentation:
   ```
   php artisan l5-swagger:generate
   ```

8. Start the development server:
   ```
   php artisan serve
   ```

## API Documentation

Access the API documentation at:
```
http://localhost:8000/api/documentation
```

## Testing

Import the Postman collection from the `postman` directory to test the API endpoints.

## License

This project is open-sourced software licensed under the MIT license.