@php
    $model = $item->approvalable;
    $docType = $type;
@endphp
<div class="d-flex justify-content-center gap-1">
    <a href="{{ route('erkap.approvals.show', [$docType, $model->id]) }}" class="btn btn-sm btn-info" title="Detail Dokumen">
        <i class="mdi mdi-eye"></i>
    </a>
    <form method="POST" action="{{ route('erkap.approvals.approve', [$docType, $model->id]) }}" class="d-inline" onsubmit="return confirm('Setujui dokumen ini?')">
        @csrf
        <button type="submit" class="btn btn-sm btn-success" title="Setujui">
            <i class="mdi mdi-check"></i>
        </button>
    </form>
    <button type="button" class="btn btn-sm btn-danger btn-reject" data-url="{{ route('erkap.approvals.reject', [$docType, $model->id]) }}" title="Tolak">
        <i class="mdi mdi-close"></i>
    </button>
</div>