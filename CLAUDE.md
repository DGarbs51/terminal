# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with React starter kit, using:
- **Backend**: Laravel 12 with PHP 8.4+ (tested on PHP 8.4 in CI)
- **Frontend**: React 19 with TypeScript, Inertia.js for SPA routing
- **UI Components**: shadcn/ui (NOT direct Radix UI) - pre-styled component library
- **Styling**: Tailwind CSS v4 with CSS custom properties for theming
- **Testing**: Pest for PHP tests with PHPStan level 10 static analysis
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

# Run tests in parallel
php artisan test --parallel
```

### Code Quality
```bash
# PHP static analysis (PHPStan level 10)
vendor/bin/phpstan analyse --memory-limit=2G

# PHP formatting and linting
vendor/bin/duster lint         # Check for issues
vendor/bin/duster fix          # Fix issues automatically

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
php artisan migrate:fresh --seed  # Fresh migration with seeding
```

### Laravel IDE Helper (Development)
```bash
php artisan ide-helper:generate     # Generate facades
php artisan ide-helper:models -WR   # Generate model helpers (write to models)
php artisan ide-helper:meta         # Generate metadata
php artisan ide-helper:eloquent     # Generate Eloquent helpers
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
- **Form Requests**: Custom validation in `app/Http/Requests/`

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
  - `ui/` subdirectory contains shadcn/ui components
- **Hooks**: `resources/js/hooks/` - Custom React hooks
- **Types**: TypeScript definitions in `resources/js/types/`
- **Lib**: Utilities in `resources/js/lib/` (includes cn utility for class merging)

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

6. **Real-Time Features**:
   - Laravel Echo + Reverb for WebSocket communication
   - User-specific channels: `App.Models.User.{id}`
   - React integration via `useEcho` hook

7. **Theme System**:
   - Light/Dark/System theme support
   - Managed via `use-appearance` hook
   - SSR-compatible with cookie persistence

## UI Component System (shadcn/ui)

The project uses shadcn/ui, a component library built on top of Radix UI primitives:

- **Configuration**: `components.json` contains shadcn/ui settings
- **Component Location**: `resources/js/components/ui/`
- **Styling Approach**: Tailwind CSS with CSS custom properties
- **Theming**: Comprehensive color system with semantic naming
- **Icons**: Lucide React for consistent iconography
- **Utilities**: `cn()` function in `lib/utils.ts` for class merging

### Common UI Components Available:
- Button (with variants)
- Card
- Dialog/Modal
- Dropdown Menu
- Input/Label
- Select
- Checkbox
- Navigation Menu
- Avatar
- Separator
- Tooltip
- Toggle

## Type Safety

- **Frontend**: Full TypeScript coverage with strict typing
- **Global Types**: Window object extensions in `resources/js/types/global.d.ts`
- **Route Types**: Ziggy route types for type-safe routing
- **Component Props**: Inertia page props are typed interfaces
- **PHPDoc Annotations**: Used for IDE support in FormRequests and controllers

## Static Analysis & Code Quality

### PHP (Larastan/PHPStan)
- **Level**: 10 (highest level)
- **Configuration**: `phpstan.neon`
- **Includes**: Larastan and Carbon extensions
- **Scope**: Analyzes entire `app/` directory

### CI/CD Pipeline
- **GitHub Actions**: Automated linting on push/PR to main/develop branches
- **Auto-formatting**: Commits code style fixes automatically
- **Tools Run**:
  - PHPStan analysis
  - Laravel IDE Helper generation
  - Duster (PHP formatting)
  - Prettier (JS/TS formatting)
  - ESLint (JS/TS linting)

## Broadcasting Configuration

The application uses Laravel Reverb for WebSocket connections:
- Echo configuration in `resources/js/echo.ts`
- Authentication via Laravel's built-in broadcasting auth
- React components can listen to events using the `useEcho` hook

## Development Workflow

1. **Start Development**: Run `composer dev` to start all services
2. **Make Changes**: Edit files with hot reload for frontend changes
3. **Test Changes**: Run `composer test` before committing
4. **Code Quality**: Run linting/formatting before commits
5. **Type Check**: Ensure `npm run types` passes
6. **Static Analysis**: Run `vendor/bin/phpstan analyse`

## Common Patterns

### Creating New Pages
1. Create controller method that returns `Inertia::render('PageName', $data)`
2. Create React component in `resources/js/pages/`
3. Add route in appropriate route file
4. TypeScript types are automatically inferred from props

### Adding UI Components
1. Check if shadcn/ui has the component you need
2. Use the shadcn/ui CLI or manually create in `resources/js/components/ui/`
3. Use CVA (class-variance-authority) for variant styling
4. Follow existing component patterns for consistency

### Database Changes
1. Create migration: `php artisan make:migration create_table_name`
2. Update model if needed
3. Run `php artisan migrate`
4. Update TypeScript types if frontend needs the data
5. Run `php artisan ide-helper:models -WR` to update IDE helpers

### Form Validation
1. Create FormRequest: `php artisan make:request NameRequest`
2. Add PHPDoc `@method User|null user()` for IDE support
3. Use `$validated = $request->validate()` with type hints
4. Access validated data with proper typing

## Important Notes

- **Ziggy Routes**: Currently using Ziggy for route generation (despite earlier Wayfinder mention)
- **Type Annotations**: Use PHPDoc annotations for better IDE support with Laravel magic methods
- **Code Style**: Automatically enforced via CI/CD pipeline
- **shadcn/ui**: This project uses shadcn/ui components, NOT direct Radix UI imports
