let invoiceWindow;

function handlePartialPayments() {
    let serialPaymentsActivator = $('#makeAllBillsActivator');
    let billsWrapper = $('#billsWrapper');

    function handlePartialAnswer(data) {
        // покажу модальное окно с предложением отправить сообщения по электронной почте каждому из садоводов
        let length = data['bills'].length;
        let modal = makeModal('Отправка сообщений', '<h2>Счета успешно сформированы.</h2><p>Сформировано счетов: ' + length + '.</p><p>Подтвердите отправку сообщений по электронной почте</p><div><table id="mailSubmitsTable" class="table table-striped table-hover"></table><button id="sendMailsBtn" class="btn btn-info">Отправить уведомления</button> </div>');
        let container = modal.find('table#mailSubmitsTable');
        container.append('<tbody>');
        let counter = 0;
        while (counter < length) {
            container.append('<tr><td>Участок ' + data.bills[counter]['cottageNumber'] + (data.bills[counter]['double'] ? '-a' : '') + '</td><td><label class="btn btn-success"><input type="checkbox" data-double="' + data.bills[counter]['double'] + '" data-id="' + data.bills[counter]['billId'] + '" class="accept-fill" checked="">Отправить письмо</label></td></tr>');
            counter++;
        }
        container.append('</tbody>');
        let sendBtn = modal.find('button#sendMailsBtn');
        sendBtn.on('click.send', function () {
            let btn = $(this);
            // заблокирую кнопку
            $(this).prop('disabled', true).text('Отправляю сообщения');
            // найду все чекбоксы
            let checkboxes = container.find('input[type="checkbox"]');
            checkboxes.prop('disabled', true);
            let counter = 0;
            sendInvoice(checkboxes.eq(counter), checkboxes, counter);
        });


        function sendInvoice(checkbox, checkboxes, counter) {
            let isActive = checkbox.prop('checked');
            let label = checkbox.parent();
            let tr = label.parent();
            label.remove();
            if (isActive) {
                let billId = checkbox.attr('data-id');
                let double = checkbox.attr('data-double');
                let url;
                if (double === 'true') {
                    url = '/bank-invoice/double/send/' + billId;
                } else {
                    url = '/bank-invoice/send/' + billId;
                }
                sendSilentAjax('post', url, function (data) {
                    tr.append('<span><b class="text-success">' + data['message'] + '</b></span>');
                    ++counter;
                    if (checkboxes.eq(counter).length > 0) {
                        sendInvoice(checkboxes.eq(counter), checkboxes, counter);
                    }
                });

            }
            else {
                tr.append('<span><b class="text-info">Отправка уведомления не требуется</b></span>');
                ++counter;
                if (checkboxes.eq(counter).length > 0) {
                    sendInvoice(checkboxes.eq(counter), checkboxes, counter);
                }
            }
            sendBtn.text('Обработано ' + counter + ' из ' + length);
        }
    }

    function handleCottagesList(data) {
        billsWrapper.html(data);
        let frm = billsWrapper.find('form#billsAutofill');
        frm.on('submit.send', function (e) {
            e.preventDefault();
            let url = '/serial-payments/confirm';
            sendAjax('post', url, handlePartialAnswer, frm, true);
        });
    }

    serialPaymentsActivator.on('click.getList', function () {
        let url = '/serial-payments/get-cottages';
        sendAjax('get', url, handleCottagesList);
    });
}

