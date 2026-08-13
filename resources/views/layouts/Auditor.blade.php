<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Audit Access - {{ $link->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-audit { background-color: #2B3142; }
        .badge-year { font-size: 0.75rem; }
        .folder-card { cursor: pointer; transition: all 0.2s ease; border: 1px solid #e9ecef; }
        .folder-card:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12); border-color: #f6c23e; }
        .folder-icon { font-size: 2.8rem; line-height: 1; }
        .file-icon-table { font-size: 1.2rem; line-height: 1; }
        .breadcrumb-item + .breadcrumb-item::before { content: "\F0142"; font-family: "Material Design Icons"; }
    </style>
    @yield('css')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-audit">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('auditor.access', $link->token) }}">
                <i class="mdi mdi-shield-check me-1"></i> Auditor Access
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="mdi mdi-account-circle me-1"></i> {{ $link->name }}
                </span>
                @foreach($link->allowed_years ?? [] as $year)
                    <span class="badge bg-light text-dark badge-year me-1">{{ $year }}</span>
                @endforeach
            </div>
        </div>
    </nav>

    <div class="container-fluid py-3">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        function showToast(message, type) {
            var toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;';
                document.body.appendChild(toastContainer);
            }
            var toast = document.createElement('div');
            toast.className = 'alert alert-' + type + ' alert-dismissible fade show shadow-sm';
            toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            toastContainer.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 3000);
        }
    </script>
    @yield('plugin')
</body>
</html>
