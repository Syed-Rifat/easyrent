# EasyRent - House Rental System

A modern web application for house rental management built with PHP and MySQL.

## Features

- User authentication (Login/Register)
- Property listing and management
- Advanced search functionality
- Featured properties section
- Responsive design
- Admin dashboard
- Property booking system

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/MAMP (for local development)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/Syed-Rifat/easyrent.git
```

2. Create a MySQL database named `easyrent`

3. Import the database schema:
```bash
mysql -u your_username -p easyrent < database/easyrent.sql
```

4. Configure database connection:
   - Copy `database/config.example.php` to `database/config.php`
   - Update database credentials in `config.php`

5. Set up your web server:
   - Point your web server to the project root directory
   - Ensure proper permissions are set for upload directories

## Directory Structure

```
easyrent/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── database/
│   ├── config.php
│   └── easyrent.sql
├── pages/
│   ├── admin/
│   ├── auth/
│   ├── properties/
│   └── includes/
└── index.php
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact

Email - syedrifat411@gmail.com
Project Link: [https://github.com/Syed-Rifat/easyrent](https://github.com/Syed-Rifat/easyrent)
