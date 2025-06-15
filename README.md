# Omegle-like Chat Application

A PHP-based real-time chat application inspired by Omegle, allowing users to have anonymous conversations with random strangers.

## Features

- Anonymous random chat matching
- Real-time messaging
- Secure user authentication
- SQLite database for data storage
- Modern and responsive UI
- Logging system for monitoring

## Requirements

- PHP >= 7.4
- SQLite3 extension
- JSON extension
- Composer (PHP package manager)
- Web server (Apache/Nginx)

## Installation

1. Clone the repository:

```bash
git clone https://github.com/sohagsrz/omegle.git
cd omegle
```

2. Install dependencies using Composer:

```bash
composer install
```

3. Configure your environment:

   - Copy `.env.example` to `.env` (if not exists)
   - Update the environment variables as needed

4. Set up the database:

   - The application uses SQLite, which will be automatically created in the `database` directory
   - Ensure the `database` directory is writable by your web server

5. Configure your web server:
   - Point your web server's document root to the project's public directory
   - Ensure the `.htaccess` file is properly configured (for Apache)

## Directory Structure

```
├── api/            # API endpoints
├── css/            # Stylesheets
├── database/       # SQLite database files
├── includes/       # PHP includes
├── js/             # JavaScript files
├── logs/           # Application logs
├── public/         # Public assets
├── src/            # Source code
└── vendor/         # Composer dependencies
```

## Usage

1. Start your web server
2. Navigate to the application URL in your browser
3. Click "Start Chat" to begin a random conversation
4. Use the interface to send messages and interact with your chat partner

## Security

- JWT-based authentication
- Secure session management
- Input validation and sanitization
- XSS protection
- CSRF protection

## Dependencies

- vlucas/phpdotenv: Environment variable management
- monolog/monolog: Logging
- firebase/php-jwt: JWT authentication
- guzzlehttp/guzzle: HTTP client

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you encounter any issues or have questions, please open an issue in the GitHub repository.
