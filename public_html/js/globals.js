/*exported handleAjaxActivators */
let navbar;

function individualIntegrityCheckResults(result) {
    if (result['hasErrors'] === 1) {
        // перенаправлю на страницу заполнения индивидуальных тарифов
        makeInformer('info', 'Не заполнены тарифы', 'Необходимо заполнить индивидуальные тарифы <br/> <a href="/individual/fill" target="_blank" class="btn btn-info">Заполнить</a>');
        //location.replace('/individual/fill');
    }
}

function integrityChecks() {
    // проверю целостность системы индивидуальных тарифов
    sendSilentAjax('get', '/check/individual', individualIntegrityCheckResults);
}

$(function () {
    navbar = $('ul#w1');
    checkUnsendedMessages();
    integrityChecks();

    // активирую переход к участку по ссылке
    $('#goToCottageActivator').on('click.go', function () {
        let cottageValue = $('#goToCottageInput').val();
        if (cottageValue) {
            location.replace('/show-cottage/' + cottageValue);
        }
    });
    $('#goToCottageInput').on('keypress.go', function (e) {
        if (e.charCode === 13) {
            let cottageValue = $('#goToCottageInput').val();
            if (cottageValue) {
                location.replace('/show-cottage/' + cottageValue);
            }
        }
    });
});

function serialize(obj) {
    const str = [];
    for (let p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

function sendAjax(method, url, callback, attributes, isForm) {
    showWaiter();
    ajaxDangerReload();
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            deleteWaiter();
            ajaxNormalReload();
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            ajaxNormalReload();
            deleteWaiter();
            checkMessages();
            if (e.responseJSON) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON['message']);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
                console.log(e);
            }
            //callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            deleteWaiter();
            normalReload();
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            deleteWaiter();
            normalReload();
            checkMessages();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON.message);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            //callback(false)
        });
    }
}

function sendSilentAjax(method, url, callback, attributes, isForm) {
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            checkMessages();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON['message']);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            checkMessages();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON.message);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            callback(false)
        });
    }
}

// ========================================================== ИНФОРМЕР
// СОЗДАЮ ИНФОРМЕР
function makeInformer(type, header, body) {
    if (!body)
        body = '';
    const container = $('div#alertsContentDiv');
    const informer = $('<div class="alert-wrapper"><div class="alert alert-' + type + ' alert-dismissable my-alert"><div class="panel panel-' + type + '"><div class="panel-heading">' + header + '<button type="button" class="close">&times;</button></div><div class="panel-body">' + body + '</div></div></div></div>');
    informer.find('button.close').on('click.hide', function (e) {
        e.preventDefault();
        closeAlert(informer)
    });
    container.append(informer);
    showAlert(informer)
}

// ПОКАЗЫВАЮ ИНФОРМЕР
function showAlert(alertDiv) {
    // считаю расстояние от верха страницы до места, где располагается информер
    const topShift = alertDiv[0].offsetTop;
    const elemHeight = alertDiv[0].offsetHeight;
    let shift = topShift + elemHeight;
    alertDiv.css({'top': -shift + 'px', 'opacity': '0.1'});
    // анимирую появление информера
    alertDiv.animate({
        top: 0,
        opacity: 1
    }, 500, function () {
        // запускаю таймер самоуничтожения через 5 секунд
        setTimeout(function () {
            closeAlert(alertDiv)
        }, 5000);
    });

}

// СКРЫВАЮ ИНФОРМЕР
function closeAlert(alertDiv) {
    const elemWidth = alertDiv[0].offsetWidth;
    alertDiv.animate({
        left: elemWidth
    }, 500, function () {
        alertDiv.animate({
            height: 0,
            opacity: 0
        }, 300, function () {
            alertDiv.remove();
        });
    });
}

function checkUnsendedMessages() {
    checkMessages();
    setInterval(function () {
        checkMessages()
    }, 600000);
}

function checkMessages() {
    sendSilentAjax('get', '/notifications/check-unsended', handleUnsended);
}