function handleMailing() {
    // определю кнопку-активатор
    let sendMailingActivator = $('#createMailingActivator');
    sendMailingActivator.on('click.send', function (e) {
        e.preventDefault();
        let mailData;
        for(var i in CKEDITOR.instances){
            mailData = CKEDITOR.instances[i].getData();
            break;
        }
        let subjectData = $('#mailingSubject').val();
        if (!mailData) {
            makeInformer('warning', 'Ошибка', 'Не стоит отправлять пустое письмо');
        }
        else {
            // получу список адресов для рассылки
            sendAjax('get', '/mailing/get-list', sendMail);
        }

        function sendMail(data) {
            let modal = makeModal("Подтверждение рассылки", data);

            // назначу кнопки
            let sendActivator = modal.find('#startMailingActivator');
            let selectAllActivator = modal.find('#selectAllActivator');
            let selectNoneActivator = modal.find('#selectNoneActivator');
            let selectInvertActivator = modal.find('#selectInvertActivator');
            let selectOwnersActivator = modal.find('#selectOwnersActivator');
            let selectContactersActivator = modal.find('#selectContactersActivator');

            let destination = modal.find('input[type="checkbox"]');

            selectAllActivator.on('click.change', function (e) {
                e.preventDefault();
                destination.prop('checked', true);
            });
            selectNoneActivator.on('click.change', function (e) {
                e.preventDefault();
                destination.prop('checked', false);
            });
            selectInvertActivator.on('click.change', function (e) {
                e.preventDefault();
                destination.each(function () {
                    $(this).prop('checked', !$(this).prop('checked'));
                });
            });
            selectOwnersActivator.on('click.change', function (e) {
                e.preventDefault();
                destination.filter('.owner-mail').prop('checked', true);
            });
            selectContactersActivator.on('click.change', function (e) {
                e.preventDefault();
                destination.filter('.contacter-mail').prop('checked', true);
            });

            sendActivator.on('click.send', function (e) {
                e.preventDefault();
                disableElement(sendActivator, "Отправляю почту");
                destination.parent().hide();
                let active = destination.filter(':checked');
                let counter = 0;
                active.parent().parent().append('<span class="wait-sending text-info"><img alt="loading" class="loading_img" src="/graphics/loading.gif" /> ожидание отправки</span>');
                destination.not(':checked').parent().parent().append('<span class="not-send text-info">Сообщение не оправляется</span>');
                console.log(mailData);
                makeSending(active.eq(0), active, counter, subjectData, mailData);
            });
        }
    });

    let sendErrors = 0;

    function resendMessage(button, mailData, subject) {
        let checkbox = button.parent().find('input[type="checkbox"]');
        let cottageNumber = checkbox.attr('data-cottage-id');
        let double = checkbox.attr('data-double') ? 'double' : 'main';
        let type = checkbox.hasClass('owner-mail') ? 'owner' : 'contacter';
        let url = '/mailing/' + double + '/' + type + '/' + cottageNumber;
        let attributes = {'text' : mailData, 'subject' : subject};
        sendAjax('post', url, function (answer) {
            if(answer['status'] === 1){
                --sendErrors;
                if(sendErrors === 0){
                    makeInformer('success', 'Отправка завершена', 'Похоже, все письма отправлены');
                }
                {
                    makeInformer('info', 'Отправка завершена', 'Осталось отправить ещё ' + sendErrors + ' писем.');
                }
                button.remove();
                checkbox.parent().parent().append('<a class="btn btn-success"><span class="text-success glyphicon glyphicon-ok"></span> Сообщение отправлено</a>');
            }
            else{
                makeInformer('danger', 'Ошибка отправки', 'Не удалось отправить письмо на номер участка ' + cottageNumber);
            }
        }, attributes);
    }

    function makeSending(checkbox, checkboxes, counter, subject, mailData) {
        let cottageNumber = checkbox.attr('data-cottage-id');
        let double = checkbox.attr('data-double') ? 'double' : 'main';
        let type = checkbox.hasClass('owner-mail') ? 'owner' : 'contacter';
        let url = '/mailing/' + double + '/' + type + '/' + cottageNumber;
        let attributes = {'text' : mailData, 'subject' : subject};
        sendSilentAjax('post', url, function (answer) {
            checkbox.parent().parent().find('span.wait-sending').remove();
            if(answer['status'] === 1){
                checkbox.parent().parent().append('<a class="btn btn-success"><span class="text-success glyphicon glyphicon-ok"></span> Сообщение отправлено</a>');
            }
            else{
                let resendButton = $('<a class="btn btn-warning"><span class="text-danger glyphicon glyphicon-refresh"></span> Повторить отправку</a>');
                resendButton.on('click.resend', function () {
                   resendMessage($(this), mailData, subject);
                });
                checkbox.parent().parent().append(resendButton);
                makeInformer('danger', 'Ошибка отправки', 'Не удалось отправить письмо на номер участка ' + cottageNumber);
                sendErrors++;
            }
            ++counter;
            if (checkboxes.eq(counter).length > 0) {
                makeSending(checkboxes.eq(counter), checkboxes, counter, subject, mailData);
            }
            else{
                console.log(sendErrors);
                if(sendErrors === 0){
                    makeInformer('success', 'Отправка завершена', 'Отправлено писем: ' + counter);
                }
                else{
                    makeInformer('info', 'Отправка частично завершена', 'Отправлено ' + (counter - sendErrors) + ' писем. Повторить отправку можно нажав на значок рядом с номером участка.')
                }

            }
        }, attributes);
    }
}

$(function () {
    handle();
    handlePartialPayments();
    handleMailing();

    $(window).on('beforeunload.closeChild', function () {
        if (invoiceWindow) {
            invoiceWindow.close();
        }
    });
});

