{{--
    Form Konfigurasi Data SPPH 773 - Kalkulasi.
--}}
@php
    $sections = [
        [
            'key' => 'A',
            'title' => 'Pekerjaan Tanah',
            'icon' => 'mdi-landscape',
            'color' => 'primary',
            'groups' => [
                [
                    'title' => 'Pekerjaan Tanah',
                    'fields' => [
                        ['name' => 'tarif_gali_tanah', 'label' => 'Tarif Gali', 'icon' => 'mdi-pickaxe', 'route' => 'simka.spph773.tarif-gali-tanah'],
                        ['name' => 'tarif_angkutan_tanah', 'label' => 'Tarif Angkutan', 'icon' => 'mdi-truck', 'route' => 'simka.spph773.tarif-angkutan-tanah'],
                        ['name' => 'tarif_sebar_tanah', 'label' => 'Tarif Sebar', 'icon' => 'mdi-food', 'route' => 'simka.spph773.tarif-sebar-tanah'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'B',
            'title' => 'Pekerjaan Batu Bara',
            'icon' => 'mdi-fire',
            'color' => 'warning',
            'groups' => [
                [
                    'title' => 'Pekerjaan Batubara',
                    'fields' => [
                        ['name' => 'tarif_gali_batubara', 'label' => 'Tarif Gali', 'icon' => 'mdi-pickaxe', 'route' => 'simka.spph773.tarif-gali-batubara'],
                        ['name' => 'tarif_angkutan_batubara', 'label' => 'Tarif Angkutan', 'icon' => 'mdi-truck', 'route' => 'simka.spph773.tarif-angkutan-batubara'],
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
                            <div class="col-12 col-lg-4">
                                <a href="{{ route($field['route']) }}" class="spph-konfigurasi-card text-decoration-none d-block text-dark h-100">
                                    <div class="p-4 d-flex flex-column justify-content-between h-100">
                                        <div class="spph-icon-soft bg-soft-{{ $section['color'] }} mb-3">
                                            <i class="mdi {{ $field['icon'] }} fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">{{ $field['label'] }}</h6>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <small class="text-muted">Klik untuk mengelola konfigurasi</small>
                                            <span class="badge bg-soft-{{ $section['color'] }} text-{{ $section['color'] }}">
                                                <i class="mdi mdi-arrow-right me-1"></i> Buka
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
