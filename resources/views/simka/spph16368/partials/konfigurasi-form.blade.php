{{--
    Form Konfigurasi Data SPPH 16368.

    Input dikelompokkan per kategori:
    A. Bobot (Pekerjaan Tanah & Sewa Alat Berat & Dump Truck untuk Coal Getting)
    B. Asumsi Dasar (Harga Acuan & Harga Aktual)
    C. Komponen Tarif

    Alur CRUD mengikuti SPPH 773: seluruh input dikirim sekaligus lalu disimpan
    dengan updateOrCreate ke tabel simka_spph16368_*. Tombol Hapus pada tiap
    bagian (serta Hapus Semua) menghapus baris pada tabel terkait.
--}}
@php
    $hasData = $hasData ?? [];
    $sections = [
        [
            'key' => '#',
            'title' => 'Nilai Adjustment',
            'hapus' => 'adjustment',
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
            'hapus' => 'bobot',
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
                    'title' => 'b. Sewa Alat Berat & Dump Truck untuk Coal Getting',
                    'step' => '0.0001',
                    'suffix' => '%',
                    'fields' => [
                        ['name' => 'bobot_coal_getting_bbm_bulldozer_525_hp', 'label' => 'BBM Bulldozer 525 HP atau setara'],
                        ['name' => 'bobot_coal_getting_umsp_bulldozer_525_hp', 'label' => 'UMSP Bulldozer 525 HP atau setara'],
                        ['name' => 'bobot_coal_getting_bbm_bulldozer_225_hp', 'label' => 'BBM Bulldozer 225 HP atau setara'],
                        ['name' => 'bobot_coal_getting_umsp_bulldozer_225_hp', 'label' => 'UMSP Bulldozer 225 HP atau setara'],
                        ['name' => 'bobot_coal_getting_bbm_excavator_3_2_cum', 'label' => 'BBM Excavator 3,2 Cum atau setara'],
                        ['name' => 'bobot_coal_getting_umsp_excavator_3_2_cum', 'label' => 'UMSP Excavator 3,2 Cum atau setara'],
                        ['name' => 'bobot_coal_getting_bbm_excavator_0_9_cum', 'label' => 'BBM Excavator 0,9 Cum atau setara'],
                        ['name' => 'bobot_coal_getting_umsp_excavator_0_9_cum', 'label' => 'UMSP Excavator 0,9 Cum atau setara'],
                        ['name' => 'bobot_coal_getting_bbm_dump_truck_30_ton', 'label' => 'BBM Dump Truck Kap 30 Ton (2,61 Km)'],
                        ['name' => 'bobot_coal_getting_umsp_dump_truck_30_ton', 'label' => 'UMSP Dump Truck Kap 30 Ton (2,61 Km)'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'B',
            'title' => 'Asumsi Dasar',
            'hapus' => 'asumsi-dasar',
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
            'hapus' => 'komponen-tarif',
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

<form action="{{ route('simka.spph16368.konfigurasi.simpan') }}" method="POST" autocomplete="off">
    @csrf

    @foreach($sections as $section)
        <div class="card spph-form-card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center">
                <div class="spph-icon-soft spph-icon-sm bg-soft-{{ $section['color'] }} me-3">
                    <i class="mdi {{ $section['icon'] }}"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold text-dark">{{ $section['key'] }}. {{ $section['title'] }}</h6>
                </div>
                @if(!empty($hasData[$section['hapus']]))
                    <button
                        type="submit"
                        form="formHapus-{{ $section['hapus'] }}"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Hapus seluruh data {{ $section['title'] }} SPPH 16368?');">
                        <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                    </button>
                @endif
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
        @if(!empty(array_filter($hasData)))
            <button
                type="submit"
                form="formHapusKonfigurasiSemua"
                class="btn btn-outline-danger me-auto"
                onclick="return confirm('Hapus seluruh data konfigurasi SPPH 16368?');">
                <i class="mdi mdi-delete-sweep-outline me-1"></i> Hapus Semua
            </button>
        @endif
        <button type="reset" class="btn btn-light">
            <i class="mdi mdi-refresh me-1"></i> Reset
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save me-1"></i> Simpan Konfigurasi
        </button>
    </div>
</form>

{{-- Form hapus per bagian. Diletakkan di luar form utama agar tidak bersarang. --}}
@foreach($sections as $section)
    @if(!empty($hasData[$section['hapus']]))
        <form
            id="formHapus-{{ $section['hapus'] }}"
            action="{{ route('simka.spph16368.konfigurasi.hapus', $section['hapus']) }}"
            method="POST"
            onsubmit="return confirm('Hapus seluruh data {{ $section['title'] }} SPPH 16368?');">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endforeach

@if(!empty(array_filter($hasData)))
    <form
        id="formHapusKonfigurasiSemua"
        action="{{ route('simka.spph16368.konfigurasi.hapus', 'semua') }}"
        method="POST"
        onsubmit="return confirm('Hapus seluruh data konfigurasi SPPH 16368?');">
        @csrf
        @method('DELETE')
    </form>
@endif

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
