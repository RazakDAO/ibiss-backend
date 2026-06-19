<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hash = app('hash')->make('Genius04');

$user = App\Models\User::updateOrCreate(
    ['email' => 'abdouldao1998@gmail.com'],
    [
        'name'              => 'Abdoul DAO',
        'email'             => 'abdouldao1998@gmail.com',
        'password'          => $hash,
        'role'              => 'admin',
        'email_verified_at' => now(),
    ]
);

echo "Compte admin cree : " . $user->email . " (ID: " . $user->id . ")\n";
