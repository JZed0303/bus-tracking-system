<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = Module::query()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $parentOptions = Module::query()
            ->whereIn('menu_type', ['header', 'dropdown'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.modules.index', compact('modules', 'parentOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120', 'unique:modules,title'],
            'icon' => ['nullable', 'string', 'max:80'],
            'route_name' => ['nullable', 'string', 'max:180'],
            'menu_url' => ['nullable', 'string', 'max:255'],
            'menu_type' => ['required', 'in:header,dropdown,link'],
            'parent_id' => ['nullable', 'integer', 'exists:modules,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated = $this->normalizeForMenuType($validated);

        $slug = Str::of($validated['title'])->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
        if ($slug === '') {
            return back()->withErrors(['title' => 'Invalid module title.'])->withInput();
        }

        if (Module::where('slug', $slug)->exists()) {
            return back()->withErrors(['title' => 'A module with similar title already exists.'])->withInput();
        }

        $module = Module::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'icon' => $validated['icon'] ?: 'ri-apps-2-line',
            'route_name' => $validated['route_name'] ?: null,
            'menu_url' => $validated['menu_url'] ?: null,
            'scope' => 'both',
            'menu_type' => $validated['menu_type'],
            'parent_id' => $this->resolveParentId($validated),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->syncModulePermissions($module);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', 'Module created. CRUD permissions were generated automatically.');
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120', 'unique:modules,title,' . $module->id],
            'icon' => ['nullable', 'string', 'max:80'],
            'route_name' => ['nullable', 'string', 'max:180'],
            'menu_url' => ['nullable', 'string', 'max:255'],
            'menu_type' => ['required', 'in:header,dropdown,link'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('modules', 'id'),
                Rule::notIn([$module->id]),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated = $this->normalizeForMenuType($validated);

        $module->update([
            'title' => $validated['title'],
            'icon' => $validated['icon'] ?: 'ri-apps-2-line',
            'route_name' => $validated['route_name'] ?: null,
            'menu_url' => $validated['menu_url'] ?: null,
            'scope' => 'both',
            'menu_type' => $validated['menu_type'],
            'parent_id' => $this->resolveParentId($validated),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        if ((int) $module->parent_id === $module->id) {
            $module->update(['parent_id' => null]);
        }

        $this->syncModulePermissions($module);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', 'Module updated.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $this->removeModulePermissions($module);

        $module->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', 'Module and generated permissions removed.');
    }

    private function resolveParentId(array $validated): ?int
    {
        $menuType = $validated['menu_type'] ?? 'link';

        if ($menuType === 'header') {
            return null;
        }

        $parentId = $validated['parent_id'] ?? null;
        if (!$parentId) {
            return null;
        }

        $parent = Module::query()->find($parentId);
        if (!$parent) {
            return null;
        }

        if ($menuType === 'dropdown') {
            // Dropdown can be nested under a Header only.
            return $parent->isHeader() ? (int) $parentId : null;
        }

        if ($menuType === 'link') {
            // Link can be nested under Header or Dropdown.
            return ($parent->isHeader() || $parent->isDropdown()) ? (int) $parentId : null;
        }

        return null;
    }

    private function syncModulePermissions(Module $module): void
    {
        if ($module->isHeader() || $module->isDropdown()) {
            $this->removeModulePermissions($module);
            return;
        }

        foreach (['view', 'create', 'update', 'delete'] as $action) {
            Permission::findOrCreate($module->permissionName($action), 'web');
        }
    }

    private function removeModulePermissions(Module $module): void
    {
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            Permission::where('name', $module->permissionName($action))->delete();
        }
    }

    private function normalizeForMenuType(array $validated): array
    {
        if (($validated['menu_type'] ?? 'link') === 'header') {
            $validated['icon'] = null;
            $validated['route_name'] = null;
            $validated['menu_url'] = null;
            $validated['parent_id'] = null;
            $validated['sort_order'] = 0;
        }

        return $validated;
    }
}
