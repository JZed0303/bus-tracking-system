<style>
/* =========================================================
   HM FINANCE - GLOBAL THEME OVERRIDE (Tocly + Bootstrap)
   Blue variant
   ========================================================= */

/* -------------------------------
   1) Brand Tokens (Finance Palette)
--------------------------------- */
:root{
  --hm-red: #346bec !important;        /* primary finance blue */
  --hm-red-2: #0b4aa0;      /* deeper blue for hover */
  --hm-orange: #1f7ae0;     /* accent blue */
  --hm-yellow: #7cc4ff;     /* light accent */

  --hm-bg: #F5F8FC;
  --hm-surface: #FFFFFF;
  --hm-surface-2: #F9FBFE;
  --hm-border: #DCE6F2;
  --hm-text: #111827;
  --hm-muted: #6B7280;

  --hm-shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
  --hm-shadow-sm: 0 6px 18px rgba(17, 24, 39, 0.06);

  --hm-radius: 14px;
  --hm-radius-sm: 10px;

  --hm-ring: 0 0 0 4px rgba(31, 122, 224, 0.18);

  --hm-grad: linear-gradient(135deg, var(--hm-red) 0%, var(--hm-orange) 65%, var(--hm-yellow) 120%);
  --hm-grad-soft: linear-gradient(135deg, rgba(15, 92, 192, 0.10) 0%, rgba(31, 122, 224, 0.10) 60%, rgba(124, 196, 255, 0.10) 120%);
}

html, body{
  background: var(--hm-bg) !important;
  color: var(--hm-text) !important;
}

body{
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif !important;
}

a:hover{
  color: var(--hm-orange) !important;
}

.page-title-box .page-title,
h1,h2,h3,h4,h5{
  letter-spacing: 0.2px;
}

#layout-wrapper{
  background: transparent !important;
}

#page-topbar,
.navbar-header{
  background: var(--hm-surface) !important;
  border-bottom: 1px solid var(--hm-border) !important;
  box-shadow: 0 6px 18px rgba(17,24,39,0.04) !important;
}

#page-topbar::after{
  content: "";
  display: block;
  height: 3px;
  background: var(--hm-grad);
}

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

.app-search .form-control,
.navbar-header .form-control{
  border-radius: 999px !important;
  border: 1px solid var(--hm-border) !important;
  background: var(--hm-surface-2) !important;
}
.app-search .form-control:focus,
.navbar-header .form-control:focus{
  box-shadow: var(--hm-ring) !important;
  border-color: rgba(31,122,224,0.45) !important;
}

.vertical-menu,
.navbar-brand-box,
.sidebar-menu-scroll,
.metismenu{
  background: linear-gradient(to bottom, #003c89 0%, #094597 48%, #083375 100%) !important;
}
.vertical-collpsed .vertical-menu #sidebar-menu>ul ul{
  background-color:#155cb8!important;
}
body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu ul li li a.mm-active,
body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu ul li li a.active{
  color:#ffffff !important;
  width:90%!important;
}
.sidebar-user-box.mm-active{
  background:#003478;
}
.sidebar-user-box{
  background:#052e65;
  padding: 15px 12px!important;
}
.vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>ul a{
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

.navbar-brand-box{
  border-right: 1px solid rgba(17, 39, 77, 0.67) !important;
}
body[data-sidebar=colored].vertical-collpsed .vertical-menu #sidebar-menu>ul>li:hover>a{
  background-color:#0a4eaa!important;
}

.table thead th,
table thead th,
.table > :not(caption) > * > th,
.dataTable thead th,
table.dataTable thead th{
  background:#0f5cc0 !important;
  color:#ffffff !important;
  border-color:#0f5cc0 !important;
}
.table-light{
  color: #ffffff;
  border-color: #0f5cc0 !important;
  background-color: #0f5cc0!important;
}

.vertical-menu .menu-title{
  color: rgba(255,255,255,0.55) !important;
  letter-spacing: 0.10em !important;
}

