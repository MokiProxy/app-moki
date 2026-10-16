<script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
<script>
    $(function () {
        if (!$.fn.select2) {
            return;
        }

        $('.page-content select').each(function () {
            var $select = $(this);

            if ($select.hasClass('no-select2') || $select.data('select2')) {
                return;
            }

            var options = { width: '100%' };
            var $empty = $select.find('option[value=""]').first();

            if ($empty.length) {
                options.placeholder = $empty.text().trim();
            }

            $select.select2(options);
        });
    });
</script>
