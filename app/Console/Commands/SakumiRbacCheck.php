<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class SakumiRbacCheck extends Command
{
    protected $signature = 'sakumi:rbac-check';

    protected $description = 'Check RBAC health for SAKUMI';

    public function handle()
    {
        $this->info("SAKUMI RBAC DIAGNOSTIC");
        $this->line("--------------------------------");

        $roles = Role::count();
        $permissions = Permission::count();
        $users = User::count();

        $this->info("Roles: $roles");
        $this->info("Permissions: $permissions");
        $this->info("Users: $users");

        $pivotRolePerm = DB::table('role_has_permissions')->count();
        $pivotUserRole = DB::table('model_has_roles')->count();

        $this->line("");
        $this->info("Role-Permission mappings: $pivotRolePerm");
        $this->info("User-Role mappings: $pivotUserRole");

        $this->line("");
        $this->info("Checking super_admin...");

        $super = Role::where('name','super_admin')->first();

        if ($super) {
            $this->info("super_admin role exists");
            $this->info("Permissions attached: ".$super->permissions->count());
        } else {
            $this->error("super_admin role missing");
        }

        $this->line("");
        $this->info("RBAC CHECK COMPLETE");

        return Command::SUCCESS;
    }
}
