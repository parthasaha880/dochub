<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Authentication\Models\UserDevice;
use App\Modules\Authentication\Policies\UserDevicePolicy;
use App\Modules\Authentication\Repositories\AuthRepository;
use App\Modules\Authentication\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;
use App\Modules\Dashboard\Repositories\DashboardRepository;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\Folder;
use App\Modules\Documents\Policies\DocumentPolicy;
use App\Modules\Documents\Policies\FolderPolicy;
use App\Modules\Documents\Repositories\Contracts\DocumentRepositoryInterface;
use App\Modules\Documents\Repositories\DocumentRepository;
use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Designation;
use App\Modules\Organization\Models\Employee;
use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\Section;
use App\Modules\Organization\Models\Unit;
use App\Modules\Organization\Policies\OrganizationStructurePolicy;
use App\Modules\Organization\Repositories\Contracts\OrganizationStructureRepositoryInterface;
use App\Modules\Organization\Repositories\OrganizationStructureRepository;
use App\Modules\Search\Models\SavedSearch;
use App\Modules\Search\Policies\SavedSearchPolicy;
use App\Modules\Search\Repositories\Contracts\SearchRepositoryInterface;
use App\Modules\Search\Repositories\SearchRepository;
use App\Modules\Users\Policies\PermissionPolicy;
use App\Modules\Users\Policies\RolePolicy;
use App\Modules\Users\Policies\UserPolicy;
use App\Modules\Users\Repositories\Contracts\UserManagementRepositoryInterface;
use App\Modules\Users\Repositories\UserManagementRepository;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowInstance;
use App\Modules\Workflow\Policies\WorkflowPolicy;
use App\Modules\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(OrganizationStructureRepositoryInterface::class, OrganizationStructureRepository::class);
        $this->app->bind(UserManagementRepositoryInterface::class, UserManagementRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(WorkflowRepositoryInterface::class, WorkflowRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(SearchRepositoryInterface::class, SearchRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(UserDevice::class, UserDevicePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowInstance::class, WorkflowPolicy::class);
        Gate::policy(SavedSearch::class, SavedSearchPolicy::class);

        foreach ([
            Organization::class,
            Branch::class,
            Department::class,
            Section::class,
            Unit::class,
            Office::class,
            Designation::class,
            Employee::class,
        ] as $model) {
            Gate::policy($model, OrganizationStructurePolicy::class);
        }

        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        Password::defaults(function () {
            $rule = Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
