<style>
    /* MOBILE: sidebar should slide in/out */
@media (max-width: 991.98px) {
  /* default: hidden/off-canvas */
  .vertical-menu {
    position: fixed;
    top: 0;
    bottom: 0;
    left: -260px;          /* match your sidebar width */
    width: 260px;
    z-index: 1050;
    height:100%!important;
    transition: left .2s ease;
  }

  /* open state: body.sidebar-enable is what template uses */
  body.sidebar-enable .vertical-menu {
    left: 0;
  }

  /* overlay must be visible only when open */
  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1040;
  }

  body.sidebar-enable .sidebar-overlay {
    display: block;
  }
}

/* ===============================
   SIDEBAR LAYOUT FIXES
   =============================== */

/* Sidebar full height + stable internal layout */
.vertical-menu {
    height: 100%!!important;
    display: flex;
    flex-direction: column;
}

/* Header blocks should not scroll */
.sidebar-logo,
.sidebar-toggle {
    flex: 0 0 auto;
}

/* Scroll container should fill remaining height */
.vertical-scroll {
    flex: 1 1 auto;
    min-height: 0; /* IMPORTANT for simplebar inside flex */
}

/* USER BOX: default visible */
.sidebar-user-box {
    display: block;
    padding: 12px 12px;
    transition: opacity 0.2s ease;
}

/* Desktop collapsed state: keep user box visible but compact */
body.vertical-collapsed .sidebar-user-box {
    display: block !important;
    padding: 10px 8px;
}

/* Optional: reduce titles in collapsed mode to avoid jitter */
body.vertical-collapsed .menu-title {
    display: none;
}

/* Ensure user text truncates nicely */
.sidebar-user-text .text-truncate {
    max-width: 180px;
}
.sidebar-user-logo {
    max-width: 150px;
    width: 100%;
    height: auto;
    margin: 0 auto 0px;
    display: block;
}a.btn.btn-sm:hover{
    color:white!important;
}

/* Collapsed mode: constrain user text */
body.vertical-collapsed .sidebar-user-text .text-truncate {
    max-width: 64px;
}

/* MOBILE OVERLAY:
   hide user box only when sidebar overlay is open on mobile */
@media (max-width: 991.98px) {
    body.sidebar-enable .sidebar-user-box {
        display: none !important;
    }
}

/* Optional logo spacing */
.sidebar-logo {
    padding: 14px 0;
}
.sidebar-logo img {
    max-width: 100%;
    height: auto;
}
</style>

