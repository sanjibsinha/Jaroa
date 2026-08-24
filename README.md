# Jaroa

**Version 1.0**

Jaroa is a simple, lightweight web application platform built around a clear separation between the frontend and backend.

The frontend is an independent PHP application. The backend currently uses WordPress and a custom plugin to provide application data through a REST API.

The two applications communicate over HTTPS using JSON.

Jaroa is being developed incrementally. Version 1.0 establishes the foundational architecture from which the project will evolve into a more reusable and approachable application platform.

---

## Architecture

The fundamental architecture of Jaroa is:

```text
Browser
   │
   ▼
Jaroa Frontend
   │
   ▼
Router
   │
   ▼
Controllers
   │
   ▼
Services
   │
   ▼
ApiClient
   │
   │ HTTPS / JSON
   ▼
Jaroa Backend
   │
   ▼
WordPress + fullstack-app plugin
```

The frontend and backend are separate applications.

The API is the boundary between them.

This separation is central to the Jaroa architecture.

---

## Version 1.0

Jaroa 1.0 is the first formal version of the project.

It establishes a working foundation consisting of:

* An independent PHP frontend
* A separate backend application
* A defined API boundary
* Routing
* Controllers
* Services
* An API client
* Server-side views
* A WordPress-based backend
* A custom WordPress plugin for application-specific API functionality
* HTTPS communication between frontend and backend
* Independent DDEV development environments

Version 1.0 should be considered a **foundational architecture**, not a finished framework.

The purpose of this version is to establish a clean, working system before introducing higher-level abstractions and automation.

---

## Repository Structure

```text
Jaroa/
│
├── backend/
│   ├── wp-admin/
│   ├── wp-content/
│   │   └── plugins/
│   │       └── fullstack-app/
│   ├── wp-includes/
│   ├── wp-config.php
│   └── ...
│
├── frontend/
│   ├── app/
│   │   ├── Api/
│   │   ├── Controllers/
│   │   ├── Routing/
│   │   ├── Services/
│   │   ├── Application.php
│   │   └── View.php
│   │
│   ├── config/
│   │   └── app.php
│   │
│   ├── views/
│   ├── public/
│   │   └── index.php
│   │
│   └── composer.json
│
└── README.md
```

The `frontend` and `backend` directories represent two independent applications.

Together they form the Jaroa system.

---

## Frontend

The Jaroa frontend is a standalone PHP application.

It does not communicate directly with the WordPress database and does not depend on WordPress templates.

Instead, requests move through a small application architecture:

```text
Request
   │
   ▼
Router
   │
   ▼
Controller
   │
   ▼
Service
   │
   ▼
ApiClient
   │
   ▼
Backend API
```

The frontend is responsible for:

* HTTP request handling
* Routing
* Controllers
* Application services
* API communication
* Views
* HTML rendering
* Frontend assets
* User-facing application behavior

This allows the frontend to remain independent of the technology used by the backend.

---

## Backend

The current Jaroa backend is powered by WordPress.

WordPress provides the content management and persistence layer, while the `fullstack-app` plugin provides Jaroa-specific backend functionality and API endpoints.

Conceptually:

```text
Jaroa Backend
     │
     ├── WordPress
     │     └── Database
     │
     └── fullstack-app
           └── Application API
```

The backend is deliberately isolated from the frontend.

The frontend does not need to know how the backend stores or manages its data.

---

## API Boundary

The API is the contract between the two applications.

```text
Jaroa Frontend
      │
      │ HTTPS
      │ JSON
      ▼
Application API
      │
      ▼
Jaroa Backend
```

This boundary is one of the most important architectural decisions in Jaroa.

The frontend consumes application data through an API client and services rather than accessing WordPress internals directly.

This creates the possibility of changing or replacing the backend without fundamentally redesigning the frontend.

---

## Development Environment

