$(function () {
    handle();
});

function handle() {
    const re = /^\s*\d+[,.]?\d{0,2}\s*$/;
    const re1 = /^\s*\d+\s*$/;
    dangerReload();
    let frm = $('form');
    frm.on('submit.test', function (e) {
        // проверю, есть ли незаполненные поля. если есть- отменю действие и сфокусируюсь на первом незаполненном
        let unfilled = $('input.required').not('.ready');
        if(unfilled.length > 0){
            e.preventDefault();
            unfilled.eq(0).focus();
            makeInformer('danger', 'Рано', 'Сначала заполните все поля!');
        }
        else{
            normalReload();
        }
    });
    let inputs = $('input.required');
    inputs.eq(0).focus();
    inputs.on('input.test', function () {
        if($(this).val()){
            if($(this).hasClass('float')){
                if($(this).val().match(re)){
                    $(this).addClass('ready').removeClass('failed');
                    $(this).parent().addClass('has-success').removeClass('has-error');
                }
                else{
                    $(this).removeClass('ready').addClass('failed');
                    $(this).parent().removeClass('has-success').addClass('has-error');
                }
            }
            else if($(this).hasClass('integer')){
                if($(this).val().match(re1)){
                    $(this).addClass('ready').removeClass('failed');
                    $(this).parent().addClass('has-success').removeClass('has-error');
                }
                else{
                    $(this).removeClass('ready').addClass('failed');
                    $(this).parent().removeClass('has-success').addClass('has-error');
                }
            }
        }
    });
    inputs.on('blur.text', function (e) {
        if(!$(this).val()){
            $(this).parent().removeClass('has-success').addClass('has-error');
            makeInformer('danger', 'Необходимо заполнение', 'Это поле обязательно нужно заполнить!');
            $(this).focus();
            return false;
        }
        else{
            $(this).parent().addClass('has-success').removeClass('has-error');
        }
    });
    const copyDataBtn = $('button.copy-data');
    copyDataBtn.on('click.copy', function () {
        // заполню поля месяца ниже значениями из полей этого периода
        let par = $(this).parents('div.form-group');
        let next = par.nextAll('div.form-group').eq(0);
        if(next && par.hasClass('power-group') && next.hasClass('power-group')){
            next.find('input.power-limit').val(par.find('input.power-limit').val());
            next.find('input.power-cost').val(par.find('input.power-cost').val());
            next.find('input.power-overcost').val(par.find('input.power-overcost').val());
            next.find('input').trigger('input');
            next.find('input').trigger('blur');
        }
        else if(next && par.hasClass('membership-group') && next.hasClass('membership-group')){
            next.find('input.mem-fixed').val(par.find('input.mem-fixed').val());
            next.find('input.mem-float').val(par.find('input.mem-float').val());
            next.find('input').trigger('input');
            next.find('input').trigger('blur');
        }
    });
    // // определю, какая форма заполняется
    // let formName = form.attr('id');
    // if(formName === 'targetPaymentsForm'){
    //     const inputs = form.find('input,textarea');
    //     inputs.on('focus.lightGroup', function () {
    //         $(this).tooltip('destroy');
    //         $(this).parents('.form-group').eq(0).removeClass('has-error');
    //         // найду все поля в данной группе, отмечу их как обязательные к заполнению
    //         let siblings = $(this).parents('.leader').eq(0).find('input, textarea').not($(this));
    //         siblings.each(function () {
    //             // если поле пустое- отмечаю его как необходимое к заполнению
    //             if(!$(this).val()){
    //                 $(this).parents('.form-group').eq(0).addClass('has-error');
    //                 $(this).tooltip({'trigger' : 'manual', 'title' : 'Необходимо заполнить'});
    //                 $(this).tooltip('show');
    //             }
    //         });
    //     });
    //     inputs.on('input.test', function () {
    //         if(!$(this).hasClass('textarea')){
    //             // проверю правильность ввода
    //             if($(this).val().match(re)){
    //                 $(this).parents('div.form-group').removeClass('has-error').addClass('has-success').find('div.help-block').text('');
    //             }
    //             else{
    //                 $(this).parents('div.form-group').addClass('has-error').removeClass('has-success').find('div.help-block').text('Тут должно быть число');
    //             }
    //         }
    //         else{
    //             if($(this).val()){
    //                 $(this).parents('div.form-group').removeClass('has-error').addClass('has-success').find('div.help-block').text('');
    //             }
    //             else{
    //                 $(this).parents('div.form-group').addClass('has-error').removeClass('has-success').find('div.help-block').text('Поле нужно заполнить!');
    //             }
    //         }
    //     });
    //     inputs.on('change.send', function () {
    //         if(!$(this).hasClass('textarea')){
    //             if($(this).val().match(re)){
    //                 $(this).addClass('filled');
    //             }
    //             else{
    //                 $(this).removeClass('filled');
    //             }
    //         }
    //         else if($(this).val()){
    //                 $(this).addClass('filled');
    //             }
    //             else{
    //             $(this).removeClass('filled');
    //         }
    //             if($(this).hasClass('filled') && $(this).parents('div.leader').eq(0).find('input,textarea').not('.filled').length === 0){
    //                 // отправлю запрос на сохранение данных
    //                 let data = $(this).parents('div.leader').eq(0).find('input,textarea');
    //                 data.addClass('readonly').prop('readonly', true);
    //                 let attributes = {'Filling[targetFixedPay]': data.eq(0).val(), 'Filling[targetFloatPay]' : data.eq(1).val(), 'Filling[description]' : data.eq(2).val(), 'Filling[year]' : $(this).parents('div.leader').eq(0).attr('data-year')};
    //                 sendAjax('post', '/fill/target/year', callback, attributes);
    //                 function callback(answer) {
    //                     if(answer['status'] === 1){
    //                         makeInformer('success', 'Успешно', 'Тариф на ' + $(this).parents('div.leader').eq(0).attr('data-year') + 'год сохранён');
    //                         data.addClass('saved');
    //                         if(inputs.not('.saved').length === 0){
    //                             $(window).off('beforeunload.message');
    //                             window.close();
    //                         }
    //                     }
    //                 }
    //             }
    //     });
    //     inputs.eq(0).focus();
    // }
    // // найду все поля ввода, буду контролировать ввод
    // // обрабатываю отдельно каждое поле ввода
    // const inputs = $('form#membershipPaymentsForm input');
    // inputs.eq(0).focus();
    // inputs.on('change.fill', function () {
    //     if($(this).val().match(re)){
    //         $(this).addClass('filled').parents('div.form-group').removeClass('has-error').addClass('has-success').find('div.help-block').text('');
    //         // ищу второе поле ввода, если оба успешно заполнены- сохраняю данные о квартале
    //         let parent = $(this).parents('div.leader').eq(0);
    //         let info = parent.find('input.filled');
    //         if(info.length === 2){
    //             // Соберу данные и отправлю
    //             let attributes = {'Filling[membershipFixedPay]': info.eq(0).val(), 'Filling[membershipFloatPay]' : info.eq(1).val(), 'Filling[quarter]' : parent.attr('data-quarter')};
    //             info.prop('readonly', true);
    //             sendAjax('post', '/fill/membership/quarter', callback, attributes);
    //             function callback(e) {
    //                 if(e.status === 1){
    //                     makeInformer('success', "Успешно", "Тариф сохранён");
    //                     info.addClass('ready');
    //                     // Проверю, если все поля на странице успешно заполнены- закрываю её
    //                     if(inputs.not('.ready').length === 0){
    //                         $(window).off('beforeunload.message');
    //                         window.close();
    //                     }
    //                 }
    //                 else{
    //                     makeInformer('danger', 'Ошибка', handleErrors(e['errors']));
    //                 }
    //             }
    //         }
    //     }
    //     else{
    //         $(this).focus();
    //         $(this).removeClass('filled').parents('div.form-group').addClass('has-error').removeClass('has-success');
    //     }
    // });
}