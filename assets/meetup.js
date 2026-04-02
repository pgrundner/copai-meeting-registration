/**
 * @author  Peter Grundner <peter.grundner@murbit.at>
 * @date    September 2025
 * @license MIT License – https://opensource.org/licenses/MIT
 */
(function ($) {
    'use strict';

    $(document).on('submit', '.cmr-form', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var meetupId = $form.data('meetup');
        var $msg     = $('#cmr-msg-' + meetupId);
        var $btn     = $form.find('.cmr-btn');

        $msg.hide().removeClass('cmr-message--success cmr-message--error');
        $btn.prop('disabled', true).text('Wird gesendet…');

        $.ajax({
            url:    CMR.ajax_url,
            method: 'POST',
            data: {
                action:    'cmr_register',
                nonce:     CMR.nonce,
                meetup_id: meetupId,
                name:      $form.find('[name="cmr_name"]').val(),
                company:   $form.find('[name="cmr_company"]').val(),
                zip:       $form.find('[name="cmr_zip"]').val(),
                email:     $form.find('[name="cmr_email"]').val(),
            },
        })
        .done(function (res) {
            if (res.success) {
                $msg.addClass('cmr-message--success').text(res.data.message).show();
                $form.find('input').val('');
                $btn.hide();
            } else {
                $msg.addClass('cmr-message--error').text(res.data.message).show();
                $btn.prop('disabled', false).text('Verbindlich anmelden');
            }
        })
        .fail(function () {
            $msg.addClass('cmr-message--error').text('Verbindungsfehler. Bitte versuche es erneut.').show();
            $btn.prop('disabled', false).text('Verbindlich anmelden');
        });
    });

}(jQuery));