Jaroa currently uses [DDEV](https://ddev.com/) to provide separate development environments for the frontend and backend.

The development architecture looks like:

```text
┌─────────────────────────┐
│   frontend.ddev.site    │
│                         │
│     Jaroa Frontend      │
└────────────┬────────────┘
             │
             │ HTTPS / JSON API
             ▼
┌─────────────────────────┐
│    backend.ddev.site    │
│                         │
│ WordPress + fullstack-  │
│ app plugin              │
└─────────────────────────┘
```

This separation is intentional.

Each application can be developed, tested, and evolved independently.

---

## Requirements

To work with the current Jaroa development environment, you should have:

* Git
* Docker
* DDEV
* PHP
* Composer
* A web browser

Basic familiarity with PHP, Git, Docker, and DDEV is recommended.

---

## Getting Started

Clone the repository:

```bash
git clone https://github.com/sanjibsinha/Jaroa.git
cd Jaroa
```

The repository contains both the frontend and backend applications, along with their DDEV configuration.

### Start the Backend

Move into the backend application:

```bash
cd backend
```

Start the DDEV environment:

```bash
ddev start
```

The backend is available at:

```text
https://backend.ddev.site
```

### Start the Frontend

Open another terminal and move into the Jaroa project:

```bash
cd ~/Jaroa/frontend
```

Install the frontend dependencies:

```bash
composer install
```

Start the frontend DDEV environment:

```bash
ddev start
```

The frontend is available at:

```text
https://frontend.ddev.site
```

Both DDEV projects should be running independently.

---

## Testing the Architecture

Once both applications are running, the basic communication path should be:

```text
Browser
   │
   ▼
https://frontend.ddev.site
   │
   ▼
Jaroa Frontend
   │
   ▼
API Client
   │
   │ HTTPS / JSON
   ▼
https://backend.ddev.site
   │
   ▼
WordPress
   │
   ▼
fullstack-app
```

The important test is not simply whether both websites open independently.

The important test is whether the **frontend can successfully obtain application data from the backend through the API**.

You can also inspect the communication through your browser's developer tools:

```text
Chrome / Browser
    → Developer Tools
    → Network
    → Reload the frontend
```

The API request should reach the backend and return the expected JSON response.

---

## Why Separate the Frontend and Backend?

A conventional WordPress application commonly combines content management, database access, application logic, templates, HTML, CSS, and JavaScript in one environment.

Jaroa deliberately separates these concerns.

```text
Traditional Application

WordPress
├── CMS
├── Database
├── Application Logic
├── Templates
├── HTML
├── CSS
└── JavaScript
```

Jaroa instead moves toward:

```text
Jaroa

Backend
├── CMS
├── Database
└── API
      │
      ▼
Frontend
├── Routing
├── Controllers
├── Services
├── Views
├── HTML
├── CSS
└── JavaScript
```

The result is a system where the frontend and backend can evolve independently.

More importantly, the API becomes a stable architectural boundary.

---

## Vision

Jaroa is intended to grow beyond the current WordPress-based implementation.

The long-term vision is to create a simple and lightweight platform for building web applications without forcing every application to begin with a large framework or tightly coupled architecture.

The intended direction is:

```text
Jaroa 1.0
   │
   ▼
Foundational Architecture
   │
   ▼
Reusable Frontend Components
   │
   ▼
Application Templates
   │
   ├── Blog
   ├── Profile
   └── Other Applications
   │
   ▼
Automated Installer
   │
   ▼
Progressively More Capabilities
   │
   ▼
Native Jaroa Backend
   │
   ▼
More Approachable Application Development
```

The current WordPress backend is therefore not necessarily the final destination.

It provides a practical and mature backend while the frontend architecture is being developed.

Eventually, Jaroa may provide its own native backend capable of replacing the WordPress implementation while preserving the API-oriented architecture.

---

## The Larger Idea

The central idea behind Jaroa is simple:

> **Build the application first. Discover the platform through real applications.**

Jaroa will not attempt to predict every possible requirement before those requirements exist.

Instead, reusable components and abstractions will emerge from actual applications built with the system.

This approach is intended to keep Jaroa:

* Small
* Understandable
* Practical
* Modular
* Maintainable
* Extensible

The project will grow progressively rather than attempting to become a complete framework overnight.

---

## Development Philosophy

Jaroa is being developed one deliberate change at a time.

The working principle is:

```text
One meaningful change
        ↓
Implement
        ↓
Test
        ↓
Verify
        ↓
Commit
        ↓
Next change
```

The architecture should evolve from working software rather than from premature abstraction.

Every new capability should justify its place in the platform through actual use.

---

## Project Status

**Version:** 1.0
**Status:** Foundational architecture established

Jaroa is actively under development.

The current version establishes the independent frontend/backend architecture and API communication layer.

Future versions will build upon this foundation.

---

## Repository

The Jaroa source code is available on GitHub:

**https://github.com/sanjibsinha/Jaroa**

---

## License

License information will be added as the project develops.

