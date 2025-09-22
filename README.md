<p align="center">
  <img src="https://usefokus.com/asset/images/sprint-overview-chart-fokus.png" alt="Fokus Logo" width="200">
</p>

<h1 align="center">Fokus - Project Management System</h1>

<p align="center">
  A modern, all-in-one project management solution built with Laravel 12 and the TALL stack
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Livewire-3.0-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire Version">
  <img src="https://img.shields.io/badge/Postgres-15-336791?style=for-the-badge&logo=postgresql&logoColor=white" alt="Postgres Version">
</p>

## About Fokus

Fokus is a comprehensive project management system inspired by tools like Jira, Asana, and Trello. It aims to consolidate all team collaboration needs into a single platform, eliminating the need for multiple tools.

<p align="center">
  <img src="https://usefokus.com/asset/images/no-workspace.png" alt="Fokus Logo" width="200">
</p>

### Development Status

Fokus is currently under active development with version 1.0.0 targeted for release by the end of September 2025. After this date, you will be able to download stable releases from the releases page.

### Key Features

- **Project Management**: Create and manage projects with customizable workflows
- **Task Tracking**: Organize tasks with priorities, assignments, and deadlines
- **Sprint Planning**: Plan and track sprints with burndown charts and retrospectives
- **Team Collaboration**: Built-in communication tools and screen sharing
- **Kanban Boards**: Visual task management with drag-and-drop functionality
- **Time Tracking**: Monitor time spent on tasks and projects
- **Reporting**: Generate comprehensive reports and analytics
- **User Management**: Role-based access control and team organization

## Tech Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: TALL Stack (Tailwind CSS, Alpine.js, Laravel, Livewire 3)
- **Database**: PostgreSQL 15
- **Real-time Features**: Laravel Reverb
- **UI Components**: MaryUI 2
- **Package Management**: Yarn
- **Icons**: Font Awesome (with fas.icon format)

## Installation

### Prerequisites

- PHP 8.3 or higher
- Composer
- PostgreSQL 15
- Node.js and Yarn
- Redis (for queues and broadcasting)
- Docker and Docker Compose (optional)

### Setup Instructions

#### Option 1: Standard Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/fokus.git
   cd fokus
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies:
   ```bash
   yarn install
   ```

4. Copy the environment file and configure your database:
   ```bash
   cp .env.example .env
   ```

5. Generate application key:
   ```bash
   php artisan key:generate
   ```

6. Run migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```

7. Build assets:
   ```bash
   yarn build
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

#### Option 2: Docker Installation

Fokus can be easily installed and run using Docker. This method provides a container environment containing all necessary dependencies.

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/fokus.git
   cd fokus
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Build and start the Docker containers:
   ```bash
   docker-compose up -d --build
   ```

4. Install dependencies and set up the application:
   ```bash
   # Install composer dependencies inside the container
   docker-compose exec app composer install
   
   # Generate application key
   docker-compose exec app php artisan key:generate
   
   # Create database tables and add sample data
   docker-compose exec app php artisan migrate --seed
   ```

5. Build frontend assets:
   ```bash
   docker-compose exec app yarn install
   docker-compose exec app yarn build
   ```

6. Access the application:
   - Web application: `http://localhost:9000`
   - PostgreSQL database: `localhost:5432` (username: projecta, password: secret)

7. To stop Docker containers:
   ```bash
   docker-compose down
   ```

#### About Docker Environment

- **app**: Main application container with PHP 8.3, Composer, Node.js and Yarn
- **db**: PostgreSQL 15 database container
- Database data is stored in a persistent Docker volume (`db-data`)
- Your application code is mounted from your local machine to the container, so changes are reflected instantly

## Project Structure

- `app/` - Contains the core code of the application
  - `Models/` - Eloquent models
  - `Http/Controllers/` - Request handlers
  - `Http/Livewire/` - Livewire components
  - `Enums/` - PHP enumerations
  - `Events/` - Event classes
  - `Listeners/` - Event listeners
  - `Concerns/` - Shared traits
  
- `resources/`
  - `views/` - Blade templates
    - `livewire/` - Livewire component templates
    - `components/` - Blade components
  - `css/` - Stylesheets
  - `js/` - JavaScript files

- `database/`
  - `migrations/` - Database migrations
  - `seeders/` - Database seeders
  - `factories/` - Model factories

## Recent UI Modernization

Fokus has undergone significant UI/UX improvements across various sections:

- **Dashboard**: Redesigned with statistics cards at the top, workspaces and activity info in the left column, and projects and tasks in the right column. Implemented tab structure for better organization.

- **Projects**: Modernized project cards, team lists, task lists, and project settings with improved visual design.

- **Sprints**: Enhanced sprint information cards, task lists, and sprint progress indicators with a modern design approach.

- **Workspaces**: Updated workspace management pages including workspace cards, team lists, and project creation modals.

All sections feature:
- Modern MaryUI 2 components
- Properly formatted Font Awesome icons (fas.icon-name)
- Updated color palette and typography
- Improved responsive design
- Animation and transition effects

## Development Guidelines

- Follow PSR coding standards
- Use Laravel Livewire Volt for components
- Use MaryUI 2 components with `<x-component>` syntax
- Use Font Awesome icons with `fas.icon-name` format
- Implement toast notifications using `\Mary\Traits\Toast`

## License

The Fokus project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
