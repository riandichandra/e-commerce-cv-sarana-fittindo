# Email Verification Implementation Plan

## Goal
Add email verification to the current Laravel auth flow so that:
1. A verification email is automatically sent when a user registers.
2. Unverified users cannot use the account until their email is verified.
3. Mail sender credentials are configurable through `.env`.

## Current state in repo
- `routes/auth.php` already includes standard verification routes:
  - `verification.notice`
  - `verification.verify`
  - `verification.send`
- `app/Http/Controllers/Auth/RegisteredUserController.php` already fires `event(new Registered($user))` after registration.
- `app/Models/User.php` currently does not implement `Illuminate\Contracts\Auth\MustVerifyEmail`.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` logs users in and redirects them by role without checking verification.
- `config/mail.php` and `.env.example` already define environment-driven SMTP settings.

## Required implementation changes

### 1. Enable Laravel email verification on the User model
- Update `app/Models/User.php`:
  - import `Illuminate\Contracts\Auth\MustVerifyEmail`
  - implement `MustVerifyEmail` on the `User` class
- Verify the `users` table has an `email_verified_at` datetime column (Laravel auth migrations normally include this).

### 2. Keep auto-email sending on registration
- The registered event is already fired in `RegisteredUserController::store()`.
- With `MustVerifyEmail`, Laravel will automatically queue/send the verification notification via the `Registered` event.
- Confirm mailer configuration is correct for the selected driver.

### 3. Block unverified accounts from using the app
There are two valid enforcement strategies:

Option A (recommended):
- Keep the current auto-login behavior.
- Enforce verification on protected routes by applying the `verified` middleware to all authenticated route groups that require a real account.
- This is the standard Laravel approach and keeps UX consistent.

Option B (more strict):
- Prevent unverified users from logging in or immediately log them out after registration.
- Show a message asking them to verify their email before continuing.

### 4. Apply `verified` middleware to authenticated routes
- Add `->middleware(['auth', 'verified'])` to routes or route groups that should only be accessible by verified users.
- At minimum, protect:
  - dashboard routes
  - order/checkout flows
  - profile/account management routes
- Ensure the default redirect for unverified users goes to `verification.notice`.

### 5. Optionally strengthen login validation
- Add a verification check in `LoginRequest::authenticate()` or `AuthenticatedSessionController::store()`.
- If the user is authenticated but `! $user->hasVerifiedEmail()`, return a validation error or redirect to the verification notice.
- Example error message: `Your email address is not verified. Please check your inbox.`

## `.env` / mail credential plan
Use the existing environment variables in `.env.example`:
- `MAIL_MAILER` (e.g. `smtp`)
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_SCHEME` (e.g. `tls`)
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

This is already wired by `config/mail.php`, so no new config file is required.

## Implementation checklist
1. `app/Models/User.php`
   - implement `MustVerifyEmail`
2. `app/Http/Controllers/Auth/RegisteredUserController.php`
   - keep `event(new Registered($user))`
   - optionally redirect fresh registrants to the verification notice instead of dashboard
3. `routes/web.php` and/or `routes/auth.php`
   - add `verified` middleware where required
4. `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
   - optionally reject login for unverified users or redirect them to verification notice
5. `.env.example` and `.env`
   - document or set mail credentials for SMTP provider
6. Test cases
   - registration triggers verification email
   - unverified users cannot access verified routes
   - email verification link successfully updates `email_verified_at`
   - verified users can log in and use the app normally

## Notes
- The app already has the necessary verification route scaffolding.
- The primary missing piece is the `MustVerifyEmail` contract on the `User` model and route enforcement for verified users.
- If the goal is strict enforcement, consider logging users out after registration until verification is complete.