function handleUnsended(e) {
    // удалю все значки, потом покажу заново, если надо
    let unsendedBtn = navbar.find('#unsendedMessagesButton');
    unsendedBtn.remove();
    let unsendedErrorsBtn = navbar.find('#unsendedErrorsButton');
    unsendedErrorsBtn.remove();
    if (e['status'] === 1) {
        makeInformer('info', 'Сообщения', 'Найдены неотправленные сообщения. Их нужно будет отправить вручную, когда появится подключение к интернету');
        // noinspection JSValidateTypes
        navbar.prepend('<li id="unsendedMessagesButton"><a href="#" id="sendMessagesButton" type="button" class="btn btn-default btn-lg" data-toggle="tooltip" data-placement="bottom" title="Найдены неотправленные сообщения. Нажмите, чтобы попытаться отправить их заново."><span class="glyphicon glyphicon-bullhorn text-danger"></span></a></li>');
        $('a#sendMessagesButton').tooltip().on('click.send', function (e) {
            e.preventDefault();
            sendAjax('post', '/notify/resend', callback);
        });

        function callback(data) {
            if (data === '0') {
                makeInformer('success', 'Успешно', 'Все сообщения успешно отправлены адресатам');
            } else {
                makeInformer('warning', 'Неудача', 'Не удалось отправить сообщения. Возможно, вы используете нестабильное подключение к интернету. Попробуйте ещё раз позднее.');
            }
            checkMessages();
        }
    }
    if (e['errorsStatus'] && e['errorsStatus'] === 1) {
        // найдены сообщения об ошибках, которые нужно отправить мне на почту
        // noinspection JSValidateTypes
        navbar.prepend('<li id="unsendedErrorsButton"><a href="#" id="sendErrorsButton" type="button" class="btn btn-warning btn-lg" data-toggle="tooltip" data-placement="bottom" title="Найдены ошибки. Нажмите, чтобы отправить их мне."><span class="glyphicon glyphicon-bell text-default"></span></a></li>');
        $('a#sendErrorsButton').tooltip().on('click.send', function (e) {
            e.preventDefault();
            sendAjax('post', '/errors/send', callbackE);
        });

        function callbackE(data) {
            if (data === '0') {
                $('li#unsendedErrorsButton').remove();
                makeInformer('success', 'Успешно', 'Ошибки успешно отправлены');
            } else {
                makeInformer('warning', 'Неудача', 'Не удалось отправить сообщение. Возможно, вы используете нестабильное подключение к интернету. Попробуйте ещё раз позднее.');
            }
            checkMessages();
        }
    }
}


