let membershipFillWindow;

function handleAddingCottage() {
    $(window).on('unload', function () {
        if (membershipFillWindow) {
            $(membershipFillWindow).off('unload.test');
            membershipFillWindow.close();
        }
    });
}

function handle() {
    let markers = $('span.custom-icon');
    markers.tooltip();
    let cottagesWithDuty = $('a.popovered');
    cottagesWithDuty.popover({'html': true, 'trigger': 'hover', 'delay': {'show': 500}, 'container': 'body'});
    let emptyCottages = $('button.empty');
    emptyCottages.tooltip({'trigger': 'hover'});
    emptyCottages.hover(
        function () {
            $(this).text('').addClass('glyphicon glyphicon-plus');
        }, function () {
            $(this).text($(this).attr('data-index')).removeClass('glyphicon glyphicon-plus');
        });
    let modal = false;
    emptyCottages.on('click.addCottage', function () {
        // добавлю новый участок. Прокачанная версия.
        modal = makeModal('Регистрация нового участка.');
        modal.find('div.modal-content').addClass('test-transparent');
        sendAjax('get', '/add-cottage/' + $(this).attr('data-index'), appendForm);
    });

    let btn = $('button#addCottageBtn');
    btn.on('click.add', function () {
        btn.addClass('disabled').prop('disabled', true);
        modal = makeModal('Добавление нового участка');
        modal.find('div.modal-content').addClass('test-transparent');
        // отправлю запрос на форму добавления участка
        sendAjax('get', '/add-cottage', appendForm);
    });

    function appendForm(data) {
        if (data['data']) {
            let frm = $(data['data']);
            frm.find('button.popover-btn').popover({trigger: 'focus'});

            let ajaxInProcess = false;
            let formErrors = true;

            modal.find('div.modal-body').append(frm);

            // ====================================  Парсинг

            let cottageSquare = modal.find('input#addcottage-cottagesquare');
            // найду значения стоимости целевых взносов
            let targetSummContainers = modal.find('b.summ');
            cottageSquare.focus();
            cottageSquare.on('blur.checkInt', function () {
                let square = parseInt($(this).val());
               if(square > 0){
                   // пересчитаю сумму целевого платежа за год с учётом плавающей ставки
                   targetSummContainers.each(function () {
                       let fixed = toRubles($(this).attr('data-fixed'));
                       let float = toRubles($(this).attr('data-float'));
                       $(this).text(toRubles(fixed + float / 100 * square));
                   });
               }
            });

            let namePattern = /^\s*([ёа-я-]+)\s+([ёа-я]+)\s+([ёа-я]+)\s*$/i;
            let nameInputs = modal.find('input#addcottage-cottageownerpersonals, input#addcottage-cottagecontacterpersonals');
            nameInputs.on('blur.testName', function () {
                let match;
                match = $(this).val().match(namePattern);
                if (match[1]) {
                    let text = "Фамилия: " + match[1] + ", имя: " + match[2] + ", отчество: " + match[3];
                    $(this).parent().find("div.hint-block").text(text);
                }
                else {
                    $(this).parent().find("div.hint-block").html('<b class="text-success">Обязательное поле.</b> Буквы, пробелы и тире.');
                }
            });
            let phoneInputs = modal.find('input#addcottage-cottageownerphone, input#addcottage-cottagecontacterphone');
            phoneInputs.on('input.testName', function () {
                let hint = $(this).parent().find('div.hint-block');
                let link = $(this).val();
                let filtredVal = link.replace(/[^0-9]/g, '');
                if (filtredVal.length === 7) {
                    hint.html('Распознан номер +7 831 ' + filtredVal.substr(0, 3) + '-' + filtredVal.substr(3, 2) + '-' + filtredVal.substr(5, 2));
                }
                else if (filtredVal.length === 10) {
                    hint.html('Распознан номер +7 ' + filtredVal.substr(0, 3) + ' ' + filtredVal.substr(3, 3) + '-' + filtredVal.substr(6, 2) + '-' + filtredVal.substr(8, 2));
                }
                else if (filtredVal.length === 11) {
                    hint.html('Распознан номер +7 ' + filtredVal.substr(1, 3) + ' ' + filtredVal.substr(4, 3) + '-' + filtredVal.substr(7, 2) + '-' + filtredVal.substr(9, 2));
                }
                else if(link.length > 0)
                {
                    hint.html('Номер не распознан!');
                }
                else{
                    hint.html('<b class="text-info">Необязательное поле.</b> Десять цифр, без +7.');
                }
            });

            // обработаю добавление контакта
            let addContactInput = modal.find('input#addcottage-hascontacter');
            let cotacterFieldset = modal.find('fieldset#contacterInfo');
            addContactInput.on('change.switch', function () {
                if ($(this).prop('checked')) {
                    cotacterFieldset.removeClass('hidden');
                }
                else {
                    cotacterFieldset.addClass('hidden');
                }
            });
            // при вводе данных по оплаченным членским взносам- проверю наличие тарифов за все месяцы. Если тарифы не заполнены- выведу предложение заполнить их
            const membership = $('input#addcottage-membershippayfor');
            handleMembershipInput(membership);
            // Обработаю поля ввода целевых платежей
            // найду количество целевых платежей
            let powerRadios = modal.find('input.target-radio');
            let powerInputs = modal.find('input.target-input');
            const re = /^\s*\d+[,.]?\d{0,2}\s*$/;
            powerInputs.on('blur.testCost', function () {
                let par = $(this).parents('div.form-group').eq(0);
                let helpBlock = $(this).parents('div.text-input-parent').find('div.help-block');
                helpBlock.text('');
                let summ = toRubles(par.find('b.summ').text());
                let val = toRubles($(this).val());
                if (val === 0) {
                    $(this).focus();
                    makeInformer('info', 'Информация', 'Значение платежа должно быть больше нуля');
                    par.addClass('has-error').removeClass('has-success');
                }
                else if ($(this).val() === '') {
                    $(this).focus();
                    makeInformer('info', 'Информация', 'Введите сумму в рублях');
                    par.addClass('has-error').removeClass('has-success');
                }
                else if (val >= summ) {
                    $(this).focus();
                    makeInformer('info', 'Информация', 'Сумма не может быть больше полной суммы платежа');
                    par.addClass('has-error').removeClass('has-success');
                }
                else if ($(this).val().match(re)) {
                    par.removeClass('has-error').addClass('has-success');
                }
                else {
                    $(this).focus();
                    par.addClass('has-error').removeClass('has-success');
                    makeInformer('danger', 'Ошибка', 'Неверное число!');
                    helpBlock.text("Это не в рублях!");
                }
            });
            let powerRadioNames = {};
            let c = 0;
            while (powerRadios[c]) {
                let year = $(powerRadios[c]).attr('data-year');
                powerRadioNames[year] = $(powerRadios[c]).attr('name');
                c++;
            }
            let powerRadiosParents = powerRadios.parent();
            powerRadiosParents.on('click.switch', function (e) {
                // сначала проверю, что заполнена площадь участка, она понадобится для расчёта суммы платежа
                if(!cottageSquare.val()){
                    e.preventDefault();
                    e.stopPropagation();
                    makeInformer('info', 'Внимание', 'Для расчёта суммы платежа нужно ввести площадь участка');
                    cottageSquare.focus();
                }
            });
            powerRadios.on('change.switch', function () {
                let par = $(this).parents('div.form-group').eq(0);
                let myInput = par.find('input[type="text"]');
                let myInputHelp = myInput.parents('div.text-input-parent').find('div.help-block');
                let type = $(this).val();
                if (type === 'full') {
                    myInputHelp.text('');
                    let summ = par.find('b.summ');
                    // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
                    myInput.prop('disabled', false).addClass('readonly').removeClass('disabled').prop('readonly', true).val(toRubles(summ.text()));
                    par.removeClass('has-error').addClass('has-success');
                }
                else if (type === 'no-payed') {
                    myInputHelp.text('');
                    // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
                    myInput.prop('disabled', true).addClass('disabled').removeClass('readonly').prop('readonly', false).val(0);
                    par.removeClass('has-error').addClass('has-success');
                }
                else if (type === 'partial') {
                    // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
                    myInput.prop('disabled', false).removeClass('readonly disabled').prop('readonly', false).val('').focus();
                    par.removeClass('has-error has-success');
                }
            });
            frm.on('ajaxBeforeSend', function () {
                ajaxInProcess = true;
            });
            frm.on('ajaxComplete', function () {
                ajaxInProcess = false;
            });
            frm.on('afterValidate', function (event, fields, errors) {
                if (errors.length > 0) {
                    // разберусь, что не так с полями целевых взносов
                    let powers = modal.find('input.target-radio:checked');
                    if (powers.length !== powerRadioNames.length) {
                        // если не заполнен какой-либо из периодов целевых плтежей- помечу его как незаполненный
                        for (let i in powerRadioNames) {
                            // проверю, заполнен ли именно этот раздел
                            if (powerRadioNames.hasOwnProperty(i)) {
                                let filled = modal.find('input.target-radio[name="' + powerRadioNames[i] + '"]:checked');
                                if (filled.length === 0) {
                                    // отмечаю как неверно заполненный
                                    let firstUnfilled = modal.find('input.target-radio[name="' + powerRadioNames[i] + '"]').eq(0);
                                    let par = firstUnfilled.parents('div.form-group').eq(0);
                                    firstUnfilled.parents('div.col-lg-5').eq(0).find('div.help-block').text("Выберите один из вариантов");
                                    par.addClass('has-error').removeClass('has-success');
                                }
                                else {
                                    // если выбрана частичная оплата- проверю правильность заполнения текстового поля
                                    let firstFilled = modal.find('input.target-radio[name="' + powerRadioNames[i] + '"]:checked').eq(0);
                                    let par = firstFilled.parents('div.form-group').eq(0);
                                    if (firstFilled.val() === 'partial') {
                                        // если поле не отмечено как успешно заполненное- помечаю как неуспешно заполненное
                                        if (!par.hasClass('has-success')) {
                                            par.addClass('has-error');
                                        }
                                    }
                                    else {
                                        firstFilled.parents('div.col-lg-5').eq(0).find('div.help-block').text("");
                                        par.removeClass('has-error').addClass('has-success');
                                    }
                                }
                            }
                        }
                    }
                    if (errors[0].id === 'addcottage-targetfilled') {

                    }
                    else {
                        let errorInput = modal.find('#' + errors[0].id);
                        errorInput.focus();
                    }
                    formErrors = true;
                }
                else {
                    ajaxInProcess = false;
                    formErrors = false;
                }
            });
            frm.on('submit.test', function (e) {
                e.preventDefault();
                if (!ajaxInProcess && !formErrors) {
                    let i = 0;
                    let loadedForm;
                    while (frm[i]) {
                        if (frm[i].nodeName === "FORM") {
                            loadedForm = frm[i];
                            break;
                        }
                        i++;
                    }
                    const url = "/add-cottage/save/add";
                    sendAjax('post', url, answerMe, loadedForm, true);

                    function answerMe(e) {
                        enableElement(frm.find('button#addSubmit'), 'Попробовать ещё раз');
                        if (e && e.status === 1) {
                            // успешно добавлено, перезагружаю страницу
                            modal.hide();
                            location.reload();
                        }
                        else if (e && e['status'] === 0) {
                            // получаю список ошибок, вывожу его
                            let errorsList = '';
                            for (let i in e['errors']) {
                                if (e['errors'].hasOwnProperty(i))
                                    errorsList += e['errors'][i] + '\n';
                            }
                            makeInformer('danger', 'Сохранение не удалось.', errorsList);
                        }
                    }
                }
            });
        }
        else {
            makeInformer('warning', 'Ошибка.', 'Не удалось загрузить форму создания участка. Повторите попытку!');
        }
    }
}
$(function () {
    handleAddingCottage();
    handle();
});