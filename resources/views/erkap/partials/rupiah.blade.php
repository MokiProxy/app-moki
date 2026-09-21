<script>
(function ($) {
    'use strict';

    window.Rupiah = window.Rupiah || {};

    window.Rupiah.parse = function (value) {
        if (typeof value !== 'string') {
            return 0;
        }
        var clean = value.replace(/[^\d,]/g, '').replace(/\./g, '').replace(',', '.');
        var number = parseFloat(clean);
        return isNaN(number) ? 0 : number;
    };

    window.Rupiah.format = function (value) {
        if (value === '' || value === null || value === undefined) {
            return '';
        }
        var number = typeof value === 'string' ? window.Rupiah.parse(value) : value;
        if (isNaN(number)) {
            return '';
        }
        var negative = number < 0;
        number = Math.abs(number);
        var parts = String(number).split('.');
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var decPart = parts.length > 1 ? ',' + parts[1] : '';
        return (negative ? '-' : '') + intPart + decPart;
    };

    function maskInput(el) {
        var digits = el.val().replace(/[^\d,]/g, '');
        if (digits === '') {
            el.val('');
            return;
        }
        var parts = digits.split(',');
        var intPart = (parts.shift() || '').replace(/^0+(?=\d)/, '');
        var formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        if (parts.length > 0) {
            formatted += ',' + parts.join('');
        }
        el.val(formatted);
    }

    $(document).on('input', '.rupiah-input', function () {
        maskInput($(this));
    });

    $(document).on('focusout', '.rupiah-input', function () {
        maskInput($(this));
    });

    $(document).on('submit', 'form', function () {
        $(this).find('.rupiah-input').each(function () {
            $(this).val(window.Rupiah.parse($(this).val()));
        });
    });

    $(function () {
        $('.rupiah-input').each(function () {
            var el = $(this);
            if (el.val()) {
                el.val(window.Rupiah.format(el.val()));
            }
        });
    });
})(jQuery);
</script>