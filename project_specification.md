# Project Specification

## Web-Based Event Management System for Sining Banwa with SMS Notification

**Document type:** Project Specification\
**Project status:** Starting Development (new build, thesis defense prototype)\
**Organization:** Sining Banwa\
**Location:** Albay, Philippines\
**Purpose:** Thesis planning, implementation guidance, and LLM/Vibe Coding reference

## 1. Project Overview

The Web-Based Event Management System for Sining Banwa with SMS Notification is a responsive web application intended to centralize event management, ticket reservations, payment monitoring, attendance tracking, sales reporting, and audience evaluation.

Sining Banwa is an independent arts organization in Albay, Philippines, that conducts theater performances, workshops, and other cultural activities. The system will support online customer reservations and staff-created bookings while complementing existing publicity channels such as social media, meetings, and other communication methods.

### 1.1 Project Nature

The system is a working prototype built for the thesis defense and presentation; it is not yet intended for live, real-money transactions. Sining Banwa does not have a payment system. Payment is made by sending money to a QR code (a static image) supplied by management, and payment is verified manually by authorized staff. No payment-provider API is integrated.

## 2. Objectives

1.  Centralize event information and reservation records.
2.  Allow audiences to browse events and reserve seats online.
3.  Allow management to design a separate seating layout and pricing structure for each event.
4.  Support online and admin/manual bookings in one system.
5.  Organize payment submissions and manual payment verification.
6.  Generate unique electronic tickets and validate them during event entry.
7.  Record attendance through ticket scanning.
8.  Send SMS notifications for important reservation and event updates.
9.  Organize sales, recorded expenses, and event reports.
10. Collect and summarize audience feedback.
11. Protect operational and customer information through role-based access control.

## 3. Scope

### 3.1 Included Features

-   Public homepage, event listings, event details, and posters
-   Login and customer registration
-   Customer dashboard and profile management
-   Administrator/staff dashboard
-   Event creation and management
-   Visual seating layout designer
-   Event-specific sections, rows, seats, and seat pricing
-   Online reservations and admin/manual bookings
-   Temporary seat locking
-   Payment instructions and proof-of-payment submission
-   Manual payment verification
-   E-ticket generation with unique ticket code and QR/barcode
-   Ticket scanning and validation
-   Attendance recording
-   SMS and email notifications
-   Sales and reservation monitoring
-   Production expense recording
-   Reports and event evaluation
-   Responsive interface for desktop, tablet, and mobile
-   Security and role-based access control

### 3.2 Outside the Initial Scope

Unless separately approved, the initial system will not include a native mobile application, automatic payment verification without a payment-provider integration, integrations with third-party ticketing platforms or POS systems, automatic computation of actor/production staff compensation, or replacement of Sining Banwa's existing publicity activities.

## 4. User Roles and Permissions

### 4.1 Customer / Audience

Customers can view published events, register and log in, manage permitted profile information, view seating layouts and prices, reserve available seats, view reservation and payment statuses, submit payment details and proof when required, access their own e-tickets, view reservation history, receive notifications, and submit eligible event evaluations.

Customers must only access their own account, reservations, payment submissions, tickets, and evaluations.

### 4.2 Administrator / Staff

Authorized administrator/staff users can access the dashboard; create, edit, publish, complete, and cancel events according to permissions; design seating layouts; create sections, rows, and seats; set section and individual seat prices; block seats; manage reservations; create manual bookings; record the staff member who created each manual booking; review payments; monitor tickets and attendance; view sales information and reports; record production expenses and manually entered compensation amounts where required; review evaluations; and monitor notifications.

Sensitive operations must be restricted to authorized personnel. If administrator and staff permissions differ, the differences must be documented and enforced.

### 4.3 Venue Personnel

Venue personnel can access a venue dashboard, select assigned events, scan tickets, validate ticket status and event association, record successful check-ins, and view attendance information required for event entry.

Venue personnel must not access payment verification, user administration, or sensitive financial reports unless specifically authorized.

