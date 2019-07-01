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
    sendAjax('post', url, notificationCallback);
}

function notificationCallback(answer) {
    //БЛОК ПРОВЕРКИ СТАТУСА ОТПРАВКИ АВТОМАТИЧЕСКОГО УВЕДОМЛЕНИЯ
    if (answer['messageStatus']) {
        if (answer['messageStatus'].status === 2) {
            makeInformer('danger', 'Неуспешно', 'Нет подключения к интернету. Сообщение сохранено, вы сможете отправить его, когда подключение появится!');
        } else if (answer['messageStatus'].status === 1) {
            if (answer['messageStatus']['results']['to-owner']) {
                if (answer['messageStatus']['results']['to-owner'] === true) {
                    makeInformerModal('Успешно', 'Письмо владельцу успешно отправлено!', function () {
                    });
                } else {
                    makeInformer('danger', 'Неуспешно', 'Письмо владельцу отправить не удалось!');
                }
            }
            if (answer['messageStatus']['results']['to-contacter']) {
                if (answer['messageStatus']['results']['to-contacter'] === true) {
                    makeInformerModal('Успешно', 'Письмо контактному лицу успешно отправлено!', function () {
                    });
                } else {
                    makeInformer('danger', 'Неуспешно', 'Письмо контактному лицу отправить не удалось!');
                }
            }
        }
    }
    if (answer.status === 3) {
        makeInformer('warning', "Не вышло", "Проверьте подключение к интернету, отправка не удалась")
    } else if (answer.status === 4) {
        makeInformer('warning', "Не вышло", "Ни у владельца ни у контактного лица не указан адрес электронной почты")
    }
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
    let modal = makeModal("Сумма внесения", '<div class="input-group"><input id="toDepositSumm" type="number" class="form-control"><div class="input-group-btn"><button id="sendDataBtn" type="button" class="btn btn-default" disabled="">Зачислить</button> </div></div>');
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
    }
}

