<style>
/* =========================================================
   HM TRANSPORT - GLOBAL THEME OVERRIDE (Tocly + Bootstrap)
   BLUE ENTERPRISE VARIANT (Drop-in)
   ========================================================= */

/* -------------------------------
   1) Brand Tokens (Blue Palette)
--------------------------------- */
:root{
  /* Core Brand */
  --hm-primary: #2563EB;      /* primary blue */
  --hm-primary-2: #1D4ED8;    /* deeper blue hover */
  --hm-accent: #60A5FA;       /* accent sky */
  --hm-accent-2: #93C5FD;     /* soft accent */

  /* Apex palette (charts) */
  --hm-apex-1: #2563EB;
  --hm-apex-2: #60A5FA;
  --hm-apex-3: #93C5FD;
  --hm-apex-4: #1D4ED8;
  --hm-apex-5: #6B7280;

  /* Neutrals */
  --hm-bg: #F5F7FB;
  --hm-surface: #FFFFFF;
  --hm-surface-2: #FAFAFB;
  --hm-border: #E5E7EB;
  --hm-text: #111827;
  --hm-muted: #6B7280;

  /* Elevation */
  --hm-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
  --hm-shadow-sm: 0 6px 18px rgba(0, 0, 0, 0.05);

  /* Radii */
  --hm-radius: 14px;
  --hm-radius-sm: 10px;

  /* Focus ring */
  --hm-ring: 0 0 0 4px rgba(37, 99, 235, 0.16);

  /* Gradients */
  --hm-grad: linear-gradient(135deg, #2563EB 0%, #60A5FA 72%);
  --hm-grad-soft: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(96, 165, 250, 0.08) 100%);
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

#page-topbar,
.navbar-header{
  background: var(--hm-surface) !important;
  box-shadow: 0 6px 18px rgba(0,0,0,0.04) !important;
}

#page-topbar::after{
  content: "";
  display: block;
  height: 3px;
  background: var(--hm-grad);
}

/* Topbar logo next to hamburger */
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
  border-color: rgba(37,99,235,0.45) !important;
}

/* -------------------------------
   4) Sidebar (Deep Navy)
--------------------------------- */
.vertical-menu,
.navbar-brand-box,
.sidebar-menu-scroll,
.metismenu{
  background: linear-gradient(to bottom, #0B1F3B 0%, #0A1730 58%, #060C18 100%) !important;
}

.vertical-collpsed .vertical-menu #sidebar-menu>ul ul{
  background-color: #0A1730 !important;
}

.sidebar-user-box.mm-active{ background: rgba(96,165,250,0.16); }
.sidebar-user-box{
  background: rgba(255,255,255,0.06);
  padding: 15px 12px !important;
}

.navbar-brand-box{
  border-right: 1px solid rgba(255,255,255,0.08) !important;
}

.vertical-menu .menu-title{
  color: rgba(255,255,255,0.55) !important;
  letter-spacing: 0.10em !important;
}

/* Sidebar links */
.vertical-menu .metismenu > li > a,
.vertical-menu .metismenu li a{
  color: rgba(255,255,255,0.88) !important;
  border-radius: 10px !important;
  margin: 4px 10px !important;
}
.vertical-menu .metismenu li a i{
  color: rgba(255,255,255,0.88) !important;
}

/* Hover */
.vertical-menu .metismenu li a:hover{
  background: rgba(96,165,250,0.18) !important;
  color: #fff !important;
}

/* Active item */
.vertical-menu .mm-active > a,
.vertical-menu .metismenu li.mm-active > a{
  background: rgba(96,165,250,0.22) !important;
  color: #fff !important;
  border: 1px solid rgba(147,197,253,0.22) !important;
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
  background: var(--hm-accent);
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
   6) Buttons (Blue primary)
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

.btn-primary,
.btn-soft-primary{
  background: var(--hm-primary) !important;
  border-color: var(--hm-primary) !important;
  color: #fff !important;
}

.btn-primary:hover,
.btn-soft-primary:hover{
  background: var(--hm-primary-2) !important;
  border-color: var(--hm-primary-2) !important;
}

.btn-primary:focus,
.btn-primary:active,
.btn-soft-primary:focus{
  box-shadow: var(--hm-ring) !important;
}

.btn-outline-primary{
  color: var(--hm-primary) !important;
  border-color: rgba(37,99,235,0.45) !important;
}
.btn-outline-primary:hover{
  background: var(--hm-primary) !important;
  border-color: var(--hm-primary) !important;
  color: #fff !important;
}

