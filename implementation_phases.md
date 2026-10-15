# Implementation Phases
## Web-Based Event Management System for Sining Banwa with SMS Notification

This document divides the project into manageable development phases for a **new build**. Complete and test each phase before moving to the next.

**Technology stack (see Project Specification, Section 9):** PHP 8 (PDO, prepared statements), MySQL/MariaDB (InnoDB), HTML, CSS, vanilla JavaScript, Bootstrap 5 (or Tailwind CSS), PHPMailer, a PHP QR library, a browser QR scanner, an SMS provider for Philippine numbers, and polling for live seat updates. Local development uses XAMPP and VS Code. Deployment uses a PHP/MySQL-compatible host (to be selected).

**Project nature:** This is a prototype for the thesis defense. There is no payment system; payment is made by sending money to a static QR code image supplied by management, and staff verify payments manually.

## Phase 1: Project Setup and Environment
**Goal:** Prepare a clean, organized project foundation.

- Install and configure XAMPP (PHP 8, MySQL) and VS Code, and confirm that PHP and MySQL run locally.
- Create the project folder structure, for example: `public/` (web root), `app/` (PHP logic), `config/`, `database/migrations/`, `uploads/` (outside or protected from the web root), and `assets/` (CSS, JavaScript, images).
- Set up Composer for PHP libraries.
- Create a configuration file for database credentials and settings, kept out of public pages and out of version control.
- Create the database and a reusable PDO connection.
- Initialize Git for version control.
- Create a basic page layout (header, footer, navigation) with Bootstrap or Tailwind.

**Completion check:** The project runs locally, connects to the database, shows a styled home page, and is committed to Git.

## Phase 2: Database Design and Migration
**Goal:** Create the database for the complete system.

- Design and create tables for `users`, `events`, `event_sections`, `event_seats`, `reservations`, `reservation_items`, `payments`, `tickets`, `attendance`, `evaluations`, `production_expenses`, and `notifications`.
- Support multiple seats per reservation.
- Store the actual price paid for each reserved seat so later price changes do not alter old bookings.
- Store the booking source (`online` or `admin`) and the staff member who created an admin booking.
- Add suitable primary keys, foreign keys, unique constraints, and indexes (for example, a unique seat label per event and a unique ticket code).
- Keep every change as a numbered, repeatable migration SQL file in `database/migrations/`.
- Create a seed file with sample data (an admin account, a staff account, a venue account, and a sample customer) for testing and the demo.

**Completion check:** All tables and relationships are verified in phpMyAdmin, migrations run cleanly on an empty database, and seed data loads correctly.

## Phase 3: User Accounts and Access Control
**Goal:** Make sure each user can access only the pages and actions allowed for their role.

- Support customer, administrator/staff, and venue personnel roles.
- Implement customer registration and login for all roles.
- Hash passwords with `password_hash()` and verify them with `password_verify()`; use secure PHP sessions.
- Block inactive accounts.
- Protect restricted pages and validate permissions on the server using one central role-check function.
- Add logout and basic profile management.

**Completion check:** Each role can log in and is prevented from accessing unauthorized pages or actions.

## Phase 4: Event Management and Public Event Pages
**Goal:** Allow staff to manage Sining Banwa events and let the public view them.

- Create, view, edit, publish, unpublish, complete, and cancel events.
- Store the event title, description, poster, date, time, venue, status, and creator.
- Validate required fields, poster file type and size, and event dates.
- Show the public homepage, event listings, and event details for published events only.
- Optionally record event listing views.
- Make sure cancelling or archiving an event does not delete historical records.

**Completion check:** Staff can manage events and audiences can view only published events.

## Phase 5: Visual Seat Layout Designer
**Goal:** Let management design seating for each event.

- Create and manage seating sections and rows.
- Add, rename, move (drag and drop), and remove seats in the layout using SVG, Canvas, or Konva.js.
- Save each seat's label and visual position in the database.
- Set a default price for each section and allow individual seat price overrides.
- Mark seats as available, blocked, reserved, or sold as appropriate.
- Provide a preview of the layout before bookings are accepted.
- Display the saved layout consistently to management and customers.
- Prevent changes that would damage existing confirmed bookings.

