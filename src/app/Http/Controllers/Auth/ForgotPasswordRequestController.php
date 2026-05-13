<?php

// =============================================================================
// REMOVED on 2026-05-12.
//
// This controller backed the legacy verify-identity → admin-approval password
// reset flow. It was replaced with Laravel's built-in email-link reset
// (PasswordResetLinkController + NewPasswordController, routes in
// routes/auth.php under the names `password.request` / `password.email` /
// `password.reset` / `password.store`).
//
// Safe to `git rm` this file. The class is no longer referenced anywhere in
// the codebase. Routes that used it have been removed from routes/tenant.php,
// and the login page now links to `route('password.request')` instead.
// =============================================================================
