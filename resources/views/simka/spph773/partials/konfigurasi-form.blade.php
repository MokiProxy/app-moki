{{--
    Form Konfigurasi Data SPPH 773.

    Input dikelompokkan per kategori:
    A. Bobot (Pekerjaan Tanah & Pekerjaan Batu Bara)
    B. Asumsi Dasar (Harga Acuan & Harga Aktual)
    C. Komponen Tarif
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
                    'title' => 'a. Pekerjaan Tanah',
                    'step' => '0.0001',
                    'suffix' => '%',
                    'fields' => [
                        ['name' => 'bobot_pekerjaan_tanah_bbm_gali', 'label' => 'BBM Gali Pekerjaan Tanah'],
                        ['name' => 'bobot_pekerjaan_tanah_umsp_gali', 'label' => 'UMSP Gali Pekerjaan Tanah'],
                        ['name' => 'bobot_pekerjaan_tanah_bbm_angkutan', 'label' => 'BBM Angkutan Pekerjaan Tanah'],
                        ['name' => 'bobot_pekerjaan_tanah_umsp_angkutan', 'label' => 'UMSP Angkutan Pekerjaan Tanah'],
                        ['name' => 'bobot_pekerjaan_tanah_bbm_sebar', 'label' => 'BBM Sebar Pekerjaan Tanah'],
                        ['name' => 'bobot_pekerjaan_tanah_umsp_sebar', 'label' => 'UMSP Sebar Pekerjaan Tanah'],
                    ],
                ],
                [
                    'title' => 'b. Pekerjaan Batu Bara',
                    'step' => '0.0001',
                    'suffix' => '%',
                    'fields' => [
                        ['name' => 'bobot_pekerjaan_batubara_bbm_gali', 'label' => 'BBM Gali Pekerjaan Batubara'],
                        ['name' => 'bobot_pekerjaan_batubara_umsp_gali', 'label' => 'UMSP Gali Pekerjaan Batubara'],
                        ['name' => 'bobot_pekerjaan_batubara_bbm_angkutan', 'label' => 'BBM Angkutan Pekerjaan Batubara'],
                        ['name' => 'bobot_pekerjaan_batubara_umsp_angkutan', 'label' => 'UMSP Angkutan Pekerjaan Batubara'],
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
                    'title' => 'Harga Acuan',
                    'fields' => [
                        ['name' => 'harga_acuan_bbm_dasar', 'label' => 'Harga Acuan BBM Dasar', 'prefix' => 'Rp', 'currency' => true, 'help' => 'Harga acuan per liter.'],
                        ['name' => 'harga_acuan_umk_dasar', 'label' => 'Harga Acuan UMK Dasar', 'prefix' => 'Rp', 'currency' => true],
                        ['name' => 'harga_acuan_kurs_dasar', 'label' => 'Harga Acuan Kurs Dasar', 'prefix' => 'Rp', 'currency' => true, 'help' => 'Kurs acuan per USD.'],
                    ],
                ],
                [
                    'title' => 'Harga Aktual',
                    'fields' => [
                        ['name' => 'harga_aktual_bbm_dasar', 'label' => 'Harga Aktual BBM Dasar', 'prefix' => 'Rp', 'currency' => true, 'help' => 'Harga aktual per liter.'],
                        ['name' => 'harga_aktual_umk_dasar', 'label' => 'Harga Aktual UMK Dasar', 'prefix' => 'Rp', 'currency' => true],
                        ['name' => 'harga_aktual_kurs_dasar', 'label' => 'Harga Aktual Kurs Dasar', 'prefix' => 'Rp', 'currency' => true, 'help' => 'Kurs aktual per USD.'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'C',
            'title' => 'Komponen Tarif',
            'icon' => 'mdi-percent-outline',
            'color' => 'success',
            'groups' => [
                [
                    'title' => 'Persentase Komponen Tarif',
                    'fields' => [
                        ['name' => 'komponen_tarif_usd_pekerjaan_tanah', 'label' => 'USD Pekerjaan Tanah (%)', 'suffix' => '%'],
                        ['name' => 'komponen_tarif_usd_pekerjaan_batubara', 'label' => 'USD Pekerjaan Batubara (%)', 'suffix' => '%'],
                        ['name' => 'komponen_tarif_rupiah_pekerjaan_tanah', 'label' => 'Rupiah Pekerjaan Tanah (%)', 'suffix' => '%'],
                        ['name' => 'komponen_tarif_rupiah_pekerjaan_batubara', 'label' => 'Rupiah Pekerjaan Batubara (%)', 'suffix' => '%'],
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

<form action="{{ route('simka.spph773.konfigurasi.simpan') }}" method="POST" autocomplete="off">
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
