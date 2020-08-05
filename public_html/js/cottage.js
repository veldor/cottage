const cottageNumber = location.pathname.split('/')[2];

function individualTariff() {
    let individualTariffActivator = $('#indivTariffBtn');
    let additionalBtn = $('#additionalIndivTariffBtn');
    let cancelBtn = $('#indivTariffOffBtn');
    let additionalCancelBtn = $('#additionalIndivTariffOffBtn');

    function disablePersonalCallback(answer) {
        if (answer.status && answer.status === 2) {
            makeInformer('warning', 'Есть неоплаченный счёт', 'Для отключения индивидуального тарифа необходимо завершить операцию с выставленным счётом');
            return;
        }
        let modal = makeModal('Возвращение к обычному тарифу', answer);
        let frm = modal.find('form');
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
                $(this).focus().removeClass('ready');
                makeInformer('info', 'Информация', 'Значение платежа должно быть больше нуля');
                par.addClass('has-error').removeClass('has-success');
            } else if ($(this).val() === '') {
                $(this).focus().removeClass('ready');
                makeInformer('info', 'Информация', 'Введите сумму в рублях');
                par.addClass('has-error').removeClass('has-success');
            } else if (val >= summ) {
                $(this).focus().removeClass('ready');
                makeInformer('info', 'Информация', 'Сумма не может быть больше полной суммы платежа');
                par.addClass('has-error').removeClass('has-success');
            } else if ($(this).val().match(re)) {
                par.removeClass('has-error').addClass('has-success');
                $(this).addClass('ready');
            } else {
                $(this).focus().removeClass('ready');
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
        powerRadios.on('change.switch', function () {
            let par = $(this).parents('div.form-group').eq(0);
            let myInput = par.find('input[type="text"]');
            let myInputHelp = myInput.parents('div.text-input-parent').find('div.help-block');
            let type = $(this).val();
            if (type === 'full') {
                myInputHelp.text('');
                let summ = par.find('b.summ');
                // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
                myInput.prop('disabled', false).addClass('readonly ready').removeClass('disabled').prop('readonly', true).val(toRubles(summ.text()));
                par.removeClass('has-error').addClass('has-success');
            } else if (type === 'no-payed') {
                myInputHelp.text('');
                // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
                myInput.prop('disabled', true).addClass('disabled ready').removeClass('readonly').prop('readonly', false).val(0);
                par.removeClass('has-error').addClass('has-success');
            } else if (type === 'partial') {
                // год оплачен полностью, убираю параметр disabled, добавляю параметр readonly, выставляю полную сумму платежа
                myInput.prop('disabled', false).removeClass('readonly disabled ready').prop('readonly', false).val('').focus();
                par.removeClass('has-error has-success');
            }
        });
        let sended = false;
        frm.on('submit', function (e) {
            function callback(answer) {
                if (answer.status === 1) {
                    location.reload();
                } else {
                    sended = false;
                }
            }

            e.preventDefault();
            if (powerInputs.not('.ready').length > 0) {
                makeInformer('info', 'Рано', 'Сначала заполните тарифы!.');
            } else {
                if (!sended) {
                    // отправлю данные формы на обработку
                    sendAjax('post', '/tariff/personal/disable/' + cottageNumber, callback, frm, true);


                    sended = true;
                }
            }
        })
    }

    cancelBtn.on('click.off', function (e) {
        e.preventDefault();
        sendAjax('get', '/tariff/personal/disable/' + cottageNumber, disablePersonalCallback);

    });
    additionalCancelBtn.on('click', function (e) {
        e.preventDefault();

        function callback(answer) {
            if (answer.status && answer.status === 2) {
                makeInformer('warning', 'Есть неоплаченный счёт', 'Для активации индивидуального тарифа необходимо завершить операцию с выставленным счётом');
                return;
            } else if (answer.status && answer.status === 1) {
                makeInformerModal('Успешно', 'Индивидуальный тариф отключен');
                return;
            }
            let modal = makeModal('Настройка индивидуального тарифа');
            modal.find('div.modal-body').html(answer);
            let frm = modal.find('form');
            let inputs = frm.find('input[type="text"].required');
            handleCashInput(inputs);
            inputs.eq(0).focus();
            const copyDataBtn = modal.find('button.copy-data');
            copyDataBtn.on('click.copy', function () {
                // заполню поля месяца ниже значениями из полей этого периода
                let par = $(this).parents('div.form-group');
                let next = par.nextAll('div.form-group').eq(0);
                if (next && par.hasClass('power-group') && next.hasClass('power-group')) {
                    next.find('input.power-limit').val(par.find('input.power-limit').val());
                    next.find('input.power-cost').val(par.find('input.power-cost').val());
                    next.find('input.power-overcost').val(par.find('input.power-overcost').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                } else if (next && par.hasClass('membership-group') && next.hasClass('membership-group')) {
                    next.find('input.mem-fixed').val(par.find('input.mem-fixed').val());
                    next.find('input.mem-float').val(par.find('input.mem-float').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                }
            });
            let sended = false;
            frm.on('submit', function (e) {
                function callback(answer) {
                    if (answer.status === 1) {
                        location.reload();
                    } else {
                        sended = false;
                    }
                }

                e.preventDefault();
                if (inputs.filter('.failed').length > 0) {
                    makeInformer('info', 'Рано', 'Обнаружено неверно заполненное поле!.');
                } else if (inputs.not('.ready').length > 0) {
                    makeInformer('info', 'Рано', 'Сначала заполните все поля!.');
                } else {
                    if (!sended) {
                        // отправлю данные формы на обработку
                        sendAjax('post', '/tariff/personal/enable/' + cottageNumber, callback, frm, true);


                        sended = true;
                    }
                }
            })
        }

        sendAjax('get', '/tariff/personal-additional/disable/' + cottageNumber, callback);

    });

    function activatePersonalTariff(data) {
        if (data['satus']) {
            makeInformer('warning', 'Ошибка', data['message']);
            return;
        }
        let modal = makeModal('Активация индивидуального тарифа', data);
        let frm = modal.find('form');
        let inputs = frm.find('input[type="number"].required');
        handleCashInput(inputs);
        inputs.eq(0).focus();
        const copyDataBtn = modal.find('button.copy-data');
        copyDataBtn.on('click.copy', function () {
            // заполню поля месяца ниже значениями из полей этого периода
            let par = $(this).parents('div.form-group');
            let next = par.nextAll('div.form-group').eq(0);
            if (next && par.hasClass('power-group') && next.hasClass('power-group')) {
                next.find('input.power-limit').val(par.find('input.power-limit').val());
                next.find('input.power-cost').val(par.find('input.power-cost').val());
                next.find('input.power-overcost').val(par.find('input.power-overcost').val());
                next.find('input').trigger('input');
                next.find('input').trigger('blur');
            } else if (next && par.hasClass('membership-group') && next.hasClass('membership-group')) {
                next.find('input.mem-fixed').val(par.find('input.mem-fixed').val());
                next.find('input.mem-float').val(par.find('input.mem-float').val());
                next.find('input').trigger('input');
                next.find('input').trigger('blur');
            }
        });
        frm.on('submit', function (e) {
            e.preventDefault();
            // отправлю данные формы на обработку
            sendAjax('post', '/tariff/personal/enable/' + cottageNumber, simpleAnswerHandler, frm, true);
        });
    }

    individualTariffActivator.on('click.activatePersonalTariff', function (e) {
        e.preventDefault();
        sendAjax('get', '/tariff/personal/enable/' + cottageNumber, activatePersonalTariff);
    });

    additionalBtn.on('click', function (e) {
        e.preventDefault();
        sendAjax('get', '/tariff/personal/enable/additional/' + cottageNumber, callback);

        function callback(answer) {
            if (answer.status && answer.status === 2) {
                makeInformer('warning', 'Есть неоплаченный счёт', 'Для активации индивидуального тарифа необходимо завершить операцию с выставленным счётом');
                return;
            }
            let modal = makeModal('Настройка индивидуального тарифа');
            modal.find('div.modal-body').html(answer);
            let frm = modal.find('form');
            let inputs = frm.find('input[type="text"].required');
            handleCashInput(inputs);
            inputs.eq(0).focus();
            const copyDataBtn = modal.find('button.copy-data');
            copyDataBtn.on('click.copy', function () {
                // заполню поля месяца ниже значениями из полей этого периода
                let par = $(this).parents('div.form-group');
                let next = par.nextAll('div.form-group').eq(0);
                if (next && par.hasClass('power-group') && next.hasClass('power-group')) {
                    next.find('input.power-limit').val(par.find('input.power-limit').val());
                    next.find('input.power-cost').val(par.find('input.power-cost').val());
                    next.find('input.power-overcost').val(par.find('input.power-overcost').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                } else if (next && par.hasClass('membership-group') && next.hasClass('membership-group')) {
                    next.find('input.mem-fixed').val(par.find('input.mem-fixed').val());
                    next.find('input.mem-float').val(par.find('input.mem-float').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                }
            });
            let sended = false;
            frm.on('submit', function (e) {
                e.preventDefault();
                if (inputs.filter('.failed').length > 0) {
                    makeInformer('info', 'Рано', 'Обнаружено неверно заполненное поле!.');
                } else if (inputs.not('.ready').length > 0) {
                    makeInformer('info', 'Рано', 'Сначала заполните все поля!.');
                } else {
                    if (!sended) {
                        // отправлю данные формы на обработку
                        sendAjax('post', '/tariff/personal/enable/additional/' + cottageNumber, callback, frm, true);

                        function callback(answer) {
                            if (answer.status === 1) {
                                location.reload();
                            } else {
                                sended = false;
                            }
                        }

                        sended = true;
                    }
                }
            });
        }
    });
    // просмотр данных тарифа
    let showDataBtn = $('#showPersonalTariff');
    showDataBtn.on('click.show', function (e) {
        e.preventDefault();
        sendAjax('get', '/show/personal-tariff/' + cottageNumber, callback);

        function callback(answer) {
            makeModal('Данные о тарифе', answer);
        }
    });

    let showAdditionalDataBtn = $('#showAdditionalPersonalTariff');
    showAdditionalDataBtn.on('click.show', function (e) {
        e.preventDefault();
        sendAjax('get', '/show/personal-tariff-additional/' + cottageNumber, callback);

        function callback(answer) {
            makeModal('Данные о тарифе', answer);
        }
    });

    // изменение данных тарифа
    let changeDataBtn = $('#editPersonalTariff');
    changeDataBtn.on('click.change', function (e) {
        e.preventDefault();
        sendAjax('get', '/tariff/personal/change/' + cottageNumber, callback);

        function callback(answer) {
            if (answer.status && answer.status === 2) {
                makeInformer("warning", "Невозможно", "Обнаружен выставленный но неоплаченный счёт. Оплатите или отмените его перед изменением данных");
                return;
            } else if (answer.status && answer.status === 3) {
                makeInformer("info", "Невозможно", "Нет доступных для изменения тарифов.");
                return;
            }
            let modal = makeModal('Изменение тарифа', answer);
            let frm = modal.find('form');
            let inputs = frm.find('input[type="text"].required');
            handleCashInput(inputs);
            inputs.eq(0).focus();
            const copyDataBtn = modal.find('button.copy-data');
            copyDataBtn.on('click.copy', function () {
                // заполню поля месяца ниже значениями из полей этого периода
                let par = $(this).parents('div.form-group');
                let next = par.nextAll('div.form-group').eq(0);
                if (next && par.hasClass('power-group') && next.hasClass('power-group')) {
                    next.find('input.power-limit').val(par.find('input.power-limit').val());
                    next.find('input.power-cost').val(par.find('input.power-cost').val());
                    next.find('input.power-overcost').val(par.find('input.power-overcost').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                } else if (next && par.hasClass('membership-group') && next.hasClass('membership-group')) {
                    next.find('input.mem-fixed').val(par.find('input.mem-fixed').val());
                    next.find('input.mem-float').val(par.find('input.mem-float').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                }
            });
            let sended = false;
            frm.on('submit', function (e) {
                e.preventDefault();
                if (inputs.filter('.failed').length > 0) {
                    makeInformer('info', 'Рано', 'Обнаружено неверно заполненное поле!.');
                } else if (inputs.not('.ready').length > 0) {
                    makeInformer('info', 'Рано', 'Сначала заполните все поля!.');
                } else {
                    if (!sended) {
                        // отправлю данные формы на обработку
                        sendAjax('post', '/tariff/personal/change/' + cottageNumber, callback, frm, true);

                        function callback(answer) {
                            if (answer.status === 1) {
                                location.reload();
                            } else {
                                sended = false;
                            }
                        }

                        sended = true;
                    }
                }
            })
        }
    });
    // изменение данных тарифа
    let changeAdditionalDataBtn = $('#editAdditionalPersonalTariff');
    changeAdditionalDataBtn.on('click.change', function (e) {
        e.preventDefault();
        sendAjax('get', '/tariff/personal-additional/change/' + cottageNumber, callback);

        function callback(answer) {
            if (answer.status && answer.status === 2) {
                makeInformer("warning", "Невозможно", "Обнаружен выставленный но неоплаченный счёт. Оплатите или отмените его перед изменением данных");
                return;
            } else if (answer.status && answer.status === 3) {
                makeInformer("info", "Невозможно", "Нет доступных для изменения тарифов.");
                return;
            }
            let modal = makeModal('Изменение тарифа', answer);
            let frm = modal.find('form');
            let inputs = frm.find('input[type="text"].required');
            handleCashInput(inputs);
            inputs.eq(0).focus();
            const copyDataBtn = modal.find('button.copy-data');
            copyDataBtn.on('click.copy', function () {
                // заполню поля месяца ниже значениями из полей этого периода
                let par = $(this).parents('div.form-group');
                let next = par.nextAll('div.form-group').eq(0);
                if (next && par.hasClass('power-group') && next.hasClass('power-group')) {
                    next.find('input.power-limit').val(par.find('input.power-limit').val());
                    next.find('input.power-cost').val(par.find('input.power-cost').val());
                    next.find('input.power-overcost').val(par.find('input.power-overcost').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                } else if (next && par.hasClass('membership-group') && next.hasClass('membership-group')) {
                    next.find('input.mem-fixed').val(par.find('input.mem-fixed').val());
                    next.find('input.mem-float').val(par.find('input.mem-float').val());
                    next.find('input').trigger('input');
                    next.find('input').trigger('blur');
                }
            });
            let sended = false;
            frm.on('submit', function (e) {
                e.preventDefault();
                if (inputs.filter('.failed').length > 0) {
                    makeInformer('info', 'Рано', 'Обнаружено неверно заполненное поле!.');
                } else if (inputs.not('.ready').length > 0) {
                    makeInformer('info', 'Рано', 'Сначала заполните все поля!.');
                } else {
                    if (!sended) {
                        // отправлю данные формы на обработку
                        sendAjax('post', '/tariff/personal-additional/change/' + cottageNumber, callback, frm, true);

                        function callback(answer) {
                            if (answer.status === 1) {
                                makeInformerModal('Успешно', 'Данные индивидуального тарифа обновлены');
                            } else {
                                sended = false;
                            }
                        }

                        sended = true;
                    }
                }
            })
        }
    });
}

let tariffsFillWindow;
let invoiceWindow;

function remind(url) {
    sendAjax('post', url, simpleAnswerHandler);
}

function deleteSingle(deleteActivator) {
    let payId = deleteActivator.attr('data-id');
    let url = '/single/delete/' + cottageNumber + '/' + payId;
    sendAjax('post', url, simpleAnswerHandler);
}

function deleteSingleDouble(deleteActivator) {
    let payId = deleteActivator.attr('data-id');
    let url = '/single/delete_double/' + cottageNumber + '/' + payId;
    sendAjax('post', url, simpleAnswerHandler);
}

function editSingle(editActivator, double) {
    let payId = editActivator.attr('data-id');
    if (double) {
        let url = '/single/edit_double/' + cottageNumber + '/' + payId;
        sendAjax('get', url, showDoubleEditor);
    } else {
        let url = '/single/edit/' + cottageNumber + '/' + payId;
        sendAjax('get', url, showEditor);
    }

    function showEditor(data) {
        let modal = simpleModalHandler(data);
        if (modal) {
            let frm = modal.find('form');
            sended = false;
            simpleSendForm(frm, '/single/edit/' + cottageNumber + '/' + payId);
        }
    }

    function showDoubleEditor(data) {
        let modal = simpleModalHandler(data);
        if (modal) {
            let frm = modal.find('form');
            sended = false;
            simpleSendForm(frm, '/single/edit_double/' + cottageNumber + '/' + payId);
        }
    }

}

function addToDeposit(double) {
    let url = '/deposit/add';
    // покажу форму ввода значения суммы зачисления
    let modal = makeModal("Сумма внесения", '<div class="input-group"><input id="toDepositSumm" type="number" step="0.01" class="form-control"><div class="input-group-btn"><button id="sendDataBtn" type="button" class="btn btn-default" disabled="">Зачислить</button> </div></div>');
    let input = modal.find('input#toDepositSumm');
    let btn = modal.find('button#sendDataBtn');
    handleFloatInput(input, btn);
    btn.on('click.send', function () {
        let attributes = {
            'DepositHandler[summ]': input.val(),
            'DepositHandler[additional]': double ? 1 : 0,
            'DepositHandler[cottageNumber]': cottageNumber,
        };
        sendAjax('post', url, simpleAnswerHandler, attributes);
    });
}

function showFines(data) {
    if (data['status'] === 1) {
        makeModal('Расчёт пени', data['text']);
        handleAjaxActivators();
    }
}

function basementFunctional() {
    // отменю смену счётчика
    let discardCounterChangeBtn = $('button#discardCounterChange');
    discardCounterChangeBtn.on('click.discard', function () {
        sendAjax('post', '/counter/discard-change/' + cottageNumber + '/' + $(this).attr('data-month'), simpleAnswerHandler);
    });
    // расчитаю пени
    let countFinesActivator = $('#finesSumm');
    countFinesActivator.on('click.count', function (e) {
        e.preventDefault();
        sendAjax('get', '/fines/count/' + cottageNumber, showFines);
    });
    // расчитаю пени дополнительного участка
    let countFinesDoubleActivator = $('#finesSummDouble');
    countFinesDoubleActivator.on('click.count', function (e) {
        e.preventDefault();
        sendAjax('get', '/fines/count/' + cottageNumber + '-a', showFines);
    });

    // добавление средств напрямую на депозит
    const addToDepositActivator = $('#addToDepositActivator');
    addToDepositActivator.on('click.addToDeposit', function (e) {
        e.preventDefault();
        addToDeposit();
    });
    const addToDepositDoubleActivator = $('#addToDepositDoubleActivator');
    addToDepositDoubleActivator.on('click.addToDeposit', function (e) {
        e.preventDefault();
        addToDeposit(true);
    });

    // отменю внесённые показания по электричеству
    let cBtn = $('button#cancelFillPower');
    cBtn.on('click.cancel', function () {
        sendAjax('get', '/power/cancel-previous/' + cottageNumber, pCallback);
    });
    // отменю внесённые показания по электричеству
    let caBtn = $('button#cancelFillAdditionalPower');
    caBtn.on('click.cancel', function () {
        sendAjax('post', '/power/cancel-previous/additional/' + cottageNumber, pCallback);
    });

    function pCallback(data) {
        if (data.hasOwnProperty('answer') && data.hasOwnProperty('main')) {
            // ответ на запрос формы удаления последних данных о потреблённой электроэнергии
            makeInformerModal(
                "Удаление данных о потреблённой электроэнергии",
                data.answer,
                function () {
                    if (data.main) {
                        sendAjax('post', '/power/cancel-previous/' + cottageNumber, simpleAnswerHandler);
                    } else {
                        sendAjax('post', '/power/cancel-previous/additional/' + cottageNumber, simpleAnswerHandler);
                    }
                },
                function () {}
                );
        }
    }

    // обработаю неоплаченный платёж при его наличии
    let payBtn = $('#handleUnpayedBtn');
    payBtn.on('click.pay', function (e) {
        e.preventDefault();
        editBill($(this).attr('data-identificator'));
    });
    // обработаю неоплаченный платёж при его наличии
    let unpayedDoubleBtn = $('#handleDoubleUnpayedBtn');
    unpayedDoubleBtn.on('click.pay', function (e) {
        e.preventDefault();
        editBill($(this).attr('data-identificator'), true);
    });

    const createSingleButton = $('#createSinglePayButton');
    const createSingleDoubleButton = $('#createSinglePayDoubleButton');
    createSingleButton.on('click.createSingle', function (e) {
        e.preventDefault();
        createSinglePay();
    });
    createSingleDoubleButton.on('click.createSingle', function (e) {
        e.preventDefault();
        createSinglePay(true);
    });

    function createSinglePay(double) {
        let url;
        if (double) {
            url = "/payment/get-form/single/double/" + cottageNumber;
        } else {
            url = "/payment/get-form/single/" + cottageNumber;
        }
        sendAjax('get', url, callback);

        function callback(answer) {
            if (answer.status === 1) {
                let modal = makeModal('Создание разового платежа', answer['data']);
                let frm = modal.find('form');
                let ready = false;
                let validateInProcess = false;
                frm.on('submit', function (e) {
                    e.preventDefault();
                    if (!validateInProcess) {
                        if (ready) {
                            const url = '/payment/single/save';
                            sendAjax('post', url, callback, frm[0], true);

                            function callback(answer) {
                                if (answer.status === 1) {
                                    makeInformerModal('Успех', 'Разовый платёж зарегистрирован');
                                } else {
                                    makeInformer('danger', 'Сохранение платежа', 'Сохранение почему-то не удалось');
                                }
                            }
                        } else {
                            makeInformer('info', 'Сохранение платежа', 'Сначала заполните все необходимые поля.');
                        }
                    }
                });
                frm.on('beforeValidate', function () {
                    validateInProcess = true;
                });
                frm.on('beforeValidateAttribute', function () {
                    validateInProcess = true;
                });
                frm.on('afterValidate', function (event, fields, errors) {
                    validateInProcess = false;
                    ready = !errors.length;
                    if (errors.length > 0) {
                        makeInformer('danger', 'Ошибка', 'Не заполнены все необходимые поля или заполнены с ошибками. Проверьте введённые данные!');
                    }
                });
                frm.on('afterValidateAttribute', function (event, fields, errors) {
                    validateInProcess = false;
                    ready = !errors.length;
                });
            }
        }
    }

    const testTariffs = $('#tariff-no-filled');
    if (testTariffs.length === 1) {
        makeNewWindow('/tariffs/index', tariffsFillWindow, handle);

        function handle() {
            location.reload();
        }
    }
    const payButton = $('#payForCottageButton');

    const payDoubleBtn = $('#payForDoubleCottageBtn');

    payDoubleBtn.on('click.pay', function (e) {
        e.preventDefault();
        sendAjax('get', '/payment/get-form/complex/double/' + cottageNumber, payCallback);
    });

    payButton.on('click.pay', function (e) {
        e.preventDefault();
        // загружу данные обо ВСЕХ платежах
        sendAjax('get', '/payment/get-form/complex/' + cottageNumber, payCallback);
    });

    function payCallback(answer) {
        if (answer.status === 1) {
            let modal = makeModal('Создание счёта на оплату', answer['data']);
        } else if (answer.status === 2) {
            makeInformer('info', 'Найден платеж', 'Завершите действие с ним!');
            editBill(answer['unpayedBillId']['id']);
        }
    }

    function showPaymentsStory(double) {

        let url;

        if (double) {
            url = '/get/bills/double/' + cottageNumber;
        } else {
            url = '/get/bills/' + cottageNumber;
        }

        sendAjax('get', url, getBillsCallback);

        function getBillsCallback(answer) {
            if (answer.status === 1) {
                let modal = makeModal('Просмотр истории платежей');
                let modalBody = modal.find('div.modal-body');
                let billsTable = $('<table class="table table-striped table-hover table-condensed"></table>');
                modalBody.append(billsTable);
                let i = 0;
                while (answer['data'][i]) {
                    let simple = answer['data'][i];
                    let text = '<tr class="hoverable" data-payment-id="' + simple['id'] + '"><td >№ <b class="text-info">' + simple['id'] + '</b></td>';
                    text += '<td><b class="text-success">' + simple['summ'] + ' &#8381;</b></td>';
                    text += simple['isPayed'] ? '<td><button class="btn btn-default"><span class="glyphicon glyphicon-lock text-danger"></span></button></td>' : '<td></td>';
                    if (simple['payed-summ'] > 0) {
                        if (simple['payed-summ'] < simple['summ']) {
                            text += '<td><b class="text-info">Оплачено частично, ' + simple['payed-summ'] + ' &#8381;</b></td>';
                        } else {
                            text += '<td><b class="text-success">Оплачено полностью</b></td>';
                        }
                    } else {
                        if (!simple['isPayed']) {
                            text += '<td><b class="text-warning">Ожидает оплаты</b></td>';
                        } else {
                            text += '<td><b class="text-danger">Не оплачено</b></td>';
                        }
                    }
                    if (simple['paymentTime']) {
                        text += '<td><b class="text-info">Дата оплаты: ' + simple['paymentTime'] + '</b></td>';
                    }
                    text += '</tr>';
                    let item = $(text);
                    billsTable.append(item);
                    item.on('click.show', function () {
                        editBill(simple['id'], simple['double']);
                    });
                    // let payed = simple['isPartialPayed'] ? '<button class="btn btn-info ">Частично оплачен</button>' : (simple['isPayed'] ? '<button class="btn btn-default"><span class="glyphicon glyphicon-lock text-danger"></span></button>' : '<button class="btn btn-warning">В ожидании оплаты</button>');
                    // if (simple['isPayed'] || simple['isPartialPayed']) {
                    //     let summ;
                    //     if (simple['isPartialPayed']) {
                    //         summ = ' Оплачено ' + simple['payed-summ'] + ' из ' + simple['summ'] + ' ';
                    //     } else {
                    //         if (simple['payed-summ'] >= simple['summ']) {
                    //             summ = '<b class="text-success">' + simple['summ'] + ' &#8381;</b> Оплачено полностью ';
                    //         } else {
                    //             summ = '<b class="text-danger">' + simple['summ'] + ' &#8381;</b> Не оплачено ';
                    //         }
                    //     }
                    //     let item = $('<p class="hoverable" data-payment-id="' + simple['id'] + '">Платёж № <b class="text-info">' + simple['id'] + '</b>, сумма: ' + summ + payed + ' ' + simple['paymentTime'] + '</p>');
                    //     item.on('click.show', function () {
                    //         editBill(simple['id'], simple['double']);
                    //     });
                    //     modalBody.append(item);
                    // } else {
                    //     let item = $('<p class="hoverable" data-payment-id="' + simple['id'] + '">Платёж № <b class="text-info">' + simple['id'] + '</b>, сумма: <b class="text-warning">' + simple['summ'] + ' &#8381;</b>. ' + payed + ' ' + simple['paymentTime'] + '</p>');
                    //     item.on('click.show', function () {
                    //         editBill(simple['id'], simple['double']);
                    //     });
                    //     modalBody.append(item);
                    // }
                    i++;
                }
                /*                modal.find('p.hoverable').on('click.show', function () {
                                    let id = $(this).attr('data-payment-id');
                                    modal.modal('hide');
                                    modal.on('hidden.bs.modal', function () {
                                        editBill(id, double);
                                    });
                                })*/
            } else if (answer.status === 2) {
                makeInformer('info', 'Список платежей', 'Платежей по данному участку не найдено');
            } else {
                makeInformer('danger', 'Что-то пошло не так', 'Сообщите мне об этой ошибке')
            }
        }
    }

    const paymentsHistoryBtn = $('#buttonShowPaymentsStory');
    paymentsHistoryBtn.on('click.showHistory', function (e) {
        e.preventDefault();
        showPaymentsStory()
    });
    const paymentsHistoryDoubleActivator = $('#showDoublePaymentsStory');
    paymentsHistoryDoubleActivator.on('click.showHistory', function (e) {
        e.preventDefault();
        showPaymentsStory(true);
    });

    let changeBtn = $('#changeInfoButton');
    let changeAddBtn = $('#changeAddInfoButton');
    changeAddBtn.on('click.change', function (e) {
        e.preventDefault();
        let modal = makeModal('Изменение информации об участке.');
        modal.find('div.modal-content').addClass('test-transparent');
        sendAjax('get', '/get-form/change-add/' + cottageNumber, callback);

        function callback(answer) {
            if (answer.status === 1) {
                let data = $(answer['data']);
                modal.find('div.modal-body').append(data);
                const membership = $('input#addcottage-membershippayfor');
                membership.on('input.fill', function () {
                    // если введённое значение совпадает с шаблоном ввода- отправлю запрос на проверку заполненности тарифов
                    const re = /^\s*(\d{4})\W*([1-4])\s*$/;
                    let found;
                    if (found = membership.val().match(re)) {
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
                                        membership.trigger('input');
                                        membership.trigger('change');
                                    });
                                })
                            } else if (e.status === 2) {
                                if (membershipFillWindow)
                                    membershipFillWindow.close();
                            }
                        }
                    }
                });
                // парсинг имени
                let namePattern = /^\s*([ёа-я-]+)\s+([ёа-я]+)\s+([ёа-я]+)\s*$/i;
                let nameInputs = modal.find('input#addcottage-cottageownerpersonals, input#addcottage-cottagecontacterpersonals');
                nameInputs.on('blur.testName', function () {
                    let match;
                    match = $(this).val().match(namePattern);
                    if (match) {
                        let text = "Фамилия: " + match[1] + ", имя: " + match[2] + ", отчество: " + match[3];
                        $(this).parent().find("div.hint-block").text(text);
                    } else {
                        $(this).parent().find("div.hint-block").html('<b class="text-success">Обязательное поле.</b> Буквы, пробелы и тире.');
                    }
                });

                // парсинг номера телефона
                let phoneInputs = modal.find('input#addcottage-cottageownerphone, input#addcottage-cottagecontacterphone');
                phoneInputs.on('input.testName', function () {
                    let hint = $(this).parent().find('div.hint-block');
                    let link = $(this).val();
                    let filtredVal = link.replace(/[^0-9]/g, '');
                    if (filtredVal.length === 7) {
                        hint.html('Распознан номер +7 831 ' + filtredVal.substr(0, 3) + '-' + filtredVal.substr(3, 2) + '-' + filtredVal.substr(5, 2));
                    } else if (filtredVal.length === 10) {
                        hint.html('Распознан номер +7 ' + filtredVal.substr(0, 3) + ' ' + filtredVal.substr(3, 3) + '-' + filtredVal.substr(6, 2) + '-' + filtredVal.substr(8, 2));
                    } else if (filtredVal.length === 11) {
                        hint.html('Распознан номер +7 ' + filtredVal.substr(1, 3) + ' ' + filtredVal.substr(4, 3) + '-' + filtredVal.substr(7, 2) + '-' + filtredVal.substr(9, 2));
                    } else if (link.length > 0) {
                        hint.html('Номер не распознан!');
                    } else {
                        hint.html('<b class="text-info">Необязательное поле.</b> Десять цифр, без +7.');
                    }
                });

                let addContacter = modal.find('input#addcottage-hascontacter');
                let contacterDiv = modal.find('fieldset#contacterInfo');
                addContacter.on('change.switch', function () {
                    if ($(this).prop('checked'))
                        contacterDiv.removeClass('hidden');
                    else
                        contacterDiv.addClass('hidden');
                });
                data.on('submit', function (e) {
                    e.preventDefault();
                    let i = 0;
                    let loadedForm;
                    while (data[i]) {
                        if (data[i].nodeName === "FORM") {
                            loadedForm = data[i];
                            break;
                        }
                        i++;
                    }
                    const url = "/add-cottage/save/change-add";
                    sendAjax('post', url, simpleAnswerHandler, loadedForm, true);
                })

            }
        }
    });
    changeBtn.on('click.change', function (e) {
        e.preventDefault();
        let modal = makeModal('Изменение информации об участке.');
        modal.find('div.modal-content').addClass('test-transparent');
        sendAjax('get', '/get-form/change/' + cottageNumber, callback);

        function callback(answer) {
            if (answer.status === 1) {
                let data = $(answer['data']);
                modal.find('div.modal-body').append(data);
                const membership = $('input#addcottage-membershippayfor');
                membership.on('input.fill', function () {
                    // если введённое значение совпадает с шаблоном ввода- отправлю запрос на проверку заполненности тарифов
                    const re = /^\s*(\d{4})\W*([1-4])\s*$/;
                    let found;
                    if (found = membership.val().match(re)) {
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
                                        membership.trigger('input');
                                        membership.trigger('change');
                                    });
                                })
                            } else if (e.status === 2) {
                                if (membershipFillWindow)
                                    membershipFillWindow.close();
                            }
                        }
                    }
                });
                // парсинг имени
                let namePattern = /^\s*([ёа-я-]+)\s+([ёа-я]+)\s+([ёа-я]+)\s*$/i;
                let nameInputs = modal.find('input#addcottage-cottageownerpersonals, input#addcottage-cottagecontacterpersonals');
                nameInputs.on('blur.testName', function () {
                    let match;
                    match = $(this).val().match(namePattern);
                    if (match) {
                        let text = "Фамилия: " + match[1] + ", имя: " + match[2] + ", отчество: " + match[3];
                        $(this).parent().find("div.hint-block").text(text);
                    } else {
                        $(this).parent().find("div.hint-block").html('<b class="text-success">Обязательное поле.</b> Буквы, пробелы и тире.');
                    }
                });

                // парсинг номера телефона
                let phoneInputs = modal.find('input#addcottage-cottageownerphone, input#addcottage-cottagecontacterphone');
                phoneInputs.on('input.testName', function () {
                    let hint = $(this).parent().find('div.hint-block');
                    let link = $(this).val();
                    let filtredVal = link.replace(/[^0-9]/g, '');
                    if (filtredVal.length === 7) {
                        hint.html('Распознан номер +7 831 ' + filtredVal.substr(0, 3) + '-' + filtredVal.substr(3, 2) + '-' + filtredVal.substr(5, 2));
                    } else if (filtredVal.length === 10) {
                        hint.html('Распознан номер +7 ' + filtredVal.substr(0, 3) + ' ' + filtredVal.substr(3, 3) + '-' + filtredVal.substr(6, 2) + '-' + filtredVal.substr(8, 2));
                    } else if (filtredVal.length === 11) {
                        hint.html('Распознан номер +7 ' + filtredVal.substr(1, 3) + ' ' + filtredVal.substr(4, 3) + '-' + filtredVal.substr(7, 2) + '-' + filtredVal.substr(9, 2));
                    } else if (link.length > 0) {
                        hint.html('Номер не распознан!');
                    } else {
                        hint.html('<b class="text-info">Необязательное поле.</b> Десять цифр, без +7.');
                    }
                });

                let addContacter = modal.find('input#addcottage-hascontacter');
                let contacterDiv = modal.find('fieldset#contacterInfo');
                addContacter.on('change.switch', function () {
                    if ($(this).prop('checked'))
                        contacterDiv.removeClass('hidden');
                    else
                        contacterDiv.addClass('hidden');
                });
                data.on('submit', function (e) {
                    e.preventDefault();
                    let i = 0;
                    let loadedForm;
                    while (data[i]) {
                        if (data[i].nodeName === "FORM") {
                            loadedForm = data[i];
                            break;
                        }
                        i++;
                    }
                    const url = "/add-cottage/save/change";
                    sendAjax('post', url, simpleAnswerHandler, loadedForm, true);
                })

            }
        }
    });

    // ================================== РЕДАКТИРОВАНИЕ РАЗОВЫХ ПЛАТЕЖЕЙ ==============================
    const singleChangesActivator = $('#editSinglesActivator');
    const singleChangesDoubleActivator = $('#editSinglesDoubleActivator');

    singleChangesActivator.on('click.changeSingle', function (e) {
        e.preventDefault();
        changeSingles();
    });
    singleChangesDoubleActivator.on('click.changeSingle', function (e) {
        e.preventDefault();
        changeSingles(true);
    });

    function handleSingles(data) {
        let modal = makeModal('Список существующих платежей', data);
        let pays = modal.find('tr.single-item');
        pays.each(function () {
            let id = $(this).attr('data-id');
            $(this).append('<div class="single-management"><div class="btn-group"><button type="button" class="btn btn-danger delete-single" data-id="' + id + '"><span class="glyphicon glyphicon-trash"></span></button><button type="button" class="btn btn-info edit-single" data-id="' + id + '"><span class="glyphicon glyphicon-pencil"></span></button></div></div>');
        });
        let deleteButtons = modal.find('button.delete-single');
        deleteButtons.on('click.deleteSingle', function () {
            modal.modal('hide');
            deleteSingle($(this));
        });
        let changeButtons = modal.find('button.edit-single');
        changeButtons.on('click.editSingle', function () {
            modal.modal('hide');
            editSingle($(this));
        });
    }

    function handleSinglesDouble(data) {
        let modal = makeModal('Список существующих платежей', data);
        let pays = modal.find('tr.single-item');
        pays.each(function () {
            let id = $(this).attr('data-id');
            $(this).append('<div class="single-management"><div class="btn-group"><button type="button" class="btn btn-danger delete-single" data-id="' + id + '"><span class="glyphicon glyphicon-trash"></span></button><button type="button" class="btn btn-info edit-single" data-id="' + id + '"><span class="glyphicon glyphicon-pencil"></span></button></div></div>');
        });
        let deleteButtons = modal.find('button.delete-single');
        deleteButtons.on('click.deleteSingle', function () {
            modal.modal('hide');
            deleteSingleDouble($(this));
        });
        let changeButtons = modal.find('button.edit-single');
        changeButtons.on('click.editSingle', function () {
            modal.modal('hide');
            editSingle($(this), true);
        });
    }

    function changeSingles(double) {
        let url;
        if (double) {
            url = '/show/debt/detail/single_additional/' + cottageNumber;
            sendAjax('get', url, handleSinglesDouble);
        } else {
            url = '/show/debt/detail/single/' + cottageNumber;
            sendAjax('get', url, handleSingles);
        }
    }

    let addMailBtn = $('button#addMailBtn');
    addMailBtn.on('click.addMail', function () {
        sendAjax('get', '/form/mail-add/' + cottageNumber, handleModalForm);
    });

    let changeMailBtn = $('button.mail-change');
    changeMailBtn.on('click.changeMail', function () {
        sendAjax('get', '/form/mail-change/' + $(this).attr('data-id'), handleModalForm);
    });

    let mailDelete = $('.mail-delete');
    mailDelete.on('click.delete', function () {
        let anchor = $(this);
        makeInformerModal('Удаление электронной почты',
            "Удалить адрес электронной почты?",
            function () {
                let id = anchor.attr('data-id');
                sendAjax('post',
                    '/mail-delete',
                    ajaxFormAnswerHandler,
                    {'id': id}
                );
            },
            function () {
            });
    });
}