.vertical-menu .metismenu > li > a,
.vertical-menu .metismenu li a{
  color: rgba(255,255,255,0.86) !important;
  border-radius: 10px !important;
  margin: 4px 10px !important;
}

.vertical-menu .metismenu li a i{
  color: rgba(255,255,255,0.86) !important;
}.btn-primary:hover, .btn-soft-primary:hover{
  color:white!important;
}

.vertical-menu .metismenu li a:hover{
  background: #0a4eaa !important;
  color: #fff !important;
}

.vertical-menu .mm-active > a,
.vertical-menu .metismenu li.mm-active > a{
  background: rgba(31,122,224,0.22) !important;
  color: #fff !important;
  border: 1px solid rgba(124,196,255,0.35) !important;
}

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

.btn{
  /* border-radius: 12px !important; */
  font-weight: 600 !important;
}
.btn i,
.btn svg{
  color: inherit !important;
  fill: currentColor;
}
a.btn:hover,
a.btn:focus,
a.btn:active,
.btn:hover,
.btn:focus,
.btn:active{
  
  color: white !important;
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
  color: var(--hm-red) !important;
  border-color: rgba(15,92,192,0.45) !important;
}
.btn-outline-primary:hover{
  background: var(--hm-red) !important;
  border-color: var(--hm-red) !important;
  color: #fff !important;
}

.btn-warning{
  background: var(--hm-orange) !important;
  border-color: var(--hm-orange) !important;
  color: #fff !important;
}
.btn-warning:hover{
  filter: brightness(0.95);
  color: #fff !important;
}

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
  border-color: rgba(31,122,224,0.45) !important;
  box-shadow: var(--hm-ring) !important;
  background: #fff !important;
}

.form-check-input:checked{
  background-color: var(--hm-red) !important;
  border-color: var(--hm-red) !important;
}
.form-check-input:focus{
  box-shadow: var(--hm-ring) !important;
}

.badge,
.badge-soft-success,
.badge-soft-warning,
.badge-soft-danger,
.badge-soft-info{
  border-radius: 999px !important;
  padding: 0.45rem 0.7rem !important;
  font-weight: 700 !important;
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

.table thead th{
  background: #0f5cc0 !important;
  border-bottom: 1px solid #0f5cc0 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
}

.table tbody tr{
  border-color: var(--hm-border) !important;
}

.table-hover tbody tr:hover{
  background: rgba(31,122,224,0.08) !important;
}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select{
  border-radius: 12px !important;
  border: 1px solid var(--hm-border) !important;
  background: var(--hm-surface-2) !important;
  padding: 0.45rem 0.7rem !important;
}

.dataTables_wrapper .dataTables_filter input:focus{
  box-shadow: var(--hm-ring) !important;
  border-color: rgba(31,122,224,0.45) !important;
}

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

::-webkit-scrollbar{ width: 10px; height: 10px; }
::-webkit-scrollbar-thumb{
  background: rgba(15,92,192,0.28);
  border-radius: 999px;
}
::-webkit-scrollbar-thumb:hover{
  background: rgba(31,122,224,0.35);
}

.hm-kpi-card{
  border: 0 !important;
  border-radius: 0 !important;
  color: #fff !important;
  background: linear-gradient(135deg, #0f5cc0 0%, #1f7ae0 65%, #7cc4ff 120%) !important;
  box-shadow: 0 10px 24px rgba(15, 92, 192, 0.28) !important;
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

.hm-kpi-label{
  font-size: .72rem;
  font-weight: 700;
  color: rgba(255, 255, 255, .96);
  margin-bottom: .35rem;
}

.hm-kpi-value{
  font-size: 2rem;
  line-height: 1;
  font-weight: 800;
  margin-bottom: .3rem;
  color: #fff;
}

.hm-kpi-meta{
  font-size: .72rem;
  margin: 0;
  color: rgba(255, 255, 255, .9);
}

.hm-kpi-icon{
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

.hm-kpi-icon i{
  font-size: 1rem;
  color: #fff;
}

.page-content{
  padding:94px 12px 60px!important;
}
</style>
