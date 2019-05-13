$(function () {
    handle();
});

function handle() {
    // найду все поля ввода, буду контролировать ввод
    // обрабатываю отдельно каждое поле ввода
    const inputs = $('form#membershipPaymentsForm input');
    const re = /^\d+[,.]?\d{0,2}$/;
    inputs.on('change.fill', function () {
        if($(this).val().match(re)){
            $(this).addClass('filled').parents('div.form-group').removeClass('has-error').addClass('has-success').find('div.help-block').text('');
            // ищу второе поле ввода, если оба успешно заполнены- сохраняю данные о квартале
            let parent = $(this).parents('div.leader').eq(0);
            let info = parent.find('input.filled');
            if(info.length === 2){
                // Соберу данные и отправлю
                let attributes = {'Filling[membershipFixedPay]': info.eq(0).val(), 'Filling[membershipFloatPay]' : info.eq(1).val(), 'Filling[quarter]' : parent.attr('data-quarter')};
                info.prop('readonly', true);
                sendAjax('post', '/fill/membership/quarter', callback, attributes);
                function callback(e) {
                    if(e.status === 1){
                        makeInformer('success', "Успешно", "Тариф сохранён");
                        info.addClass('ready');
                        // Проверю, если все поля на странице успешно заполнены- закрываю её
                        if(inputs.not('.ready').length === 0){
                            window.close();
                        }
                    }
                    else{
                        makeInformer('danger', 'Ошибка', handleErrors(e.errors));
                    }
                }
            }
        }
        else{
            $(this).focus();
            $(this).removeClass('filled').parents('div.form-group').addClass('has-error').removeClass('has-success');
        }
    });
}