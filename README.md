# NeuroNet Temporal Processing Tests

A comprehensive web-based spatial audio assessment system for temporal processing tests with admin panel and user management.

## Features

### 🎧 **Audio Testing System**
- Spatial audio tests with Left, Center, Right positioning
- Customizable test assessments with admin-created content
- Real-time response timing measurement
- Visual feedback indicators for test progress
- Score bar visualization showing trial-by-trial results

### 👥 **User Management**
- Role-based access control (Admin, Site Admin, Test Creator, Test Taker)
- Email-based authentication system
- User creation with ownership tracking
- Session management and tab persistence

### 📊 **Assessment Results**
- Comprehensive test results tracking
- Individual trial analysis with response times
- Position-specific accuracy metrics (Left/Center/Right percentages)
- Visual score indicators with channel letters (L/C/R)
- Test session management with delete capabilities
- Detailed timing analysis (first sound to final click)

### 🛠️ **Admin Panel**
- Assessment creation and management
- Image and audio file uploads
- User management with role assignments
- Test results filtering and analysis
- Bulk operations for data management

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: SQLite
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5
- **Audio**: HTML5 Audio API with preloading optimization
- **File Management**: PHP file upload handling

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/neuronet-temporal-processing-tests.git
   cd neuronet-temporal-processing-tests
   ```

2. **Set up web server** (Apache/Nginx) with PHP support

3. **Configure permissions**:
   ```bash
   chmod 755 var/www/
   chmod 777 var/data/
   chmod 777 var/tmp/sessions/
   chmod 777 var/www/assets/uploads/
   ```

4. **Access the application**:
   - Navigate to your web server URL
   - Default admin credentials will be created on first run

## Project Structure

```
├── var/
│   ├── data/                     # Database files
│   ├── logs/                     # Application logs
│   ├── private/                  # Server-side includes
│   ├── tmp/sessions/             # PHP session storage
│   └── www/                      # Web root
│       ├── admin_*.php           # Admin panel files
│       ├── assets/               # Static assets
│       │   ├── images/           # UI images
│       │   ├── uploads/          # User uploads
│       │   │   └── feedback/     # Feedback images
│       │   ├── assessments.json  # Assessment data
│       │   └── users.json        # User data
│       ├── index.php             # Main landing page
│       ├── test.php              # Test interface
│       └── login.php             # Authentication
```

## Usage

### For Administrators
1. Log in to the admin panel
2. Create assessments with custom images and audio
3. Manage users and assign roles
4. View and analyze test results

### For Test Takers
1. Log in with provided credentials
2. Select and start available tests
3. Complete spatial audio assessments
4. View immediate results

## Database Schema

### `test_results` table:
- `id` - Primary key
- `userID` - User identifier
- `fullName` - User's full name
- `email` - User's email
- `test_name` - Assessment name
- `prompt_number` - Trial number
- `user_answer` - User's response (Left/Center/Right)
- `correct_answer` - Expected response
- `response_time` - Time from sound to click (ms)
- `session_id` - Test session identifier
- `timestamp` - Response timestamp

## Security Features

- Role-based access control
- Session management
- File upload validation
- SQL injection prevention
- XSS protection
- CSRF token implementation

## Browser Compatibility

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

**Note**: Requires modern browser with HTML5 Audio API support and user interaction for autoplay compliance.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues and questions:
- Create an issue on GitHub
- Check the documentation in `/docs/`
- Review the troubleshooting guide

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.