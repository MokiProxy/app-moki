{{--
    Form Konfigurasi Data SPPH 2287.

    Input dikelompokkan per kategori:
    A. Bobot (BBM & UMK)         -> simka_spph2287_bobot
    B. Asumsi Dasar (BBM & UMSK) -> simka_spph2287_asumsi_dasar
    C. Nilai Adjustment          -> simka_spph2287_adjustment_value

    Nilai field ($fieldValues) disiapkan oleh SPPH2287Controller::konfigurasi().
--}}
@php
    $sections = [
        [
            'key' => '#',
            'title' => 'Nilai Adjustment',
            'icon' => 'mdi-calculator-variant',
            'color' => 'warning',
            'groups' => [
                [
                    'title' => 'Adjustment',
                    'fields' => [
                        ['name' => 'nilai_adjustment', 'label' => 'Nilai Adjustment', 'suffix' => '%', 'step' => '0.01'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'A',
            'title' => 'Bobot',
            'icon' => 'mdi-scale-balance',
            'color' => 'primary',
            'groups' => [
                [
                    'title' => 'Bobot',
                    'suffix' => '%',
                    'fields' => [
                        ['name' => 'bobot_bbm', 'label' => 'Bobot BBM'],
                        ['name' => 'bobot_umk', 'label' => 'Bobot UMK'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'B',
            'title' => 'Asumsi Dasar',
            'icon' => 'mdi-chart-timeline-variant',
            'color' => 'info',
            'groups' => [
                [
                    'title' => 'Asumsi Dasar',
                    'fields' => [
                        ['name' => 'harga_bbm_acuan', 'label' => 'Harga BBM Acuan', 'prefix' => 'Rp', 'currency' => true],
                        ['name' => 'harga_bbm_aktual', 'label' => 'Harga BBM Aktual', 'prefix' => 'Rp', 'currency' => true],
                        ['name' => 'umsk_acuan', 'label' => 'UMSK Acuan', 'prefix' => 'Rp', 'currency' => true],
                        ['name' => 'umsk_aktual', 'label' => 'UMSK Aktual', 'prefix' => 'Rp', 'currency' => true],
                    ],
                ],
            ],
        ],
    ];
@endphp

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle-outline me-1"></i>
        Terdapat {{ $errors->count() }} input yang belum valid. Mohon periksa kembali.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('simka.spph2287.konfigurasi.simpan') }}" method="POST" autocomplete="off">
    @csrf

    @foreach($sections as $section)
        <div class="card spph-form-card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center">
                <div class="spph-icon-soft spph-icon-sm bg-soft-{{ $section['color'] }} me-3">
                    <i class="mdi {{ $section['icon'] }}"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">{{ $section['key'] }}. {{ $section['title'] }}</h6>
                </div>
            </div>
            <div class="card-body">
                @foreach($section['groups'] as $group)
                    <div class="{{ $loop->first ? '' : 'mt-4 pt-4 border-top' }}">
                        <h6 class="text-muted fw-bold small mb-3">{{ $group['title'] }}</h6>
                        <div class="row g-3">
                            @foreach($group['fields'] as $field)
                                @include('simka.partials.spph-field', $field + [
                                    'step' => $field['step'] ?? ($group['step'] ?? '0.01'),
                                    'suffix' => $field['suffix'] ?? ($group['suffix'] ?? null),
                                    'value' => $fieldValues[$field['name']] ?? '',
                                ])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="d-flex justify-content-end gap-2">
        <button type="reset" class="btn btn-light">
            <i class="mdi mdi-refresh me-1"></i> Reset
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save me-1"></i> Simpan Konfigurasi
        </button>
    </div>
</form>

<script>
    function formatRupiah(angka) {
        // angka sudah dalam bentuk string digit-only, tambahkan titik pemisah ribuan
        return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Format input ke format Rupiah SAAT KETIK (hanya field currency/Rupiah, bukan persen)
    document.querySelectorAll('input[data-type="currency"]').forEach(function(input) {
        // Set nilai awal jika ada
        if (input.value) {
            input.value = formatRupiah(input.value);
        }

        input.addEventListener('input', function(e) {
            let value = e.target.value;
            // Hapus semua karakter non-digit
            value = value.replace(/\D/g, '');
            if (value !== '') {
                e.target.value = formatRupiah(value);
            }
        });

        input.addEventListener('blur', function() {
            if (input.value && !input.value.startsWith('Rp')) {
                input.value = formatRupiah(input.value.replace(/\D/g, ''));
            }
        });
    });

    // Sebelum submit, hapus format titik pada input currency agar nilai yang dikirim adalah angka murni
    // Jangan sentuh field persen (data-type="percent")
    document.querySelector('form').addEventListener('submit', function() {
        document.querySelectorAll('input[data-type="currency"]').forEach(function(input) {
            if (input.value) {
                input.value = input.value.replace(/\./g, '');
            }
        });
    });
</script>