## 5. Functional Requirements

### FR-01: Authentication and Access Control

-   Users must log in before accessing protected features.
-   Customer registration must validate required information.
-   Passwords must be stored securely using password hashing.
-   Server-side authorization must protect every restricted operation.
-   Inactive or unauthorized accounts must not access protected features.
-   Users must be able to log out securely.

### FR-02: Event Management

-   Authorized users must be able to create and edit events.
-   Event fields should include title, description, poster, date, time, venue, status, creator, and relevant timestamps.
-   Events should support draft, published, completed, and cancelled statuses.
-   Only published events should appear in public listings.
-   Event cancellation or deletion must not silently damage historical transactions.

### FR-03: Visual Seat Layout Designer

-   Management must be able to create a separate seating layout for each event.
-   Management must be able to create and manage sections, rows, and individual seats.
-   Each seat must have a unique label within its event.
-   Visual seat positions must be saved and reloaded from the database.
-   Management must be able to assign a default price to a section and override individual seat prices.
-   Management must be able to block seats.
-   Saved layouts must persist after the page is refreshed.
-   Existing booked or sold seats must be protected when editing a layout.
-   Management must be able to preview the layout before customer bookings are accepted.

### FR-04: Seat Availability and Pricing

-   Customers must see each seat's current availability and price.
-   The seat map must update live (for example, by polling the server every few seconds) so customers see newly locked or booked seats without refreshing the page.
-   The server must calculate booking totals using database prices.
-   Prices submitted by the browser must not be trusted.
-   A reservation may contain multiple seats.
-   The price charged for each seat must be stored as a historical snapshot.
-   Seat status must remain consistent across reservation, payment, cancellation, and expiration workflows.

Suggested seat states: `available`, `locked`, `reserved`, `sold`, and `blocked`. The final transition rules must be defined and tested.

### FR-05: Reservation Management

-   Customers must be able to reserve one or more available seats.
-   Selected seats must be temporarily locked for five minutes by default; the duration should be configurable.
-   Expired locks must be released by a reliable server-side process (for example, expired locks are checked and released whenever seat availability is read or a reservation is attempted, with an optional scheduled cleanup).
-   The system must prevent duplicate or conflicting reservations.
-   Reservations must distinguish online bookings from admin/manual bookings.
-   Manual bookings must record the staff member who created them.
-   Reservation statuses should distinguish pending, awaiting payment, payment verification, confirmed, cancelled, and expired states, or an equivalent documented status model.
-   Reservation totals must be calculated and validated on the server.

### FR-06: Payment Handling

-   Display organization-approved payment instructions and the static payment QR code image supplied by management.
-   Allow customers to submit payment references and proof of payment when required.
-   Allow authorized staff to verify or reject payment submissions.
-   Record payment amount, status, submission time, verification time, and verifying user where applicable.
-   Displaying a QR code must not be treated as proof of payment.
-   Without a real payment-provider API, payment verification remains manual.
-   Pending or rejected payments must not be counted as collected revenue.

### FR-07: E-Ticket Generation

-   Generate a unique ticket identifier for each eligible ticket.
-   Issue tickets only after the required reservation and payment conditions are met.
-   Allow customers to access their own tickets.
-   Provide a QR code or barcode linked to a server-validated identifier.
-   Do not embed sensitive personal or payment information directly in ticket codes.
-   Provide a print-friendly ticket view where practical.

The final policy (one ticket per reservation or one ticket per reserved seat) must be decided before implementation.

### FR-08: Ticket Validation and Attendance

-   Authorized personnel must be able to scan or enter a ticket identifier.
-   The system must check ticket validity, event association, cancellation status, and prior use.
-   Successful check-in must record the ticket, event, time, and responsible user.
-   Repeated scans must not create duplicate successful check-ins.
-   Invalid, cancelled, already-used, or wrong-event tickets must be clearly identified.
-   Venue personnel must only access information needed for event entry.

