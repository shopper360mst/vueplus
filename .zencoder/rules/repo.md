---
description: Repository Information Overview
alwaysApply: true
---

# Repository Information Overview

## Repository Summary
This repository is a multi-project setup comprising a **Symfony (PHP)** backend and a **Vue.js** frontend. It appears to be a web application for managing forms, submissions, and reports (possibly related to CVS/GWP campaigns). The project uses a modern stack with Symfony 7 on the backend and Vue 3 with Vite and Tailwind CSS on the frontend.

## Repository Structure
- **[./symfony/](./symfony/)**: The PHP backend built with the Symfony framework. Contains core logic, database entities, CLI commands, and API services.
- **[./vue/](./vue/)**: The frontend application built with Vue 3. It utilizes Vite for building, Pinia for state management, and Tailwind CSS for styling.
- **[./README.md](./README.md)**: Main project setup guide for both JavaScript and PHP development.
- **[./RS_VUE.md](./RS_VUE.md)**: Vue 3 best practices and architectural guidelines for the project.

### Main Repository Components
- **Symfony Backend**: Handles data persistence, complex business logic via CLI commands and services, and serves as the API provider.
- **Vue Frontend**: Provides the user interface, utilizing the Composition API and modern state management.

## Projects

### Symfony Backend
**Configuration File**: [./symfony/composer.json](./symfony/composer.json)

#### Language & Runtime
**Language**: PHP  
**Version**: >= 8.2  
**Build System**: Symfony Flex  
**Package Manager**: Composer

#### Dependencies
**Main Dependencies**:
- `symfony/framework-bundle`: 7.4.*
- `doctrine/orm`: ^3.6
- `aws/aws-sdk-php`: AWS integration
- `guzzlehttp/guzzle`: HTTP client
- `league/flysystem-aws-s3-v3`: S3 storage integration
- `openspout/openspout` & `shuchkin/simplexlsx`: Excel/CSV processing

#### Build & Installation
```bash
cd symfony
composer install
```

#### Docker
**Configuration**: [./symfony/compose.yaml](./symfony/compose.yaml) defines a PostgreSQL database service using `postgres:16-alpine`.

#### Main Files & Resources
- **Entry Point**: [./symfony/public/index.php](./symfony/public/index.php)
- **Entities**: Located in [./symfony/src/Entity/](./symfony/src/Entity/)
- **Commands**: Extensive CLI tools in [./symfony/src/Command/](./symfony/src/Command/)

---

### Vue Frontend
**Configuration File**: [./vue/package.json](./vue/package.json)

#### Language & Runtime
**Language**: JavaScript (Vue 3)  
**Version**: Node.js ^20.19.0 || >=22.12.0  
**Build System**: Vite 7.3  
**Package Manager**: NPM

#### Dependencies
**Main Dependencies**:
- `vue`: ^3.5.27
- `pinia`: ^3.0.4 (State management)
- `vue-router`: ^5.0.1 (Routing)
- `vue-i18n`: ^11.2.8 (Internationalization)

**Development Dependencies**:
- `@tailwindcss/vite`: ^4.1.18
- `eslint`: ^9.39.2
- `oxlint`: ~1.42.0
- `prettier`: 3.8.1

#### Build & Installation
```bash
cd vue
npm install
npm run build
```

#### Main Files & Resources
- **Entry Point**: [./vue/src/main.js](./vue/src/main.js) and [./vue/index.html](./vue/index.html)
- **App Component**: [./vue/src/App.vue](./vue/src/App.vue)
- **Router**: [./vue/src/router/index.js](./vue/src/router/index.js)
- **Best Practices**: [./RS_VUE.md](./RS_VUE.md)

#### Testing & Validation
**Framework**: No dedicated testing framework (like Vitest/Cypress) was found.
**Validation**: Uses ESLint, Prettier, and Oxlint for code quality.
**Run Command**:
```bash
cd vue
npm run lint
```
