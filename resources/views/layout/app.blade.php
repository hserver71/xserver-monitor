<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Client Management System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
:root {
            --sidebar-width: 250px;
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --hover-color: #395ccc;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        
        /* Layout Structure */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: white;
            transition: all var(--transition-speed);
            z-index: 1000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            background: #3a56c4;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            position: relative;
        }
        
        .sidebar-menu li a {
            padding: 15px 25px;
            display: block;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all var(--transition-speed);
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: var(--hover-color);
            color: white;
            border-left: 4px solid #fff;
        }
        
        .sidebar-menu li a i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 20px;
            transition: all var(--transition-speed);
            background-color: #f8f9fc;
        }
        
        /* Header Bar */
        .header-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Card Styles */
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
            transition: transform 0.2s;
        }
        
        .card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
            padding: 15px 20px;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }
        
        /* Client List Styles */
        .client-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .client-item {
            padding: 15px;
            border-bottom: 1px solid #e3e6f0;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .client-item:hover {
            background-color: #f8f9fc;
        }
        
        .client-item.active {
            background-color: #e8f0fe;
            border-left: 4px solid var(--primary-color);
        }
        
        .client-info h5 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .client-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .client-status {
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 20px;
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .table th {
            background-color: #f8f9fc;
            font-weight: 600;
            padding: 12px 15px;
            border-top: none;
            color: #4e73df;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #d1d3e2;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        /* Action Buttons */
        .action-buttons .btn {
            margin-right: 5px;
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            vertical-align: text-bottom;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
            color: #4e73df;
        }
        
        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
            }
            
            .sidebar-menu li {
                flex: 1;
                min-width: 120px;
            }
            
            .sidebar-menu li a {
                text-align: center;
                padding: 15px 10px;
                border-left: none;
                border-bottom: 4px solid transparent;
            }
            
            .sidebar-menu li a:hover,
            .sidebar-menu li a.active {
                border-left: none;
                border-bottom: 4px solid #fff;
            }
            
            .sidebar-menu li a i {
                display: block;
                margin: 0 auto 5px;
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .header-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-bar .btn {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        @include('partials.sidebar')
        
        <div class="main-content">
            @yield('header')
            
            <div class="row">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>