**Completion check:** Management can save a layout with different seat prices, reopen it, and see the same layout and pricing.

## Phase 6: Ticket Reservation, Seat Locking, and Live Seat Map
**Goal:** Allow customers to reserve available seats without double-booking.

- Display available events, seating layouts, and prices.
- Allow customers to select one or more seats.
- Calculate the total on the server using the selected seats' database prices; never trust prices sent by the browser.
- Temporarily lock selected seats for five minutes (configurable) using database transactions and row locking (`SELECT ... FOR UPDATE`) plus a unique constraint to block duplicates.
- Release expired locks whenever seat availability is read or a reservation is attempted, with an optional scheduled cleanup.
- Update the seat map live by polling the server every few seconds.
- Create a reservation with its individual reservation items.
- Show reservation status and details to the customer.

**Completion check:** Multiple seats can be reserved together, totals are correct, expired locks are released, the seat map updates for other users without refreshing, and the same seat cannot be booked twice.

## Phase 7: Admin and Staff Manual Booking
**Goal:** Allow authorized staff to book tickets on behalf of customers.

- Add a booking function in the admin/staff area.
- Find an existing customer or enter the customer's required details.
- Select the event and seats, and calculate the total using the saved seat prices.
- Record the booking source as `admin` and store the staff member who created it.
- Apply the same seat availability, locking, and duplicate-booking checks used for online reservations.
- Make admin-created bookings visible in reservation records and reports.

**Completion check:** Staff can create a valid booking, and the system records who created it and how it was booked.

## Phase 8: Payment Monitoring and Verification (Static QR, Manual Verification)
**Goal:** Track payments and prevent unverified payments from being treated as completed sales.

- Let management upload and display the static payment QR code image and payment instructions.
- Allow customers to submit a payment reference number and proof of payment (screenshot).
- Validate proof-of-payment file type and size, store it safely, and allow only the owner and authorized staff to view it.
- Let authorized staff review and approve or reject submitted payments, with a way to resubmit after rejection.
- Track pending, verified, and rejected payment statuses.
- Record payment amount, method, reference, submission time, verification time, and verifying staff member.
- Confirm reservations and issue tickets only after a payment is verified.
- Do not claim automatic payment verification; no payment-provider integration is included.

**Completion check:** Staff can verify or reject a payment, and reports distinguish pending payments from verified payments.

## Phase 9: E-Ticket and QR Code Validation
**Goal:** Generate tickets and prevent ticket reuse.

- Decide whether tickets are issued per reservation or per reserved seat.
- Generate a unique ticket identifier and QR code for each eligible ticket (the QR code holds only the ticket identifier).
- Display the e-ticket to the customer, with a print-friendly view.
- Build the venue scanning page using a browser QR scanner, with manual code entry as a fallback.
- Validate ticket status and event details when scanned.
- Record successful entry with the date, time, and venue personnel account.
- Reject invalid, cancelled, wrong-event, or previously used tickets.
- Prevent duplicate scans from recording a second entry.

**Completion check:** A valid ticket can be scanned once, and a second scan is rejected.

## Phase 10: Email and SMS Notifications
**Goal:** Inform customers about important reservation and event updates.

- Prepare notification templates for reservation creation, payment decisions, ticket confirmation, and event reminders.
- Send email notifications using PHPMailer with a configured SMTP account.
- Connect a suitable SMS provider for Philippine phone numbers and keep its credentials only in server-side configuration.
- Store notification type, recipient, status, provider response, and timestamps.
- Handle provider errors, avoid reporting failed messages as sent, and make sure notification failures never break a reservation.
- Create a reminder script for day-before reminders (run by cron or triggered manually for the demo) that avoids duplicate reminders.
- Test using approved test accounts and numbers.

**Completion check:** Test notifications are sent through the configured services, and their results are recorded accurately.

## Phase 11: Attendance, Reports, Expenses, and Evaluation
**Goal:** Help Sining Banwa monitor event performance.

