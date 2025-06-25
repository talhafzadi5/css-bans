{{--

/**
*
* Created a new component <x-base-layout/>.
*
*/

--}}
@php
    $isBoxed = layoutConfig()['boxed'];
    $isAltMenu = layoutConfig()['alt-menu'];
@endphp
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="description" content="CS2 Admin web panel for Counter-Strike 2 (CS2) servers. Manage bans, mutes, and more using CounterStrikeSharp for enhanced server administration by HobsRKM.">
    <meta name="keywords" content="CS2 admin panel, CS2 admin web panel, Counter-Strike 2 server management, CS2 bans, CS2 mutes, CounterStrikeSharp, Counter-Strike 2 administration, CS2 server tools, css bans">
    <title>{{ $pageTitle }}</title>
    <link rel="icon" type="image/x-icon" href="{{secure_url('/logo/favicon.ico')}}"/>
    @vite(['resources/scss/layouts/modern-light-menu/light/loader.scss'])
    @vite(['resources/layouts/modern-light-menu/loader.js'])

    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('plugins/bootstrap/bootstrap.min.css')}}">
    @vite(['resources/scss/light/assets/main.scss', 'resources/scss/dark/assets/main.scss'])

    <link rel="stylesheet" type="text/css" href="{{asset('plugins/waves/waves.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('plugins/highlight/styles/monokai-sublime.css')}}">
    @vite([ 'resources/scss/light/plugins/perfect-scrollbar/perfect-scrollbar.scss'])

    @vite([
        'resources/scss/layouts/modern-light-menu/light/structure.scss',
        'resources/scss/layouts/modern-light-menu/dark/structure.scss',
    ])

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    @vite(['resources/scss/light/assets/components/list-group.scss'])

    @vite(['resources/scss/dark/assets/components/list-group.scss'])
    @vite(['resources/scss/light/assets/elements/alert.scss'])
    @vite(['resources/scss/dark/assets/elements/alert.scss'])
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- CUSTOM HEADERS IF ANY -->
    {{$headerFiles}}
    <link rel="stylesheet" href="{{asset('plugins/notification/snackbar/snackbar.min.css')}}">
    @vite(['resources/scss/light/plugins/notification/snackbar/custom-snackbar.scss'])
    <link rel="stylesheet" href="{{asset('theme/theme.css')}}">
    
    <!-- RTL Support -->
    <style>
        body[dir="rtl"] {
            text-align: right;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        }
        
        body[dir="rtl"] .navbar-item {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .nav-item:not(:last-child) {
            margin-left: 1rem;
            margin-right: 0;
        }
        
        body[dir="rtl"] .dropdown-menu {
            left: auto !important;
            right: 0 !important;
        }
        
        body[dir="rtl"] .sidebar-wrapper ul.menu-categories li.menu .nav-link {
            padding-right: 30px;
            padding-left: 15px;
            text-align: right;
        }
        
        body[dir="rtl"] .sidebar-wrapper ul.menu-categories li.menu .nav-link svg {
            margin-left: 10px;
            margin-right: 0;
            float: left;
        }
        
        body[dir="rtl"] .main-content {
            margin-right: 260px !important;
            margin-left: 0 !important;
        }
        
        body[dir="rtl"] .sidebar-wrapper {
            right: 0 !important;
            left: auto !important;
        }
        
        body[dir="rtl"] #container {
            padding-right: 0 !important;
            padding-left: 0 !important;
        }
        
        body[dir="rtl"] .main-container {
            direction: rtl;
        }
        
        body[dir="rtl"] .table th,
        body[dir="rtl"] .table td {
            text-align: right;
        }
        
        body[dir="rtl"] .btn {
            margin-left: 5px;
            margin-right: 0;
        }
        
        body[dir="rtl"] .form-label {
            text-align: right;
        }
        
        body[dir="rtl"] .alert {
            text-align: right;
        }
        
        body[dir="rtl"] .breadcrumb {
            text-align: right;
        }
        
        body[dir="rtl"] .card-header {
            text-align: right;
        }
        
        body[dir="rtl"] .card-body {
            text-align: right;
        }
        
        body[dir="rtl"] .pagination {
            justify-content: flex-end;
        }
        
        body[dir="rtl"] .float-left {
            float: right !important;
        }
        
        body[dir="rtl"] .float-right {
            float: left !important;
        }
        
        body[dir="rtl"] .text-left {
            text-align: right !important;
        }
        
        body[dir="rtl"] .text-right {
            text-align: left !important;
        }
        
        body[dir="rtl"] .ml-auto {
            margin-left: 0 !important;
            margin-right: auto !important;
        }
        
        body[dir="rtl"] .mr-auto {
            margin-right: 0 !important;
            margin-left: auto !important;
        }
        
        body[dir="rtl"] .pl-3 {
            padding-left: 0 !important;
            padding-right: 1rem !important;
        }
        
        body[dir="rtl"] .pr-3 {
            padding-right: 0 !important;
            padding-left: 1rem !important;
        }
        
        body[dir="rtl"] input, 
        body[dir="rtl"] textarea, 
        body[dir="rtl"] select {
            text-align: right;
        }
        
        /* Responsive adjustments for RTL */
        @media (max-width: 1199px) {
            body[dir="rtl"] .main-content {
                margin-right: 0 !important;
                margin-left: 0 !important;
            }
        }
        
        body[dir="rtl"] .layout-px-spacing {
            padding-right: 20px;
            padding-left: 20px;
        }
        
        body[dir="rtl"] .middle-content {
            text-align: right;
        }
        
        /* Fix for Bootstrap components in RTL */
        body[dir="rtl"] .modal-header .btn-close {
            margin: -0.5rem auto -0.5rem -0.5rem;
        }
        
        body[dir="rtl"] .dropdown-toggle::after {
            margin-left: 0;
            margin-right: 0.255em;
        }
        
        /* Header positioning for RTL */
        body[dir="rtl"] .header-container {
            right: 260px !important;
            left: 0 !important;
            width: calc(100% - 260px) !important;
        }
        
        @media (max-width: 1199px) {
            body[dir="rtl"] .header-container {
                right: 0 !important;
                width: 100% !important;
            }
        }
        
        /* Sidebar toggle positioning */
        body[dir="rtl"] .sidebarCollapse {
            float: right;
        }
        
        /* Ensure content doesn't overlap */
        body[dir="rtl"] .main-content {
            min-height: calc(100vh - 60px);
        }
    </style>
    <!-- END GLOBAL MANDATORY STYLES -->