<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">



    {{-- TOGGLE --}}
    <div class="text-center sidebar-toggle">
        <button type="button"
                class="btn btn-sm px-3 font-size-22 header-item vertical-menu-btn"
                id="vertical-menu-btn">
            <i class="ri-menu-2-line align-middle"></i>
        </button>
    </div>

    {{-- SCROLL AREA --}}
    <div data-simplebar class="vertical-scroll">
        <div id="sidebar-menu">

            {{-- USER --}}
              <img src="{{ asset('images/sidebar-logo.png') }}"
                             alt="HM Transport Logo"
                             class="sidebar-user-logo">
            <div class="sidebar-user-box">
                <div class="d-flex align-items-center justify-content-center w-100">
                    
                    <div class="sidebar-user-text text-center overflow-hidden">
                      
                        <div class="text-white fw-semibold text-truncate">
                            {{ auth()->user()->full_name }}
                        </div>
                        <small class="text-white text-truncate d-block">
                            {{ auth()->user()->isSystemAdmin() ? 'Administrator' : 'Company User' }}
                        </small>
                    </div>
                </div>
            </div>

            <ul class="metismenu list-unstyled" id="side-menu">

                {{-- ================= DASHBOARDS ================= --}}
                <li class="menu-title">Dashboards</li>

                @if (auth()->user()->canByRole('view_admin_dashboard'))
                    <li>
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="uim uim-airplay"></i>
                            <span>Admin Dashboard</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->canByRole('view_company_dashboard'))
                    <li>
                        <a href="{{ route('company.dashboard') }}">
                            <i class="uim uim-airplay"></i>
                            <span>Company Dashboard</span>
                        </a>
                    </li>
                @endif

                {{-- ================= MANAGEMENT ================= --}}
                @if (
                    auth()->user()->canByRole('view_companies') ||
                    auth()->user()->canByRole('view_users') ||
                    auth()->user()->canByRole('manage_users') ||
                    auth()->user()->canByRole('view_employees') ||
                    auth()->user()->canByRole('view_drivers') ||
                    auth()->user()->canByRole('view_buses') ||
                    auth()->user()->canByRole('view_routes') ||
                    auth()->user()->canByRole('view_schedules')
                )
                    <li class="menu-title">Management</li>

                    <li>
                        <a href="javascript:void(0);" class="has-arrow">
                            <i class="ri-briefcase-fill"></i>
                            <span>Management</span>
                        </a>

                        <ul class="sub-menu">

                            {{-- @if (auth()->user()->canByRole('view_schedules'))
                                <li>
                                    <a href="{{ auth()->user()->isCompanyUser()
                                        ? route('company.schedules.index')
                                        : route('admin.schedules.index') }}">
                                        <i class="ri-calendar-check-fill me-1"></i>
                                        Employee Schedules
                                    </a>
                                </li>
                            @endif --}}

                            @if (auth()->user()->canByRole('view_companies'))
                                <li>
                                    <a href="{{ route('admin.companies.index') }}">
                                        <i class="ri-building-fill me-1"></i>
                                        Companies
                                    </a>
                                </li>
                            @endif

                        
                            @if (auth()->user()->canByRole('view_employees'))
                                <li>
                                    <a href="{{ auth()->user()->isCompanyUser()
                                        ? route('company.employees.index')
                                        : route('admin.employees.index') }}">
                                        <i class="ri-user-2-fill me-1"></i>
                                        Employees
                                    </a>
                                </li>
                            @endif

                            @if (auth()->user()->canByRole('view_drivers'))
                                <li>
                                    <a href="{{ auth()->user()->isCompanyUser()
                                        ? route('company.drivers.index')
                                        : route('admin.drivers.index') }}">
                                        <i class="ri-user-location-fill me-1"></i>
                                        Drivers
                                    </a>
                                </li>
                            @endif

                            @if (auth()->user()->canByRole('view_buses'))
                                <li>
                                    <a href="{{ auth()->user()->busRoute() }}">
                                        <i class="ri-bus-fill me-1"></i>
                                        Buses
                                    </a>
                                </li>
                            @endif
                                @if (auth()->user()->canByRole('view_users') || auth()->user()->canByRole('manage_users'))
                                <li>
                                    <a href="{{ route('admin.users.index') }}">
                                        <i class="ri-user-settings-fill me-1"></i>
                                        Users
                                    </a>
                                </li>
                            @endif


                            @if (auth()->user()->canByRole('view_routes'))
                                <li>
                                    <a href="{{ route('admin.routes.index') }}">
                                        <i class="ri-map-pin-fill me-1"></i>
                                        Routes
                                    </a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif

                {{-- ================= CHATS ================= --}}
                @php
                    $canSeeChats =

                        auth()->user()->canByRole('view_chat') ||
                        auth()->user()->hasRole('super_admin');
                @endphp

                @if ($canSeeChats)
                    <li class="menu-title">Chats</li>



                    @if (auth()->user()->hasRole('super_admin'))
                        <li>
                            <a href="{{ route('admin.group-chats.index') }}">
                                <i class="ri-group-fill"></i>
                                <span>Group Chats</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->canByRole('view_chat'))
                        <li>
                            <a href="{{ auth()->user()->isCompanyUser()
                                ? route('admin.chat.index')
                                : route('admin.chat.index') }}">
                                <i class="ri-chat-3-line"></i>
                                <span>My Chats</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ================= OPERATIONS ================= --}}
                @php
                    $canSeeOperations =
                        auth()->user()->canByRole('view_trips') ||
                        auth()->user()->canByRole('view_assignments') ||
                        auth()->user()->canByRole('view_live_tracking') ||
                        auth()->user()->hasRole('super_admin');
                @endphp

                @if ($canSeeOperations)
                    <li class="menu-title">Operations</li>

                    <li>
                        <a href="javascript:void(0);" class="has-arrow">
                            <i class="ri-settings-fill"></i>
                            <span>Operations</span>
                        </a>

                        <ul class="sub-menu">

                            @if (auth()->user()->canByRole('view_trips'))
                                <li>
                                    <a href="{{ auth()->user()->tripsRoute() }}">
                                        <i class="uim uim-clock me-1"></i>
                                        Trips
                                    </a>
                                </li>
                            @endif