### FR-09: SMS and Email Notifications

Notifications should cover reservation updates, payment verification or rejection, ticket confirmation, day-before event reminders, and relevant event or reservation changes.

Requirements:

-   Record notification attempts and results.
-   Distinguish provider acceptance from confirmed delivery when supported.
-   Log failures and handle retries appropriately.
-   Store SMS credentials securely; never expose them in frontend code.
-   Use a reliable scheduler or supported alternative for reminders.
-   Avoid duplicate reminders.
-   Test actual SMS delivery with the selected provider before claiming the integration is operational.

### FR-10: Sales and Expense Monitoring

-   Allow authorized users to review ticket sales and reservations.
-   Distinguish online bookings from admin/manual bookings.
-   Distinguish reservation value, verified payments, pending payments, rejected payments, and recorded expenses.
-   Allow authorized users to record production expenses.
-   Compensation amounts may be entered manually when required.
-   Automatic compensation calculation is outside the initial scope unless separately approved.

### FR-11: Reports and Dashboard

Reports should include reservations by event, booking source, payment statuses and verified payment totals, ticket sales, attendance, seat availability, event evaluation summaries, production expenses, and event-level financial summaries.

Where practical, reports should support event and date-range filters. Dashboard totals must agree with the underlying records.

### FR-12: Event Evaluation

-   Eligible customers must be able to rate completed events and leave optional comments.
-   Validate ratings and prevent duplicate evaluations according to the approved rules.
-   Authorized users must be able to review evaluation summaries.
-   Protect customer information from unauthorized access.

### FR-13: Event Marketing and View Monitoring

-   Public pages must display published events and relevant information.
-   Event details should include posters, descriptions, schedules, venues, and reservation links.
-   The system may track event listing views.
-   The website complements existing publicity channels rather than replacing them.

## 6. Main Workflows

### 6.1 Event Setup

1.  Authorized user creates an event.
2.  Management creates sections, rows, and seats.
3.  Management assigns section prices and individual seat price overrides.
4.  Management reviews and saves the layout.
5.  The event is published when ready for public viewing and booking.

### 6.2 Online Reservation

1.  Customer selects a published event.
2.  Customer views the seating layout and prices.
3.  Customer selects available seats.
4.  The system validates availability and temporarily locks the seats.
5.  The system calculates the total using database prices.
6.  Customer follows payment instructions and submits payment details.
7.  Authorized staff reviews the payment.
8.  Once required conditions are satisfied, the reservation is confirmed and ticket(s) are issued.
9.  Relevant notifications are sent through configured providers.

### 6.3 Admin / Manual Booking

1.  Staff finds an existing customer or enters required customer information.
2.  Staff selects the event and available seats.
3.  The system validates availability and calculates the total.
4.  The system records the booking source and staff creator.
5.  Payment and confirmation follow the actual transaction.
6.  The booking is included in reservation, ticket, attendance, and reporting workflows.

### 6.4 Event-Day Entry

1.  Venue personnel selects the event.
2.  Personnel scans or enters a ticket identifier.
3.  The system validates the ticket.
4.  If valid and unused, attendance is recorded.
5.  If invalid or already used, the system displays the reason and does not record a second successful check-in.

### 6.5 Evaluation and Reporting

1.  The event is completed.
2.  Eligible customers submit evaluations.
3.  The system summarizes ratings and comments.
4.  Authorized management reviews sales, payments, expenses, attendance, and evaluations.

## 7. Proposed Database Structure

These are logical entities. The exact schema, data types, and relationships must be designed and reviewed before implementation.

