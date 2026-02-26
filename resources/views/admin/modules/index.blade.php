@extends('layouts.master')

@section('title')
    Module Management
@endsection

@section('page-title')
    Module Management
@endsection

@section('css')
<style>
    .module-form-card .form-label {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        font-weight: 600;
    }

    .module-form-card .form-text {
        font-size: .74rem;
    }

    .modules-table thead th {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .module-meta {
        font-size: .78rem;
        color: #6c757d;
    }

    .module-actions {
        display: flex;
        gap: .35rem;
        justify-content: center;
        align-items: center;
    }

    .icon-preview-box {
        width: 40px;
        min-width: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ced4da;
        border-radius: .375rem;
        background: #f8f9fa;
        font-size: 1rem;
    }

    .icon-picker-remix {
        max-height: 55vh;
        overflow-y: auto;
    }

    .icon-picker-custom-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .5rem;
        max-height: 55vh;
        overflow-y: auto;
    }

    .icon-choice-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid #dee2e6;
        border-radius: .35rem;
        padding: .45rem .55rem;
        cursor: pointer;
        background: #fff;
    }

    .icon-choice-item:hover {
        background: #f0f7ff;
        border-color: #0d6efd;
    }

    .icon-choice-item i {
        font-size: 1rem;
    }

    .icon-choice-item i.uim::before {
        font-family: "unicons" !important;
        font-style: normal;
        font-weight: 400;
        line-height: 1;
    }

    .icon-choice-item i.mdi::before {
        font-family: "Material Design Icons" !important;
        font-style: normal;
        font-weight: 400;
        line-height: 1;
    }

    .icon-choice-name {
        font-size: .78rem;
        color: #495057;
    }

    .icon-picker-remix .icon-demo-content > div {
        cursor: pointer;
        border-radius: .35rem;
        padding: .35rem .5rem;
    }

    .icon-picker-remix .icon-demo-content > div:hover {
        background: #f0f7ff;
    }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-1">Module Management</h4>
            <p class="text-muted mb-0">
                Create dynamic modules. Each new module auto-generates: view/create/update/delete permissions.
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3 module-form-card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Create Module</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.modules.store') }}" class="row g-3">
                @csrf
                <div class="col-xl-4 col-lg-5 col-md-6">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Incident Reports" value="{{ old('title') }}">
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6" id="create-icon-group">
                    <label class="form-label">Icon Class</label>
                    <div class="d-flex gap-2">
                        <input type="text" name="icon" id="create_icon" class="form-control" placeholder="Select icon" value="{{ old('icon') }}" readonly>
                        <span class="icon-preview-box" id="create_icon_preview"><i class="{{ old('icon') ?: 'ri-apps-2-line' }}"></i></span>
                        <button type="button" class="btn btn-outline-secondary btn-icon-picker" data-icon-target="create_icon" data-icon-preview="create_icon_preview">
                            <i class="mdi mdi-magnify"></i>
                        </button>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">Type</label>
                    <select name="menu_type" class="form-select" required>
                        <option value="link" @selected(old('menu_type', 'link') === 'link')>Link</option>
                        <option value="dropdown" @selected(old('menu_type') === 'dropdown')>Dropdown</option>
                        <option value="header" @selected(old('menu_type') === 'header')>Header</option>
                    </select>
                </div>
                <div class="col-xl-1 col-lg-2 col-md-4" id="create-sort-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', 0) }}">
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">Active</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', 1))>
                        <label class="form-check-label">Show in Sidebar</label>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6" id="create-parent-group">
                    <label class="form-label">Parent (Header/Dropdown)</label>
                    <select name="parent_id" id="create_parent_id" class="form-select">
                        <option value="">None</option>
                        <optgroup label="Headers">
                            @foreach($parentOptions->where('menu_type', 'header') as $parent)
                                <option value="{{ $parent->id }}" data-parent-type="header" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->title }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Dropdowns">
                            @foreach($parentOptions->where('menu_type', 'dropdown') as $parent)
                                <option value="{{ $parent->id }}" data-parent-type="dropdown" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->title }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <small class="text-muted">For dropdown: choose a Header. For link: choose Header or Dropdown.</small>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6" id="create-route-group">
                    <label class="form-label">Route Name (Optional)</label>
                    <input type="text" name="route_name" class="form-control" placeholder="admin.calendar.index" value="{{ old('route_name') }}">
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6" id="create-url-group">
                    <label class="form-label">Menu URL (Optional fallback)</label>
                    <input type="text" name="menu_url" class="form-control" placeholder="/admin/custom-module" value="{{ old('menu_url') }}">
                </div>
                <div class="col-12 text-end">
                    <button id="create-module-btn" class="btn btn-primary px-4">Create Module</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Existing Modules</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 small mb-3">
                Module visibility depends on both assigned <code>view_*</code> permission and module <strong>Scope</strong> (<code>admin</code>, <code>company</code>, <code>both</code>).
            </div>
            @php
                $headerModules = $modules->where('menu_type', 'header')->values();
                $dropdownModules = $modules->where('menu_type', 'dropdown')->values();
                $linkNoParentModules = $modules->where('menu_type', 'link')->whereNull('parent_id')->values();
                $childLinkModules = $modules->where('menu_type', 'link')->whereNotNull('parent_id')->values();
            @endphp

            <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="heading-tab" data-bs-toggle="tab" data-bs-target="#heading-pane" type="button" role="tab">
                        Heading ({{ $headerModules->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="dropdown-tab" data-bs-toggle="tab" data-bs-target="#dropdown-pane" type="button" role="tab">
                        Dropdown + Child Links ({{ $dropdownModules->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="link-tab" data-bs-toggle="tab" data-bs-target="#link-pane" type="button" role="tab">
                        Link Only / No Parent ({{ $linkNoParentModules->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="child-link-tab" data-bs-toggle="tab" data-bs-target="#child-link-pane" type="button" role="tab">
                        Child Links ({{ $childLinkModules->count() }})
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="heading-pane" role="tabpanel" aria-labelledby="heading-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle modules-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Slug</th>
                                    <th class="text-center">Status</th>
                                    <th width="160" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($headerModules as $module)
                                    @php
                                        $modulePayload = [
                                            'id' => $module->id,
                                            'title' => $module->title,
                                            'menu_type' => $module->menu_type,
                                            'parent_id' => $module->parent_id,
                                            'sort_order' => $module->sort_order,
                                            'icon' => $module->icon,
                                            'route_name' => $module->route_name,
                                            'menu_url' => $module->menu_url,
                                            'is_active' => (bool) $module->is_active,
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $module->title }}</td>
                                        <td><code>{{ $module->slug }}</code></td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $module->is_active ? 'success' : 'secondary' }}">
                                                {{ $module->is_active ? 'Active' : 'Hidden' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="module-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-module" data-module='@json($modulePayload)'>
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </button>
                                                <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Delete this module and its CRUD permissions?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="mdi mdi-trash-can-outline"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No heading modules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="dropdown-pane" role="tabpanel" aria-labelledby="dropdown-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle modules-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dropdown</th>
                                    <th>Slug</th>
                                    <th>Parent Header</th>
                                    <th>Child Links</th>
                                    <th class="text-center">Status</th>
                                    <th width="160" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dropdownModules as $module)
                                    @php
                                        $modulePayload = [
                                            'id' => $module->id,
                                            'title' => $module->title,
                                            'menu_type' => $module->menu_type,
                                            'parent_id' => $module->parent_id,
                                            'sort_order' => $module->sort_order,
                                            'icon' => $module->icon,
                                            'route_name' => $module->route_name,
                                            'menu_url' => $module->menu_url,
                                            'is_active' => (bool) $module->is_active,
                                        ];
                                        $childLinks = $modules->where('menu_type', 'link')->where('parent_id', $module->id)->values();
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                @if(!empty($module->icon))
                                                    <i class="{{ $module->icon }} me-1"></i>
                                                @endif
                                                {{ $module->title }}
                                            </div>
                                        </td>
                                        <td><code>{{ $module->slug }}</code></td>
                                        <td>{{ $module->parent?->title ?? '—' }}</td>
                                        <td>
                                            @if($childLinks->isEmpty())
                                                <span class="text-muted">No child links</span>
                                            @else
                                                @foreach($childLinks as $child)
                                                    @php
                                                        $childPayload = [
                                                            'id' => $child->id,
                                                            'title' => $child->title,
                                                            'menu_type' => $child->menu_type,
                                                            'parent_id' => $child->parent_id,
                                                            'sort_order' => $child->sort_order,
                                                            'icon' => $child->icon,
                                                            'route_name' => $child->route_name,
                                                            'menu_url' => $child->menu_url,
                                                            'is_active' => (bool) $child->is_active,
                                                        ];
                                                    @endphp
                                                    <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1 mb-1">
                                                        <span class="small fw-medium">{{ $child->title }}</span>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary btn-edit-module py-0 px-2"
                                                            data-module='@json($childPayload)'
                                                            title="Edit child link">
                                                            <i class="mdi mdi-pencil-outline"></i>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $module->is_active ? 'success' : 'secondary' }}">
                                                {{ $module->is_active ? 'Active' : 'Hidden' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="module-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-module" data-module='@json($modulePayload)'>
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </button>
                                                <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Delete this module and its CRUD permissions?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="mdi mdi-trash-can-outline"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No dropdown modules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="link-pane" role="tabpanel" aria-labelledby="link-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle modules-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Link</th>
                                    <th>Slug</th>
                                    <th>Route / URL</th>
                                    <th class="text-center">Status</th>
                                    <th width="160" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($linkNoParentModules as $module)
                                    @php
                                        $modulePayload = [
                                            'id' => $module->id,
                                            'title' => $module->title,
                                            'menu_type' => $module->menu_type,
                                            'parent_id' => $module->parent_id,
                                            'sort_order' => $module->sort_order,
                                            'icon' => $module->icon,
                                            'route_name' => $module->route_name,
                                            'menu_url' => $module->menu_url,
                                            'is_active' => (bool) $module->is_active,
                                        ];
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                @if(!empty($module->icon))
                                                    <i class="{{ $module->icon }} me-1"></i>
                                                @endif
                                                {{ $module->title }}
                                            </div>
                                        </td>
                                        <td><code>{{ $module->slug }}</code></td>
                                        <td>
                                            <div class="module-meta">Route: {{ $module->route_name ?: '—' }}</div>
                                            <div class="module-meta">URL: {{ $module->menu_url ?: '—' }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $module->is_active ? 'success' : 'secondary' }}">
                                                {{ $module->is_active ? 'Active' : 'Hidden' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="module-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-module" data-module='@json($modulePayload)'>
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </button>
                                                <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Delete this module and its CRUD permissions?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="mdi mdi-trash-can-outline"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No link-only modules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="child-link-pane" role="tabpanel" aria-labelledby="child-link-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle modules-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Child Link</th>
                                    <th>Parent</th>
                                    <th>Slug</th>
                                    <th>Route / URL</th>
                                    <th class="text-center">Status</th>
                                    <th width="160" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($childLinkModules as $module)
                                    @php
                                        $modulePayload = [
                                            'id' => $module->id,
                                            'title' => $module->title,
                                            'menu_type' => $module->menu_type,
                                            'parent_id' => $module->parent_id,
                                            'sort_order' => $module->sort_order,
                                            'icon' => $module->icon,
                                            'route_name' => $module->route_name,
                                            'menu_url' => $module->menu_url,
                                            'is_active' => (bool) $module->is_active,
                                        ];
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                @if(!empty($module->icon))
                                                    <i class="{{ $module->icon }} me-1"></i>
                                                @endif
                                                {{ $module->title }}
                                            </div>
                                        </td>
                                        <td>{{ $module->parent?->title ?? '—' }}</td>
                                        <td><code>{{ $module->slug }}</code></td>
                                        <td>
                                            <div class="module-meta">Route: {{ $module->route_name ?: '—' }}</div>
                                            <div class="module-meta">URL: {{ $module->menu_url ?: '—' }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $module->is_active ? 'success' : 'secondary' }}">
                                                {{ $module->is_active ? 'Active' : 'Hidden' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="module-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-module" data-module='@json($modulePayload)'>
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </button>
                                                <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Delete this module and its CRUD permissions?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="mdi mdi-trash-can-outline"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No child link modules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="editModuleForm">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Module</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select name="menu_type" id="edit_menu_type" class="form-select" required>
                                    <option value="link">Link</option>
                                    <option value="dropdown">Dropdown</option>
                                    <option value="header">Header</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="edit-sort-group">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
                            </div>

                            <div class="col-md-6" id="edit-parent-group">
                                <label class="form-label">Parent (Header/Dropdown)</label>
                                <select name="parent_id" id="edit_parent_id" class="form-select">
                                    <option value="">None</option>
                                    <optgroup label="Headers">
                                        @foreach($parentOptions->where('menu_type', 'header') as $parent)
                                            <option value="{{ $parent->id }}" data-parent-type="header">{{ $parent->title }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Dropdowns">
                                        @foreach($parentOptions->where('menu_type', 'dropdown') as $parent)
                                            <option value="{{ $parent->id }}" data-parent-type="dropdown">{{ $parent->title }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-6" id="edit-icon-group">
                                <label class="form-label">Icon Class</label>
                                <div class="d-flex gap-2">
                                    <input type="text" name="icon" id="edit_icon" class="form-control" placeholder="Select icon" readonly>
                                    <span class="icon-preview-box" id="edit_icon_preview"><i class="ri-apps-2-line"></i></span>
                                    <button type="button" class="btn btn-outline-secondary btn-icon-picker" data-icon-target="edit_icon" data-icon-preview="edit_icon_preview">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6" id="edit-route-group">
                                <label class="form-label">Route Name (Optional)</label>
                                <input type="text" name="route_name" id="edit_route_name" class="form-control">
                            </div>
                            <div class="col-md-6" id="edit-url-group">
                                <label class="form-label">Menu URL (Optional fallback)</label>
                                <input type="text" name="menu_url" id="edit_menu_url" class="form-control">
                            </div>

                            <div class="col-12">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">Show in Sidebar</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="iconPickerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="icon-picker-search" class="form-control mb-3" placeholder="Search icon class...">
                    <ul class="nav nav-pills nav-justified mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active icon-picker-tab" data-bs-toggle="pill" data-bs-target="#icon-tab-remix" type="button" role="tab" data-icon-set="remix">Remix</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link icon-picker-tab" data-bs-toggle="pill" data-bs-target="#icon-tab-unicons" type="button" role="tab" data-icon-set="unicons">Unicons</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link icon-picker-tab" data-bs-toggle="pill" data-bs-target="#icon-tab-mdi" type="button" role="tab" data-icon-set="mdi">Material Design</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="icon-tab-remix" role="tabpanel">
                            <div id="icons" class="icon-picker-remix"></div>
                        </div>
                        <div class="tab-pane fade" id="icon-tab-unicons" role="tabpanel">
                            <div id="unicons-grid" class="icon-picker-custom-grid"></div>
                        </div>
                        <div class="tab-pane fade" id="icon-tab-mdi" role="tabpanel">
                            <div id="mdi-grid" class="icon-picker-custom-grid"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/remix-icons-list.js') }}"></script>
<script>
    (function () {
        const typeSelect = document.querySelector('select[name="menu_type"]');
        if (!typeSelect) return;

        const groups = {
            icon: document.getElementById('create-icon-group'),
            sort: document.getElementById('create-sort-group'),
            parent: document.getElementById('create-parent-group'),
            route: document.getElementById('create-route-group'),
            url: document.getElementById('create-url-group'),
        };

        const fields = {
            icon: document.querySelector('input[name="icon"]'),
            sort: document.querySelector('input[name="sort_order"]'),
            parent: document.querySelector('select[name="parent_id"]'),
            route: document.querySelector('input[name="route_name"]'),
            url: document.querySelector('input[name="menu_url"]'),
        };
        const createButton = document.getElementById('create-module-btn');
        const createIconInput = document.getElementById('create_icon');
        const createIconPreview = document.getElementById('create_icon_preview');
        const iconPickerModalEl = document.getElementById('iconPickerModal');
        const iconPickerSearchEl = document.getElementById('icon-picker-search');
        const iconPickerIconsEl = document.getElementById('icons');
        const uniconsGridEl = document.getElementById('unicons-grid');
        const mdiGridEl = document.getElementById('mdi-grid');
        const iconPickerTabs = document.querySelectorAll('.icon-picker-tab');
        const iconPickerModal = iconPickerModalEl ? new bootstrap.Modal(iconPickerModalEl) : null;
        const uniconsChoices = [
            'uim uim-airplay', 'uim uim-user-circle', 'uim uim-users-alt', 'uim uim-bus',
            'uim uim-map-marker', 'uim uim-schedule', 'uim uim-calender', 'uim uim-clock',
            'uim uim-chart', 'uim uim-layer-group', 'uim uim-setting', 'uim uim-shield-check',
            'uim uim-comment-alt-lines', 'uim uim-envelope-alt', 'uim uim-route',
            'uim uim-clipboard-notes', 'uim uim-file-blank', 'uim uim-location-point',
            'uim uim-truck', 'uim uim-signout', 'uim uim-signin', 'uim uim-book-open'
        ];
        const mdiChoices = [
            'mdi mdi-view-grid-outline', 'mdi mdi-view-dashboard-outline', 'mdi mdi-account-group-outline',
            'mdi mdi-account-outline', 'mdi mdi-bus', 'mdi mdi-map-marker-outline',
            'mdi mdi-route', 'mdi mdi-calendar-check-outline', 'mdi mdi-clock-outline',
            'mdi mdi-chart-bar', 'mdi mdi-file-document-outline', 'mdi mdi-clipboard-text-outline',
            'mdi mdi-shield-account-outline', 'mdi mdi-cog-outline', 'mdi mdi-chat-outline',
            'mdi mdi-email-outline', 'mdi mdi-truck-outline', 'mdi mdi-map-outline',
            'mdi mdi-history', 'mdi mdi-finance', 'mdi mdi-account-cog-outline', 'mdi mdi-folder-outline'
        ];
        let currentIconTargetInput = null;
        let currentIconTargetPreview = null;

        function syncIconPreview(inputEl, previewEl) {
            if (!inputEl || !previewEl) return;
            const icon = (inputEl.value || 'ri-apps-2-line').trim();
            previewEl.innerHTML = `<i class="${icon}"></i>`;
        }

        function filterRemixIcons(filterText = '') {
            if (!iconPickerIconsEl) return;
            const search = filterText.trim().toLowerCase();
            iconPickerIconsEl.querySelectorAll('.icon-demo-content > div').forEach((item) => {
                const text = (item.textContent || '').toLowerCase();
                item.style.display = text.includes(search) ? '' : 'none';
            });
        }

        function renderCustomIconGrid(targetEl, classNames) {
            if (!targetEl) return;
            targetEl.innerHTML = classNames.map((className) => {
                return `<div class="icon-choice-item" data-icon="${className}">
                    <i class="${className}"></i>
                    <span class="icon-choice-name">${className}</span>
                </div>`;
            }).join('');
        }

        function activeIconSet() {
            const activeTab = document.querySelector('.icon-picker-tab.active');
            return activeTab ? activeTab.getAttribute('data-icon-set') : 'remix';
        }

        function filterActiveIconSet(filterText = '') {
            const set = activeIconSet();
            if (set === 'remix') {
                filterRemixIcons(filterText);
                return;
            }

            const container = set === 'unicons' ? uniconsGridEl : mdiGridEl;
            if (!container) return;
            const search = filterText.trim().toLowerCase();
            container.querySelectorAll('.icon-choice-item').forEach((item) => {
                const text = (item.getAttribute('data-icon') || '').toLowerCase();
                item.style.display = text.includes(search) ? '' : 'none';
            });
        }

        function syncParentOptions(parentSelect, moduleType) {
            if (!parentSelect) return;

            const allowHeaderOnly = moduleType === 'dropdown';
            const allowHeaderAndDropdown = moduleType === 'link';

            Array.from(parentSelect.options).forEach((option) => {
                if (!option.value) {
                    option.disabled = false;
                    option.hidden = false;
                    return;
                }

                const parentType = option.dataset.parentType;
                const allowed = allowHeaderOnly
                    ? parentType === 'header'
                    : allowHeaderAndDropdown
                        ? (parentType === 'header' || parentType === 'dropdown')
                        : false;

                option.disabled = !allowed;
                option.hidden = !allowed;
            });

            const selected = parentSelect.options[parentSelect.selectedIndex];
            if (selected && selected.disabled) {
                parentSelect.value = '';
            }
        }

        function setHeaderMode(isHeader) {
            ['icon', 'sort', 'parent', 'route', 'url'].forEach((key) => {
                if (groups[key]) groups[key].style.display = isHeader ? 'none' : '';
            });

            if (isHeader) {
                if (fields.icon) fields.icon.value = '';
                if (fields.sort) fields.sort.value = '0';
                if (fields.parent) fields.parent.value = '';
                if (fields.route) fields.route.value = '';
                if (fields.url) fields.url.value = '';
            }
        }

        function syncByType() {
            const currentType = typeSelect.value;
            setHeaderMode(currentType === 'header');
            syncParentOptions(fields.parent, currentType);
            if (createButton) {
                createButton.textContent = currentType === 'header' ? 'Create Header' : 'Create Module';
            }
            syncIconPreview(createIconInput, createIconPreview);
        }

        typeSelect.addEventListener('change', syncByType);
        syncByType();

        const editModalEl = document.getElementById('editModuleModal');
        const editMenuType = document.getElementById('edit_menu_type');
        const editParent = document.getElementById('edit_parent_id');
        const editIconInput = document.getElementById('edit_icon');
        const editIconPreview = document.getElementById('edit_icon_preview');
        const editForm = document.getElementById('editModuleForm');
        const updateUrlTemplate = @json(route('admin.modules.update', ['module' => '__MODULE__']));
        const editGroups = {
            icon: document.getElementById('edit-icon-group'),
            sort: document.getElementById('edit-sort-group'),
            parent: document.getElementById('edit-parent-group'),
            route: document.getElementById('edit-route-group'),
            url: document.getElementById('edit-url-group'),
        };

        function syncEditType() {
            const currentType = editMenuType.value;
            const isHeader = currentType === 'header';
            ['icon', 'sort', 'parent', 'route', 'url'].forEach((key) => {
                if (editGroups[key]) editGroups[key].style.display = isHeader ? 'none' : '';
            });
            syncParentOptions(editParent, currentType);
        }

        editMenuType.addEventListener('change', syncEditType);

        renderCustomIconGrid(uniconsGridEl, uniconsChoices);
        renderCustomIconGrid(mdiGridEl, mdiChoices);

        document.querySelectorAll('.btn-icon-picker').forEach((btn) => {
            btn.addEventListener('click', function () {
                const inputId = this.getAttribute('data-icon-target');
                const previewId = this.getAttribute('data-icon-preview');
                const inputEl = document.getElementById(inputId);
                const previewEl = document.getElementById(previewId);
                if (!inputEl || !previewEl || !iconPickerModal) return;

                currentIconTargetInput = inputEl;
                currentIconTargetPreview = previewEl;
                if (iconPickerSearchEl) iconPickerSearchEl.value = '';
                filterActiveIconSet('');
                iconPickerModal.show();
            });
        });

        iconPickerTabs.forEach((tab) => {
            tab.addEventListener('shown.bs.tab', function () {
                filterActiveIconSet(iconPickerSearchEl ? iconPickerSearchEl.value : '');
            });
        });

        if (iconPickerSearchEl) {
            iconPickerSearchEl.addEventListener('input', function () {
                filterActiveIconSet(this.value || '');
            });
        }

        if (iconPickerIconsEl) {
            iconPickerIconsEl.addEventListener('click', function (event) {
                const iconItem = event.target.closest('.icon-demo-content > div');
                if (!iconItem || !currentIconTargetInput || !currentIconTargetPreview) return;
                const iconEl = iconItem.querySelector('i');
                if (!iconEl) return;
                const iconClass = Array.from(iconEl.classList).find((cls) => cls.startsWith('ri-'));
                if (!iconClass) return;

                currentIconTargetInput.value = iconClass;
                syncIconPreview(currentIconTargetInput, currentIconTargetPreview);
                iconPickerModal?.hide();
            });
        }

        [uniconsGridEl, mdiGridEl].forEach((gridEl) => {
            if (!gridEl) return;
            gridEl.addEventListener('click', function (event) {
                const iconItem = event.target.closest('.icon-choice-item');
                if (!iconItem || !currentIconTargetInput || !currentIconTargetPreview) return;
                const iconClass = iconItem.getAttribute('data-icon');
                if (!iconClass) return;
                currentIconTargetInput.value = iconClass;
                syncIconPreview(currentIconTargetInput, currentIconTargetPreview);
                iconPickerModal?.hide();
            });
        });

        document.querySelectorAll('.btn-edit-module').forEach((btn) => {
            btn.addEventListener('click', function () {
                let module;
                try {
                    module = JSON.parse(this.getAttribute('data-module') || '{}');
                } catch (e) {
                    return;
                }

                if (!module.id) return;

                editForm.setAttribute('action', updateUrlTemplate.replace('__MODULE__', String(module.id)));
                document.getElementById('edit_title').value = module.title || '';
                document.getElementById('edit_menu_type').value = module.menu_type || 'link';
                document.getElementById('edit_sort_order').value = module.sort_order ?? 0;
                document.getElementById('edit_parent_id').value = module.parent_id ?? '';
                editIconInput.value = module.icon || '';
                document.getElementById('edit_route_name').value = module.route_name || '';
                document.getElementById('edit_menu_url').value = module.menu_url || '';
                document.getElementById('edit_is_active').checked = !!module.is_active;
                syncIconPreview(editIconInput, editIconPreview);

                syncEditType();
                const modal = new bootstrap.Modal(editModalEl);
                modal.show();
            });
        });

        syncIconPreview(createIconInput, createIconPreview);
    })();
</script>
@endsection
