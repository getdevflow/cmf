(function (window, document, $) {
    'use strict';

    $(function () {
        var input = document.getElementById('site-logo');
        var preview = document.getElementById('site-logo-preview');
        var choose = document.getElementById('choose-site-logo');
        var remove = document.getElementById('remove-site-logo');

        if (!input || !preview || !choose || !remove) {
            return;
        }

        function updatePreview() {
            var value = input.value.trim();
            var url = null;

            try {
                var candidate = new URL(value, document.baseURI);
                if (value && /^https?:$/.test(candidate.protocol) && !candidate.username && !candidate.password) {
                    url = candidate.href;
                }
            } catch (error) {
                // An invalid saved URL can still be removed from Settings.
            }

            preview.hidden = !url;
            if (url) {
                preview.setAttribute('src', url);
            } else {
                preview.removeAttribute('src');
            }
            remove.style.display = value ? '' : 'none';
        }

        choose.addEventListener('click', function () {
            if (window.DevflowMediaPicker && typeof window.DevflowMediaPicker.open === 'function') {
                window.DevflowMediaPicker.open(input);
            }
        });

        remove.addEventListener('click', function () {
            $(input).val('').trigger('input').trigger('change');
        });

        $(input).on('input change', updatePreview);
        updatePreview();
    });
})(window, document, jQuery);