/* Secondary action */
.btn-warning{
  background: var(--hm-accent) !important;
  border-color: var(--hm-accent) !important;
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
  border-color: rgba(37,99,235,0.45) !important;
  box-shadow: var(--hm-ring) !important;
  background: #fff !important;
}

.form-check-input:checked{
  background-color: var(--hm-primary) !important;
  border-color: var(--hm-primary) !important;
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
.table thead th,
table thead th,
.table > :not(caption) > * > th,
.dataTable thead th,
table.dataTable thead th{
  background:#324878 !important;
  color:white !important;
    border: 0 !important;
  font-weight: bold !important;
  
}body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>a{
  background: rgb(188 48 59) !important;
}

.chat-conversation .conversation-list .ctext-wrap-content{
 
    padding: 12px 16px;
    background-color: #363a43;
    border-radius: 0 .25rem .25rem;
    color: #fff;
    margin-left: 16px;
    position: relative;
    
}.chat-conversation .conversation-list .ctext-wrap-content:before{
    content: "";
    position: absolute;
    border: 5px solid transparent;
    border-right-color: #131a29;
    border-top-color: #3b4456;
    left: -10px;
    top: 0;
}
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

.table-hover tbody tr:hover{
  background: rgba(37,99,235,0.05) !important;
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
  border-color: rgba(37,99,235,0.45) !important;
}

/* Pagination */
.page-item .page-link{
  border-radius: 10px !important;
  border: 1px solid var(--hm-border) !important;
  color: var(--hm-text) !important;
}
.page-item.active .page-link{
  background: var(--hm-primary) !important;
  border-color: var(--hm-primary) !important;
  color: #fff !important;
}
.page-item .page-link:focus{
  box-shadow: var(--hm-ring) !important;
}

/* -------------------------------
   10) Scrollbars
--------------------------------- */
::-webkit-scrollbar{ width: 10px; height: 10px; }
::-webkit-scrollbar-thumb{
  background: rgba(37,99,235,0.20);
  border-radius: 999px;
}
::-webkit-scrollbar-thumb:hover{
  background: rgba(37,99,235,0.30);
}

/* KPI card (Blue) */
.hm-kpi-card{
  border: 0 !important;
  border-radius: 0px !important;
  color: #fff !important;
  background: linear-gradient(135deg, #0B1F3B 0%, #2563EB 60%, #60A5FA 120%) !important;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22) !important;
  position: relative;
  overflow: hidden;
}

.hm-kpi-card::after{
  content: "";
  position: absolute;
  right: -32px;
  bottom: -34px;
  width: 140px;
  height: 140px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, 0) 70%);
}

.hm-kpi-label{ font-size: .72rem; font-weight: 700; color: rgba(255,255,255,.96); margin-bottom: .35rem; }
.hm-kpi-value{ font-size: 2rem; line-height: 1; font-weight: 800; margin-bottom: .3rem; color: #fff; }
.hm-kpi-meta{ font-size: .72rem; margin: 0; color: rgba(255,255,255,.9); }
.hm-kpi-icon{
  width: 4.15rem; height: 4.15rem; border-radius: 999px;
  display: inline-flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.2);
  border: 1px solid rgba(255,255,255,.35);
  z-index: 1; position: relative; top: 12px; left: -15px;
}
.hm-kpi-icon i{ font-size: 1rem; color: #fff; }

.page-content{
  padding: 94px 12px 60px !important;
}

/* Apex tooltip polish */
.apexcharts-tooltip,
.apexcharts-xaxistooltip,
.apexcharts-yaxistooltip{
  background: #fff !important;
  border: 1px solid var(--hm-border) !important;
  color: var(--hm-text) !important;
}
.apexcharts-legend-text{ color: var(--hm-text) !important; }
.apexcharts-gridline{ stroke: #eceff3 !important; }

</style>

<script>
  (function () {
    function cssVar(name, fallback) {
      var value = getComputedStyle(document.documentElement).getPropertyValue(name);
      return (value && value.trim()) || fallback;
    }

    function applyApexThemeDefaults() {
      var palette = [
        cssVar('--hm-apex-1', '#2563EB'),
        cssVar('--hm-apex-2', '#60A5FA'),
        cssVar('--hm-apex-3', '#93C5FD'),
        cssVar('--hm-apex-4', '#1D4ED8'),
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