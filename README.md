# Appointment Manager

## Overview

A simple Laravel project that is using ![Fullcalendar](https://fullcalendar.io) V6. This project is using a MYSQL Database with two tables, one table is used for displaying the schedule times and one is for storing the booked appointments for customers.

## Languages/Tools/Technologies

- Laravel Framework (backend)
- JavaScript (frontend)
- MYSQL

## Installation

### Step 1: Clone the repository

```bash
git clone https://github.com/Walrider32/appointment-manager.git
```

### Step 2: Get XAMPP

- Download ![Xampp](https://www.apachefriends.org/download.html) Installer.
- Run .exe file
- Start the installation process

### Step 3: Get Composer

- Download Composer and install from ![here](https://getcomposer.org)

### Step 4: Get Laravel Library

```bash
composer global require laravel/installer
```

### Step 5: Open the cloned repository in your desired programming environment.

- Eg.: Visual Studio Code | Rider

### Step 6: Migrate the database

```bash
php artisan migrate
```

### Step 7: Seed the database

```bash
php artisan db:seed --class=SchedulesTableSeeder
```

### Step 8: Run The Project

```bash
php artisan serve
```