// Функция вызова пустого модального окна
function makeModal(header, text, delayed) {
    if (delayed) {
        // открытие модали поверх другой модали
        let modal = $("#myModal");
        if (modal.length == 1) {
            modal.modal('hide');
            let newModal = $('<div id="myModal" class="modal fade mode-choose"><div class="modal-dialog  modal-lg"><div class="modal-content"><div class="modal-header">' + header + '</div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-danger"  data-dismiss="modal" type="button" id="cancelActionButton">Отмена</button></div></div></div>');
            modal.on('hidden.bs.modal', function () {
                modal.remove();
                if (!text)
                    text = '';
                $('body').append(newModal);
                dangerReload();
                newModal.modal({
                    keyboard: true,
                    show: true
                });
                newModal.on('hidden.bs.modal', function () {
                    normalReload();
                    newModal.remove();
                    $('div.wrap div.container, div.wrap nav').removeClass('blured');
                });
                $('div.wrap div.container, div.wrap nav').addClass('blured');
            });
            return newModal;
        }
    }
    if (!text)
        text = '';
    let modal = $('<div id="myModal" class="modal fade mode-choose"><div class="modal-dialog  modal-lg"><div class="modal-content"><div class="modal-header">' + header + '</div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-danger"  data-dismiss="modal" type="button" id="cancelActionButton">Отмена</button></div></div></div>');
    $('body').append(modal);
    dangerReload();
    modal.modal({
        keyboard: true,
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        normalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');
    return modal;
}

function makeInformerModal(header, text, acceptAction, declineAction) {
    if (!text)
        text = '';
    let modal = $('<div class="modal fade mode-choose"><div class="modal-dialog text-center"><div class="modal-content"><div class="modal-header"><h3>' + header + '</h3></div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-success" type="button" id="acceptActionBtn">Ок</button></div></div></div>');
    $('body').append(modal);
    let acceptButton = modal.find('button#acceptActionBtn');
    if (declineAction) {
        let declineBtn = $('<button class="btn btn-warning" role="button">Отмена</button>');
        declineBtn.insertAfter(acceptButton);
        declineBtn.on('click.custom', function () {
            normalReload();
            modal.modal('hide');
            declineAction();
        });
    }
    dangerReload();
    modal.modal({
        keyboard: false,
        backdrop: 'static',
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        normalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    modal.on('shown.bs.modal', function () {
        acceptButton.focus();
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');

    acceptButton.on('click', function () {
        normalReload();
        modal.modal('hide');
        if (acceptAction) {
            acceptAction();
        } else {
            location.reload();
        }
    });

    return modal;
}

function loadForm(url, modal, postUrl) {
    sendAjax('get', url, appendForm);

    function appendForm(form) {
        let ready = false;
        const frm = $(form.data);
        frm.find('button.popover-btn').popover({trigger: 'focus'});
        modal.find('div.modal-body').append(frm);
        frm.on('afterValidate', function (event, fields, errors) {
            ready = !errors.length;
        });
        frm.on('submit.test', function (e) {
            e.preventDefault();
            if (ready) {
                // отправлю форму
                // заблокирую кнопку отправки, чтобы невозможно было отправить несколько раз
                frm.find('button#addSubmit').addClass('disabled').prop('disabled', true);
                let i = 0;
                let loadedForm;
                while (frm[i]) {
                    if (frm[i].nodeName === "FORM") {
                        loadedForm = frm[i];
                        break;
                    }
                    i++;
                }
                sendAjax('post', postUrl, answerMe, loadedForm, true);

                function answerMe(e) {
                    normalReload();
                    if (e && e.status === 1) {
                        // успешно добавлено, перезагружаю страницу
                        location.reload();
                    } else if (e && e.status === 0) {
                        // получаю список ошибок, вывожу его
                        let errorsList = '';
                        for (let i in e['errors']) {
                            if (e['errors'].hasOwnProperty(i))
                                errorsList += e['errors'][i] + '\n';
                        }
                        makeInformer('danger', 'Сохранение не удалось.', errorsList);
                        modal.hide();
                    }
                }
            }
        });
    }
}

function handleErrors(errors) {
    let content = '';
    for (let i in errors) {
        if (errors.hasOwnProperty(i))
            content += errors[i][0]
    }
    return content;
}

function makeNewWindow(url, link, closeCallback) {
    if (link)
        link.close();
    link = window.open(url, '_blank');
    link.focus();
    $(link).on('load', function () {
        $(link).on('unload.call', function () {
            if (closeCallback)
                closeCallback();
        })
    });
    return link;
}

function disableElement(elem, newText) {
    elem.addClass('disabled').prop('disabled', true);
    if (newText) {
        elem.attr('data-realname', elem.text()).text(newText);
    }
}

function enableElement(elem, newText) {
    elem.removeClass('disabled').prop('disabled', false);
    if (newText)
        elem.text(newText);
    else if (elem.attr('data-realname'))
        elem.text(elem.attr('data-realname'));
}

function toRubles(summ) {
    if (typeof (summ) === 'string')
        summ = summ.replace(',', '.');
    summ = parseFloat(summ);
    return parseFloat(summ.toFixed(2));
}

function ajaxDangerReload() {
    $(window).on('beforeunload.ajax', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function ajaxNormalReload() {
    $(window).off('beforeunload.ajax');
}

function dangerReload() {
    $(window).on('beforeunload.message', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function normalReload() {
    $(window).off('beforeunload');
}

function showWaiter() {
    let shader = $('<div class="shader"></div>');
    $('body').append(shader).css({'overflow': 'hidden'});

    $('div.wrap, div.flyingSumm, div.modal').addClass('blured');
    shader.showLoading();
}

function deleteWaiter() {
    $('div.wrap, div.flyingSumm, div.modal').removeClass('blured');
    $('body').css({'overflow': ''});
    let shader = $('div.shader');
    if (shader.length > 0)
        shader.hideLoading().remove();
}

function handleCashInput(input) {
    const re = /^\d+[,.]?\d{0,2}$/;
    input.on('input.int', function () {
        if ($(this).val().match(re)) {
            $(this).addClass('ready').removeClass('failed');
            $(this).parent().addClass('has-success').removeClass('has-error');
        } else {
            $(this).removeClass('ready').addClass('failed');
            $(this).parent().removeClass('has-success').addClass('has-error');
        }
    });
    input.on('blur.int', function () {
        if ($(this).val().match(re)) {
            makeInputRight($(this));
        } else {
            makeInputWrong($(this));
        }
    });
}

function isSumm(summ) {
    const re = /^\d+[,.]?\d{0,2}$/;
    if (summ.match(re)) {
        return parseFloat(summ.replace(',', '.'));
    }
    return false;
}

function makeInputWrong(input) {
    input.removeClass('ready').addClass('failed');
    input.parent().removeClass('has-success').addClass('has-error');

}

function makeInputRight(input) {
    input.addClass('ready').removeClass('failed');
    input.parent().addClass('has-success').removeClass('has-error');
}

function getLastDayOfMonth(y, m) {
    if (m === 1) {
        return y % 4 || (!(y % 100) && y % 400) ? 28 : 29;
    }
    return m === 3 || m === 5 || m === 8 || m === 10 ? 30 : 31;
}

function handlePowerInputs(modal, squareInput) {
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
        } else if ($(this).val() === '') {
            $(this).focus();
            makeInformer('info', 'Информация', 'Введите сумму в рублях');
            par.addClass('has-error').removeClass('has-success');
        } else if (val >= summ) {
            $(this).focus();
            makeInformer('info', 'Информация', 'Сумма не может быть больше полной суммы платежа');
            par.addClass('has-error').removeClass('has-success');
        } else if ($(this).val().match(re)) {
            par.removeClass('has-error').addClass('has-success');
        } else {
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
        let float = toRubles($(this).attr('data-float'));
        if (float > 0) {
            if (!squareInput.val()) {
                e.preventDefault();
                e.stopPropagation();
                makeInformer('info', 'Внимание', 'Для расчёта суммы платежа нужно ввести площадь участка');
                squareInput.focus();
            }
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
        } else if (type === 'no-payed') {
            myInputHelp.text('');
            // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
            myInput.prop('disabled', true).addClass('disabled').removeClass('readonly').prop('readonly', false).val(0);
            par.removeClass('has-error').addClass('has-success');
        } else if (type === 'partial') {
            // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
            myInput.prop('disabled', false).removeClass('readonly disabled').prop('readonly', false).val('').focus();
            par.removeClass('has-error has-success');
        }
    });
    let targetSummContainers = modal.find('b.summ');
    squareInput.on('change.calculate', function () {
        let square = parseInt($(this).val());
        if (square > 0) {
            // пересчитаю сумму целевого платежа за год с учётом плавающей ставки
            targetSummContainers.each(function () {
                let float = toRubles($(this).attr('data-float'));
                if (float > 0) {
                    let fixed = toRubles($(this).attr('data-fixed'));
                    $(this).text(toRubles(fixed + float / 100 * square));
                }
            });
        }
    });
    modal.find('form').on('afterValidate', function (e) {
        // разберусь, что не так с полями целевых взносов
        if (!powerRadios.eq(0).prop('disabled')) {
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
                            e.preventDefault();
                        } else {
                            // если выбрана частичная оплата- проверю правильность заполнения текстового поля
                            let firstFilled = modal.find('input.target-radio[name="' + powerRadioNames[i] + '"]:checked').eq(0);
                            let par = firstFilled.parents('div.form-group').eq(0);
                            if (firstFilled.val() === 'partial') {
                                // если поле не отмечено как успешно заполненное- помечаю как неуспешно заполненное
                                if (!par.hasClass('has-success')) {
                                    par.addClass('has-error');
                                    e.preventDefault();
                                }
                            } else {
                                firstFilled.parents('div.col-lg-5').eq(0).find('div.help-block').text("");
                                par.removeClass('has-error').addClass('has-success');
                            }
                        }
                    }
                }
            }
        }
    });
}

function handleMembershipInput(input) {
    input.on('input.fill', function () {
        // если введённое значение совпадает с шаблоном ввода- отправлю запрос на проверку заполненности тарифов
        const re = /^\s*(\d{4})\W*([1-4])\s*$/;
        let found = input.val().match(re);
        if (found) {
            sendAjax('get', '/check/membership/interval/' + found[1] + '-' + found[2], callback);

            function callback(e) {
                if (e.status === 1) {
                    // открою новое окно для заполнения тарифа
                    if (membershipFillWindow)
                        membershipFillWindow.close();
                    membershipFillWindow = window.open('fill/membership/' + found[1] + '-' + found[2], '_blank');
                    membershipFillWindow.focus();
                    $(membershipFillWindow).on('load.trigger', function () {
                        // при закрытии окна повторно отсылаю данные поля на проверку
                        $(membershipFillWindow).on('unload.test', function () {
                            input.trigger('input');
                            input.trigger('change');
                        });
                    })
                } else if (e.status === 2) {
                    if (membershipFillWindow)
                        membershipFillWindow.close();
                }
            }
        }
    });
}

// handle simple int field input
function handleIntInput(input, button) {
    if (input.length === 1 && button.length === 1) {
        button.prop('disabled', true);
        input.on('input', function () {
            if (/^\+?(0|[1-9]\d*)$/.test(input.val())) {
                button.prop('disabled', false);
            } else {
                button.prop('disabled', true);
            }
        });
    }
}

// handle simple float field input
function handleFloatInput(input, button) {
    if (input.length === 1 && button.length === 1) {
        button.prop('disabled', true);
        input.on('input', function () {
            if (/^\+?(\d*[.,]?\d{0,2})$/.test(input.val())) {
                button.prop('disabled', false);
            } else {
                button.prop('disabled', true);
            }
        });
    }
}

function stringify(data) {
    if (typeof data === 'string') {
        return data;
    } else if (typeof data === 'object') {
        let answer = '';
        for (let i in data) {
            answer += data[i] + '<br/>';
        }
        return answer;
    }
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformerModal("Успешно", message);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerInformerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformer('success', "Успешно", message);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
}

function simpleModalHandler(data) {
    if (data.status) {
        if (data.status === 1) {
            return makeModal(data.header, data.view);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
    return null;
}

function simpleSendForm(form, url) {
    form.on('submit.send', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        sendAjax('post', url, simpleAnswerHandler, form, true);
        return false;
    });
}

// навигация по табам
function enableTabNavigation() {
    let url = location.href.replace(/\/$/, "");
    if (location.hash) {
        const hash = url.split("#");
        $('a[href="#' + hash[1] + '"]').tab("show");
        url = location.href.replace(/\/#/, "#");
        history.replaceState(null, null, url);
    }

    $('a[data-toggle="tab"]').on("click", function () {
        let newUrl;
        const hash = $(this).attr("href");
        if (hash === "#home") {
            newUrl = url.split("#")[0];
        } else {
            newUrl = url.split("#")[0] + hash;
        }
        history.replaceState(null, null, newUrl);
    });
}

function toMathRubles(value) {
    return value.replace(',', '.');
}

// скрою существующую модаль
function closeModal() {
    let modal = $("#myModal");
    if (modal.length === 1) {
        modal.modal('hide');
    }
}

// обработаю ответ на передачу формы через AJAX ========================================================================
function ajaxFormAnswerHandler(data) {
    "use strict";
    if (data.status === 1) {
        // если передана ссылка на скачивание файла- открою её в новом окне
        if(data.href){
            // закрою модальное окно
            closeModal();
            console.log('saving file');
            for(let i = 0; i < data.href.length; i++){
                let newWindow = window.open(data.href[i]);
            }
        }
    } else if (data.message) {
        makeInformer('danger', "Ошибка", data.message);
    }
}

// обработка формы, переданной через AJAX ==============================================================================
function handleModalForm(data) {
    "use strict";
    let readyToSend = false;
    if (data.status && data.status === 1) {
        let modal = makeModal(data.header, data.data);
        let form = modal.find('form');
        form.on('afterValidate', function (event, messages) {
            if (messages) {
                let key;
                for (key in messages) {
                    if (messages.hasOwnProperty(key)) {
                        if (messages[key].length > 0) {
                            readyToSend = false;
                            return;
                        }
                    }
                }
                readyToSend = true;
            }
        });
        // при подтверждении форму не отправляю, жду валидации
        form.on('submit.sendByAjax', function (e) {
            console.log('submit');
            e.preventDefault();
            console.log(readyToSend);
            if (readyToSend === true) {
                sendAjax('post',
                    form.attr('action'),
                    ajaxFormAnswerHandler,
                    form,
                    true);
                readyToSend = false;
            }
        });
    }
    else if(data.status && data.status === 2){
        location.reload();
    }
}
// обработка формы, переданной через AJAX без валидации ===============================================================
function handleModalFormNoValidate(data) {
    "use strict";
    if (data.status && data.status === 1) {
        let modal = makeModal(data.header, data.data);
        let form = modal.find('form');
        // при подтверждении форму не отправляю, жду валидации
        form.on('submit.sendByAjax', function (e) {
            console.log('submit');
            e.preventDefault();
                sendAjax('post',
                    form.attr('action'),
                    simpleAnswerHandler,
                    form,
                    true);
        });
    }
    else if(data.status && data.status === 2){
        location.reload();
    }
}

// обработка активаторов AJAX-запросов =================================================================================
function handleAjaxActivators() {
    "use strict";
    // найду активаторы AJAX-запросов
    let activators = $('.activator');
    activators.on('click.request', function () {
        let action = $(this).attr('data-action');
        if (action) {
            // отправлю запрос на форму
            sendAjax(
                "get",
                action,
                handleModalFormNoValidate
            )
        } else {
            makeInformer(
                "danger",
                "Ошибка",
                "Кнопке не назначено действие"
            )
        }
    });
}

function handleTooltipEnabled() {
    $('.tooltip-enabled').tooltip();
}