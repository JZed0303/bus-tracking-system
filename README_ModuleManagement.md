# Module Management Guide

## Overview
This guide explains how to implement a dynamic **Module Management** feature in a Laravel app using Spatie Permission.

Goal:
1. Admin creates a module from UI.
2. System auto-creates permissions:
   - `view_{module_slug}`
   - `create_{module_slug}`
   - `update_{module_slug}`
   - `delete_{module_slug}`
3. Sidebar auto-shows module when user has `view_{module_slug}`.
4. Role/User permission pages include newly created modules automatically.


## Stack Assumption
1. Laravel app
2. Spatie Permission package installed
3. Existing role/permission-based middleware in routes


## 1. Database Migration
Create a `modules` table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon')->default('ri-apps-2-line');
            $table->string('route_name')->nullable();
            $table->string('menu_url')->nullable();
            $table->enum('scope', ['admin', 'company', 'both'])->default('both');
            $table->enum('menu_type', ['header', 'dropdown', 'link'])->default('link');
            $table->foreignId('parent_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
```

Run:

```bash
php artisan migrate
```


## 2. Module Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'icon',
        'route_name',
        'menu_url',
        'scope',
        'menu_type',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function permissionName(string $action): string
    {
        return strtolower($action . '_' . $this->slug);
    }
}
```


## 3. Admin Controller (CRUD + Auto Permissions)

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = Module::query()->orderBy('sort_order')->orderBy('title')->get();
        return view('admin.modules.index', compact('modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120', 'unique:modules,title'],
            'icon' => ['nullable', 'string', 'max:80'],
            'route_name' => ['nullable', 'string', 'max:180'],
            'menu_url' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', 'in:admin,company,both'],
            'menu_type' => ['required', 'in:header,dropdown,link'],
            'parent_id' => ['nullable', 'integer', 'exists:modules,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::of($validated['title'])
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($slug === '' || Module::where('slug', $slug)->exists()) {
            return back()->withErrors(['title' => 'Module title is invalid or already exists.'])->withInput();
        }

        $module = Module::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'icon' => $validated['icon'] ?: 'ri-apps-2-line',
            'route_name' => $validated['route_name'] ?: null,
            'menu_url' => $validated['menu_url'] ?: null,
            'scope' => $validated['scope'],
            'menu_type' => $validated['menu_type'],
            'parent_id' => (($validated['menu_type'] ?? 'link') === 'link') ? ($validated['parent_id'] ?? null) : null,
            'sort_order' => (int)($validated['sort_order'] ?? 0),
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);

        if (($validated['menu_type'] ?? 'link') !== 'header') {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::findOrCreate($module->permissionName($action), 'web');
            }
        }

        return redirect()->route('admin.modules.index')->with('success', 'Module created.');
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120', 'unique:modules,title,' . $module->id],
            'icon' => ['nullable', 'string', 'max:80'],
            'route_name' => ['nullable', 'string', 'max:180'],
            'menu_url' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', 'in:admin,company,both'],
            'menu_type' => ['required', 'in:header,dropdown,link'],
            'parent_id' => ['nullable', 'integer', 'exists:modules,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $module->update([
            'title' => $validated['title'],
            'icon' => $validated['icon'] ?: 'ri-apps-2-line',
            'route_name' => $validated['route_name'] ?: null,
            'menu_url' => $validated['menu_url'] ?: null,
            'scope' => $validated['scope'],
            'menu_type' => $validated['menu_type'],
            'parent_id' => (($validated['menu_type'] ?? 'link') === 'link') ? ($validated['parent_id'] ?? null) : null,
            'sort_order' => (int)($validated['sort_order'] ?? 0),
            'is_active' => (bool)($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.modules.index')->with('success', 'Module updated.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            Permission::where('name', $module->permissionName($action))->delete();
        }

        $module->delete();

        return redirect()->route('admin.modules.index')->with('success', 'Module deleted.');
    }
}
```


## 4. Admin Routes
Protect routes so only admin/super_admin can access.

```php
Route::middleware(['auth', 'can:manage_roles'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::prefix('modules')
            ->name('modules.')
            ->middleware('role:super_admin|admin')
            ->group(function () {
                Route::get('/', [ModuleController::class, 'index'])->name('index');
                Route::post('/', [ModuleController::class, 'store'])->name('store');
                Route::put('{module}', [ModuleController::class, 'update'])->name('update');
                Route::delete('{module}', [ModuleController::class, 'destroy'])->name('destroy');
            });
    });