function editBill(identificator, double) {
    // запрошу сведения о платеже
    if (double) {
        sendAjax('get', '/get-info/bill/double/' + identificator, callback);
    } else {
        sendAjax('get', '/get-info/bill/' + identificator, callback);
    }


    function callback(answer) {
        if (answer.status === 1) {
            let modal = makeModal('Информация о счёте', answer['view'], true);
            // Обработаю использование депозита
            //const totalPaymentSumm = modal.find('span#paymentTotalSumm');
            let deleteBillBtn = modal.find('button#deleteBill');
            console.log(deleteBillBtn);
            deleteBillBtn.on('click.delete', function () {
                let url = double ? '/bill/delete/double/' + identificator : '/bill/delete/' + identificator;
                sendAjax('post', url, simpleAnswerHandler);
            });
            // Обработаю функции кнопок
            // формирую банковскую квитанцию
            const bankInvoiceActivator = modal.find('button#formBankInvoice');
            bankInvoiceActivator.on('click.formInvoice', function () {
                let url;
                if (double) {
                    url = '/bank-invoice/double/' + identificator;
                } else {
                    url = '/bank-invoice/' + identificator;
                }
                makeNewWindow(url, invoiceWindow, printCallback);
            });
            const bankInvoiceSendActivator = modal.find('button#sendBankInvoice');
            bankInvoiceSendActivator.on('click.sendInvoice', function () {
                let url;
                if (double) {
                    url = '/bank-invoice/double/send/' + identificator;
                } else {
                    url = '/bank-invoice/send/' + identificator;
                }
                sendAjax('post', url, simpleAnswerHandler);
            });

            function printCallback() {
                makeInformer('success', 'Квитанция распечатана');
            }

            const reopenBillActivator = modal.find('button#payReopenActivator');

            reopenBillActivator.on('click.reopen', function () {
                modal.modal('hide');
                makeInformerModal("Повторное открытие счёта", "Вы точно хотите повторно открыть закрытый счёт?", reopenClosedBill, function () {
                });
            });

            function reopenClosedBill() {
                if (double) {
                    sendAjax('post', '/bill/reopen/double/' + identificator, simpleAnswerHandler);
                } else {
                    sendAjax('post', '/bill/reopen/' + identificator, simpleAnswerHandler);
                }
            }

            const remindAboutPayBtn = modal.find('button#remindAbout');
            remindAboutPayBtn.on('click.remind', function () {
                remind('/send/pay/' + identificator);
            });
            const printInvoiceBtn = modal.find('button#printInvoice');
            const sendInvoiceBtn = modal.find('button#sendInvoice');
            sendInvoiceBtn.on('click.send', function () {
                disableElement(sendInvoiceBtn, "Шлю письмо");
                sendAjax('post', '/send-invoice/' + identificator, callback);

                function callback(answer) {
                    if (answer.status === 1) {
                        makeInformer('success', 'Квитанция отправлена', "Квитанция успешно отправлена на адрес, указанный в профиле участка");
                        enableElement(sendInvoiceBtn, "Отправить квитанцию ещё раз");
                    } else if (answer.status === 2) {
                        makeInformer('danger', 'Квитанция не отправлена', "Квитанция не отправлена. Возможно, в профиле не указан адрес почтового ящика или нет соединения с интернетом");
                    }
                }
            });
            printInvoiceBtn.on('click.print', function () {
                disableElement(printInvoiceBtn, "Распечатываю счёт");
                makeNewWindow('/invoice/' + identificator, invoiceWindow, callback);

                function callback() {
                    // квитанция распечатана
                    makeInformer('success', 'Счёт распечатан');
                    enableElement(printInvoiceBtn, 'Распечатать ещё один счёт');
                }
            });
            let link = modal;
            // отмечу платёж оплаченным
            let payActivator = modal.find('button#payedActivator');
            payActivator.on('click.save', function () {
                link.modal('hide');
                modal.on('hidden.bs.modal', function () {
                    let url;
                    if (double) {
                        url = '/get-form/pay/double/' + identificator;
                    } else {
                        url = '/get-form/pay/' + identificator;
                    }
                    sendAjax('get', url, payConfirmCallback);

                    function payConfirmCallback(answer) {
                        if (answer.status === 1) {
                            let newModal = makeModal('Подтверждение оплаты', answer['view']);
                            newModal.find('.popovered').popover();
                        }
                    }
                });
            });
            let payCloseActivator = modal.find('#payClosedActivator');
            payCloseActivator.on('click.closePay', function () {
                let url;
                if (double) {
                    url = '/pay/close/double/' + identificator;
                } else {
                    url = '/pay/close/' + identificator;
                }
                // отправлю запрос на закрытие частично оплаченного счёта
                sendAjax('post', url, simpleAnswerHandler);
            });
        }
    }
}

