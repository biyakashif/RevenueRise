# Task App

A comprehensive task management application built with Laravel, Vue.js, and Inertia.js that enables users to earn commissions by completing tasks based on their VIP level.

## 🚀 Features

### **User Management**
- Mobile number-based authentication system
- Multi-tier VIP levels (VIP1-VIP7) with progressive costs
- User balance and profit tracking
- Referral system with invitation codes

### **Task System**
- VIP-level based task assignment
- Three product types:
  - **VIP1**: Entry-level tasks for beginners
  - **VIPs**: Advanced tasks for higher VIP levels
  - **Lucky Order**: Special high-reward tasks
- Commission-based reward system
- Daily profit tracking and reset

### **Balance Management**
- Real-time balance updates
- Frozen balance for pending transactions
- Today's profit tracking
- Commission rewards on task completion

### **Admin Panel**
- User management and balance control
- Product and task management
- Order monitoring and control
- Force lucky order functionality
- Comprehensive dashboard with analytics

### **Multi-language Support**
- Internationalization ready
- Support for multiple languages (EN, ES, IT, RO, RU, DE, BN, HI)
- Dynamic language switching

## 🛠️ Technology Stack

- **Backend**: Laravel 11.x with PHP 8.2+
- **Frontend**: Vue.js 3 with Inertia.js
- **Styling**: Tailwind CSS
- **Database**: SQLite (configurable)
- **Authentication**: Laravel Breeze with mobile number login
- **Build Tool**: Vite

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd task-app
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   php artisan db:seed --class=UserSeeder
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

## 👥 Demo Accounts

After seeding, you can use these accounts:

### Regular User
- **Mobile**: `1234567890`
- **Password**: `password`
- **VIP Level**: VIP1
- **Balance**: 1000.00 USDT

### Admin User
- **Mobile**: `0987654321`
- **Password**: `admin123`
- **VIP Level**: VIP3
- **Balance**: 5000.00 USDT

## 📱 Application Structure

### **User Flow**
1. **Registration**: Users register with mobile number and optional invitation code
2. **VIP Upgrade**: Users can upgrade their VIP level for access to higher-paying tasks
3. **Task Completion**: Complete assigned tasks to earn commissions
4. **Balance Management**: Track earnings and manage withdrawals

### **Admin Flow**
1. **User Management**: Monitor and manage user accounts
2. **Product Management**: Create and manage task products
3. **Task Assignment**: Assign tasks to users based on VIP levels
4. **Balance Control**: Manage user balances and frozen funds

## 🔧 Configuration

### **VIP Levels and Costs**
- **VIP1**: Free (Entry level)
- **VIP2**: 300.00 USDT
- **VIP3**: 750.00 USDT
- **VIP4**: 1500.00 USDT
- **VIP5**: 3500.00 USDT
- **VIP6**: 6500.00 USDT
- **VIP7**: 10000.00 USDT

### **Product Types**
- **VIP1**: Regular tasks for VIP1 users
- **VIPs**: Advanced tasks for VIP2+ users
- **Lucky Order**: Special high-reward tasks with higher risk

## 🌟 Key Components

### **Task Assignment Logic**
- VIP1 users get 40 VIP1 tasks
- Higher VIP users get mix of VIPs and Lucky Orders
- Lucky Orders appear every 10th task for VIP2+ users

### **Commission System**
- Each product has a base commission reward
- Commission percentage based on product value
- Daily profit tracking with automatic reset

### **Balance Management**
- Real-time balance updates
- Frozen balance for pending Lucky Orders
- Commission rewards added upon task completion

## 🔒 Security Features

- CSRF protection on all forms
- Mobile number-based authentication
- Role-based access control
- Secure password hashing
- Protected admin routes

## 🎨 UI/UX Features

- Responsive design for mobile and desktop
- Professional purple-themed interface
- Real-time balance updates
- Smooth transitions and animations
- Multi-language support

## 📊 Database Schema

### **Key Tables**
- `users` - User accounts and VIP information
- `products` - Task products with commission details
- `tasks` - User-assigned tasks
- `user_orders` - Completed task orders
- `deposits` - User deposit history

## 🚀 Development

### **Running in Development**
```bash
# Start Laravel server
php artisan serve

# Start Vite dev server (in another terminal)
npm run dev
```

### **Building for Production**
```bash
npm run build
```

### **Running Tests**
```bash
php artisan test
```

## 📝 API Endpoints

### **Public Routes**
- `GET /` - Homepage
- `GET /login` - Login page
- `GET /register` - Registration page

### **Authenticated Routes**
- `GET /dashboard` - User dashboard
- `GET /my-orders` - User task orders
- `GET /profile` - User profile management
- `GET /deposit` - Deposit management

### **Admin Routes** (requires admin role)
- `GET /admin/dashboard` - Admin dashboard
- `GET /admin/users` - User management
- `GET /admin/products` - Product management
- `GET /admin/task-manager` - Task management

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

For support, please contact the development team or create an issue in the repository.

---

**Built with ❤️ using Laravel and Vue.js**