| Table / Entity        | Purpose                                                                  |
|-----------------------|--------------------------------------------------------------------------|
| `users`               | Accounts, roles, credentials, and account status                         |
| `events`              | Event information, schedule, venue, poster, status, and creator          |
| `event_sections`      | Event-specific seating sections and default prices                       |
| `event_seats`         | Individual seats, section membership, labels, prices, positions, and availability |
| `reservations`        | Booking, customer, status, total amount, source, and creator             |
| `reservation_items`   | Seats included in a reservation and historical price per seat            |
| `payments`            | Payment submissions, amounts, references, proof, status, and verification details |
| `tickets`             | Unique ticket identifiers, status, and issuance details                  |
| `attendance`          | Check-in records and responsible personnel                               |
| `evaluations`         | Customer ratings and comments for events                                 |
| `production_expenses` | Event expenses and manually recorded compensation entries where applicable |
| `notifications`       | SMS/email messages, recipients, related records, and sending results     |

Database requirements:

-   Use primary keys and appropriate foreign keys.
-   Support multiple seats per reservation.
-   Preserve the price charged for each seat at booking time.
-   Ensure seats belong to an event and may belong to a section.
-   Enforce ticket identifier uniqueness.
-   Prevent duplicate successful attendance for the same ticket.
-   Preserve historical transaction consistency when event details change.
-   Keep database changes organized as documented, repeatable migration scripts.

## 8. Non-Functional Requirements

### Usability

-   Interfaces should be understandable to customers, staff, and venue personnel.
-   Common tasks should require as few steps as practical.
-   Error and validation messages should be clear.
-   The seating designer must be practical for real event layouts.

### Responsiveness

-   Public pages and dashboards must adapt to desktop, tablet, and mobile screens.
-   Ticket scanning should work on supported devices with camera permission, with manual code entry as a fallback where practical.

### Reliability and Data Integrity

-   Reservation and seat availability changes must remain consistent.
-   Concurrent booking attempts must not result in two confirmed reservations for the same seat.
-   Notification failures must not corrupt reservations.
-   Use database transactions where appropriate.
-   Document backups and recovery procedures.

### Security

-   Use password hashing, secure session handling, prepared statements (or equivalent protection against SQL injection), and server-side validation.
-   Enforce role-based authorization on every protected operation.
-   Restrict customers to their own records.
-   Validate uploaded file types, sizes, and access permissions.
-   Protect payment proof and sensitive records.
-   Keep API credentials and database secrets out of public-facing code.
-   Log important administrative actions where appropriate.

### Maintainability

-   Keep backend code, styles, scripts, and reusable components organized.
-   Use clear naming conventions and avoid unnecessary duplication.
-   Document migrations and environment configuration.
-   Test each module before implementing the next.

## 9. Selected Technology Stack

The technology stack for the new build has been selected. All development must use this stack.

| Layer | Selection |
|-------|-----------|
| Backend | PHP 8 (plain PHP organized with Composer packages), using PDO with prepared statements |
| Database | MySQL / MariaDB (InnoDB), with transactions and row locking (`SELECT ... FOR UPDATE`) for seat locking |
| Frontend | HTML, CSS, and vanilla JavaScript, with Bootstrap 5 (or Tailwind CSS) for responsive layouts |
| Seat designer | SVG or Canvas, optionally with Konva.js, for drag-and-drop seat positioning |
| Email | PHPMailer using SMTP or a suitable email service |
| QR code generation | A PHP QR library such as endroid/qr-code |
| QR code scanning | A browser-based scanner such as html5-qrcode, with manual code entry as a fallback |
| SMS | An SMS provider that supports Philippine numbers (to be selected and tested) |
| Live seat updates | Polling the server every few seconds (an external real-time service is optional) |
| Scheduled tasks | Hosting cron job for reminders and optional cleanup; expired seat locks are also released on demand |
| Hosting | A PHP- and MySQL-compatible host (to be selected; Vercel is not used) |
| Local development | XAMPP and VS Code, with an offline copy kept as a backup for the defense |

Selection notes:

-   Providers and libraries must be evaluated and tested before they are relied upon.
-   Hosting must be checked for PHP version, required extensions, MySQL access, file uploads, HTTPS, scheduled tasks, and outbound API access.
-   Payment proof images must be validated for type and size, and protected from unauthorized access.