function additionalFunctions() {

    function fillPowerCallback(data) {
        if (data['data']) {
            let modal = makeModal("Заполнение данных", data['data']);
            let counterChangeActivator = modal.find('input#powerhandler-dochangecounter');
            let changeCounterTypeWrapper = modal.find('div.field-powerhandler-counterchangetype');
            let changeCounterOptions = modal.find('input[name="PowerHandler[counterChangeType]"]');
            let counterStartDataWrapper = modal.find('div.field-powerhandler-newcounterstartdata');
            let counterFinishDataWrapper = modal.find('div.field-powerhandler-newcounterfinishdata');
            counterChangeActivator.on('change', function () {
                if ($(this).prop('checked')) {
                    changeCounterTypeWrapper.removeClass('hidden');
                } else {
                    changeCounterTypeWrapper.addClass('hidden');
                    counterStartDataWrapper.addClass('hidden');
                    counterFinishDataWrapper.addClass('hidden');
                }
            });
            changeCounterOptions.on('change', function () {
                if ($(this).val() === 'simple') {
                    counterStartDataWrapper.removeClass('hidden');
                    counterFinishDataWrapper.addClass('hidden');
                } else {
                    counterStartDataWrapper.removeClass('hidden');
                    counterFinishDataWrapper.removeClass('hidden');
                }
            });

            let modalForm = modal.find('form');
            modalForm.on('submit.send', function (e) {
                e.preventDefault();
                sendAjax('post', "/fill/power/" + cottageNumber, simpleAnswerHandler, modalForm, true);
            })
        } else {
            if (data['status'] === 2) {
                makeInformer('danger', 'Не получится', 'Данные за месяц уже внесены');
            } else if (data['status'] === 3) {
                makeInformer('info', 'Не заполнен тариф', 'Сначала заполните тарифные ставки на текущий месяц');
                makeNewWindow('/fill/power/' + data['month'], tariffsFillWindow, function () {
                });
            }
        }
    }

    // заполнение данных электроэнергии за предыдущий месяц
    let prevMonthEnergyBtn = $('button#fillPower');
    prevMonthEnergyBtn.on('click.fill', function () {


        // запрошу форму заполнения данных
        let url = "/fill/power/" + cottageNumber;
        sendAjax('get', url, fillPowerCallback);
        /*let modal = makeModal("Отправить показания", '<div class="input-group"><input id="counter_data" type="number" class="form-control"><div class="input-group-btn"><button id="send_counter_data_btn" type="button" class="btn btn-default" disabled="">Заполнить</button> </div></div>');
        // обработка ввода в поле заполнения электроэнергии
        let counterDataInput = modal.find('input#counter_data');
        let counterDataSender = modal.find('button#send_counter_data_btn');
        handleIntInput(counterDataInput, counterDataSender);
        counterDataSender.on('click', function () {
            disableElement(counterDataSender);
            // отправлю запрос с данными
            let attributes = {
                'PowerHandler[cottageNumber]': cottageNumber,
                'PowerHandler[newPowerData]': counterDataInput.val(),
            };
            sendAjax('post', '/fill/power/' + cottageNumber, callback, attributes);

            function callback(answer) {
                enableElement(counterDataSender);
                if (answer.status === 1) {
                    makeInformerModal("Сохранение показаний", "Показания внесены!");
                } else if (answer.status === 0) {
                    makeInformerModal("Ошибка сохранения показаний", stringify(answer['errors']));
                } else {
                    makeInformerModal("Ошибка сохранения показаний", answer.toString());
                }
            }
        });*/
    });
    // заполнение данных электроэнергии за предыдущий месяц
    let prevMonthAdditionalEnergyBtn = $('button#fillAdditionalPower');
    prevMonthAdditionalEnergyBtn.on('click.fill', function () {
        let modal = makeModal("Отправить показания", '<div class="input-group"><input id="counter_data" type="number" class="form-control"><div class="input-group-btn"><button id="send_counter_data_btn" type="button" class="btn btn-default" disabled="">Заполнить</button> </div></div>');

        // обработка ввода в поле заполнения электроэнергии
        let counterDataInput = modal.find('input#counter_data');
        let counterDataSender = modal.find('button#send_counter_data_btn');
        handleIntInput(counterDataInput, counterDataSender);
        counterDataSender.on('click', function () {
            disableElement(counterDataSender);
            // отправлю запрос с данными
            let attributes = {
                'PowerHandler[cottageNumber]': cottageNumber,
                'PowerHandler[newPowerData]': counterDataInput.val(),
                'PowerHandler[additional]': 1,
            };
            sendAjax('post', '/fill/power/' + cottageNumber, callback, attributes);

            function callback(answer) {
                enableElement(counterDataSender);
                if (answer.status === 1) {
                    makeInformerModal("Сохранение показаний", "Показания внесены!");
                } else if (answer.status === 0) {
                    makeInformerModal("Ошибка сохранения показаний", stringify(answer['errors']));
                } else {
                    makeInformerModal("Ошибка сохранения показаний", answer.toString());
                }
            }
        });
    });

    // заполнение данных по электроэнергии за текущий месяц
    let thisMonthEnergyBtn = $('#fillCurrentPowerMonth');
    thisMonthEnergyBtn.on('click.fill', function (e) {
        e.preventDefault();
        console.log('click');
        sendAjax('get', '/fill/power/current/' + cottageNumber, fillPowerCallback);
    });

    /*    function makeFillModal(data) {
            if (data.status) {
                if (data.status === 1) {
                    newModal = makeModal('Внесение информации по электроэнергии');
                    let frm = $(data['data']);
                    newModal.find('div.modal-body').append(frm);
                    let sended = false;
                    frm.on('submit.send', function (e) {
                        e.preventDefault();
                        if (!sended) {
                            sended = true;
                            let i = 0;
                            let loadedForm;
                            while (frm[i]) {
                                if (frm[i].nodeName === "FORM") {
                                    loadedForm = frm[i];
                                    break;
                                }
                                i++;
                            }
                            const url = '/fill/power/' + cottageNumber;
                            sendAjax('post', url, callback, loadedForm, true);

                            function callback(data) {
                                if (data.status === 1) {
                                    newModal.modal('hide');
                                    makeInformerModal('Успешно', 'Данные за ' + data['data']['month'] + ' внесены.<br/>К оплате ' + data['data']['totalPay'] + '&#8381;.')
                                }
                            }
                        }
                    });
                } else if (data.status === 3) {
                    makeInformer('info', 'Не заполнен тариф', 'Сначала заполните тарифные ставки на текущий месяц');
                    makeNewWindow('/fill/power/' + data['month'], tariffsFillWindow, handle);

                    function handle(e) {
                    }
                } else if (data.status === 2) {
                    makeInformer('danger', 'Невозможно', 'Похоже, данные за этот месяц уже внесены');
                }
            }
        }*/

    // Отображение подробной информации о долгах
    let detailViewers = $('a.detail-debt');
    detailViewers.on('click.show', function (e) {
        e.preventDefault();
        let type = $(this).attr('data-type');
        sendAjax('get', '/show/debt/detail/' + type + '/' + cottageNumber, callback);

        function callback(answer) {
            makeModal('Подробности', answer);
        }
    });


    // покажу отчёты по участку за выбранный период
    let showRepotrsBtn = $('#showReports');
    showRepotrsBtn.on('click.show', function (e) {
        e.preventDefault();
        handleReport();
    });
    // покажу отчёты по дополнительному участку за выбранный период
    let showRepotrsDoubleBtn = $('#showReportsDouble');
    showRepotrsDoubleBtn.on('click.show', function (e) {
        e.preventDefault();
        handleReport(true);
    });
    // отправляю оповещение о задолженности
    let sendDutiesBtn = $('#sendNotificationBtn');
    let sendRegInfoBtn = $('#sendRegInfoNotificationBtn');


    sendDutiesBtn.on('click.send', function (e) {
        e.preventDefault();
        remind('/send/duties/' + cottageNumber);
    });
    sendRegInfoBtn.on('click.send', function (e) {
        e.preventDefault();
        remind('/send/reg-info/' + cottageNumber);
    });
    // замена счётчика электроэнергии
    let changeCounterBtn = $('#changePowerCounter');
    changeCounterBtn.on('click.change', function (e) {
        e.preventDefault();
        let modal = makeModal('Замена счётчика электроэнергии');
        sendAjax('get', '/service/change-counter/' + cottageNumber, callback);

        function callback(answer) {
            if (answer['data']) {
                let frm = $(answer['data']);
                modal.find('div.modal-body').append(frm);
                let sended = false;
                frm.on('submit.test', function (e) {
                    e.preventDefault();
                    if (!sended) {
                        sended = true;
                        let i = 0;
                        let loadedForm;
                        while (frm[i]) {
                            if (frm[i].nodeName === "FORM") {
                                loadedForm = frm[i];
                                break;
                            }
                            i++;
                        }
                        const url = "/service/change-counter/" + cottageNumber;
                        sendAjax('post', url, callback, loadedForm, true);

                        function callback(answer) {
                            if (answer.status === 1) {
                                location.reload();
                            } else {
                                sended = false;
                                makeInformer('danger', 'Не удалось сохранить данные', handleErrors(answer['errors']));
                            }
                        }
                    }
                })
            }
        }
    });
    let createCottageBtn = $('button#createAdditionalCottage');
    createCottageBtn.on('click.add', function () {
        sendAjax('get', '/create/additional-cottage/' + cottageNumber, callback);

        function callback(data) {
            let modal = makeModal('Добавление дополнительного участка', data);
            let frm = modal.find('form#AdditionalCottage');
            let ready = false;
            let validateInProcess = false;
            let powerBtn = $('input#additionalcottage-ispower');
            let ownerBtn = $('input#additionalcottage-differentowner');
            let powerDependent = modal.find('input.power-dependent');
            let ownerdependent = modal.find('input.owner-dependent');
            let membershipBtn = $('input#additionalcottage-ismembership');
            let membershipDependent = modal.find('input.membership-dependent');
            let targetBtn = $('input#additionalcottage-istarget');
            let targetDependent = modal.find('input.target-dependent, label.target-dependent');

            ownerBtn.on('change.switch', function () {

                if ($(this).prop('checked')) {
                    ownerdependent.prop('disabled', false);
                } else {
                    ownerdependent.prop('disabled', true).val('');
                }
            });

            powerBtn.on('change.switch', function () {
                if ($(this).prop('checked')) {
                    powerDependent.prop('disabled', false);
                } else {
                    powerDependent.prop('disabled', true).val('');
                }
            });
            membershipBtn.on('change.switch', function () {
                if ($(this).prop('checked')) {
                    membershipDependent.prop('disabled', false);
                } else {
                    membershipDependent.prop('disabled', true).val('');
                }
            });
            targetBtn.on('change.switch', function () {
                if ($(this).prop('checked')) {
                    targetDependent.prop('disabled', false).removeClass('disabled');
                } else {
                    targetDependent.prop('disabled', true).addClass('disabled');
                }
            });
            const membership = $('input#additionalcottage-membershippayfor');
            handleMembershipInput(membership);
            let squareInput = modal.find('input#additionalcottage-cottagesquare');
            handlePowerInputs(modal, squareInput);

            frm.on('beforeValidate.ready', function () {
                validateInProcess = true;
            });
            frm.on('afterValidate.ready', function (event, fields, errors) {
                validateInProcess = false;
                ready = !errors.length;
                if (errors.length > 0) {
                    makeInformer('danger', 'Ошибка', 'Не заполнены все необходимые поля или заполнены с ошибками. Проверьте введённые данные!');
                }
            });

            frm.on('submit', function (e) {
                e.preventDefault();
                if (ready && !validateInProcess) {
                    sendAjax('post', '/save/additional-cottage/' + cottageNumber, callback, frm[0], true);

                    function callback(answer) {
                        if (answer.status && answer.status === 1) {
                            makeInformerModal('Успех', 'Дополнительный участок зарегистрирован');
                        }
                    }
                }
            });
        }
    });
}

