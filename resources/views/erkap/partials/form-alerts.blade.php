@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="mdi mdi-alert-circle me-1"></i>Error:</strong> {{ session('error') }}
        @if(session('error_detail'))
            <hr>
            <small class="text-muted">
                <strong>File:</strong> {{ session('error_detail.file') }}<br>
                <strong>Line:</strong> {{ session('error_detail.line') }}
            </small>
            <details class="mt-2">
                <summary class="text-muted" style="cursor:pointer">Stack Trace</summary>
                <pre class="mt-1 p-2 bg-light border rounded" style="font-size:11px;max-height:200px;overflow:auto">{{ session('error_detail.trace') }}</pre>
            </details>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="mdi mdi-alert-circle me-1"></i>Periksa kembali isian berikut:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
