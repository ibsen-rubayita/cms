# HVU Voice Desk

A PHP and MySQL website for Horizon Valley University. The public site introduces HVU, shares news and admissions information, and connects students to Voice Desk for complaints and feedback. Names, motto and contact details are configured in `config/site.php`.

## Features

- Responsive university homepage with faculties, campus facilities, announcements, FAQ and contact information.
- Account registration, login, profile editing and password changes.
- Five failed login attempts trigger a 15-minute lockout. Authenticated sessions expire after 30 minutes of inactivity.
- CSRF protection on POST forms and POST-only logout.
- Submit, view, edit and delete complaints while pending, with anonymous submissions, priority levels, reference numbers, replies and a progress tracker.
- Optional JPG, PNG or PDF complaint attachment, up to 2 MB. Files are stored outside the public web root and downloads are limited to the complaint owner and admins.
- One-time 1-to-5 rating and optional comment after a complaint is resolved.
- Admin dashboard totals, average rating and CSS-based status/priority charts.
- Complaint search, priority/status/category filters, sorting, pagination and CSV export.
- Admin account pause/activate and promote/demote controls. Admins cannot change their own account from the users page.
- Announcement create, edit, publish/draft and delete.

## Technologies

PHP 7.4+, PDO, MySQL/MariaDB, HTML, CSS and JavaScript. File uploads require PHP's `fileinfo` extension.

## Test Logins

Admin account:

| Email | Password |
|---|---|
| `admin@cms.com` | `Admin@123` |

Demo complainant accounts (all use `Demo@123`):

| Name | Email |
|---|---|
| Maya Chen | `maya.chen.demo@hvu.example` |
| Jordan Rivera | `jordan.rivera.demo@hvu.example` |
| Samira Okafor | `samira.okafor.demo@hvu.example` |
| Leo Martinez | `leo.martinez.demo@hvu.example` |
| Amara Mensah | `amara.mensah.demo@hvu.example` |
| Ethan Brooks | `ethan.brooks.demo@hvu.example` |

## Local Setup (XAMPP)

1. Copy the `cms` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. In phpMyAdmin, create an empty database named `cms`.
4. Import `database/cms.sql` first. It drops and recreates the application tables; use only a new or empty database.
5. Import `database/demo_data.sql` to add six demo accounts and 25 sample complaints with replies and ratings. This seed can be rerun; it replaces only complaints whose references begin with `CMP-DEMO-`.
6. Confirm `config/connection.php` uses the XAMPP defaults: host `localhost`, database `cms`, user `root`, and an empty password.
7. Check `config/site.php` and replace its sample contact details if needed.
8. Ensure Apache can create/write `C:\xampp\cms_private_uploads`. Complaint attachments are stored there, outside `htdocs`.
9. Open `http://localhost/cms/` in a browser.

## Upgrade an Existing Installation

1. Back up the current database.
2. Select the existing database in phpMyAdmin and import `database/upgrade_current_schema.sql`. This script adds missing columns and tables without dropping existing users or complaints.
3. Keep `database/cms.sql` for a new/empty database only; it drops and recreates the application tables.
4. Reload the site and try signing in again.

## Deploy

1. Create a MySQL database with the hosting provider and note its host, database name, username and password.
2. Import `database/cms.sql` into a new/empty database. This script drops and recreates tables, so do not import it over data you need to keep.
3. Demo data is for testing only. Do not import `database/demo_data.sql` on a production site. Change or remove the default admin account before launch.
4. Update the database settings in `config/connection.php` and the university contact settings in `config/site.php`.
5. Ensure PHP has PDO MySQL and `fileinfo` enabled. Configure a writable private upload directory outside the public document root; if the host does not allow creating it one directory above the site, update `complaintUploadDirectory()` in `includes/functions.php` to use an approved private path.
6. Upload the application files to the site's web root and verify HTTPS, login, uploads and private attachment downloads.

## Database Tables

`users`, `categories`, `complaints`, `responses`, `announcements` and `login_attempts`. See `database/cms.sql` for the schema and `database/demo_data.sql` for local sample records.

## Project Layout

```
config/      database and site settings
includes/    layout, helpers, session and access checks
auth/        registration, login and logout
user/        complainant dashboard and complaint workflows
account/     user profile
admin/       dashboard, complaints, users and announcements
database/    schema and demo data
assets/      CSS and JavaScript
```
