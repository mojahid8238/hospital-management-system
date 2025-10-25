# Hospital Management System

This project is a Hospital Management System built with PHP, MySQL, and Apache, containerized using Docker.

## Table of Contents

-   [Prerequisites](#prerequisites)
-   [Getting Started](#getting-started)
    -   [Clone the Repository](#clone-the-repository)
    -   [Build and Run Docker Containers](#build-and-run-docker-containers)
-   [Accessing the Application](#accessing-the-application)
-   [Database Initialization](#database-initialization)
-   [Troubleshooting](#troubleshooting)

## Prerequisites

Before you begin, ensure you have the following installed on your system:

-   **Docker:** [Install Docker Engine](https://docs.docker.com/engine/install/)
-   **Docker Compose:** [Install Docker Compose](https://docs.docker.com/compose/install/) (usually comes with Docker Desktop)

## Getting Started

Follow these steps to get your Hospital Management System up and running.

### Clone the Repository

First, clone the project repository to your local machine:

```bash
git clone https://github.com/your-username/hospital-management-system.git
cd hospital-management-system
```
*(Note: Replace `https://github.com/your-username/hospital-management-system.git` with the actual repository URL if different.)*

### Build and Run Docker Containers

Navigate to the root directory of the cloned project (where `docker-compose.yml` is located) and run the following commands:

1.  **Build the Docker images:**
    ```bash
    docker compose build --no-cache
    ```
    The `--no-cache` flag ensures that new images are built from scratch, picking up any recent changes.

2.  **Start the Docker containers:**
    ```bash
    docker compose up -d
    ```
    The `-d` flag runs the containers in detached mode (in the background).

This will start three services:
-   `web`: The Apache web server with PHP.
-   `db`: A MySQL 5.7 database server.
-   `phpmyadmin`: A web-based MySQL administration tool.

## Accessing the Application

Once the containers are up and running, you can access the application and phpMyAdmin:

-   **Hospital Management System:** Open your web browser and go to `http://localhost:8000/`
-   **phpMyAdmin:** Open your web browser and go to `http://localhost:8080/`
    -   **Username:** `root`
    -   **Password:** `root`

## Database Initialization

The `db` service is configured to automatically initialize the database using the `sql/hospital_db.sql` file when it starts for the first time. This script creates the necessary tables and populates them with initial data.

If you need to reset your database, you can stop and remove the containers and the associated volume:

```bash
docker compose down -v
docker compose up -d
```
**Warning:** `docker compose down -v` will delete all data stored in the `db_data` volume. Only use this if you want to completely reset your database.

## Troubleshooting

-   **"Site not reachable" or "Connection refused"**:
    -   Ensure Docker Desktop (or Docker daemon) is running.
    -   Check if the containers are running: `docker compose ps`. If not, try `docker compose up -d`.
    -   Verify that no other service is using ports 8000 or 8080 on your host machine.
-   **Images/CSS/JS not loading correctly (e.g., `hospital-management-system` in URL)**:
    -   This usually indicates a browser caching issue. Perform a **hard refresh** in your browser (Ctrl+F5 on Windows/Linux, Cmd+Shift+R on Mac).
    -   If the issue persists, try clearing your browser's entire cache and site data for `localhost`.
    -   Ensure you have rebuilt the Docker images with `docker compose build --no-cache` after any code changes.
-   **Database connection errors**:
    -   Ensure the `db` container is running (`docker compose ps`).
    -   Verify the database credentials in `includes/db.php` match those in `docker-compose.yml`.

---