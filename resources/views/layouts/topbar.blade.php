

<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
              <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="index" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="logo-sm-dark" height="24">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('build/images/logo-sm-dark.png') }}" alt="logo-dark" height="25">
                    </span>
                </a>

                <a href="index" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="logo-sm-light" height="24">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('build/images/logo-sm-light.png') }}" alt="logo-light" height="25">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect vertical-menu-btn" id="vertical-menu-btn">
                <i class="ri-menu-2-line align-middle"></i>
            </button>
            @php
                $segments = request()->segments();
                $breadcrumbText = collect($segments)
                    ->map(fn ($segment) => ucwords(str_replace(['-', '_'], ' ', $segment)))
                    ->implode(' / ');
            @endphp
            <div class="page-title-box align-self-center ms-2">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-3 mt-4 flex-wrap">
                    <div>
                        <h4 class="card-title mb-1">@yield('title')</h4>
                        <p class="text-muted mb-0">{{ $breadcrumbText !== '' ? $breadcrumbText : 'Home' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex">

        
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="ri-search-line"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-search-dropdown">

                    <form class="p-3">
                        <div class="mb-3 m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                    <i class="ri-fullscreen-line"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown"
                      data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-notification-3-line"></i>
                    <span class="badge bg-danger rounded-pill d-none"
                          id="rt-notification-count"
                          style="position:absolute; top:8px; right:6px; min-width:18px;">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0"> Notifications </h6>
                            </div>
                            <div class="col-auto">
                                <button type="button" id="rt-notification-clear" class="btn btn-link btn-sm p-0 text-decoration-none small">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="rt-notification-list" data-simplebar style="max-height: 230px;">
                        <div class="px-3 py-3 text-center text-muted small" id="rt-notification-empty">
                            No notifications yet.
                        </div>
                    </div>
                    <div class="p-2 border-top">
                        <div class="d-grid">
                            <span class="btn btn-sm btn-link font-size-14 text-center disabled">
                                Realtime feed enabled
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>
