<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Clear permission cache first
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

echo "--- Debugging Permissions ---\n";

$users = User::all();

foreach ($users as $user) {
    echo "User: " . $user->name . " (" . $user->email . ")\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";

    $canImport = $user->can('master.students.import');
    echo "Can 'master.students.import': " . ($canImport ? 'YES' : 'NO') . "\n";
    echo "---------------------------\n";
}

$permission = Permission::where('name', 'master.students.import')->first();
if ($permission) {
    echo "Permission 'master.students.import' exists in DB.\n";
    $roles = $permission->roles->pluck('name')->implode(', ');
    echo "Roles with this permission: $roles\n";
} else {
    echo "Permission 'master.students.import' DOES NOT EXIST in DB.\n";
}

echo "--- End Debug ---\n";
