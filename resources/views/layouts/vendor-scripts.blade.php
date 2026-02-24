<!-- JAVASCRIPT -->

<!-- 1️⃣ jQuery (FIRST – required by everything else) -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>

<!-- 2️⃣ Bootstrap -->
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<!-- 3️⃣ Simplebar (used by sidebar scroll) -->
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js') }}"></script>

<!-- 4️⃣ MetisMenu (sidebar menu) -->
<script src="{{ URL::asset('build/libs/metismenu/metisMenu.min.js') }}"></script>

<!-- 5️⃣ Waves (ripple effects – optional but required by app.js) -->
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js') }}"></script>

<!-- 6️⃣ Icons (safe to keep here) -->
<script src="https://unicons.iconscout.com/release/v2.0.1/script/monochrome/bundle.js"></script>

<!-- 7️⃣ APP JS (MUST BE LAST) -->
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<!-- 8️⃣ Page-specific scripts -->
@yield('scripts')
