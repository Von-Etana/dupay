<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\AdminRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        if (! $this->app->routesAreCached()) {
            Passport::ignoreRoutes();
        }

        if (\Schema::hasTable("permissions")) {
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
			Gate::define(
				$permission["key"],
				function ($user = null) use ($permission) {
                    if (!auth()->guard("admin")->check()) {
						return false;
					}
                    if ((auth()->guard("admin")->user()->id == 1)) {
						return true;
					}
					$roles = AdminRole::where("admin_id", auth()->guard("admin")->user()["id"])->select("role_id")->get();
					foreach ($roles as $role) {
                        $check = RolePermission::where(
							[
								["role_id", $role["role_id"]], ["permission_id", $permission["id"]]
							]
						)->count();
						if (!$check) {
							return false;
						}
						return  true;

					}
                    return false;
				}

			);

		}
    }
    }
}
