{{--
    REMOVED on 2026-05-12.

    This view was the legacy verify-identity password-reset form. It has been
    replaced with `auth/forgot-password.blade.php` (single email field, sends
    Laravel's built-in reset link) and `auth/reset-password.blade.php` (the
    target of the email link).

    Safe to `git rm` this file. Nothing references it anymore.
--}}
