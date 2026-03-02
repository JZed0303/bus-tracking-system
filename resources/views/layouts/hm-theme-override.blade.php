<style>
/* =========================================================
   HM TRANSPORT - GLOBAL THEME OVERRIDE (Tocly + Bootstrap)
   Drop-in override: red/orange/yellow brand
   ========================================================= */

/* -------------------------------
   1) Brand Tokens (HM Palette)
--------------------------------- */
:root{
  /* Core Brand */
  --hm-red: #B32025;        /* primary crimson */
  --hm-red-2: #9E1B20;      /* deeper red for hover */
  --hm-orange: #E85D04;     /* accent orange */
  --hm-yellow: #F4C430;     /* accent yellow */
  --hm-apex-1: #B32025;
  --hm-apex-2: #E85D04;
  --hm-apex-3: #F4C430;
  --hm-apex-4: #9E1B20;
  --hm-apex-5: #6B7280;

  /* Neutrals */
  --hm-bg: #F7F8FA;
  --hm-surface: #FFFFFF;
  --hm-surface-2: #FBFBFC;
  --hm-border: #E7E9EE;
  --hm-text: #111827;
  --hm-muted: #6B7280;

  /* Elevation */
  --hm-shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
  --hm-shadow-sm: 0 6px 18px rgba(17, 24, 39, 0.06);

  /* Radii */
  --hm-radius: 14px;
  --hm-radius-sm: 10px;

  /* Focus ring */
  --hm-ring: 0 0 0 4px rgba(232, 93, 4, 0.18);

  /* Gradients */
  --hm-grad: linear-gradient(135deg, var(--hm-red) 0%, var(--hm-orange) 65%, var(--hm-yellow) 120%);
  --hm-grad-soft: linear-gradient(135deg, rgba(179, 32, 37, 0.10) 0%, rgba(232, 93, 4, 0.10) 60%, rgba(244, 196, 48, 0.10) 120%);
}

/* -------------------------------
   2) Base + Typography
--------------------------------- */
html, body{
  background: var(--hm-bg) !important;
  color: var(--hm-text) !important;
}

body{
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif !important;
}

/* Subtle section title styling */
.page-title-box .page-title,
h1,h2,h3,h4,h5{
  letter-spacing: 0.2px;
}

/* -------------------------------
   3) Topbar + Layout Wrapper
--------------------------------- */
#layout-wrapper{
  background: transparent !important;
}

/* Topbar: keep clean, brand underline */
#page-topbar,
.navbar-header{
  background: var(--hm-surface) !important;
  /* border-bottom: 1px solid var(--hm-border) !important;  */
  box-shadow: 0 6px 18px rgba(17,24,39,0.04) !important;
}

#page-topbar::after{
  content: "";
  display: block;
  height: 3px;
  background: var(--hm-grad);
}

/* Topbar logo next to hamburger (logo-only spacing/separator) */
.topbar-side-logo{
  margin-left: 12px;
    padding-left: 12px;
    
    height: 78px;
    flex: 0 0 auto;
    overflow: hidden;
    display: flex;
    align-items: center;
}
.topbar-side-logo img{
  height: 439% !important;
  width: auto;
  position: relative;
    left: -79px;
    top: 12px;
}

/* Search input in topbar */
.app-search .form-control,
.navbar-header .form-control{
  border-radius: 999px !important;
  border: 1px solid var(--hm-border) !important;
  background: var(--hm-surface-2) !important;
}
.app-search .form-control:focus,
.navbar-header .form-control:focus{
  box-shadow: var(--hm-ring) !important;
  border-color: rgba(232,93,4,0.45) !important;
}

/* -------------------------------
   4) Sidebar (make it HM red)
--------------------------------- */
.vertical-menu,
.navbar-brand-box,
.sidebar-menu-scroll,
.metismenu {
background:linear-gradient(to bottom, #b4181e 0%, #940005 48%, #660509 100%) !important;
  /* background:linear-gradient(to bottom, #132b91 0%, #004794 48%, #120566 100%) !important; */
}.vertical-collpsed .vertical-menu #sidebar-menu>ul ul{
  background-color:#c72228!important;
}body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu ul li li a.mm-active, body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu ul li li a.active{
  color:#ffffff !important;
  width:90%!important;
}.sidebar-user-box.mm-active{
  background:#6f1818;
}.sidebar-user-box{
  background:#740808;
  padding: 15px 12px!important;
}.vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>ul a{
      width: 167px;
}

.vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>ul{
    display: block;
    left: 70px;
    position: absolute;
    width: 190px;
    height: auto !important;
    box-shadow: 3px 5px 12px -4px #1213151a;
    top: 54px;
}
.vertical-collpsed .vertical-menu #sidebar-menu > ul > li > a {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    padding: 0 !important;
    height: 60px; 
}
.vertical-collpsed .vertical-menu #sidebar-menu > ul > li > a i {
    margin: 0 !important;
    font-size: 20px;
}

