/**
 *
 * Template : Metroluxe HTML TEMPLATE
 * Author : reacthemes
 * Author URI : https://reactheme.com/ 
 *
 **/

(function ($) {
    'use strict';
    // Get the form.
    var form = $('#contact-form');

    // Get the messages div.
    var formMessages = $('#form-messages');

    // Set up an event listener for the contact form.
    $(form).submit(function (e) {
        // Stop the browser from submitting the form.
        e.preventDefault();

        // Serialize the form data.
        var formData = $(form).serialize();

        // Submit the form using AJAX.
        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            dataType: 'json'
        })
            .done(function (response) {
                $(formMessages).removeClass('error');
                $(formMessages).addClass('success');

                $(formMessages).text(response.message || 'Message sent successfully.');

                // Clear the form.
                $('#name, #company, #email, #phone, #subject, #service, #message').val('');
            })
            .fail(function (data) {
                $(formMessages).removeClass('success');
                $(formMessages).addClass('error');

                if (data.responseJSON && data.responseJSON.message) {
                    $(formMessages).text(data.responseJSON.message);
                } else {
                    $(formMessages).text('Oops! An error occured and your message could not be sent.');
                }
            });
    });

})(jQuery);
