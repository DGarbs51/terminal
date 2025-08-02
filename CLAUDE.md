# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with React starter kit, using:
- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: React 19 with TypeScript, Inertia.js for SPA routing
- **Styling**: Tailwind CSS v4 with Radix UI components
- **Testing**: Pest for PHP tests
- **Database**: SQLite (default), configurable
- **Development Environment**: Laravel Herd so that services are running on https://terminal.test

## Common Development Commands

### Development
```bash
# Run full development environment (server, queue, logs, vite)
composer dev

# Run with SSR (Server-Side Rendering)
composer dev:ssr

# Run individual services
php artisan serve              # Laravel server
npm run dev                    # Vite dev server
php artisan queue:listen       # Queue worker
php artisan pail               # Real-time logs
```

### Build & Production
```bash
# Frontend build
npm run build                  # Standard build
npm run build:ssr             # Build with SSR support

# Laravel optimization
php artisan optimize
php artisan config:cache
php artisan route:cache
```

### Testing
```bash
# Run all tests
composer test
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run tests with filter
php artisan test --filter=RegistrationTest
```

### Code Quality
```bash
# PHP formatting
./vendor/bin/pint              # Laravel Pint formatter

# JavaScript/TypeScript
npm run lint                   # ESLint with auto-fix
npm run format                 # Prettier formatting
npm run format:check          # Check formatting without fixing
npm run types                 # TypeScript type checking
```

### Database
```bash
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Drop all tables and re-run migrations
php artisan db:seed           # Run database seeders
```

## Architecture Overview

### Backend Structure
The application follows Laravel's MVC pattern with Inertia.js integration:

- **Controllers**: Located in `app/Http/Controllers/`, organized by feature (Auth, Settings)
- **Models**: Eloquent models in `app/Models/`
- **Middleware**: 
  - `HandleInertiaRequests`: Shares global data (auth, ziggy routes, sidebar state)
  - `HandleAppearance`: Manages theme preferences
- **Routes**: Organized in `routes/` directory:
  - `web.php`: Main application routes
  - `auth.php`: Authentication routes
  - `settings.php`: User settings routes

### Frontend Structure
React components with TypeScript, using Inertia.js for routing:

- **Pages**: `resources/js/pages/` - Inertia page components
  - Each corresponds to a Laravel route
  - Organized by feature (auth, settings, etc.)
- **Layouts**: `resources/js/layouts/` - Reusable layout templates
  - `app-layout`: Main authenticated app layout with sidebar
  - `auth-layout`: Authentication pages layout
  - Multiple layout variants for different UI patterns
- **Components**: `resources/js/components/` - Reusable UI components
  - Custom app components (header, sidebar, etc.)
  - `ui/` subdirectory contains Radix UI-based components
- **Hooks**: `resources/js/hooks/` - Custom React hooks
- **Types**: TypeScript definitions in `resources/js/types/`

### Key Integration Points

1. **Inertia.js Flow**:
   - Laravel controllers return `Inertia::render('PageComponent', $data)`
   - Data is passed as props to React components
   - Navigation happens via Inertia router, no page reloads

2. **Authentication**:
   - Laravel Breeze authentication scaffolding
   - React components for login, registration, password reset
   - Protected routes use `auth` middleware

3. **Shared Data**:
   - Global data shared via `HandleInertiaRequests` middleware
   - Includes auth user, app name, Ziggy routes, sidebar state
   - Available in all page components via `usePage()`

4. **Asset Building**:
   - Vite handles both React/TypeScript compilation and CSS processing
   - Laravel Vite plugin integrates with Laravel's asset helpers
   - Hot module replacement in development

5. **Server-Side Rendering**:
   - SSR configuration in `config/inertia.php`
   - SSR entry point at `resources/js/ssr.tsx`
   - Improves initial page load and SEO