function basementFunctional() {
    // расчитаю пени
    let countFinesActivator = $('#finesSumm');
    countFinesActivator.on('click.count', function (e) {
        e.preventDefault();
        sendAjax('get', '/fines/count/' + cottageNumber, showFines);
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
        sendAjax('post', '/power/cancel-previous/' + cottageNumber, pCallback);
    });
    // отменю внесённые показания по электричеству
    let caBtn = $('button#cancelFillAdditionalPower');
    caBtn.on('click.cancel', function () {
        sendAjax('post', '/power/cancel-previous/additional/' + cottageNumber, pCallback);
    });

    function pCallback(data) {
        if (data.status === 1) {
            makeInformerModal('Успешно', 'Сведения о потреблённой электроэнергии успешно удалены');
        } else
            makeInformer('warning', 'Неудачно', 'Операция не удалась. Возможно, не заполнены покаания за месяц или месяц уже оплачен или есть неоплаченные счета по этому участку.')
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
            url = "/payment/get-form/single-double/" + cottageNumber;
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
            let frm = modal.find('form');
            modal.find('.popovered').popover({'trigger': 'hover', 'html': true});
            $('body').append("<div class='flyingSumm'><p>Долг: <b class='text-danger totalDebtSumm'>0</b>&#8381;</p><p>К оплате: <b class='text-danger totalPaymentSumm'>0</b>&#8381;</p><p class='hidden'>Скидка: <b class='text-danger discountSumm'>0</b>&#8381;</p><p class='hidden'>С депозита: <b class='text-danger fromDepositSumm'>0</b>&#8381;</p><p class='hidden'>Итого: <b class='text-danger recalculatedSumm'>0</b>&#8381;</p><p>Останется: <b class='text-danger leftPaymentSumm'>0</b>&#8381;</p><p><button class='btn btn-success sendFormBtn'>Создать</button></p></div>");
            const memberPeriods = $('input#complexpayment-membershipperiods, input#complexpaymentdouble-membershipperiods');
            const memberAdditionalPeriods = $('input#complexpayment-additionalmembershipperiods, input#complexpaymentdouble-additionalmembershipperiods');
            const powerPeriods = $('input#complexpayment-powerperiods, input#complexpaymentdouble-powerperiods');
            const additionalPowerPeriods = $('input#complexpayment-additionalpowerperiods, input#complexpaymentdouble-additionalpowerperiods');
            const countedSumm = $('input#complexpayment-countedsumm, input#complexpaymentdouble-countedsumm');
            modal.on('hidden.bs.modal', function () {
                $('div.flyingSumm').remove();
            });
            let sendBtn = $('button.sendFormBtn');
            let totalDebt = $('b.totalDebtSumm');
            let totalPayment = $('b.totalPaymentSumm');
            let leftSumm = $('b.leftPaymentSumm');
            let globalSumm = toRubles($('span#paySumm').text());
            let discountSummInput = $('input#complexpayment-discount, input#complexpaymentdouble-discount');
            let depositSummInput = $('input#complexpayment-fromdeposit, input#complexpaymentdouble-fromdeposit');
            let discountSumm = $('b.discountSumm');
            let depositSumm = $('b.fromDepositSumm');
            let finesSumm = 0;

            let finesActivators = $('.fines-item');
            finesActivators.on('click.count', function () {
                let summ = toRubles($(this).attr('data-summ'));
                if($(this).prop('checked')){
                    finesSumm += summ;
                }
                else{
                    finesSumm -= summ;
                }
            });

            let recalculatedSumm = $('b.recalculatedSumm');
            const useDiscountBtn = modal.find('button#useDiscountBtn');
            const discountInput = modal.find('input#complexpayment-discount, input#complexpaymentdouble-discount');
            const discountReason = modal.find('textarea#discountReason');

            const useDepositBtn = modal.find('button#useDepositBtn');
            const depositInput = modal.find('input#complexpayment-fromdeposit, input#complexpaymentdouble-fromdeposit');
            const depositWholeSumm = toRubles(modal.find('span#deposit').text());
            let useDiscount = false;
            let additionalSumm = 0;
            totalDebt.text(globalSumm + additionalSumm);
            leftSumm.text(globalSumm + additionalSumm);
            let powerSumm = 0;
            let memSumm = 0;
            let targetSumm = 0;
            let simpleSumm = 0;

            let noPowerLimitBtn = modal.find('button.no-limit');
            let noLimitInput = modal.find('input#complexpayment-nolimitpower, input#complexpaymentdouble-nolimitpower');
            let noLimitAdditionalInput = modal.find('input#complexpayment-nolimitadditionalpower, input#complexpaymentdouble-nolimitadditionalpower');
            noPowerLimitBtn.on('click.removeLimit', function (e) {
                let oldSumm = toRubles($(this).parents('div.power-container').eq(0).attr('data-summ'));
                e.stopPropagation();
                if ($(this).hasClass('main')) {
                    noLimitInput.val(noLimitInput.val() + $(this).attr('data-month') + ' ');
                } else {

                    noLimitAdditionalInput.val(noLimitAdditionalInput.val() + $(this).attr('data-month') + ' ');
                }
                $(this).popover('destroy');
                disableElement($(this));
                // пересчитаю электроэнергию по максимальному тарифу
                let diff = parseInt($(this).attr('data-difference'));
                let overCost = toRubles($(this).attr('data-overcost'));
                let fullCost = toRubles(diff * overCost);
                $(this).parents('div.power-container').eq(0).attr('data-summ', fullCost).find('b').html(fullCost + ' &#8381;').attr('data-content', 'Принудительная оплата без учёта льготного лимита<br>Потрачено электроэнергии- <b class=\'text-info\'> ' + diff + ' </b><br/>Цена киловатта- <b class=\'text-info\'>' + overCost + '</b> &#8381; ');
                globalSumm = toRubles(globalSumm + fullCost - oldSumm);
                totalDebt.text(globalSumm);
                if (powerSumm > 0)
                    powerSumm += fullCost - oldSumm;
                recalculateSumm();
            });
            sendBtn.on('click.send', function () {
                // если указана сумма платежа- отправляю форму на сохранение
                if (depositInput.hasClass('failed') || discountInput.hasClass('failed')) {
                    makeInformer('danger', 'Ошибка', 'Что-то не так с данными о скидке или депозите! Проверьте правильность ввода');
                    return false;
                }
                if (countedSumm.val() > 0) {
                    sendAjax('post', '/payment/complex/save', callback, frm[0], true);

                    function callback(answer) {
                        if (answer.status === 1) {
                            modal.modal('hide');
                            modal.on('hidden.bs.modal', function () {
                                makeInformer('success', 'Счёт создан', 'Теперь нужно выбрать дальнейшее действие');
                                editBill(answer['billId'], answer['double']);
                            });
                        } else if (answer.status === 2) {
                            makeInformer('danger', "Ошибка во время оплаты", answer['errors']);
                        } else {
                            makeInformer('danger', "Ошибка во время оплаты", 'Произошла неизвестная ошибка, попробуйте ещё раз');
                        }
                    }
                } else {
                    makeInformer('danger', 'Сохранение', 'Выберите что-то для сохранения платежа');
                }
            });

            function recalculateSumm() {

                let discount = isSumm(discountSummInput.val());
                let deposit = isSumm(depositSummInput.val());
                let summ = powerSumm + memSumm + targetSumm + simpleSumm + finesSumm;
                console.log(summ);
                totalPayment.text(toRubles(summ));
                leftSumm.text(toRubles(globalSumm - summ + additionalSumm));
                countedSumm.val(toRubles(summ));
                if (discount && deposit) {
                    if (discount + deposit <= summ) {
                        depositSumm.parents('p').eq(0).removeClass('hidden');
                        discountSumm.parents('p').eq(0).removeClass('hidden');
                        recalculatedSumm.parents('p').eq(0).removeClass('hidden');
                        discountSumm.text(discount);
                        depositSumm.text(deposit);
                        console.log(summ - discount - deposit);
                        recalculatedSumm.text(toRubles((summ - discount) - deposit));
                    } else {
                        // сообщение об ошибке и сбрасываю поля ввода
                        makeInformer('danger', 'Ошибка', 'Сумма скидки и депозита не может быть больше суммы платежа! Придётся заполнить их снова!');
                        depositSumm.parents('p').eq(0).addClass('hidden');
                        discountSumm.parents('p').eq(0).addClass('hidden');
                        recalculatedSumm.parents('p').eq(0).addClass('hidden');
                        discountSumm.text(0);
                        depositSumm.text(0);
                        recalculatedSumm.text(0);
                        useDepositBtn.trigger('click');
                        useDiscountBtn.trigger('click');
                    }
                } else if (discount) {
                    if (discount <= summ) {
                        discountSumm.parents('p').eq(0).removeClass('hidden');
                        recalculatedSumm.parents('p').eq(0).removeClass('hidden');
                        discountSumm.text(discount);
                        recalculatedSumm.text(toRubles(summ - discount));
                    } else {
                        discountSumm.parents('p').eq(0).addClass('hidden');
                        recalculatedSumm.parents('p').eq(0).addClass('hidden');
                        discountSumm.text(0);
                        recalculatedSumm.text(0);
                    }
                } else if (deposit) {
                    if (deposit <= summ) {
                        depositSumm.parents('p').eq(0).removeClass('hidden');
                        recalculatedSumm.parents('p').eq(0).removeClass('hidden');
                        depositSumm.text(deposit);
                        recalculatedSumm.text(toRubles(summ - deposit));
                    } else {
                        depositSumm.parents('p').eq(0).addClass('hidden');
                        recalculatedSumm.parents('p').eq(0).addClass('hidden');
                        depositSumm.text(0);
                        recalculatedSumm.text(0);
                    }
                } else {
                    discountSumm.parents('p').eq(0).addClass('hidden');
                    depositSumm.parents('p').eq(0).addClass('hidden');
                    discountSumm.text(0);
                    depositSumm.text(0);
                    recalculatedSumm.parents('p').eq(0).addClass('hidden');
                }
            }
            recalculateSumm();

            // ОБРАБОТКА СКИДКИ ==================================================================================
            handleCashInput(discountInput);
            discountInput.on('input.checkDiscout, blur.checkDiscount', function () {
                let summ = isSumm($(this).val());
                if (summ) {
                    if (summ > toRubles(totalPayment.eq(0).text()) || summ + isSumm(depositSumm.eq(0).text()) > isSumm(totalPayment.eq(0).text())) {
                        makeInputWrong($(this));
                        makeInformer('danger', 'Ошибка', 'Сумма скидки не может превышать сумму платежа!');
                    } else {
                        recalculateSumm();
                    }
                }

            });

            useDiscountBtn.on('click.switch', function () {
                if (totalPayment.text() && toRubles(totalPayment.text()) > 0) {
                    if (useDiscount) {
                        $(this).text("Использовать скидку.").addClass('btn-success').removeClass('btn-danger');
                        discountInput.prop('disabled', true).val('').removeClass('failed');
                        discountReason.prop('disabled', true).val('');
                        recalculateSumm();
                        useDiscount = false;
                    } else {
                        // скидка не используется.
                        $(this).text("Не использовать скидку.").removeClass('btn-success').addClass('btn-danger');
                        discountInput.prop('disabled', false);
                        discountReason.prop('disabled', false);
                        discountInput.focus();
                        useDiscount = true;
                    }
                } else {
                    makeInformer('danger', 'Рано!', 'Сначала выберите что-то для оплаты');
                }
            });
            // ОБРАБОТКА ОПЛАТЫ С ДЕПОЗИТА ==================================================================================
            if (depositSumm === 0) {
                disableElement(useDepositBtn, "на депозите нет средств");
            }
            let useDeposit = false;
            handleCashInput(depositInput);
            depositInput.on('input.checkDeposit, blur.checkDiscount', function () {
                let summ;
                if (summ = isSumm($(this).val())) {
                    if (summ > toRubles(totalPayment.eq(0).text() - toRubles(discountSumm)) || summ + isSumm(discountSumm.eq(0).text()) > isSumm(totalPayment.eq(0).text())) {
                        makeInputWrong($(this));
                        makeInformer('danger', 'Ошибка', 'Использование депозита не может превышать сумму платежа!');
                    } else if (summ > depositWholeSumm) {
                        makeInputWrong($(this));
                        makeInformer('danger', 'Ошибка', 'На депозите нет таких денег!');
                    } else {
                        recalculateSumm();
                    }
                }

            });

            useDepositBtn.on('click.switch', function () {
                if (totalPayment.text() && toRubles(totalPayment.text()) > 0) {
                    if (useDeposit) {
                        $(this).text("Использовать депозит.").addClass('btn-success').removeClass('btn-danger');
                        depositInput.prop('disabled', true).val('').removeClass('failed');
                        recalculateSumm();
                        useDeposit = false;
                    } else {
                        // скидка не используется.
                        $(this).text("Не использовать депозит.").removeClass('btn-success').addClass('btn-danger');
                        depositInput.prop('disabled', false);
                        depositInput.focus();
                        useDeposit = true;
                        // добавляю в строку поиска максимальное возможное значение
                        let summ = countedSumm.val();
                        if (summ < depositWholeSumm) {
                            depositInput.val(summ);
                            depositInput.trigger('blur');
                        } else {
                            depositInput.val(depositWholeSumm);
                            depositInput.trigger('blur');
                        }
                        recalculateSumm();
                    }
                } else {
                    makeInformer('danger', 'Рано!', 'Сначала выберите что-то для оплаты');
                }
            });
            // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА ЭЛЕКТРОЭНЕРГИЮ ==================================================================================
            let powerPayAllBtn = modal.find('div#powerCollector button.pay-all');
            let powerPayNothingBtn = modal.find('div#powerCollector button.pay-nothing');
            let powerParts = modal.find('div.power-container.main');
            let additionalPowerParts = modal.find('div.power-container.additional');

            // частичная оплата по клику
            function payPowerToClick(parts, input, additional) {
                parts.hover(function () {
                    $(this).prevAll('div').addClass('choosed');
                    // рассчитаю сумму оплаты
                }, function () {
                    $(this).prevAll('div').removeClass('choosed');
                });
                parts.on('click.summ', function () {
                    // помечаю этот элемент и все ранее выбранные, как готовые для оплаты
                    $(this).prevAll('div').addClass('selected');
                    $(this).addClass('selected');
                    parts.removeClass('hoverable choosed');
                    let summ = 0;
                    let counter = 1;
                    $(this).prevAll().each(function () {
                        let s;
                        if (s = $(this).attr('data-summ')) {
                            ++counter;
                            summ += toRubles(s);
                        }
                    });
                    input.val(counter);
                    summ += toRubles($(this).attr('data-summ'));
                    parts.unbind('mouseenter mouseleave');
                    parts.off('click.summ');
                    powerSumm += summ;
                    recalculateSumm();
                    if (additional) {
                        enableElement(powerPayNothingBtn.filter('.additional'));
                        disableElement(powerPayAllBtn.filter('.additional'));
                    } else {
                        enableElement(powerPayNothingBtn.filter('.main'));
                        disableElement(powerPayAllBtn.filter('.main'));
                    }

                });
            }

            payPowerToClick(powerParts, powerPeriods);
            payPowerToClick(additionalPowerParts, additionalPowerPeriods, true);
            powerPayAllBtn.on('click.all', function () {
                // отмечу все платежи за электроэнергию как оплачиваемые
                disableElement($(this));
                enableElement($(this).parent().find('button.pay-nothing'));
                if ($(this).hasClass('main')) {
                    powerParts.addClass('selected').removeClass('hoverable choosed');
                    // считаю общую сумму платежей за электричество и выношу её в общее значение
                    powerParts.each(function () {
                        powerSumm += toRubles($(this).attr('data-summ'));
                    });
                    powerParts.unbind('mouseenter mouseleave');
                    powerParts.off('click.summ');
                    recalculateSumm();
                    powerPeriods.val(powerParts.length);
                } else {
                    additionalPowerParts.addClass('selected').removeClass('hoverable choosed');
                    // считаю общую сумму платежей за электричество и выношу её в общее значение
                    additionalPowerParts.each(function () {
                        powerSumm += toRubles($(this).attr('data-summ'));
                    });
                    additionalPowerParts.unbind('mouseenter mouseleave');
                    additionalPowerParts.off('click.summ');
                    recalculateSumm();
                    additionalPowerPeriods.val(additionalPowerParts.length);
                }
            });
            powerPayNothingBtn.on('click.nothing', function () {
                disableElement($(this));
                enableElement($(this).parent().find('button.pay-all'));
                if ($(this).hasClass('main')) {
                    // отмечу все платежи за электроэнергию как оплачиваемые
                    // считаю общую сумму платежей за электричество и выношу её в общее значение
                    let selected = powerParts.filter('.selected');
                    selected.each(function () {
                        powerSumm -= toRubles($(this).attr('data-summ'));
                    });
                    powerParts.removeClass('choosed selected').addClass('hoverable');
                    payPowerToClick(powerParts, powerPeriods);
                    recalculateSumm();
                    powerPeriods.val(0);
                } else {
                    // отмечу все платежи за электроэнергию как оплачиваемые
                    // считаю общую сумму платежей за электричество и выношу её в общее значение
                    let selected = additionalPowerParts.filter('.selected');
                    selected.each(function () {
                        powerSumm -= toRubles($(this).attr('data-summ'));
                    });
                    additionalPowerParts.removeClass('choosed selected').addClass('hoverable');
                    payPowerToClick(additionalPowerParts, additionalPowerPeriods);
                    recalculateSumm();
                    powerPeriods.val(0);
                }
            });
            // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА ЧЛЕНСКИЕ ВЗНОСЫ ==================================================================================
            let addQuartersInput = modal.find('input#addFutureQuarters');
            let addQuartersAdditionalInput = modal.find('input#addAddtionalFutureQuarters');
            let memPayAllBtn = modal.find('div#membershipCollector button.pay-all');
            let memPayNothingBtn = modal.find('div#membershipCollector button.pay-nothing');
            let memParts = modal.find('div.membership-container.main');
            let memAdditionalParts = modal.find('div.membership-container.additional');
            let futureDiv = modal.find('div#forFutureQuarters');
            let additionalFutureDiv = modal.find('div#forAdditionalFutureQuarters');

            // частичная оплата по клику
            function payMembershipToClick(parts, input, additional) {
                parts.hover(function () {
                    $(this).prevAll('div').addClass('choosed');
                    // рассчитаю сумму оплаты
                }, function () {
                    $(this).prevAll('div').removeClass('choosed');
                });
                parts.on('click.summ', function () {
                    // помечаю этот элемент и все ранее выбранные, как готовые для оплаты
                    $(this).prevAll('div').addClass('selected');
                    $(this).addClass('selected');
                    parts.removeClass('hoverable choosed');
                    let summ = 0;
                    let counter = 1;
                    $(this).prevAll().each(function () {
                        let s;
                        if (s = $(this).attr('data-summ')) {
                            ++counter;
                            summ += toRubles(s);
                        }
                    });
                    input.val(counter);
                    summ += toRubles($(this).attr('data-summ'));
                    parts.unbind('mouseenter mouseleave');
                    parts.off('click.summ');
                    memSumm += summ;
                    recalculateSumm();
                    if (additional) {
                        enableElement(memPayNothingBtn.filter('.additional'));
                        disableElement(memPayAllBtn.filter('.additional'));
                    } else {
                        enableElement(memPayNothingBtn.filter('.main'));
                        disableElement(memPayAllBtn.filter('.main'));
                    }
                });
            }

            payMembershipToClick(memParts, memberPeriods);
            payMembershipToClick(memAdditionalParts, memberAdditionalPeriods, true);

            memPayAllBtn.on('click.all', function () {
                disableElement($(this));
                enableElement($(this).parent().find('button.pay-nothing'));
                if ($(this).hasClass('main')) {
                    // сброшу количество дополнительных кварталов
                    addQuartersInput.val('');
                    futureDiv.text('');
                    additionalSumm -= futureDiv.attr('data-additional-summ');
                    futureDiv.attr('data-additional-summ', 0);
                    // отмечу все платежи  как оплачиваемые
                    memParts.addClass('selected').removeClass('hoverable choosed');
                    // считаю общую сумму платежей за электричество и выношу её в общее значение
                    memParts.each(function () {
                        memSumm += toRubles($(this).attr('data-summ'));
                    });
                    memParts.unbind('mouseenter mouseleave');
                    memParts.off('click.summ');
                    memberPeriods.val(memParts.length);
                } else {
                    // сброшу количество дополнительных кварталов
                    addQuartersAdditionalInput.val('');
                    futureDiv.text('');
                    additionalSumm -= additionalFutureDiv.attr('data-additional-summ');
                    additionalFutureDiv.attr('data-additional-summ', 0);
                    // отмечу все платежи  как оплачиваемые
                    memAdditionalParts.addClass('selected').removeClass('hoverable choosed');
                    // считаю общую сумму платежей за электричество и выношу её в общее значение
                    memAdditionalParts.each(function () {
                        memSumm += toRubles($(this).attr('data-summ'));
                    });
                    memAdditionalParts.unbind('mouseenter mouseleave');
                    memAdditionalParts.off('click.summ');
                    memberAdditionalPeriods.val(memAdditionalParts.length);
                }
                recalculateSumm();
            });
            memPayNothingBtn.on('click.nothing', function () {
                disableElement($(this));
                enableElement($(this).parent().find('button.pay-all'));

                if ($(this).hasClass('main')) {
                    // сброшу количество дополнительных кварталов
                    addQuartersInput.val('');
                    futureDiv.text('');
                    memSumm -= futureDiv.attr('data-additional-summ');
                    additionalSumm -= futureDiv.attr('data-additional-summ');
                    futureDiv.attr('data-additional-summ', 0);
                    let selected = memParts.filter('.selected');
                    selected.each(function () {
                        memSumm -= toRubles($(this).attr('data-summ'));
                    });
                    memParts.removeClass('choosed selected').addClass('hoverable');
                    payMembershipToClick(memParts, memberPeriods);
                    recalculateSumm();
                    memberPeriods.val(0);
                } else {
                    // сброшу количество дополнительных кварталов
                    addQuartersAdditionalInput.val('');
                    additionalFutureDiv.text('');
                    additionalSumm -= additionalFutureDiv.attr('data-additional-summ');
                    memSumm -= additionalFutureDiv.attr('data-additional-summ');
                    additionalFutureDiv.attr('data-additional-summ', 0);
                    let selected = memAdditionalParts.filter('.selected');
                    selected.each(function () {
                        memSumm -= toRubles($(this).attr('data-summ'));
                    });
                    memAdditionalParts.removeClass('choosed selected').addClass('hoverable');
                    payMembershipToClick(memAdditionalParts, memberAdditionalPeriods, true);
                    recalculateSumm();
                    memberAdditionalPeriods.val(0);
                }
            });
            // оплата дополнительных кварталов
            addQuartersInput.on('input.add', function () {
                if ($(this).val() > 0) {
                    sendAjax('get', '/get/future-quarters/' + $(this).val() + "/" + cottageNumber, callback);

                    function callback(answer) {
                        if (answer.status === 2) {
                            // если не заполнены тарифы- открою окно для заполнения
                            if (tariffsFillWindow)
                                tariffsFillWindow.close();
                            makeNewWindow('/fill/membership/' + answer['lastQuarterForFilling'], tariffsFillWindow, fillCallback);

                            function fillCallback() {
                                addQuartersAdditionalInput.trigger('input');
                            }
                        } else if (answer.status === 3) {
                            if (tariffsFillWindow)
                                tariffsFillWindow.close();
                            // если не заполнены тарифы- открою окно для заполнения
                            makeNewWindow('/fill/membership-personal/' + cottageNumber + '/' + answer['lastQuarterForFilling'], tariffsFillWindow, callback);

                            function callback() {
                                addQuartersInput.trigger('input');
                            }
                        } else if (answer.status === 1) {
                            enableElement(memPayAllBtn.filter('.main'));
                            enableElement(memPayNothingBtn.filter('.main'));
                            futureDiv.html(answer['content']);
                            let selected = memParts.filter('.selected');
                            selected.each(function () {
                                memSumm -= toRubles($(this).attr('data-summ'));
                            });
                            let futureQuarters = futureDiv.find('div.membership-container');
                            futureDiv.find('.popovered').popover({'trigger': 'hover', 'html': true});
                            memParts.addClass('selected').removeClass('hoverable choosed');
                            futureQuarters.addClass('selected').removeClass('hoverable choosed');
                            memParts.each(function () {
                                memSumm += toRubles($(this).attr('data-summ'));
                            });
                            // добавлю к общей сумме платежа то, что прилетело
                            memSumm += answer['totalSumm'];
                            futureDiv.attr('data-additional-summ', answer['totalSumm']);
                            memParts.unbind('mouseenter mouseleave');
                            memParts.off('click.summ');
                            additionalSumm += answer['totalSumm'];
                            memberPeriods.val(memParts.length + futureQuarters.length);
                            recalculateSumm();
                        }
                    }
                }
            });
            // оплата дополнительных кварталов
            addQuartersAdditionalInput.on('input.add', function () {
                if ($(this).val() > 0) {
                    sendAjax('get', '/get/future-quarters/additional/' + $(this).val() + "/" + cottageNumber, callback);

                    function callback(answer) {
                        if (answer.status === 2) {
                            // если не заполнены тарифы- открою окно для заполнения
                            if (tariffsFillWindow)
                                tariffsFillWindow.close();
                            makeNewWindow('/fill/membership/' + answer['lastQuarterForFilling'], tariffsFillWindow, fillCallback);

                            function fillCallback() {
                                console.log('callback');
                                addQuartersAdditionalInput.trigger('input');
                            }
                        } else if (answer.status === 3) {
                            if (tariffsFillWindow)
                                tariffsFillWindow.close();
                            // если не заполнены тарифы- открою окно для заполнения
                            makeNewWindow('/fill/membership-personal-additional/' + cottageNumber + '/' + answer['lastQuarterForFilling'], tariffsFillWindow, callback);

                            function callback() {
                                console.log('callback personal');
                                addQuartersAdditionalInput.trigger('input');
                            }
                        } else if (answer.status === 1) {
                            enableElement(memPayAllBtn.filter('.additional'));
                            enableElement(memPayNothingBtn.filter('.additional'));
                            additionalFutureDiv.html(answer['content']);
                            let selected = memAdditionalParts.filter('.selected');
                            selected.each(function () {
                                memSumm -= toRubles($(this).attr('data-summ'));
                            });
                            let futureQuarters = additionalFutureDiv.find('div.membership-container');
                            additionalFutureDiv.find('.popovered').popover({'trigger': 'hover', 'html': true});
                            memAdditionalParts.addClass('selected').removeClass('hoverable choosed');
                            futureQuarters.addClass('selected').removeClass('hoverable choosed');
                            memAdditionalParts.each(function () {
                                memSumm += toRubles($(this).attr('data-summ'));
                            });
                            // добавлю к общей сумме платежа то, что прилетело
                            memSumm += answer['totalSumm'];
                            additionalFutureDiv.attr('data-additional-summ', answer['totalSumm']);
                            memAdditionalParts.unbind('mouseenter mouseleave');
                            memAdditionalParts.off('click.summ');
                            additionalSumm += answer['totalSumm'];
                            memberAdditionalPeriods.val(memAdditionalParts.length + futureQuarters.length);
                            recalculateSumm();
                        }
                    }
                }
            });
            // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА ЦЕЛЕВЫЕ ВЗНОСЫ ==================================================================================
            // при нажатии на кнопку полной оплаты целевого или разового взноса- заполню поле ввода максимальной суммой
            let fullFillBtns = modal.find('button.btn-pay-all');
            fullFillBtns.on('click.fill', function () {
                let input = modal.find('input#' + $(this).attr('data-for'));
                input.val(input.attr('data-max-summ'));
                input.trigger('change');
            });
            // пересчитываю данные при введении суммы оплаты целевого платежа
            let targetInputs = modal.find('input.target-pay');
            targetInputs.on('change.fill', function () {
                if ($(this).val()) {
                    // если введено верное значение
                    let limit = toRubles($(this).attr('data-max-summ'));
                    let val = toRubles($(this).val());
                    if (/^\d+[,.]?\d{0,2}$/.test($(this).val()) && val <= limit) {
                        targetSumm = 0;
                        // получаю сумму всех полей ввода целевых платежей
                        targetInputs.each(function () {
                            if ($(this).val()) {
                                targetSumm += toRubles($(this).val());
                            }
                        });
                        recalculateSumm();
                    } else {
                        $(this).focus();
                        makeInformer('danger', "Ошибка", "Неверное значение!");
                    }
                }
            });
            let targetPayAllBtn = modal.find('div#targetCollector button.pay-all');
            let targetPayNothingBtn = modal.find('div#targetCollector button.pay-nothing');

            targetPayAllBtn.on('click.all', function () {
                enableElement($(this).parent().find('button.pay-nothing'));
                disableElement($(this));
                $(this).parents('div.target-container').eq(0).find('input.target-pay').each(function () {
                    let summ = toRubles($(this).attr('data-max-summ'));
                    $(this).val(summ);
                    targetSumm += summ;
                });
                recalculateSumm();
            });
            targetPayNothingBtn.on('click.nothing', function () {
                enableElement($(this).parent().find('button.pay-all'));
                disableElement($(this));
                $(this).parents('div.target-container').eq(0).find('input.target-pay').each(function () {
                    $(this).val(0);
                    $(this).trigger('change');
                });
                recalculateSumm();
            });
            // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА РАЗОВЫЕ ВЗНОСЫ ==================================================================================
            // пересчитываю данные при введении суммы оплаты целевого платежа
            let singleInputs = modal.find('input.single-pay');
            singleInputs.on('change.fill', function () {
                if ($(this).val()) {
                    // если введено верное значение
                    let limit = toRubles($(this).attr('data-max-summ'));
                    let val = toRubles($(this).val());
                    if (/^\d+[,.]?\d{0,2}$/.test($(this).val()) && val <= limit) {
                        simpleSumm = 0;
                        // получаю сумму всех полей ввода целевых платежей
                        singleInputs.each(function () {
                            if ($(this).val()) {
                                simpleSumm += toRubles($(this).val());
                            }
                        });
                        recalculateSumm();
                    } else {
                        $(this).focus();
                    }
                }
            });

            let simplePayAllBtn = modal.find('div#simpleCollector button.pay-all');
            let simplePayNothingBtn = modal.find('div#simpleCollector button.pay-nothing');

            simplePayAllBtn.on('click.all', function () {
                enableElement(simplePayNothingBtn);
                disableElement(simplePayAllBtn);
                simpleSumm = 0;
                singleInputs.each(function () {
                    let summ = toRubles($(this).attr('data-max-summ'));
                    $(this).val(summ);
                    simpleSumm += summ;
                });
                recalculateSumm();
            });
            simplePayNothingBtn.on('click.nothing', function () {
                enableElement(simplePayAllBtn);
                disableElement(simplePayNothingBtn);
                simpleSumm = 0;
                singleInputs.each(function () {
                    $(this).val('');
                });
                recalculateSumm();
            });
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
                let i = 0;
                while (answer['data'][i]) {
                    let simple = answer['data'][i];
                    let payed = simple['isPartialPayed'] ? '<button class="btn btn-info">Частично оплачен</button>' : (simple['isPayed'] ? '<button class="btn btn-success">Завершен</button>' : '<button class="btn btn-warning">В ожидании оплаты</button>');
                    if (simple['isPayed'] || simple['isPartialPayed']) {
                        let summ;
                        if (simple['isPartialPayed']) {
                            summ = ' Оплачено ' + simple['payed-summ'] + ' из ' + simple['summ'] + ' ';
                        } else {
                            if (simple['payed-summ'] >= simple['summ']) {
                                summ = '<b class="text-success">' + simple['summ'] + ' &#8381;</b> Оплачено полностью ';
                            } else {
                                summ = '<b class="text-danger">' + simple['summ'] + ' &#8381;</b> Не оплачено ';
                            }
                        }
                        /*let summ = simple['payed-summ'] ? ' Оплачено ' + simple['payed-summ'] + ' из ' + simple['summ'] : '<b class="text-warning">' + simple['summ'] + ' &#8381;</b> Не оплачен. ';*/
                        modalBody.append('<p class="hoverable" data-payment-id="' + simple['id'] + '">Платёж № <b class="text-info">' + simple['id'] + '</b>, сумма: ' + summ + payed + ' ' + simple['paymentTime'] + '</p>');
                    } else {
                        modalBody.append('<p class="hoverable" data-payment-id="' + simple['id'] + '">Платёж № <b class="text-info">' + simple['id'] + '</b>, сумма: <b class="text-warning">' + simple['summ'] + ' &#8381;</b>. ' + payed + ' ' + simple['paymentTime'] + '</p>');
                    }
                    i++;
                }
                modal.find('p.hoverable').on('click.show', function () {
                    let id = $(this).attr('data-payment-id');
                    modal.modal('hide');
                    modal.on('hidden.bs.modal', function () {
                        editBill(id, double);
                    });
                })
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
            let modal = makeModal('Информация о счёте', answer['view']);
            // Обработаю использование депозита
            //const totalPaymentSumm = modal.find('span#paymentTotalSumm');
            const deleteBillBtn = $('button#deleteBill');
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
                    makeInformer('info', 'Повторное открытие счёта', 'Сообщите мне о том, что увидели это сообщение при попытке открытия счёта');
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
                    sendAjax('get', url, payConfimCallback);

                    function payConfimCallback(answer) {
                        if (answer.status === 1) {
                            let newModal = makeModal('Подтверждение оплаты', answer['view']);
                            let frm = newModal.find('form');
                            let rawSumm = newModal.find('input#pay-rawsumm');
                            let summToPay = toRubles(newModal.find('#paySumm').attr('data-summ'));
                            let discountSumm = toRubles(newModal.find('#paySumm').attr('data-discount'));
                            let depositSumm = toRubles(newModal.find('#paySumm').attr('data-deposit'));
                            let payedBefore = toRubles(newModal.find('#paySumm').attr('data-payed-before'));
                            let change = newModal.find('span#change');
                            let changeInput = newModal.find('input#pay-change');
                            let toDepositBtn = newModal.find('input#pay-changetodeposit');
                            let roundSummGetBtn = newModal.find('span#roundSummGet');
                            let toDepositInput = newModal.find('input#pay-todeposit');
                            let allToDeposit = newModal.find('button#allChangeToDepositBtn');
                            const paymentDetailsParts = newModal.find('div.payment-details');
                            const undistributedWindow = $('<div class="left-float-window hidden">Не распределено<br/><b id="undistributedSumm"></b>&#8381;<br/><div class="btn-group-vertical"><button type="button" class="btn btn-warning" id="resetDistributeActivator">Сбросить</button><button type="button" class="btn btn-success" id="savePayActivator">Сохранить</button></div></div>');
                            $('body').append(undistributedWindow);
                            newModal.on('hidden.bs.modal', function () {
                                undistributedWindow.remove();
                            });
                            const undistributedSummContainer = undistributedWindow.find('#undistributedSumm');
                            // кнопка распределения всех доступных средств в категорию
                            const distributeAllActivator = $('.all-distributed-button');
                            const distributeSummInputs = $('input.distributed-summ-input');
                            const resetDistributeActivator = $('#resetDistributeActivator');
                            const savePayActivator = $('#savePayActivator');
                            savePayActivator.on('click.send', function () {
                                frm.trigger('submit');
                            });
                            // кнопка полной оплаты счёта
                            const fullPayRadio = newModal.find('input[type="radio"][name="Pay[payWholeness]"][value="full"]');
                            // кнопка частичной оплаты счёта
                            const partialPayRadio = newModal.find('input[type="radio"][name="Pay[payWholeness]"][value="partial"]');

                            function fillDividedSegment(fieldId, fieldVal) {
                                newModal.find('input#' + fieldId).val(fieldVal);
                            }

                            distributeAllActivator.on('click.distribute', function () {
                                let undistributedSumm = toRubles(undistributedSummContainer.text());
                                // найду полную сумму, необходимую для оплаты категории
                                let parent = $(this).parents('div.payment-details').eq(0);
                                let fullSumm = toRubles(parent.attr('data-summ'));
                                let payedBeforeSumm = toRubles(parent.attr('data-payed'));
                                if (fullSumm === payedBeforeSumm) {
                                    makeInformer('info', 'Нет смысла', 'Вся сумма уже оплачена ранее');
                                    return;
                                }
                                let neededSumm = toRubles(fullSumm - payedBeforeSumm);
                                let summInput = parent.find('input.distributed-summ-input').eq(0);
                                if (neededSumm >= undistributedSumm) {
                                    // заполняю поле ввода всей доступной суммой
                                    summInput.val(undistributedSumm);
                                    summInput.trigger('input');
                                    summInput.trigger('change');
                                    summInput.trigger('blur');
                                } else {
                                    summInput.val(neededSumm);
                                    summInput.trigger('input');
                                    summInput.trigger('change');
                                    summInput.trigger('blur');
                                }
                            });

                            resetDistributeActivator.on('click.reset', function () {
                                // сброшу введённую сумму
                                rawSumm.val(0).prop('readonly', false);
                                // удалю все отметки об оплаченных периодах
                                $('.divided-pay-informer').remove();
                                undistributedSummContainer.text(0);
                                distributeSummInputs.val('').prop('readonly', false);
                                partialPayRadio.prop('checked', false);
                                newModal.find('input.divided-input').val(0);
                            });
                            distributeSummInputs.on('change.distribute', function () {
                                let undistributedSumm = toRubles(undistributedSummContainer.text());
                                let parent = $(this).parents('div.payment-details').eq(0);
                                let fullSumm = toRubles(parent.attr('data-summ'));
                                let payedBeforeSumm = toRubles(parent.attr('data-payed'));
                                let paymentParts = parent.find('li');
                                let summ = toRubles($(this).val());
                                if (summ > undistributedSumm) {
                                    makeInformer('danger', 'Ошибка', 'Сумма не может быть больше нераспределённой суммы: ' + undistributedSumm + ' &#8381;');
                                    return;
                                }
                                if (summ <= fullSumm) {
                                    $(this).prop('readonly', true);
                                    undistributedSummContainer.text(undistributedSumm - summ);
                                    // пройду по списку периодов
                                    paymentParts.each(function () {
                                        let thisPeriodSumm = toRubles($(this).attr('data-summ'));
                                        // проверю, не оплачивался ли период ранее
                                        if (thisPeriodSumm <= payedBeforeSumm) {
                                            // считаю, что период полностью оплачен ранее
                                            payedBeforeSumm -= thisPeriodSumm;
                                            return;
                                        } else if (payedBeforeSumm > 0) {
                                            thisPeriodSumm -= payedBeforeSumm;
                                            payedBeforeSumm = 0;
                                        }
                                        if (thisPeriodSumm <= summ) {
                                            $(this).append(' <span class="btn btn-default divided-pay-informer"><b class="text-success">Оплачено полностью</b></span>');
                                            summ -= thisPeriodSumm;
                                            undistributedSumm -= thisPeriodSumm;
                                        } else if (summ > 0) {
                                            $(this).append(' <span class="btn btn-default divided-pay-informer"><b class="text-info">Оплачено частично, ' + toRubles(summ) + ' &#8381;</b></span>');
                                            summ = 0;
                                            undistributedSumm -= summ;
                                        } else {
                                            $(this).append(' <span class="btn btn-default divided-pay-informer"><b class="text-danger">Не оплачено</b></span>');
                                        }
                                    });
                                    switch ($(this).attr('id')) {
                                        case 'powerDistributed':
                                            fillDividedSegment('pay-power', $(this).val());
                                            break;
                                        case 'additionalPowerDistributed':
                                            fillDividedSegment('pay-additionalpower', $(this).val());
                                            break;
                                        case 'membershipDistributed':
                                            fillDividedSegment('pay-membership', $(this).val());
                                            break;
                                        case 'additionalMembershipDistributed':
                                            fillDividedSegment('pay-additionalmembership', $(this).val());
                                            break;
                                        case 'targetDistributed':
                                            fillDividedSegment('pay-target', $(this).val());
                                            break;
                                        case 'additionalTargetDistributed':
                                            fillDividedSegment('pay-additionaltarget', $(this).val());
                                            break;
                                        case 'singleDistributed':
                                            fillDividedSegment('pay-single', $(this).val());
                                            break;
                                    }
                                } else {
                                    makeInformer('danger', 'Ошибка', 'Сумма не может быть больше общей суммы оплаты категории: ' + fullSumm + ' &#8381;');
                                }
                            });

                            rawSumm.on('change.calculate', function () {
                                // проверю, заявлена ли полная или частичная оплата
                                let value = toRubles($(this).val());
                                if (value > 0 && value < summToPay) {
                                    // частичная оплата
                                    // помечу вариант оплаты как частичный
                                    partialPayRadio.click();
                                    $(this).prop('readonly', true);
                                } else if (value >= summToPay) {
                                    // полная оплата
                                    fullPayRadio.click();
                                }
                            });
                            fullPayRadio.on('change.select', function () {
                                // скрою поля детализации счёта
                                paymentDetailsParts.addClass('hidden');
                                undistributedWindow.addClass('hidden');
                                undistributedSummContainer.text(0);
                            });
                            partialPayRadio.on('change.select', function () {
                                // скрою поля детализации счёта
                                paymentDetailsParts.removeClass('hidden');
                                undistributedWindow.removeClass('hidden');
                                if (payedBefore) {
                                    undistributedSummContainer.text(toRubles(rawSumm.val()));
                                } else {
                                    undistributedSummContainer.text(toRubles(toRubles(rawSumm.val()) + discountSumm + depositSumm));
                                }
                            });
                            frm.on('submit', function (e) {
                                e.preventDefault();
                                const url = "/pay/confirm/" + identificator;
                                sendAjax('post', url, simpleAnswerHandler, frm, true);
                            });
                            roundSummGetBtn.on('click.all', function () {
                                rawSumm.val(summToPay);
                                fullPayRadio.click();
                                rawSumm.trigger('blur');
                            });
                            allToDeposit.on('click.allToDeposit', function () {
                                toDepositInput.val(change.attr('data-change'));
                                toDepositInput.trigger('blur');
                                change.text(0);
                                changeInput.val(0);
                            });
                            toDepositBtn.on('change.switch', function () {
                                if ($(this).prop('checked')) {
                                    // проверю, есть ли сдача
                                    if (!change.attr('data-change') || change.attr('data-change') === '0') {
                                        makeInformer('danger', 'ошибка', 'Для начисления на депозит сумма сдачи должна быть больше нуля!');
                                        $(this).prop('checked', false);
                                        return false;
                                    }
                                    allToDeposit.prop('disabled', false);
                                    toDepositInput.prop('disabled', false);
                                    allToDeposit.click();
                                    // если в окне суммы внесения на депозит какая-то сумма, вычитаю её из суммы сдачи
                                    if (toDepositInput.val()) {
                                        let depositSumm = toRubles(toDepositInput.val().replace(',', '.'));
                                        change.text(change.attr('data-change') - depositSumm);
                                        changeInput.val(change.attr('data-change') - depositSumm);
                                    }
                                } else {
                                    toDepositInput.prop('disabled', true).val(0);
                                    allToDeposit.prop('disabled', true);
                                    change.text(change.attr('data-change'));
                                    changeInput.val(change.attr('data-change'));
                                }
                            });
                            // расчёт суммы сдачи.
                            rawSumm.on('input.cash', function () {
                                if (/^\s*\d+[,.]?\d{0,2}\s*$/.test($(this).val())) {
                                    let cash = toRubles($(this).val());
                                    if (cash > summToPay) {
                                        let changeSumm = toRubles(cash - summToPay);
                                        if (toDepositBtn.prop('checked')) {
                                            let depositSumm = toRubles(toDepositInput.val());
                                            if (depositSumm + change.attr('data-change') > changeSumm) {
                                                change.text(changeSumm).attr('data-change', changeSumm);
                                                changeInput.val(changeSumm);
                                                toDepositInput.val(0);
                                            } else {
                                                if (depositSumm > 0) {
                                                    change.text(changeSumm - depositSumm).attr('data-change', changeSumm);
                                                    changeInput.val(changeSumm - depositSumm);
                                                } else {
                                                    change.text(changeSumm).attr('data-change', changeSumm);
                                                    changeInput.val(changeSumm);
                                                }
                                            }
                                        } else {
                                            change.text(changeSumm).attr('data-change', changeSumm);
                                            changeInput.val(changeSumm);
                                        }
                                    } else {
                                        change.text(0);
                                        toDepositInput.val(0);
                                    }
                                }
                            });
                            toDepositInput.on('input.toDeposit', function () {
                                if (/^\s*\d+[,.]?\d{0,2}\s*$/.test($(this).val())) {
                                    let cash = toRubles($(this).val());
                                    let changeSumm = toRubles(change.attr('data-change'));
                                    if (cash > changeSumm) {
                                        $(this).parents('div.form-group').addClass('has-error').removeClass('has-success').find('div.help-block').text('Сумма, зачисляемая на депозит не может быть больше суммы сдачи');

                                    } else {
                                        change.text(toRubles(changeSumm - cash));
                                        changeInput.val(toRubles(changeSumm - cash));
                                        $(this).parents('div.form-group').removeClass('has-error').addClass('has-success').find('div.help-block').text('');
                                    }
                                } else {
                                    change.text(change.attr('data-change'));
                                    if ($(this).val()) {
                                        $(this).parents('div.form-group').addClass('has-error').removeClass('has-success').find('div.help-block').text('Тут должна быть сумма в рублях');
                                    }
                                }
                            });
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
                window.open('/print/cottage-report/' + new Date(startVal).getTime() + '/' + new Date(endVal).getTime() + '/' + cottageNumber, '_blank')
            } else {
                makeInformer('warning', 'Рано', 'Выберите дату начала и завершения периода');
            }
        });
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

$(function () {
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

