(function ($) {
    'use strict';

    function fpfaI18n(key, fallback) {
        if (typeof fpFormsAccreditiAdmin !== 'undefined' && fpFormsAccreditiAdmin.i18n && fpFormsAccreditiAdmin.i18n[key]) {
            return fpFormsAccreditiAdmin.i18n[key];
        }
        return fallback;
    }

    function fpfaOpenPdfFrame(title, buttonText, onSelect) {
        if (typeof wp === 'undefined' || !wp.media) {
            return;
        }

        var frame = wp.media({
            title: title,
            button: { text: buttonText },
            multiple: false,
            library: { type: 'application/pdf' }
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection').first().toJSON();
            onSelect(selection);
        });

        frame.open();
    }

    $(document).on('click', '#fpfa_select_attachment', function (event) {
        event.preventDefault();

        fpfaOpenPdfFrame(
            fpfaI18n('selectRequestAttachment', 'Seleziona allegato accredito'),
            fpfaI18n('useThisFile', 'Usa questo file'),
            function (selection) {
                $('#fpfa_attachment_id').val(selection.id || '');
                $('#fpfa_attachment_label').text(selection.filename || ('ID ' + selection.id));
            }
        );
    });

    $(document).on('click', '#fpfa_select_default_attachment', function (event) {
        event.preventDefault();

        fpfaOpenPdfFrame(
            fpfaI18n('selectDefaultPdf', 'Seleziona PDF predefinito per approvazioni'),
            fpfaI18n('useAsDefault', 'Usa come predefinito'),
            function (selection) {
                var id = selection.id || '';
                $('#fpfa_default_approval_attachment_id').val(id);
                var label = selection.filename || ('ID ' + id);
                var tpl = fpfaI18n('currentFile', 'Attuale: %1$s (ID %2$d)');
                $('#fpfa_default_attachment_label').text(
                    tpl.replace(/%1\$s/g, label).replace(/%2\$d/g, String(id))
                );
            }
        );
    });

    $(document).on('click', '#fpfa_clear_default_attachment', function (event) {
        event.preventDefault();
        $('#fpfa_default_approval_attachment_id').val('0');
        $('#fpfa_default_attachment_label').text('');
    });
})(jQuery);