- Show attendance from validated ticket scans.
- Provide sales, reservation, payment, and attendance reports.
- Filter reports by event and date where useful.
- Separate online bookings from staff-created bookings.
- Count verified payments separately from pending or rejected payments.
- Allow authorized staff to record production expenses and manually entered compensation amounts.
- Provide an event evaluation form for completed events, prevent duplicate evaluations, and summarize submitted feedback.

**Completion check:** Report totals match the underlying records, and attendance and evaluation data are saved correctly.

## Phase 12: Dashboard, Security, and Interface Improvements
**Goal:** Improve usability and protect the application.

- Display useful summaries on the dashboard, such as upcoming events, reservations, verified sales, and attendance.
- Make pages responsive on desktop, tablet, and mobile screens.
- Add clear success, error, empty-state, and confirmation messages.
- Validate and sanitize user input, escape output, and use prepared SQL statements everywhere.
- Apply role checks to all protected actions.
- Protect forms against common web attacks, including CSRF.
- Prevent uploaded files from executing as code.
- Avoid exposing database credentials or sensitive information in public pages.
- Check session handling, password storage, and error logging, and log important administrative actions.

**Completion check:** Main workflows are usable on different screen sizes, and security checks are in place.

## Phase 13: Testing and User Acceptance
**Goal:** Confirm that the system meets the requirements before the defense.

- Test each feature individually and test complete workflows.
- Test incorrect login, inactive accounts, and unauthorized access.
- Test seat pricing, multiple-seat reservations, expired locks, and simultaneous booking attempts.
- Test payment approval and rejection.
- Test QR ticket validation and duplicate scans.
- Test email and SMS success and failure handling; mark any untested integration as unverified.
- Test reports against known sample data.
- Ask intended users, such as staff and audience representatives, to try the system and provide feedback.
- Record the expected result, actual result, and pass/fail status of each test.
- Fix issues and repeat the relevant tests.

**Completion check:** The required test cases pass, known issues are documented, and intended users approve the core workflows.

## Phase 14: Deployment, Backup, and Documentation
**Goal:** Prepare the system for the defense demonstration.

- Choose and configure a PHP- and MySQL-compatible host, and confirm PHP version, extensions, file uploads, HTTPS, cron support, and outbound API access.
- Configure the production database and application settings; keep credentials in secure server-side configuration.
- Configure and test email and SMS provider accounts.
- Load demo data (events, seat layouts, and sample accounts) for the presentation.
- Set up a backup and recovery procedure for the database and uploaded files.
- Keep an offline copy of the working demo (XAMPP on a laptop) in case internet access fails during the defense.
- Create user instructions for administrators, staff, customers, and venue personnel.
- Document installation, configuration, known limitations, and maintenance procedures.
- Verify the deployed system using test accounts before the defense.

**Completion check:** The deployed application works, backups can be restored, the offline copy runs, and the required documentation is available.

---

## Development Rules for AI-Assisted Coding

1. Work on only one phase at a time.
2. Use only the technology stack listed above; ask before adding another framework or service.
3. Build from the current phase's requirements and inspect the existing files and database before changing them.
4. Explain which files will be added or changed before making major changes.
5. Keep PHP, HTML, CSS, and JavaScript organized where practical.
6. Provide SQL changes as separate migration files and explain exactly where to run them (phpMyAdmin or the host's database tool).
7. Back up the database before migrations that modify existing structures or data, and never drop or recreate tables that contain data without approval.
8. Do not use placeholder integrations as if they were real email or SMS services, and do not claim payment verification beyond the manual process.
9. Explain changes in simple language suitable for a BSIT student.
10. After each phase, give clear testing steps and wait for confirmation before proceeding.
11. If an error occurs, troubleshoot the current phase before adding new features.

## Suggested Priority

Build in this order: project setup → database → access control → events → seat designer → reservations → manual bookings → payments → e-tickets and QR validation → notifications → reports and evaluation → security and interface improvements → testing → deployment.

**Important:** SMS and email delivery depend on the actual providers and credentials configured for the project. They must be tested rather than assumed to work. Payment verification remains manual because the system only displays a static QR code image.