```


## 5. Sidebar Auto-Render
Load active modules, filter by scope and permission, then render:
1. Header items (`menu_type = header`)
2. Dropdown parent (`menu_type = dropdown`)
3. Normal links (`menu_type = link`)

```blade
@php
    $dynamicModules = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('modules')) {
        $dynamicModules = \App\Models\Module::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->filter(function ($module) {
                if (auth()->user()->isCompanyUser() && $module->scope === 'admin') return false;
                if (auth()->user()->isAdminUser() && $module->scope === 'company') return false;
                if (in_array($module->menu_type, ['header', 'dropdown'])) return true;
                return auth()->user()->can('view_' . $module->slug);
            });
    }
@endphp

@if ($dynamicModules->isNotEmpty())
    <li class="menu-title">Modules</li>
    @foreach ($dynamicModules->where('menu_type', 'header') as $module)
        <li class="menu-title">{{ $module->title }}</li>
    @endforeach

    @foreach ($dynamicModules->where('menu_type', 'dropdown')->whereNull('parent_id') as $dropdown)
        @php $children = $dynamicModules->where('menu_type', 'link')->where('parent_id', $dropdown->id); @endphp
        @if ($children->isNotEmpty())
            <li>
                <a href="javascript:void(0);" class="has-arrow">
                    <i class="{{ $dropdown->icon }}"></i>
                    <span>{{ $dropdown->title }}</span>
                </a>
                <ul class="sub-menu">
                    @foreach ($children as $module)
                        <li><a href="#">{{ $module->title }}</a></li>
                    @endforeach
                </ul>
            </li>
        @endif
    @endforeach

    @foreach ($dynamicModules->where('menu_type', 'link')->whereNull('parent_id') as $module)
        @php
            $href = 'javascript:void(0);';
            if (!empty($module->route_name) && \Illuminate\Support\Facades\Route::has($module->route_name)) {
                $href = route($module->route_name);
            } elseif (!empty($module->menu_url)) {
                $href = \Illuminate\Support\Str::startsWith($module->menu_url, ['http://', 'https://'])
                    ? $module->menu_url
                    : url($module->menu_url);
            }
        @endphp
        <li>
            <a href="{{ $href }}">
                <i class="{{ $module->icon }}"></i>
                <span>{{ $module->title }}</span>
            </a>
        </li>
    @endforeach
@endif
```


## 6. Permission Matrix Integration
If your role/user permission pages are static (config-based), merge DB modules into that list.

Pattern:
1. Load modules from config
2. Load modules from DB
3. Merge them
4. Build matrix using:
   - `view_{slug}`
   - `create_{slug}`
   - `update_{slug}`
   - `delete_{slug}`


## 7. Feature Route Protection (Important)
When creating real pages for a module, protect each route with generated permissions:

1. Index/Show: `can:view_{slug}`
2. Create/Store: `can:create_{slug}`
3. Edit/Update: `can:update_{slug}`
4. Delete: `can:delete_{slug}`

Example:

```php
Route::get('/incidents', ...)->middleware('can:view_incidents');
Route::post('/incidents', ...)->middleware('can:create_incidents');
Route::put('/incidents/{id}', ...)->middleware('can:update_incidents');
Route::delete('/incidents/{id}', ...)->middleware('can:delete_incidents');
```


## 8. Operational Notes
After adding/removing permissions, clear caches:

```bash
php artisan optimize:clear
php artisan permission:cache-reset
```

If sidebar still shows stale items:
1. Hard refresh browser
2. Sign out/sign in


## 9. Recommended Enhancements
1. Add `menu_group` field (Management / Operations / Administration) for placement control.
2. Add `open_in_new_tab` for external links.
3. Add `is_system` flag to protect built-in modules from deletion.
4. Auto-assign new module permissions to selected roles at create-time.


## 10. Quick Validation Checklist
1. Create module `Incident Reports`
2. Confirm permissions created in DB:
   - `view_incident_reports`
   - `create_incident_reports`
   - `update_incident_reports`
   - `delete_incident_reports`
3. Assign `view_incident_reports` to a role
4. Login as that role
5. Confirm module appears in sidebar
6. Remove `view_incident_reports`
7. Confirm module disappears
