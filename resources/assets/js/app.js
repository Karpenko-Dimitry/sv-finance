import './telegram-web-app';

$(document).ready(() => {
    let $form = $('form');
    let formDataAttr = $form.data('form');
    let tg = window.Telegram.WebApp;
    let formMethod = $form.attr('method');
    let formUrl = $form.attr('action');

    let user = tg.initDataUnsafe.user;
    let receiver = tg.initDataUnsafe;

    console.log('initDataUnsafe', receiver);

    tg.MainButton.show();
    tg.MainButton.text = formDataAttr.submit_name || 'Продолжить';

    tg.onEvent('mainButtonClicked', () => {

        let formData = $form.serialize();
        let formDataObj = Object.fromEntries(new URLSearchParams(formData).entries());
        formDataObj.user = user;

        $.ajax({url: formUrl, method: formMethod, data: $.param(formDataObj)}).done(function (res) {
            tg.close()
        }).fail(function (data) {
            let $inputs = $form.find('input');
            let $textarea = $form.find('textarea');
            $inputs.removeClass('is-invalid');
            $inputs.parent().find('.invalid-feedback').text('');
            $textarea.removeClass('is-invalid');
            $textarea.parent().find('.invalid-feedback').text('');

            if (data.status === 422) {
                let errors = $.parseJSON(data.responseText);
                let scrollPositions = [];
                errors = errors.errors === 'undefined' ? errors : errors.errors;
                $.each(errors, function (key, value) {
                    let $input = $form.find('[name="' + key + '"]');
                    let $label = $input.siblings('label');
                    let $errorBlock = $input.parent().find('.invalid-feedback');
                    scrollPositions.push($label.offset().top);

                    $errorBlock.text(value.toString());
                    $input.addClass('is-invalid');
                });

                $(document).scrollTop(Math.min.apply(null, scrollPositions));
            } else {
                console.error(data.errors);
            }
        });
    });

});


