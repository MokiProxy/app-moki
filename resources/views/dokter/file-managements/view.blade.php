<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <iframe src="{{ route('dokter.file-managements.view', ['path' => $path, 'raw' => true]) }}"
                        style="width: 100%; height: 100vh; border: none;"
                        title="{{ $filename }}">
                </iframe>
            </div>
        </div>
    </div>
</div>
