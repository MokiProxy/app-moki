/**
 * Helper dropdown cascading struktur COA RKAP.
 *
 * Rantai master:  Bisnis Unit (a) → Lokasi (b) → Manajemen Area (c)
 *                 → Aktivitas (d) → Pusat Biaya → Elemen Biaya (e) → COA
 *
 * Semua opsi diambil dari `GET /erkap/coa-options/*` sehingga form transaksi
 * tidak perlu memuat master ke blade dan tidak perlu Izin CRUD master.
 *
 * Pemakaian:
 *
 *   ErkapCascade.init({
 *       chain: ['business_unit', 'location', 'management_area', 'activity'],
 *       selectors: {
 *           business_unit:   '#erkap_business_unit_id',
 *           location:        '#erkap_location_id',
 *           management_area: '#erkap_management_area_id',
 *           activity:        '#erkap_activity_id',
 *       },
 *   });
 *
 * Lantai ke-4 (Pusat Biaya) menampilkan preview kode 11 karakter, dan bila
 * `cost_element` ikut dalam chain, preview diperpanjang menjadi 15 karakter.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var ENDPOINTS = {
        division: 'divisions',
        business_unit: 'business-units',
        location: 'locations',
        management_area: 'management-areas',
        activity: 'activities',
        cost_center: 'cost-centers',
        cost_element: 'cost-elements',
        account: 'accounts',
    };

    /* Parent yang menentukan daftar anak, sesuai endpoint di CoaOptionService. */
    var PARENT_QUERY = {
        division: function () { return {}; },
        business_unit: function () { return {}; },
        location: function (p) { return { business_unit_id: p.business_unit ? p.business_unit.id : null }; },
        management_area: function (p) { return { location_id: p.location ? p.location.id : null, business_unit_id: p.business_unit ? p.business_unit.id : null }; },
        activity: function (p) { return { management_area_id: p.management_area ? p.management_area.id : null, location_id: p.location ? p.location.id : null, business_unit_id: p.business_unit ? p.business_unit.id : null }; },
        cost_center: function (p) { return { management_area_id: p.management_area ? p.management_area.id : null, activity_id: p.activity ? p.activity.id : null, location_id: p.location ? p.location.id : null, business_unit_id: p.business_unit ? p.business_unit.id : null }; },
        cost_element: function (p) { return { cost_center_id: p.cost_center ? p.cost_center.id : null }; },
        account: function (p) { return { cost_center_id: p.cost_center ? p.cost_center.id : null, cost_element_id: p.cost_element ? p.cost_element.id : null, division_id: p.division ? p.division.id : null }; },
    };

    var SEGMENT_ORDER = ['business_unit', 'location', 'management_area', 'activity'];
    /* Panjang harus identik dengan CoaCode::SEGMENT_LENGTHS (PHP). */
    var SEGMENT_LENGTH = { business_unit: 1, location: 2, management_area: 5, activity: 3 };
    var COST_ELEMENT_LENGTH = 4;
    var COST_CENTER_LENGTH = 11;

    /* Parent yang wajib terisi sebelum anak boleh dimuat. Daftar ini disengaja
     * eksplisit: bentuk chain berbeda antar form (mis. `account` bisa turun
     * dari Pusat Biaya atau dari Divisi), jadi tidak bisa disimpulkan dari
     * PARENT_QUERY. Override per form lewat `config.dependsOn`. */
    var DEPENDS_ON = {
        location: ['business_unit'],
        management_area: ['location'],
        activity: ['management_area'],
        cost_center: ['management_area'],
        cost_element: ['cost_center'],
        account: ['cost_center'],
    };

    var baseUrl = null;

    function url() {
        if (!baseUrl) {
            throw new Error('ErkapCascade: ERKAP_COA_OPTIONS_URL belum di-set.');
        }

        return baseUrl.replace(/\/+$/, '');
    }

    /**
     * Select2 di-destroy lalu di-init ulang setelah opsi diganti, karena
     * Select2 menyimpan salinan option di DOM ter-cache.
     */
    function refreshSelect2($select) {
        if ($select.hasClass('no-select2')) {
            return;
        }

        if ($.fn.select2 && $select.data('select2')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            placeholder: ($select.data('placeholder') || '— Pilih —').trim(),
        });
    }

    function labelOf(option) {
        return option.label || (option.code + ' — ' + option.name);
    }

    function request(endpoint, params) {
        return $.ajax({
            url: url() + '/' + ENDPOINTS[endpoint],
            type: 'GET',
            dataType: 'json',
            data: params,
        });
    }

    /**
     * Susun preview kode: segmen a..d (atau kode Pusat Biaya terpilih),
     * lalu ditambah elemen biaya bila ada.
     */
    function composeCode(selected) {
        var code = '';

        // Alur "pilih COA jadi": kode a..e sudah utuh, tidak perlu disusun.
        if (selected.account) {
            return String(selected.account.code) || '';
        }

        if (selected.cost_center) {
            // Alur "pilih Pusat Biaya langsung": kode a..d sudah utuh.
            code = String(selected.cost_center.code);
        } else {
            SEGMENT_ORDER.forEach(function (key) {
                var option = selected[key];
                if (option) {
                    code += String(option.code).padStart(SEGMENT_LENGTH[key], '0');
                }
            });
        }

        if (code.length !== COST_CENTER_LENGTH) {
            return '';
        }

        if (selected.cost_element) {
            code += String(selected.cost_element.code).padStart(COST_ELEMENT_LENGTH, '0');
        }

        return code;
    }

    function ErkapCascade(options) {
        var config = $.extend({
            chain: [],
            selectors: {},
            preview: '#erkap_code_preview',
            onChange: null,
            /* Dipanggil sekali setelah state awal terbaca (form edit / old input). */
            onReady: null,
            /* Override query parent per anak, contoh:
             *   parentQuery: { cost_element: function (p) { return {}; } }
             * untuk meminta seluruh Elemen Biaya, bukan hanya yang sudah punya
             * COA pada Pusat Biaya terpilih. */
            parentQuery: {},
            /* Param statis yang selalu dikirim bersama query parent, contoh:
             *   query: { account: function () { return { type: 'revenue' }; } }
             * untuk membatasi COA pada form Rencana Pendapatan/Beban. */
            query: {},
            /* Parent wajib per anak, contoh:
             *   dependsOn: { account: ['division'] }
             * untuk chain `divisi -> COA` pada form yang tidak memakai Pusat
             * Biaya sebagai parent. */
            dependsOn: {},
            /* Nilai tersimpan per anak, dipakai saat `<option>`-nya belum ada di
             * DOM karena select anak dirender kosong lalu diisi lewat API:
             *   initialValues: { account: 12 }
             * Tanpa ini, pilihan pada form edit dan re-render validasi hilang
             * begitu API memuat ulang daftar opsi. */
            initialValues: {},
        }, options || {});

        var chain = config.chain.filter(function (key) {
            return ENDPOINTS[key] && config.selectors[key];
        });

        if (!chain.length) {
            return;
        }

        var selected = {};

        chain.forEach(function (key) {
            var $select = $(config.selectors[key]);
            if (!$select.length) {
                return;
            }

            $select.on('change.erkapCascade', function () {
                var value = $(this).val();
                selected[key] = value ? findOption($select, value) : null;

                // Perubahan induk membatalkan seluruh pilihan di hilirnya.
                clearFrom(chain, config.selectors, selected, chain.indexOf(key) + 1, config);

                updatePreview(config, selected);
                loadChildren(key, config, selected, chain);

                if (typeof config.onChange === 'function') {
                    config.onChange(selected, key);
                }
            });
        });

        // Isi keadaan awal dari nilai yang sudah ter-render (form edit). Anak
        // form transaksi dirender kosong lalu diisi lewat API, jadi nilai
        // simpanannya tidak ada sebagai `<option>`; `initialValues` menjadi
        // sumber nilai untuk kasus itu.
        chain.forEach(function (key) {
            var $select = $(config.selectors[key]);
            var rendered = $select.length ? $select.val() : null;
            var value = rendered || (config.initialValues && config.initialValues[key]) || null;

            selected[key] = value ? findOption($select, value) : null;

            if (selected[key] && config.initialValues) {
                config.initialValues[key] = null;
            }
        });

        updatePreview(config, selected);
        loadChildren(chain[0], config, selected, chain);

        // Form edit / re-render karena validasi gagal sudah punya nilai terpilih
        // sejak blade, tanpa melewati handler `change`. Beri form kesempatan
        // menyelaraskan turunan (kode COA, owner, dsb) dari state awal itu.
        if (typeof config.onReady === 'function') {
            config.onReady(selected);
        }
    }

    function findOption($select, value) {
        var found = null;

        $select.find('option').each(function () {
            if (String(this.value) === String(value) && String(this.value) !== '') {
                found = window.ErkapCascade.optionFromNode(this);
            }
        });

        return found;
    }

    function clearFrom(chain, selectors, selected, startIndex, config) {
        chain.slice(startIndex).forEach(function (key) {
            selected[key] = null;

            // Nilai awal hanya berlaku sekali, untuk memulihkan pilihan di form
            // edit / re-render validasi. Setelah induk berubah, nilai lama sudah
            // tidak relevan dan tidak boleh muncul lagi.
            if (config && config.initialValues) {
                config.initialValues[key] = null;
            }

            $(selectors[key]).html('<option value="">- Pilih -</option>').val('');
        });
    }

    function updatePreview(config, selected) {
        var $preview = $(config.preview);
        if (!$preview.length) {
            return;
        }

        $preview.text(composeCode(selected) || '—');
    }

    /**
     * Query parent default, kecuali di-override oleh `config.parentQuery`,
     * lalu digabung dengan param statis `config.query`.
     */
    function parentQueryFor(config, key, selected) {
        var override = config.parentQuery && config.parentQuery[key];
        var query = typeof override === 'function'
            ? override(selected)
            : (PARENT_QUERY[key] ? PARENT_QUERY[key](selected) : {});

        var staticQuery = config.query && config.query[key];

        if (typeof staticQuery === 'function') {
            $.extend(query, staticQuery(selected));
        }

        return query;
    }

    function loadChildren(key, config, selected, chain) {
        var index = chain.indexOf(key);
        var nextKey = chain[index + 1];

        if (!nextKey) {
            return;
        }

        var $select = $(config.selectors[nextKey]);
        if (!$select.length) {
            return;
        }

        var query = parentQueryFor(config, nextKey, selected) || {};

        $select.prop('disabled', false);

        // Pilihan tersimpan perlu dipulihkan meski `<option>`-nya belum ada di
        // DOM, karena select anak sengaja dirender kosong lalu diisi dari API.
        var current = $select.val() || (config.initialValues && config.initialValues[nextKey]) || null;

        // Param yang tidak berlaku untuk bentuk chain ini harus dibuang, kalau
        // tidak akan ikut terkirim dan menggeser daftar anak jadi tidak terfilter.
        var params = {};
        Object.keys(query).forEach(function (param) {
            if (query[param] !== null && query[param] !== undefined) {
                params[param] = query[param];
            }
        });

        // Jangan panggil API bila parent wajibnya belum terisi.
        var required = (config.dependsOn && config.dependsOn[nextKey]) || DEPENDS_ON[nextKey] || [];
        var blocked = required.some(function (key) {
            return ! selected[key];
        });

        if (blocked) {
            $select.prop('disabled', true).html('<option value="">— Pilih —</option>');
            refreshSelect2($select);
            return;
        }

        request(nextKey, params)
            .done(function (response) {
                var options = (response && response.data) || [];
                var html = '<option value="">— Pilih —</option>';

                options.forEach(function (option) {
                    var isSelected = current && String(current) === String(option.id);
                    html += '<option value="' + option.id + '" data-code="' + option.code + '"' +
                        (isSelected ? ' selected' : '') + '>' +
                        $('<div>').text(labelOf(option)).html() + '</option>';
                });

                $select.html(html).val(current || '');

                // State ikut diperbarui supaya `onChange`/`onReady` berikutnya
                // melihat pilihan anak, bukan hanya tampilan select-nya.
                selected[nextKey] = current ? findOption($select, current) : null;

                if (config.initialValues) {
                    config.initialValues[nextKey] = null;
                }

                refreshSelect2($select);
            })
            .fail(function () {
                $select.html('<option value="">— Gagal memuat —</option>');
                refreshSelect2($select);
            });
    }

    /**
     * Reverse lookup satu kode (11 atau 15 karakter) → isi seluruh rantai.
     */
    ErkapCascade.prefill = function (config, code) {
        if (!code) {
            return $.Deferred().resolve().promise();
        }

        return $.ajax({
            url: url() + '/lookup',
            type: 'GET',
            dataType: 'json',
            data: { code: code },
        }).then(function (response) {
            var chain = config.chain || [];
            var data = (response && response.data) || {};

            chain.forEach(function (key) {
                var $select = $(config.selectors[key]);
                if (!$select.length || !data[key]) {
                    return;
                }

                $select.append(
                    $('<option>', {
                        value: data[key].id,
                        text: labelOf(data[key]),
                        'data-code': data[key].code,
                        selected: true,
                    })
                );
                refreshSelect2($select);
            });

            if (typeof config.onChange === 'function') {
                config.onChange(data, 'prefill');
            }

            return response;
        });
    };

    ErkapCascade.optionFromNode = function (node) {
        return {
            id: node.value,
            code: $(node).data('code') || '',
            name: $(node).text().trim(),
            label: $(node).text().trim(),
        };
    };

    ErkapCascade.composeCode = composeCode;
    ErkapCascade.init = function (config) {
        baseUrl = window.ERKAP_COA_OPTIONS_URL || baseUrl;
        return new ErkapCascade(config);
    };

    window.ErkapCascade = ErkapCascade;
}(window, window.jQuery));
