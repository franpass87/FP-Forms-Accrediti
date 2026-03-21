(function ($) {
    'use strict';

    $(document).on('click', '#fpfa_select_attachment', function (event) {
        event.preventDefault();

        if (typeof wp === 'undefined' || !wp.media) {
            return;
        }

        var frame = wp.media({
            title: 'Seleziona allegato accredito',
            button: { text: 'Usa questo file' },
            multiple: false
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection').first().toJSON();
            $('#fpfa_attachment_id').val(selection.id || '');
            $('#fpfa_attachment_label').text(selection.filename || ('ID ' + selection.id));
        });

        frame.open();
    });
})(jQuery);