.navbar-brand-box{
  border-right: 1px solid rgba(21, 17, 17, 0.67) !important;
}
body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>a{
  background-color:#7a1e1e!important;
}
/* Global table header color */
.table thead th,
table thead th,
.table > :not(caption) > * > th,
.dataTable thead th,
table.dataTable thead th{
  background:#f1f3f5 !important;
  color:#374151 !important;
  font-weight: bold !important;
}
.vertical-menu .menu-title{
  color: rgba(255,255,255,0.55) !important;
  letter-spacing: 0.10em !important;
}

/* Sidebar links */
.vertical-menu .metismenu > li > a,
.vertical-menu .metismenu li a{
  color: rgba(255,255,255,0.86) !important;
  border-radius: 10px !important;
  margin: 4px 10px !important;
}

.vertical-menu .metismenu li a i{
  color: rgba(255,255,255,0.86) !important;
}

/* Hover */
.vertical-menu .metismenu li a:hover{
  background: #7a1e1e !important;
  color: #fff !important;
}

/* Active item */
.vertical-menu .mm-active > a,
.vertical-menu .metismenu li.mm-active > a{
  background: rgba(232,93,4,0.22) !important;
  color: #fff !important;
  border: 1px solid rgba(244,196,48,0.25) !important;
}

/* Active indicator bar */
.vertical-menu .mm-active > a::before{
  content: "";
  position: absolute;
  left: 0;
  width: 4px;
  height: 70%;
  top: 15%;
  border-radius: 0 6px 6px 0;
  background: var(--hm-yellow);
}

/* -------------------------------
   5) Cards / Containers
--------------------------------- */
.card,
.modal-content,
.dropdown-menu{
  border-radius: var(--hm-radius) !important;
  border: 1px solid var(--hm-border) !important;
  box-shadow: var(--hm-shadow-sm) !important;
  background: var(--hm-surface) !important;
}

.card-header{
  background: transparent !important;
  border-bottom: 1px solid var(--hm-border) !important;
}

.card-title,
.card .card-title{
  color: var(--hm-text) !important;
}


/* -------------------------------
   6) Buttons (force HM primary)
--------------------------------- */
.btn{
  border-radius: 12px !important;
  font-weight: 600 !important;
}
.btn i,
.btn svg{
  color: inherit !important;
  fill: currentColor;
}
.btn.active{
  background: var(--hm-red) !important;
  color:#E7E9EE !important;
}
.btn:hover i,
.btn:focus i,
.btn:active i,
.btn:hover svg,
.btn:focus svg,
.btn:active svg{
  color: inherit !important;
  fill: currentColor;
}

.btn-primary,
.btn-soft-primary{
  background: var(--hm-red) !important;
  border-color: var(--hm-red) !important;
  color: #fff !important;
}

.btn-primary:hover,
.btn-soft-primary:hover{
  background: var(--hm-red-2) !important;
  border-color: var(--hm-red-2) !important;
}
a.btn.btn-primary.btn-sm:hover{
color:white!important;
}
.btn-primary:focus,
.btn-primary:active,
.btn-soft-primary:focus{
  box-shadow: var(--hm-ring) !important;
}

.btn-outline-primary{
  color: var(--hm-white) !important;
  border-color: rgba(179,32,37,0.45) !important;
}
.btn-outline-primary:hover{
  background: var(--hm-red) !important;
  border-color: var(--hm-red) !important;
  color: #fff !important;
}

/* Secondary action uses orange */
.btn-warning{
  background: var(--hm-orange) !important;
  border-color: var(--hm-orange) !important;
  color: #fff !important;
}
.btn-warning:hover{
  filter: brightness(0.95);
  color: #fff !important;
}

/* -------------------------------
   7) Forms / Inputs
--------------------------------- */
.form-control,
.form-select,
.select2-container .select2-selection--single{
  border-radius: 12px !important;
  border: 1px solid var(--hm-border) !important;
  background: var(--hm-surface-2) !important;
}

.form-control:focus,
.form-select:focus,
.select2-container--default .select2-selection--single:focus{
  border-color: rgba(232,93,4,0.45) !important;
  box-shadow: var(--hm-ring) !important;
  background: #fff !important;
}

/* Switch / checkbox accent */
.form-check-input:checked{
  background-color: var(--hm-red) !important;
  border-color: var(--hm-red) !important;
}
.form-check-input:focus{
  box-shadow: var(--hm-ring) !important;
}

/* -------------------------------
   8) Badges / Status chips
--------------------------------- */
.badge,
.badge-soft-success,
.badge-soft-warning,
.badge-soft-danger,
.badge-soft-info{
  border-radius: 999px !important;
  padding: 0.45rem 0.7rem !important;
  font-weight: 700 !important;
}

