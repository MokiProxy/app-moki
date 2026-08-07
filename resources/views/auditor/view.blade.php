<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $filename }} - Audit Access</title>
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; overflow: hidden; background: #525659; }
        #pdfjsContainer { width: 100vw; height: 100vh; }
    </style>
</head>
<body>
    <div id="pdfjsContainer" class="pdfjs-viewer-container"></div>

    @include('components.pdf-viewer')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            renderPdfIntoContainer('pdfjsContainer', '{{ url("auditor/{$token}/view") }}?path={{ urlencode($path) }}&raw=true');
        });

        // Disable keyboard shortcuts that might allow download
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'u')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
