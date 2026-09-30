{{--
    Konfirmasi hapus generik untuk master struktur a..d.

    Parameter:
      $deleteUrl    — prefix URL, contoh route('erkap.business-units.index')
      $deleteLabel  — label entitas, contoh "Bisnis Unit"
--}}
<form id="form-delete" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus {{ $deleteLabel }}?',
            text: '"' + name + '" akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                $('#form-delete').attr('action', '{{ $deleteUrl }}/' + id).submit();
            }
        });
    });
</script>