/* -------------------------------
   9) Tables + DataTables
--------------------------------- */
.table{
  color: var(--hm-text) !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
  border-radius: 12px !important;
  overflow: hidden !important;
}
.table-responsive{
  border-radius: 12px !important;
  overflow: hidden !important;
}
.table thead th:first-child{
  border-top-left-radius: 12px !important;
}
.table thead th:last-child{
  border-top-right-radius: 12px !important;
}
.table tbody tr:last-child td:first-child{
  border-bottom-left-radius: 12px !important;
}
.table tbody tr:last-child td:last-child{
  border-bottom-right-radius: 12px !important;
}


.table tbody tr{
  border-color: var(--hm-border) !important;
}

.table-hover tbody tr:hover{
  background: rgba(232,93,4,0.06) !important;
}

/* DataTables inputs */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select{
  border-radius: 12px !important;
  border: 1px solid var(--hm-border) !important;
  background: var(--hm-surface-2) !important;
  padding: 0.45rem 0.7rem !important;
}

.dataTables_wrapper .dataTables_filter input:focus{
  box-shadow: var(--hm-ring) !important;
  border-color: rgba(232,93,4,0.45) !important;
}

/* Pagination (Bootstrap) */
.page-item .page-link{
  border-radius: 10px !important;
  border: 1px solid var(--hm-border) !important;
  color: var(--hm-text) !important;
}
.page-item.active .page-link{
  background: var(--hm-red) !important;
  border-color: var(--hm-red) !important;
  color: #fff !important;
}
.page-item .page-link:focus{
  box-shadow: var(--hm-ring) !important;
}

/* -------------------------------
   11) Scrollbars (optional polish)
--------------------------------- */
::-webkit-scrollbar{ width: 10px; height: 10px; }
::-webkit-scrollbar-thumb{
  background: rgba(179,32,37,0.28);
  border-radius: 999px;
}
::-webkit-scrollbar-thumb:hover{
  background: rgba(232,93,4,0.35);
}

.hm-kpi-card {
        border: 0 !important;
        border-radius: 0px !important;
        color: #fff !important;
        background: linear-gradient(135deg, #b4161d 0%, #c83b41 65%, #eb5353 120%) !important;
        box-shadow: 0 10px 24px rgba(179, 32, 37, 0.28) !important;
        position: relative;
        overflow: hidden;
    }

    .hm-kpi-card::after {
        content: "";
        position: absolute;
        right: -32px;
        bottom: -34px;
        width: 140px;
        height: 140px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, 0) 70%);
    }

    .hm-kpi-label {
        font-size: .72rem;
        font-weight: 700;
        color: rgba(255, 255, 255, .96);
        margin-bottom: .35rem;
    }

    .hm-kpi-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 800;
        margin-bottom: .3rem;
        color: #fff;
    }

    .hm-kpi-meta {
        font-size: .72rem;
        margin: 0;
        color: rgba(255, 255, 255, .9);
    }

    .hm-kpi-icon {
        width: 4.15rem;
    height: 4.15rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, .2);
    border: 1px solid rgba(255, 255, 255, .35);
    z-index: 1;
    position: relative;
    top: 12px;
    left: -15px;
    }

    .hm-kpi-icon i {
        font-size: 1rem;
        color: #fff;
    }.page-content{
      padding:94px 12px 60px!important;
    }

.apexcharts-tooltip,
.apexcharts-xaxistooltip,
.apexcharts-yaxistooltip{
  background: #fff !important;
  border: 1px solid var(--hm-border) !important;
  color: var(--hm-text) !important;
}
.apexcharts-legend-text{
  color: var(--hm-text) !important;
}
.apexcharts-gridline{
  stroke: #eceff3 !important;
}
</style>
<script>
  (function () {
    function cssVar(name, fallback) {
      var value = getComputedStyle(document.documentElement).getPropertyValue(name);
      return (value && value.trim()) || fallback;
    }

    function applyApexThemeDefaults() {
      var palette = [
        cssVar('--hm-apex-1', '#B32025'),
        cssVar('--hm-apex-2', '#E85D04'),
        cssVar('--hm-apex-3', '#F4C430'),
        cssVar('--hm-apex-4', '#9E1B20'),
        cssVar('--hm-apex-5', '#6B7280')
      ];

      window.Apex = window.Apex || {};
      window.Apex.colors = palette;
      window.Apex.chart = window.Apex.chart || {};
      window.Apex.chart.foreColor = cssVar('--hm-muted', '#6B7280');
    }

    applyApexThemeDefaults();
    window.addEventListener('DOMContentLoaded', applyApexThemeDefaults);
  })();
</script>
