/**
 * Regex Builder - User-friendly regex input with visual builder
 * 
 * Features:
 * - Builder mode with presets
 * - Manual mode for experts
 * - Live preview and testing
 * - Regex syntax validation
 */

class RegexBuilder {
    constructor(fieldId, options = {}) {
        this.fieldId = fieldId;
        this.options = $.extend({
            label: 'Regex',
            presets: 'all',
            defaultMode: 'builder',
            showPreview: true,
            sampleText: ''
        }, options);

        this.currentMode = this.options.defaultMode;
        this.currentPreset = null;
        this.generatedRegex = '';
        this.container = null;

        this.init();
    }

    init() {
        this.container = $(`#${this.fieldId}-builder-container`);
        if (this.container.length === 0) {
            console.error(`Container not found: ${this.fieldId}-builder-container`);
            return;
        }

        this.render();
        this.bindEvents();

        // Parse existing value if present
        const existingValue = $(`#${this.fieldId}`).val();
        if (existingValue) {
            this.parseExistingRegex(existingValue);
        }
    }

    render() {
        const html = `
            <div class="regex-builder-wrapper border rounded p-3 mt-2" style="background: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-bold mb-0">${this.options.label} Builder</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="${this.fieldId}_mode" id="${this.fieldId}_mode_builder" value="builder" ${this.currentMode === 'builder' ? 'checked' : ''}>
                        <label class="btn btn-outline-primary" for="${this.fieldId}_mode_builder">
                            <i class="mdi mdi-wrench me-1"></i>Builder
                        </label>
                        <input type="radio" class="btn-check" name="${this.fieldId}_mode" id="${this.fieldId}_mode_manual" value="manual" ${this.currentMode === 'manual' ? 'checked' : ''}>
                        <label class="btn btn-outline-secondary" for="${this.fieldId}_mode_manual">
                            <i class="mdi mdi-code-tags me-1"></i>Manual
                        </label>
                    </div>
                </div>

                <!-- Builder Mode -->
                <div id="${this.fieldId}_builder-panel" class="regex-builder-panel" style="display: ${this.currentMode === 'builder' ? 'block' : 'none'};">
                    ${this.renderBuilderPanel()}
                </div>

                <!-- Manual Mode -->
                <div id="${this.fieldId}_manual-panel" class="regex-manual-panel" style="display: ${this.currentMode === 'manual' ? 'block' : 'none'};">
                    ${this.renderManualPanel()}
                </div>

                <!-- Preview Section -->
                ${this.options.showPreview ? this.renderPreviewSection() : ''}

                <!-- Generated Regex Display -->
                <div class="mt-3">
                    <label class="form-label fw-bold small">Generated Regex:</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace bg-white" id="${this.fieldId}_preview" readonly value="${this.escapeHtml(this.generatedRegex)}">
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-target="${this.fieldId}_preview" title="Copy to clipboard">
                            <i class="mdi mdi-content-copy"></i>
                        </button>
                    </div>
                    <div id="${this.fieldId}_validation-msg" class="small mt-1"></div>
                </div>
            </div>
        `;

        this.container.html(html);
    }

    renderBuilderPanel() {
        const presets = this.getPresets();
        const presetOptions = presets.map(p => 
            `<option value="${p.id}" ${this.currentPreset === p.id ? 'selected' : ''}>${p.name}</option>`
        ).join('');

        return `
            <div class="row g-2">
                <div class="col-md-12">
                    <label class="form-label small">Pola Regex:</label>
                    <select class="form-select form-select-sm preset-select" id="${this.fieldId}_preset">
                        <option value="">-- Pilih Pola --</option>
                        ${presetOptions}
                        <option value="custom">Custom Pattern</option>
                    </select>
                </div>
                <div class="col-md-12 mt-2">
                    <div id="${this.fieldId}_preset-config">
                        <p class="text-muted small mb-0"><i class="mdi mdi-information-outline me-1"></i>Pilih pola di atas untuk mengkonfigurasi regex</p>
                    </div>
                </div>
            </div>
        `;
    }

    renderManualPanel() {
        return `
            <div class="row g-2">
                <div class="col-md-12">
                    <label class="form-label small">Regex Manual:</label>
                    <input type="text" class="form-control form-control-sm font-monospace manual-input" id="${this.fieldId}_manual_input" 
                           placeholder="/pattern/flags" value="${this.escapeHtml(this.generatedRegex)}">
                    <small class="text-muted">Format: /pattern/flags (contoh: /^PEMBAYARAN$/mi)</small>
                </div>
            </div>
        `;
    }