</head>
<body @class([
        // 'layout-dark' => $isDark,
        'layout-boxed' => $isBoxed,
        'alt-menu' => ($isAltMenu || Request::routeIs('collapsibleMenu') ? true : false),
        'error' => (Request::routeIs('404') ? true : false),
        'maintanence' => (Request::routeIs('maintenance') ? true : false),
    ]) @if ($scrollspy == 1) {{ $scrollspyConfig }} @else {{''}} @endif   @if (Request::routeIs('fullWidth')) layout="full-width"  @endif dir="rtl" >

    <!-- BEGIN LOADER -->
    <x-layout-loader/>
    <!--  END LOADER -->




    <!--  BEGIN NAVBAR  -->
    <x-navbar.style-vertical-menu classes="{{($isBoxed ? 'container-xxl' : '')}}"/>
    <!--  END NAVBAR  -->


        <!--  BEGIN MAIN CONTAINER  -->
        <div class="main-container " id="container">

            <!--  BEGIN LOADER  -->
            <x-layout-overlay/>
            <!--  END LOADER  -->
            <x-menu.vertical-menu/>

            <!--  BEGIN CONTENT AREA  -->
            <div id="content" class="main-content {{(Request::routeIs('blank') ? 'ms-0 mt-0' : '')}}">

                @if ($scrollspy == 1)
                    <div class="container">
                        <div class="container">
                            {{ $slot }}
                        </div>
                    </div>
                @else
                    <div class="layout-px-spacing">
                        <div class="middle-content {{($isBoxed ? 'container-xxl' : '')}} p-0">
                            {{ $slot }}
                        </div>
                    </div>
                @endif

                <!--  BEGIN FOOTER  -->
                <x-layout-footer/>
                <!--  END FOOTER  -->

            </div>
            <!--  END CONTENT AREA  -->

        </div>
        <!--  END MAIN CONTAINER  -->

        <!-- BEGIN GLOBAL MANDATORY STYLES -->
        <script>
            var timeZone = '{{ config('app.timezone') }}';
        </script>
        <script
            src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>
        <script src="{{asset('plugins/bootstrap/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('plugins/perfect-scrollbar/perfect-scrollbar.min.js')}}"></script>
        <script src="{{asset('plugins/mousetrap/mousetrap.min.js')}}"></script>
        <script src="{{asset('plugins/waves/waves.min.js')}}"></script>
        <script src="{{asset('plugins/highlight/highlight.pack.js')}}"></script>
        @if ($scrollspy == 1) @vite(['resources/assets/js/scrollspyNav.js']) @endif
        @vite(['resources/layouts/modern-light-menu/app.js'])

        <!-- END GLOBAL MANDATORY STYLES -->
        {{$footerFiles}}
        <script>
            let themeMode = {{ env('DEFAULT_THEME_DARK', true) }};
            
            // Suppress passive event listener warnings for better console readability
            const originalConsoleWarn = console.warn;
            console.warn = function(message) {
                if (typeof message === 'string' && 
                    (message.includes('passive event listener') || 
                     message.includes('Added non-passive event listener'))) {
                    return; // Suppress these specific warnings
                }
                originalConsoleWarn.apply(console, arguments);
            };
            
            // Add passive event listener support for better performance
            (function() {
                var supportsPassive = false;
                try {
                    var opts = Object.defineProperty({}, 'passive', {
                        get: function() {
                            supportsPassive = true;
                            return true;
                        }
                    });
                    window.addEventListener('testPassive', null, opts);
                    window.removeEventListener('testPassive', null, opts);
                } catch (e) {}
                
                if (supportsPassive) {
                    // Override addEventListener to make wheel/touch events passive by default
                    var originalAddEventListener = EventTarget.prototype.addEventListener;
                    EventTarget.prototype.addEventListener = function(type, listener, options) {
                        if ((type === 'wheel' || type === 'mousewheel' || type === 'touchstart' || type === 'touchmove') && 
                            (typeof options === 'undefined' || (typeof options === 'boolean' && !options))) {
                            options = { passive: true };
                        }
                        return originalAddEventListener.call(this, type, listener, options);
                    };
                }
            })();
        </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{asset('plugins/notification/snackbar/snackbar.min.js')}}"></script>
    <x-loader/>
</body>
</html>