## 10. Development Phases

1.  Phase 1 --- Project Setup and Authentication
2.  Phase 2 --- Event Management
3.  Phase 3 --- Visual Seat Layout Designer
4.  Phase 4 --- Public Website and Customer Accounts
5.  Phase 5 --- Online Reservations and Admin/Manual Booking
6.  Phase 6 --- Payment Monitoring and Verification
7.  Phase 7 --- E-Ticket Generation and Validation
8.  Phase 8 --- SMS and Email Notifications
9.  Phase 9 --- Attendance Monitoring
10. Phase 10 --- Sales, Expenses, and Reports
11. Phase 11 --- Event Evaluation
12. Phase 12 --- Testing, Security, and Deployment Preparation

Each phase must include scope and dependency review, implementation, testing, a list of files changed, database migration notes, and approval before the next phase begins.

## 11. Testing and Acceptance Criteria

Testing must cover:

-   Login, logout, account status, and role permissions
-   Event creation, editing, publishing, and cancellation
-   Seating layout creation, editing, saving, and reloading
-   Section prices and individual seat price overrides
-   Online and manual reservations
-   Five-minute seat locking and expiration
-   Prevention of duplicate seat bookings
-   Payment submission and manual verification
-   Ticket issuance only after required conditions are met
-   Valid, invalid, cancelled, wrong-event, and already-used ticket scans
-   Attendance recording and duplicate check-in prevention
-   SMS/email provider responses and reminder scheduling
-   Report calculations against known test records
-   Evaluation eligibility and duplicate-submission prevention
-   Responsive display and access restrictions
-   Database integrity, backup, and recovery procedures

For each test, record expected result, actual result, and pass/fail status. Any untested external integration must be identified as unverified.

## 12. Deployment Considerations

-   Choose a host that supports PHP 8, MySQL, file uploads, HTTPS, and cron jobs.
-   Verify scheduled-task support for day-before SMS reminders and seat-lock expiry.
-   Confirm HTTPS, database access, file storage, and outbound API access.
-   Configure secrets through environment variables or secure server-side configuration.
-   Migrate the database carefully and back up existing records.
-   Test live SMS and email configuration.
-   Ensure uploaded files cannot execute as code.
-   Prepare a backup and recovery plan.
-   Keep an offline copy of the working demo (for example, on a laptop running XAMPP) in case internet access fails during the defense.
-   Do not assume free hosting supports every required feature.

## 13. Decisions to Finalize

Already decided: the technology stack (Section 9) and manual payment verification using a static QR image (Section 1.1).

1.  Whether tickets are issued per reservation or per reserved seat.
2.  Whether administrator and staff permissions differ.
3.  Which SMS provider will be used and its costs, restrictions, and delivery statuses.
4.  Which email delivery method will be used.
5.  How payment rejection and resubmission will work.
6.  How expired seat locks will be released locally and on hosting.
7.  Whether layouts can be changed after seats have been booked.
8.  Which roles may view financial reports and record expenses.
9.  Which PHP/MySQL hosting environment will be used.

## 14. Instructions for AI-Assisted Development

Any LLM or Vibe Coding tool used for this project must:

-   Build the system from scratch, following the phases in this specification.
-   Use only the technology stack in Section 9 (PHP 8, MySQL, vanilla JavaScript, and the listed libraries); ask before adding another framework or service.
-   Inspect the current project files and database before making changes in each phase.
-   Never assume a feature works unless it has been tested.
-   Never drop or recreate tables that contain data without approval and a safe migration plan.
-   Implement only the requested phase.
-   Explain changes in simple language suitable for a BSIT student.
-   Keep backend code, styles, and scripts separated and organized.
-   Provide setup and testing instructions.
-   Stop and wait for approval before beginning the next phase.
-   Never claim real SMS delivery, payment verification, or ticket validation without actual testing.
-   Preserve working features and existing data as the project grows.

------------------------------------------------------------------------

**End of Project Specification**