function handle() {
    const inputs = $('.power-fill');
    inputs.popover({'trigger': 'focus', 'html': true});
    // при изменении поля- отправляю данные на сохранение
    inputs.on('change.send', function () {
        const pointer = $(this);
        // отправлю запрос с данными
        let attributes = {
            'PowerHandler[cottageNumber]': $(this).attr('data-cottage'),
            'PowerHandler[newPowerData]': $(this).val(),
            'PowerHandler[month]': $(this).attr('data-month'),
        };
        if ($(this).attr('data-additional')) {
            attributes['PowerHandler[additional]'] = 1;
        }
        sendAjax('post', '/fill/power/' + $(this).attr('data-cottage'), callback, attributes);

        function callback(data) {
            console.log(data);
            if (data['status'] === 1)
                makeInformer('success', 'Успешно.', 'Данные сохранены');
            if (data['totalSumm'] === 0) {
                // расходов электроэнергии за месяц не было
                makeInformer('success', "Заполнено", "Данные внесены. Расходов электроэнергии за месяц не было, оплата не требуется");
                pointer.before('<p><b class="text-success">Заполнено</b></p>');
                pointer.remove();
                pointer.parent().find('input.compact').removeClass('compact');
            }
            else if (data['totalSumm'] > 0) {
                // Выведу сообщение с суммой оплаченного платежа.
                makeInformer('success', "Заполнено", "Данные внесены. Сумма оплаты за месяц: " + data['totalSumm'] + " &#8381;");
                pointer.before('<p><b class="text-success">Заполнено</b></p>');
                pointer.remove();
            }
            if (data['message']) {
                // если есть сообщение- значит, сохранение не удалось, вывожу сообщение
                makeInformer('warning', "Неудача", data['message']);
            }
            if (data['messageStatus']) {
                if (data['messageStatus']['status'] === 2) {
                    makeInformer('danger', 'Неуспешно', 'Нет подключения к интернету. Сообщение сохранено, вы сможете отправить его, когда подключение появится!');
                }
                else if (data['messageStatus']['status'] === 1) {
                    if (data['messageStatus']['results']['to-owner']) {
                        if (data['messageStatus']['results']['to-owner'] === true)
                            makeInformer('success', 'Успешно', 'Письмо владельцу успешно отправлено!');
                        else {
                            makeInformer('danger', 'Неуспешно', 'Письмо владельцу отправить не удалось!');
                        }
                    }
                    if (data['messageStatus']['results']['to-contacter']) {
                        if (data['messageStatus']['results']['to-contacter'] === true)
                            makeInformer('success', 'Успешно', 'Письмо контактному лицу успешно отправлено!');
                        else {
                            makeInformer('danger', 'Неуспешно', 'Письмо контактному лицу отправить не удалось!');
                        }
                    }
                }
            }
            if (data['errors']) {
                makeInformer('danger', "Ошибка", handleErrors(data['errors']));
                pointer.parent().addClass('has-warning');
                pointer.focus();
            }
            if (data['changeCounter']) {
                makeInformer('info', "Замена счётчика", "Был заменён счётчик электроэнергии. Дополнительная стоимость электроэнергии за месяц по данным старого счётчика: " + data['additionalPay'] + " &#8381;");
            }
        }
    });
    // при нажатии на кнопку просмотра всех платежей: покажу все неоплаченные счета
    let showBillsActivator = $('#showAllBillsActivator');
    let billsWrapper = $('#billsWrapper');

    function showAllBills(data) {
        billsWrapper.html(data);
        let parts = billsWrapper.find('tr.tr-selected');
        parts.on('click.show', function () {
            editBill($(this).attr('data-bill-id'), parseInt($(this).attr('data-double')));
        });
        let messageSendActivators = billsWrapper.find('span.unsended');
        messageSendActivators.on('click.send', function (e) {
            let link = $(this);
            e.stopPropagation();
            let url;
            let billId = $(this).attr('data-bill-id');
            let double = $(this).attr('data-double');
            if (double === '1') {
                url = '/bank-invoice/double/send/' + billId;
            } else {
                url = '/bank-invoice/send/' + billId;
            }
            sendAjax('post', url, function (data) {
                if (data['status'] === 1) {
                    link.removeClass('glyphicon-envelope btn btn-info unsended').addClass(' glyphicon-ok text-success').attr('title', 'Уведомление отправлено').off('click');
                }
                else {
                    makeInformer('info', 'Не удалось', data['message']);
                }
            });

        });
    }

    showBillsActivator.on('click.showBills', function () {
        sendAjax('get', '/show/all-bills', showAllBills);
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
        if (answer['status'] === 1) {
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

            function printCallback() {
                makeInformer('success', 'Квитанция распечатана');
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
                    if (answer['status'] === 1) {
                        makeInformer('success', 'Квитанция отправлена', "Квитанция успешно отправлена на адрес, указанный в профиле участка");
                        enableElement(sendInvoiceBtn, "Отправить квитанцию ещё раз");
                    } else if (answer['status'] === 2) {
                        makeInformer('danger', 'Квитанция не отправлена', "Квитанция не отправлена. Возможно, в профиле не указан адрес почтового ящика или нет соединения с интернетом");
                    }
                }
            });
            printInvoiceBtn.on('click.print', function () {
                disableElement(printInvoiceBtn, "Распечатываем квитанцию");
                makeNewWindow('/invoice/' + identificator, invoiceWindow, callback);

                function callback() {
                    // квитанция распечатана
                    makeInformer('success', 'Квитанция распечатана');
                    enableElement(printInvoiceBtn, 'Распечатать ещё одну квитанцию');
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
                        if (answer['status'] === 1) {
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
                }
                else {
                    url = '/pay/close/' + identificator;
                }
                // отправлю запрос на закрытие частично оплаченного счёта
                sendAjax('post', url, simpleAnswerHandler);
            });
        }
    }
}