@if (auth()->user()->canByRole('view_assignments'))
    <li>
        <a href="{{ auth()->user()->isCompanyUser()
                    ? route('company.assignments.index')
                    : route('admin.assignments.index') }}">
            <i class="ri-pushpin-line me-1"></i>
            Assignments
        </a>
    </li>
@endif

                            <li>
                                <a href="{{ auth()->user()->isCompanyUser()
                                    ? route('company.calendar.index')
                                    : route('admin.calendar.index') }}">
                                    <i class="ri-calendar-check-line me-1"></i>
                                    Calendar
                                </a>
                            </li>


                            @if (auth()->user()->canByRole('view_live_tracking'))
                                <li>
                                    <a href="{{ auth()->user()->liveTrackingRoute() }}">
                                        <i class="ri-map-fill me-1"></i>
                                        Live Tracking
                                    </a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif
                {{-- ================= ADMINISTRATIONS ================= --}}

                {{-- ================= DYNAMIC MODULES ================= --}}
                @php
                    $dynamicHeaders = collect();
                    $dynamicDropdowns = collect();
                    $dynamicLinks = collect();
                    if (\Illuminate\Support\Facades\Schema::hasTable('modules')) {
                        $existingPermissionNames = \Spatie\Permission\Models\Permission::query()
                            ->pluck('name')
                            ->all();

                        $allDynamicModules = \App\Models\Module::query()
                            ->where('is_active', true)
                            ->with('children')
                            ->orderBy('sort_order')
                            ->orderBy('title')
                            ->get()
                            ->filter(function ($module) use ($existingPermissionNames) {
                                if ($module->menu_type === 'header' || $module->menu_type === 'dropdown') {
                                    return true;
                                }

                                $viewPermission = 'view_' . $module->slug;
                                if (!in_array($viewPermission, $existingPermissionNames, true)) {
                                    return false;
                                }

                                return auth()->user()->canByRole($viewPermission);
                            });

                        $dynamicLinks = $allDynamicModules
                            ->where('menu_type', 'link');

                        $dynamicDropdowns = $allDynamicModules
                            ->where('menu_type', 'dropdown')
                            ->filter(function ($dropdownModule) use ($dynamicLinks) {
                                // Dropdown is a container only: show it only when it has visible child links.
                                return $dynamicLinks->where('parent_id', $dropdownModule->id)->isNotEmpty();
                            });

                        $dynamicHeaders = $allDynamicModules
                            ->where('menu_type', 'header')
                            ->whereNull('parent_id')
                            ->filter(function ($headerModule) use ($dynamicDropdowns, $dynamicLinks) {
                                // Header is a label only: show it only when it has visible children.
                                return $dynamicDropdowns->where('parent_id', $headerModule->id)->isNotEmpty()
                                    || $dynamicLinks->where('parent_id', $headerModule->id)->isNotEmpty();
                            });
                    }
                @endphp

                @if ($dynamicHeaders->isNotEmpty() || $dynamicDropdowns->isNotEmpty() || $dynamicLinks->isNotEmpty())
                 
                    @foreach ($dynamicHeaders as $headerModule)
                        <li class="menu-title">{{ $headerModule->title }}</li>

                        @foreach ($dynamicDropdowns->where('parent_id', $headerModule->id) as $dropdownModule)
                            @php
                                $visibleChildren = $dynamicLinks->where('parent_id', $dropdownModule->id);
                                $dropdownHref = 'javascript:void(0);';
                                if (!empty($dropdownModule->route_name) && \Illuminate\Support\Facades\Route::has($dropdownModule->route_name)) {
                                    $dropdownHref = route($dropdownModule->route_name);
                                } elseif (!empty($dropdownModule->menu_url)) {
                                    $dropdownHref = \Illuminate\Support\Str::startsWith($dropdownModule->menu_url, ['http://', 'https://'])
                                        ? $dropdownModule->menu_url
                                        : url($dropdownModule->menu_url);
                                }
                            @endphp
                            @if ($visibleChildren->isNotEmpty())
                                <li>
                                    <a href="javascript:void(0);" class="has-arrow">
                                        <i class="{{ $dropdownModule->icon }}"></i>
                                        <span>{{ $dropdownModule->title }}</span>
                                    </a>
                                    <ul class="sub-menu">
                                        @foreach ($visibleChildren as $module)
                                            @php
                                                $moduleHref = 'javascript:void(0);';
                                                if (!empty($module->route_name) && \Illuminate\Support\Facades\Route::has($module->route_name)) {
                                                    $moduleHref = route($module->route_name);
                                                } elseif (!empty($module->menu_url)) {
                                                    $moduleHref = \Illuminate\Support\Str::startsWith($module->menu_url, ['http://', 'https://'])
                                                        ? $module->menu_url
                                                        : url($module->menu_url);
                                                }
                                            @endphp
                                            <li>
                                                <a href="{{ $moduleHref }}">
                                                    <i class="{{ $module->icon }}"></i>
                                                    <span>{{ $module->title }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $dropdownHref }}">
                                        <i class="{{ $dropdownModule->icon }}"></i>
                                        <span>{{ $dropdownModule->title }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach

                        @foreach ($dynamicLinks->where('parent_id', $headerModule->id) as $module)
                            @php
                                $moduleHref = 'javascript:void(0);';
                                if (!empty($module->route_name) && \Illuminate\Support\Facades\Route::has($module->route_name)) {
                                    $moduleHref = route($module->route_name);
                                } elseif (!empty($module->menu_url)) {
                                    $moduleHref = \Illuminate\Support\Str::startsWith($module->menu_url, ['http://', 'https://'])
                                        ? $module->menu_url
                                        : url($module->menu_url);
                                }
                            @endphp
                            <li>
                                <a href="{{ $moduleHref }}">
                                    <i class="{{ $module->icon }}"></i>
                                    <span>{{ $module->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endforeach

                    @foreach ($dynamicDropdowns->whereNull('parent_id') as $dropdownModule)
                        @php
                            $visibleChildren = $dynamicLinks->where('parent_id', $dropdownModule->id);
                            $dropdownHref = 'javascript:void(0);';
                            if (!empty($dropdownModule->route_name) && \Illuminate\Support\Facades\Route::has($dropdownModule->route_name)) {
                                $dropdownHref = route($dropdownModule->route_name);
                            } elseif (!empty($dropdownModule->menu_url)) {
                                $dropdownHref = \Illuminate\Support\Str::startsWith($dropdownModule->menu_url, ['http://', 'https://'])
                                    ? $dropdownModule->menu_url
                                    : url($dropdownModule->menu_url);
                            }
                        @endphp
                        @if ($visibleChildren->isNotEmpty())
                            <li>
                                <a href="javascript:void(0);" class="has-arrow">
                                    <i class="{{ $dropdownModule->icon }}"></i>
                                    <span>{{ $dropdownModule->title }}</span>
                                </a>
                                <ul class="sub-menu">
                                    @foreach ($visibleChildren as $module)
                                        @php
                                            $moduleHref = 'javascript:void(0);';
                                            if (!empty($module->route_name) && \Illuminate\Support\Facades\Route::has($module->route_name)) {
                                                $moduleHref = route($module->route_name);
                                            } elseif (!empty($module->menu_url)) {
                                                $moduleHref = \Illuminate\Support\Str::startsWith($module->menu_url, ['http://', 'https://'])
                                                    ? $module->menu_url
                                                    : url($module->menu_url);
                                            }
                                        @endphp
                                        <li>
                                            <a href="{{ $moduleHref }}">
                                                <i class="{{ $module->icon }}"></i>
                                                <span>{{ $module->title }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li>
                                <a href="{{ $dropdownHref }}">
                                    <i class="{{ $dropdownModule->icon }}"></i>
                                    <span>{{ $dropdownModule->title }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach

                    @foreach ($dynamicLinks->whereNull('parent_id') as $module)
                        @php
                            $moduleHref = 'javascript:void(0);';
                            if (!empty($module->route_name) && \Illuminate\Support\Facades\Route::has($module->route_name)) {
                                $moduleHref = route($module->route_name);
                            } elseif (!empty($module->menu_url)) {
                                $moduleHref = \Illuminate\Support\Str::startsWith($module->menu_url, ['http://', 'https://'])
                                    ? $module->menu_url
                                    : url($module->menu_url);
                            }
                        @endphp
                        <li>
                            <a href="{{ $moduleHref }}">
                                <i class="{{ $module->icon }}"></i>
                                <span>{{ $module->title }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif

                {{-- ================= ADMINISTRATION ================= --}}
@can('manage_roles')
    <li class="menu-title">Administrations</li>
<li>
    <a href="{{ route('admin.users.permissions.index') }}">
        <i class="ri-user-fill"></i>
        <span>User Permissions</span>
    </a>
</li>


    <li>
        <a href="{{ route('admin.roles.index') }}">
            <i class="ri-shield-user-fill"></i>
            <span>Roles & Permissions</span>
        </a>
    </li>

    @php($isAuditTrailRoute = request()->routeIs('admin.audit-trail.*'))
    <li class="{{ $isAuditTrailRoute ? 'mm-active' : '' }}">
        <a href="{{ route('admin.audit-trail.index') }}" class="{{ $isAuditTrailRoute ? 'active' : '' }}">
            <i class="ri-file-list-3-line"></i>
            <span>Audit Trail</span>
        </a>
    </li>
    @if (auth()->user()->isAdminUser())
        <li>
            <a href="{{ route('admin.modules.index') }}">
                <i class="ri-apps-2-line"></i>
                <span>Module Management</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.developer-tools.index') }}">
                <i class="ri-tools-fill"></i>
                <span>Developer Tools</span>
            </a>
        </li>
    @endif
@endcan


                {{-- ================= ACCOUNT ================= --}}
                <li class="menu-title">Account</li>

                @php($isSettingsThemeOverrideRoute = request()->routeIs('settings.theme-override.*') || request()->routeIs('settings.index'))

                <li class="{{ $isSettingsThemeOverrideRoute ? 'mm-active' : '' }}">
                    <a href="{{ route('settings.theme-override.index') }}" class="{{ $isSettingsThemeOverrideRoute ? 'active' : '' }}">
                        <i class="ri-settings-3-line"></i>
                        <span>Settings</span>
                    </a>
                </li>

                @php($isProfileRoute = request()->routeIs('admin.profile.*'))
                @php($profileUrl = auth()->user()->isSuperAdmin() ? route('admin.profile.show') : '#')

                <li class="{{ $isProfileRoute ? 'mm-active' : '' }}">
                    <a href="{{ $profileUrl }}" class="{{ $isProfileRoute ? 'active' : '' }}">
                        <i class="ri-user-fill"></i>
                        <span>Profile</span>
                    </a>
                </li>

                <li>
                    <a href="#"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="uim uim-lock"></i>
                        <span>Logout</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

</div>
<!-- Left Sidebar End -->
