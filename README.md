# Sky International Travels - Flight Booking Website

## Overview
Sky International Travels is a comprehensive flight booking platform that enables users to search, book, and manage flight reservations. The system integrates with flight APIs, offers membership benefits, and provides a responsive user experience across all devices.

## Objective
The purpose of this project is to apply web technologies and concepts to develop a fully functioning, responsive, accessible, data-driven web application. It showcases practical implementation of HTML5, CSS, JavaScript, PHP, and MySQL to create a seamless flight booking experience.

## Organization
This project was developed by a team of students for INF1005. We created a fictitious airline booking service called "Sky International Travels" that provides flight booking services, membership rewards, and travel information.

## General Requirements

✅ **Main Landing Page**: An engaging homepage introducing Sky International Travels with featured destinations and promotions.

✅ **Common Navigation**: Intuitive menu available across all pages for consistent navigation experience.

✅ **Service Sub-pages**:
- Flight search and booking
- Membership and miles program
- Interactive world map of destinations
- Contact and support

✅ **About Us Page**: Information about Sky International Travels, our history, and values.

✅ **Back-end Functionality**:
- User membership system
- Flight booking management
- Newsletter subscription
- Contact form submissions
- Admin dashboard for content management

## Technical Requirements

### Front-end
- **Responsive Design**: Mobile-first approach using Tailwind CSS 4.0
- **HTML5/CSS**: Semantic markup with modern CSS features
- **JavaScript**: Dynamic client-side features including:
  - Interactive flight search
  - Form validation
  - World map visualization
  - Real-time updates

### Back-end
- **PHP**: Object-oriented approach with classes for business logic
- **MySQL**: Comprehensive database operations (CRUD) for:
  - User accounts
  - Flight data
  - Bookings
  - Passenger information
  - Newsletter subscriptions

### Security
- Form validation and sanitization
- Protection against XSS and SQL injection
- Password hashing and secure authentication
- CSRF protection
- Secure password reset system

### Accessibility
- WCAG compliant design
- Screen reader friendly navigation
- Sufficient color contrast
- Keyboard navigation support

## Key Features

### User Management
- User registration and authentication
- Profile management
- Booking history tracking
- Password reset functionality
- Account deletion options

### Flight Management
- Real-time flight search and booking
- Integration with flight schedule API
- Seat availability tracking
- Flight status updates
- Multiple passenger booking support

### Booking System
- Secure payment processing
- Booking confirmation system
- Passenger information management
- Multiple passenger handling per booking
- Real-time booking status updates

### Admin Dashboard
- User management
- Flight management
- Booking oversight
- System settings control
- Analytics and reporting

### Newsletter System
- Subscription management
- Email confirmations
- Privacy policy compliance
- Unsubscribe functionality

## Installation

1. Clone the repository
2. Set up a web server (e.g., Apache) and configure it to serve the project directory
3. Create a MySQL database and import schema.sql
4. Install dependencies: 
   npm i
   npm run build
   composer install
5. Update the `inc/config.php` and `inc/db.php` files with your database/server connection details


## Technical Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Tailwind CSS 4.0
- **Dependencies**: 
  - PHPMailer for email functionality
  - Tailwind CSS for styling
  - JavaScript for interactive features

## Notes
- For educational purposes only
- Not for production use

## Support
For support questions, please contact the development team.