function addHotkeys() {
    $('body').on('keypress', function (event) {
        console.log(event);
        // хоткей shift + n: переход к следующему участку
        if((event.keyCode === 78 || event.keyCode === 1058) && event.shiftKey === true){
            // перейду к следующему участку
            location.assign('/cottage/next');
        }
        // хоткей shift + n: переход к предыдущему участку
        else if((event.keyCode === 80 || event.keyCode === 1047) && event.shiftKey === true){
            // перейду к предыдущему участку
            location.assign('/cottage/previous');
        }
        // хоткей shift + n: переход к предыдущему участку
        else if((event.keyCode === 66 || event.keyCode === 1048) && event.shiftKey === true){
            // открою окно создания счёта
            $('button#payForCottageButton').trigger('click');
        }

    });
}

$(function () {
    // добавлю хоткеи
    addHotkeys();
    handleTooltipEnabled();
    handleAjaxActivators();
    basementFunctional();
    additionalFunctions();
    individualTariff();
    $(window).on('beforeunload.closeChild', function () {
        if (tariffsFillWindow) {
            tariffsFillWindow.close();
        }
        if (invoiceWindow) {
            invoiceWindow.close();
        }
    });
});

function handleReport(double) {
    let modal = makeModal('Выберите период для отчёта', "<div class='row'><form>\n" +
        "    <div class='form-group membership-group text-center'>\n" +
        "        <div class='col-lg-6'>\n" +
        "            <label class=\"control-label\">Начало периода\n" +
        "                <input id='begin-period' type='date' class='form-control'/>\n" +
        "            </label>\n" +
        "        </div>\n" +
        "        <div class='col-lg-6'>\n" +
        "            <label class=\"control-label\">Завершение периода\n" +
        "                <input id=\"end-period\" type='date' class='form-control'/>\n" +
        "            </label>\n" +
        "        </div>\n" +
        "    </div>\n" +
        "    <div class='col-lg-12 text-center'><button type='button' id='goBtn' class='btn btn-primary'>Показать</button></div>\n" +
        "</form></div>");
    let sendBtn = modal.find('button#goBtn');
    let start = modal.find('input#begin-period');
    let end = modal.find('input#end-period');
    sendBtn.on('click.send', function () {
        let startVal = start.val();
        let endVal = end.val();
        if (startVal && endVal) {
            if(double){
                window.open('/print/cottage-report-double/' + new Date(startVal).getTime() + '/' + new Date(endVal).getTime() + '/' + cottageNumber, '_blank')
            }
            else{
                window.open('/print/cottage-report/' + new Date(startVal).getTime() + '/' + new Date(endVal).getTime() + '/' + cottageNumber, '_blank')
            }
        } else {
            makeInformer('warning', 'Рано', 'Выберите дату начала и завершения периода');
        }
    });
}

