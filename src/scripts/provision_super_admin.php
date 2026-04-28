<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SuperAdmin;

$email = 'platform@admin.localhost';
$password = 'password';

$existing = SuperAdmin::where('email', $email)->first();
if ($existing) {
    echo "Super-admin '{$email}' already exists (id={$existing->id}).\n";
    exit(0);
}

$admin = SuperAdmin::create([
    'name'      => 'Platform Admin',
    'email'     => $email,
    'password'  => $password,
    'is_active' => true,
]);

echo "Super-admin created (id={$admin->id}).\n";
echo "  Login:    http://admin.localhost:8081/login\n";
echo "  Email:    {$email}\n";
echo "  Password: {$password}\n";
