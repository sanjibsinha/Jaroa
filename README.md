# Headless PHP Application

**Version 1.0**

A full-stack web application architecture that separates content management from presentation.

The backend uses **WordPress as a headless CMS**, while the frontend is built as a **standalone PHP application** that communicates with the backend through a custom REST API.

This repository represents **Version 1**, the first reference implementation of the architecture that will eventually evolve into a reusable headless PHP application framework.

---

## Architecture

The application is divided into two distinct layers:

```text
                    HEADLESS PHP APPLICATION
                              │
                ┌─────────────┴─────────────┐
                │                           │
             BACKEND                     FRONTEND
                │                           │
           WordPress                    PHP Application
                │                           │
        Custom WordPress Plugin        Templates / Views
                │                       Components
                │                       Routing
                │                       API Client
                │                           │
                └──────────── REST API ─────┘
                              │
                              ▼
                     WordPress Database
```

### Backend

The backend is a standard WordPress installation configured to operate primarily as a content management and data layer.

A custom WordPress plugin provides the application's API endpoints and isolates application-specific backend functionality from WordPress core.

The backend is responsible for:

* Content management
* Posts and pages
* Categories and taxonomies
* Media
* Application-specific data
* REST API endpoints
* WordPress database interaction

### Frontend

The frontend is an independent PHP application.

It does not directly interact with the WordPress database.

Instead, it communicates with the backend through the defined REST API.

The frontend is responsible for:

* Routing
* Controllers
* Services
* API communication
* Templates
* Components
* HTML rendering
* CSS
* JavaScript
* User-facing application behavior

---

## Why This Architecture?

Traditional WordPress applications usually combine the following responsibilities:

```text
WordPress
├── Database
├── CMS
├── Business logic
├── Templates
├── HTML
├── CSS
└── JavaScript
```

This project deliberately separates those concerns:

```text
WordPress
├── CMS
├── Database
└── API
       │
       ▼
PHP Application
├── Routing
├── Application logic
├── Templates
├── Components
├── HTML
├── CSS
└── JavaScript
```

This separation allows the frontend to evolve independently from the WordPress backend.

It also creates a natural foundation for reusable application architecture.

---

## Repository Structure

```text
.
├── backend/
│   └── WordPress application
│
├── frontend/
│   └── PHP application
│
├── README.md
└── .gitignore
```

The two directories represent two independent applications that together form one complete system.

---

## API Boundary

The backend and frontend communicate through a defined REST API boundary.

Conceptually:

```text
Frontend
    │
    │ HTTP / REST
    ▼
API Contract
    │
    ▼
WordPress Backend
    │
    ▼
Database
```

The frontend should consume application data through services and API clients rather than depending on WordPress internals.

This boundary is one of the most important architectural decisions in the project.

---

## Version 1

This repository is **Version 1.0** of the project.

Version 1 is intentionally treated as a **reference implementation**, not as a finished framework.

The goal is to build a complete working application first and observe which parts of the architecture are genuinely reusable.

Only after that will reusable components be extracted into a framework-level structure.

---

## Future Direction

The long-term goal is to evolve the architecture gradually:

```text
Version 1
   │
   ▼
Reference Application
   │
   ▼
Identify Reusable Components
   │
   ▼
Extract Core Packages
   │
   ▼
Headless PHP Framework
   │
   ├── WordPress Backend Core
   ├── REST API Contract
   ├── PHP Frontend Core
   ├── Routing
   ├── Controllers
   ├── Services
   ├── Views
   ├── Components
   ├── Configuration
   └── Caching
   │
   ▼
Starter Applications
```

The framework will only emerge if the architecture proves reusable across multiple applications.

---

## Development Philosophy

This project follows a simple principle:

> **First build the application. Then discover the framework.**

We will avoid premature abstraction and extract functionality only when repeated implementations demonstrate that a component genuinely belongs in the common architecture.

This keeps the framework grounded in real applications rather than theoretical requirements.

---

## Technology

### Backend

* WordPress
* PHP
* MySQL
* Custom WordPress Plugin
* WordPress REST API

### Frontend

* PHP
* HTML
* CSS
* JavaScript
* Custom application architecture

### Development Environment

* Docker
* DDEV
* Git

---

## Status

**Version:** 1.0
**Status:** Initial reference implementation

The architecture is under active development.

---

## License

License information will be added as the project evolves.