    renderPreviewSection() {
        return `
            <div class="mt-3 border-top pt-3">
                <label class="form-label fw-bold small">Test Regex:</label>
                <textarea class="form-control form-control-sm sample-text-input" id="${this.fieldId}_sample" rows="3" 
                          placeholder="Masukkan sample text untuk testing regex...">${this.escapeHtml(this.options.sampleText)}</textarea>
                <div id="${this.fieldId}_test-result" class="mt-2"></div>
            </div>
        `;
    }

    getPresets() {
        const allPresets = [
            {
                id: 'header_match',
                name: 'Header Dokumen (Primary Identifier)',
                description: 'Cocokkan judul/header dokumen untuk identifikasi jenis dokumen',
                fields: [
                    { id: 'keyword', label: 'Teks Header', type: 'text', placeholder: 'INVOICE, PEMBAYARAN, FAKTUR PAJAK' },
                    { id: 'match_type', label: 'Tipe Pencocokan', type: 'select', options: [
                        { value: 'exact', label: 'Tepat Sama (^...$)' },
                        { value: 'contains', label: 'Mengandung Teks' },
                        { value: 'starts_with', label: 'Diawali Dengan (^...)' },
                        { value: 'ends_with', label: 'Diakhiri Dengan (...$)' }
                    ]},
                    { id: 'case_insensitive', label: 'Case Insensitive (i)', type: 'checkbox', checked: true },
                    { id: 'multiline', label: 'Multiline (m) - cocok per baris', type: 'checkbox', checked: true }
                ],
                generate: (params) => {
                    const keyword = this.escapeRegex(params.keyword || 'INVOICE');
                    const matchType = params.match_type || 'exact';
                    const caseInsensitive = params.case_insensitive !== false;
                    const multiline = params.multiline !== false;
                    
                    let pattern;
                    switch (matchType) {
                        case 'exact':
                            pattern = `^${keyword}$`;
                            break;
                        case 'contains':
                            pattern = keyword;
                            break;
                        case 'starts_with':
                            pattern = `^${keyword}`;
                            break;
                        case 'ends_with':
                            pattern = `${keyword}$`;
                            break;
                        default:
                            pattern = `^${keyword}$`;
                    }
                    
                    let flags = '';
                    if (caseInsensitive) flags += 'i';
                    if (multiline) flags += 'm';
                    
                    return new RegExp(pattern, flags);
                }
            },
            {
                id: 'keyword_value',
                name: 'Kata Kunci + Nilai',
                description: 'Mencari nilai setelah kata kunci tertentu',
                fields: [
                    { id: 'keyword', label: 'Kata Kunci', type: 'text', placeholder: 'Tgl, No, Keterangan' },
                    { id: 'separator', label: 'Pemisah', type: 'select', options: [
                        { value: ':', label: 'Titik Dua (:)' },
                        { value: '=', label: 'Sama Dengan (=)' },
                        { value: ' ', label: 'Spasi' },
                        { value: '\\s', label: 'Whitespace' },
                        { value: '', label: 'Tidak Ada' }
                    ]},
                    { id: 'capture', label: 'Ambil Nilai Setelah Pemisah', type: 'checkbox', checked: true }
                ],
                generate: (params) => {
                    const keyword = this.escapeRegex(params.keyword || 'KataKunci');
                    const separator = params.separator || ':';
                    const capture = params.capture !== false;
                    
                    if (capture) {
                        return new RegExp(`${keyword}\\s*${separator}\\s*(.+)`, 'i');
                    } else {
                        return new RegExp(`${keyword}`, 'i');
                    }
                }
            },
            {
                id: 'number',
                name: 'Nomor Dokumen',
                description: 'Mencari nomor dokumen dengan format umum',
                fields: [
                    { id: 'prefix', label: 'Prefix', type: 'text', placeholder: 'No, Nomor, DOC' },
                    { id: 'separator', label: 'Pemisah', type: 'select', options: [
                        { value: ':\\s*', label: 'Titik Dua + Spasi' },
                        { value: ':', label: 'Titik Dua' },
                        { value: '\\s+', label: 'Spasi' },
                        { value: '', label: 'Tidak Ada' }
                    ]},
                    { id: 'format', label: 'Format Nomor', type: 'select', options: [
                        { value: '[A-Z0-9\\-]+', label: 'Alphanumeric + Strip' },
                        { value: '\\d+', label: 'Angka Saja' },
                        { value: '[A-Z0-9]+', label: 'Alphanumeric' },
                        { value: '.+', label: 'Semua Karakter' }
                    ]}
                ],
                generate: (params) => {
                    const prefix = this.escapeRegex(params.prefix || 'No');
                    const separator = params.separator || ':\\s*';
                    const format = params.format || '[A-Z0-9\\-]+';
                    
                    return new RegExp(`(?:No|Nomor|${prefix})\\s*${separator}${format}`, 'i');
                }
            },
            {
                id: 'date',
                name: 'Tanggal',
                description: 'Mencari format tanggal umum',
                fields: [
                    { id: 'format', label: 'Format Tanggal', type: 'select', options: [
                        { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
                        { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
                        { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD' },
                        { value: 'DD-MM-YYYY', label: 'DD-MM-YYYY' },
                        { value: 'any', label: 'Format Umum' }
                    ]}
                ],
                generate: (params) => {
                    const format = params.format || 'DD/MM/YYYY';
                    let pattern;
                    
                    switch (format) {
                        case 'DD/MM/YYYY':
                            pattern = '(\\d{1,2}/\\d{1,2}/\\d{4})';
                            break;
                        case 'MM/DD/YYYY':
                            pattern = '(\\d{1,2}/\\d{1,2}/\\d{4})';
                            break;
                        case 'YYYY-MM-DD':
                            pattern = '(\\d{4}-\\d{1,2}-\\d{1,2})';
                            break;
                        case 'DD-MM-YYYY':
                            pattern = '(\\d{1,2}-\\d{1,2}-\\d{4})';
                            break;
                        case 'any':
                            pattern = '(\\d{1,2}[/\\-.]\\d{1,2}[/\\-.]\\d{2,4})';
                            break;
                        default:
                            pattern = '(\\d{1,2}/\\d{1,2}/\\d{4})';
                    }
                    
                    return new RegExp(pattern, 'i');
                }
            },
            {
                id: 'multiline',
                name: 'Multi-baris (Antara Kata Kunci)',
                description: 'Mencari teks antara dua kata kunci',
                fields: [
                    { id: 'start', label: 'Kata Kunci Awal', type: 'text', placeholder: 'URAIAN' },
                    { id: 'end', label: 'Kata Kunci Akhir', type: 'text', placeholder: 'TOTAL' },
                    { id: 'greedy', label: 'Non-greedy (Minimal)', type: 'checkbox', checked: true }
                ],
                generate: (params) => {
                    const start = this.escapeRegex(params.start || 'START');
                    const end = this.escapeRegex(params.end || 'END');
                    const greedy = params.greedy !== false;
                    const quantifier = greedy ? '+?' : '+';
                    
                    return new RegExp(`${start}\\s*\\n(.${quantifier})\\n\\s*${end}`, 'si');
                }
            },
            {
                id: 'currency',
                name: 'Currency/Amount',
                description: 'Mencari nilai mata uang',
                fields: [
                    { id: 'symbol', label: 'Simbol Mata Uang', type: 'select', options: [
                        { value: 'Rp', label: 'Rupiah (Rp)' },
                        { value: '\\$', label: 'Dollar ($)' },
                        { value: '€', label: 'Euro (€)' },
                        { value: '', label: 'Tanpa Simbol' }
                    ]},
                    { id: 'format', label: 'Format Angka', type: 'select', options: [
                        { value: '[\\d.,]+', label: 'Angka + Titik/Koma' },
                        { value: '[\\d,]+', label: 'Angka + Koma' },
                        { value: '[\\d.]+', label: 'Angka + Titik' },
                        { value: '\\d+', label: 'Angka Saja' }
                    ]}
                ],
                generate: (params) => {
                    const symbol = params.symbol || 'Rp';
                    const format = params.format || '[\\d.,]+';
                    
                    if (symbol) {
                        return new RegExp(`${symbol}\\s*(${format})`, 'i');
                    } else {
                        return new RegExp(`(${format})`, 'i');
                    }
                }
            },
            {
                id: 'text_between',
                name: 'Teks antara Dua Karakter',
                description: 'Mencari teks antara dua delimiter',
                fields: [
                    { id: 'start_char', label: 'Delimiter Awal', type: 'text', placeholder: '(', width: '60px' },
                    { id: 'end_char', label: 'Delimiter Akhir', type: 'text', placeholder: ')', width: '60px' },
                    { id: 'multiline', label: 'Multi-baris', type: 'checkbox', checked: false }
                ],
                generate: (params) => {
                    const start = this.escapeRegex(params.start_char || '(');
                    const end = this.escapeRegex(params.end_char || ')');
                    const multiline = params.multiline === true;
                    const flags = multiline ? 'si' : 'i';
                    
                    return new RegExp(`${start}(.+?)${end}`, flags);
                }
            }
        ];

        if (this.options.presets === 'all') {
            return allPresets;
        }

        return allPresets.filter(p => this.options.presets.includes(p.id));
    }

    renderPresetConfig(presetId) {
        const presets = this.getPresets();
        const preset = presets.find(p => p.id === presetId);
        
        if (!preset) {
            return `<p class="text-muted small mb-0"><i class="mdi mdi-information-outline me-1"></i>Pilih pola di atas untuk mengkonfigurasi regex</p>`;
        }

        let html = `
            <div class="card card-sm">
                <div class="card-body p-2">
                    <p class="small text-muted mb-2">${preset.description}</p>
                    <div class="row g-2">
        `;

        preset.fields.forEach(field => {
            const fieldId = `${this.fieldId}_preset_${field.id}`;
            
            if (field.type === 'text') {
                html += `
                    <div class="col-md-6">
                        <label class="form-label small">${field.label}</label>
                        <input type="text" class="form-control form-control-sm preset-field" id="${fieldId}" 
                               data-field="${field.id}" placeholder="${field.placeholder || ''}" style="width: ${field.width || '100%'}">
                    </div>
                `;
            } else if (field.type === 'select') {
                const options = field.options.map(o => 
                    `<option value="${o.value}">${o.label}</option>`
                ).join('');
                html += `
                    <div class="col-md-6">
                        <label class="form-label small">${field.label}</label>
                        <select class="form-select form-select-sm preset-field" id="${fieldId}" data-field="${field.id}">
                            ${options}
                        </select>
                    </div>
                `;
            } else if (field.type === 'checkbox') {
                html += `
                    <div class="col-md-6">
                        <div class="form-check form-check-sm mt-4">
                            <input type="checkbox" class="form-check-input preset-field" id="${fieldId}" 
                                   data-field="${field.id}" ${field.checked ? 'checked' : ''}>
                            <label class="form-check-label small" for="${fieldId}">${field.label}</label>
                        </div>
                    </div>
                `;
            }
        });

        html += `
                    </div>
                    <button type="button" class="btn btn-sm btn-primary mt-2 apply-preset-btn">
                        <i class="mdi mdi-check me-1"></i>Terapkan
                    </button>
                </div>
            </div>
        `;

        return html;
    }

    bindEvents() {
        const self = this;

        // Mode toggle
        this.container.on('change', `input[name="${this.fieldId}_mode"]`, function() {
            self.currentMode = $(this).val();
            self.toggleMode(self.currentMode);
        });

        // Preset selection
        this.container.on('change', `#${this.fieldId}_preset`, function() {
            const presetId = $(this).val();
            self.currentPreset = presetId;
            self.renderPresetConfigById(presetId);
        });

        // Apply preset
        this.container.on('click', '.apply-preset-btn', function() {
            self.applyPreset();
        });

        // Preset field changes - auto apply
        this.container.on('change', '.preset-field', function() {
            if (self.currentPreset) {
                self.applyPreset();
            }
        });

        // Manual input changes
        this.container.on('input', `#${this.fieldId}_manual_input`, function() {
            const value = $(this).val();
            self.generatedRegex = value;
            self.updatePreview();
            self.updateHiddenField();
        });

        // Sample text changes - test regex
        this.container.on('input', `#${this.fieldId}_sample`, function() {
            self.testRegex();
        });

        // Copy button
        this.container.on('click', '.copy-btn', function() {
            const targetId = $(this).data('target');
            const text = $(`#${targetId}`).val();
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = $(this).html();
                $(this).html('<i class="mdi mdi-check"></i>').addClass('btn-success');
                setTimeout(() => {
                    $(this).html(originalHtml).removeClass('btn-success');
                }, 1500);
            });
        });
    }

    toggleMode(mode) {
        if (mode === 'builder') {
            this.container.find(`#${this.fieldId}_builder-panel`).show();
            this.container.find(`#${this.fieldId}_manual-panel`).hide();
        } else {
            this.container.find(`#${this.fieldId}_builder-panel`).hide();
            this.container.find(`#${this.fieldId}_manual-panel`).show();
            this.container.find(`#${this.fieldId}_manual_input`).val(this.generatedRegex);
        }
    }

    renderPresetConfigById(presetId) {
        const configHtml = this.renderPresetConfig(presetId);
        this.container.find(`#${this.fieldId}_preset-config`).html(configHtml);
    }

    applyPreset() {
        const presets = this.getPresets();
        const preset = presets.find(p => p.id === this.currentPreset);
        
        if (!preset) return;

        const params = {};
        this.container.find('.preset-field').each(function() {
            const field = $(this).data('field');
            const type = $(this).attr('type');
            
            if (type === 'checkbox') {
                params[field] = $(this).is(':checked');
            } else {
                params[field] = $(this).val();
            }
        });

        try {
            const regex = preset.generate(params);
            this.generatedRegex = regex.toString();
            this.updatePreview();
            this.updateHiddenField();
            this.testRegex();
        } catch (e) {
            console.error('Error generating regex:', e);
        }
    }

    parseExistingRegex(regexString) {
        this.generatedRegex = regexString;
        
        // Try to detect if it matches any preset
        // This is a simple heuristic - could be enhanced
        this.updatePreview();
    }

    updatePreview() {
        this.container.find(`#${this.fieldId}_preview`).val(this.generatedRegex);
        this.validateRegex(this.generatedRegex);
    }

    updateHiddenField() {
        $(`#${this.fieldId}`).val(this.generatedRegex);
    }

    validateRegex(pattern) {
        const msgEl = this.container.find(`#${this.fieldId}_validation-msg`);
        
        if (!pattern) {
            msgEl.html('<span class="text-muted"><i class="mdi mdi-information-outline me-1"></i>Belum ada regex</span>');
            return false;
        }

        try {
            // Check format /pattern/flags
            const match = pattern.match(/^\/(.+)\/([gimsuy]*)$/);
            if (!match) {
                msgEl.html('<span class="text-warning"><i class="mdi mdi-alert-outline me-1"></i>Format harus: /pattern/flags</span>');
                return false;
            }

            const regexPattern = match[1];
            const flags = match[2];
            
            // Try to create regex
            new RegExp(regexPattern, flags);
            
            msgEl.html('<span class="text-success"><i class="mdi mdi-check-circle me-1"></i>Regex valid</span>');
            return true;
        } catch (e) {
            msgEl.html(`<span class="text-danger"><i class="mdi mdi-alert-circle me-1"></i>Regex tidak valid: ${e.message}</span>`);
            return false;
        }
    }

    testRegex() {
        const sampleText = this.container.find(`#${this.fieldId}_sample`).val();
        const resultEl = this.container.find(`#${this.fieldId}_test-result`);
        
        if (!this.generatedRegex || !sampleText) {
            resultEl.html('');
            return;
        }

        try {
            const match = this.generatedRegex.match(/^\/(.+)\/([gimsuy]*)$/);
            if (!match) return;

            const regex = new RegExp(match[1], match[2]);
            const matches = sampleText.match(regex);

            if (matches) {
                let html = '<div class="alert alert-success py-2 px-3 mb-0 small">';
                html += '<i class="mdi mdi-check-circle me-1"></i><strong>Match!</strong> ';
                
                if (matches.length > 1) {
                    html += `Group 1: "${this.escapeHtml(matches[1])}"`;
                    if (matches.length > 2) {
                        html += ` | Group 2: "${this.escapeHtml(matches[2])}"`;
                    }
                } else {
                    html += `Matched: "${this.escapeHtml(matches[0])}"`;
                }
                
                html += '</div>';
                resultEl.html(html);
            } else {
                resultEl.html('<div class="alert alert-warning py-2 px-3 mb-0 small"><i class="mdi mdi-alert-outline me-1"></i>Tidak ada match</div>');
            }
        } catch (e) {
            resultEl.html(`<div class="alert alert-danger py-2 px-3 mb-0 small"><i class="mdi mdi-alert-circle me-1"></i>Error: ${e.message}</div>`);
        }
    }

    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public methods
    getValue() {
        return this.generatedRegex;
    }

    setValue(value) {
        this.generatedRegex = value;
        this.updatePreview();
        this.updateHiddenField();
        
        if (this.currentMode === 'manual') {
            this.container.find(`#${this.fieldId}_manual_input`).val(value);
        }
    }

    validate() {
        return this.validateRegex(this.generatedRegex);
    }
}

// Initialize all regex builders on page load
$(document).ready(function() {
    window.regexBuilders = {};
    
    $('[data-regex-builder]').each(function() {
        const fieldId = $(this).data('regex-builder');
        const options = $(this).data('regex-builder-options') || {};
        window.regexBuilders[fieldId] = new RegexBuilder(fieldId, options);
    });
});
