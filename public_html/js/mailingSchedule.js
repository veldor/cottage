"use strict";
let sendingCouner = 0;
let sendInProgress = false;
let waitingMessages;

function handleMailingSchedule() {
    let unsendedMessagesCounter = $('span#unsendedMessagesCounter');
    let cancelMailingBtn = $('button.mailing-cancel');
    console.log(cancelMailingBtn);
    cancelMailingBtn.on('click.cancelMailing', function () {
        sendAjax('post',
            '/cancel-mailing',
            ajaxFormAnswerHandler,
            {'id': $(this).attr('data-schedule-id')}
        );
    });

    let beginSendingBtn = $('button#beginSendingBtn');
    let clearSendingBtn = $('button#clearSendingBtn');
    clearSendingBtn.on('click.clear', function () {
        makeInformerModal(
            'Очистка списка рассылок',
            'Очистить список отправки? Все неотправленные сообщения будут удалены из очереди отправки.',
            function () {
                sendAjax('post',
                    '/clear-mailing-schedule',
                    simpleAnswerHandler);
            },
            function () {

            }
        );
    });
    waitingMessages = $('b.mailing-status');

    function recursiveSendMessages(waitingMessages, sendingCouner) {
        if (sendInProgress) {
            let targetMessage = waitingMessages.eq(sendingCouner);
            targetMessage.removeClass('text-info').addClass('text-primary').text('Отправка');
            sendSilentAjax('post',
                '/send-message',
                function (data) {
                    if (data.status && data.status === 1) {
                        ++sendingCouner;
                        makeInformer('success', 'Успешная отправка', 'Сообщение отправлено и удалено из очереди отправки');
                        unsendedMessagesCounter.text(waitingMessages.length - sendingCouner);
                        targetMessage.removeClass('text-primary').addClass('text-success').text('Отправлено');
                        targetMessage.parents('tr').eq(0).remove();
                        if (sendingCouner < waitingMessages.length) {
                            recursiveSendMessages(waitingMessages, sendingCouner);
                        } else {
                            location.reload();
                        }
                    } else if (data.message) {
                        // возникла ошибка отправки
                        makeInformer('danger', 'ошибка отправки', data.message);
                        targetMessage.removeClass('text-primary').addClass('text-danger').text('Не отправлено');
                        sendInProgress = !sendInProgress;
                        beginSendingBtn.find('span').text('Продолжить рассылку').removeClass('text-danger').addClass('text-info');
                    }
                },
                {'id': targetMessage.attr('data-schedule-id')},
                false,
                true);
        }
    }

    beginSendingBtn.on('click.beginSending', function () {
        sendInProgress = !sendInProgress;
        if (!sendInProgress) {
            cancelMailingBtn.prop('disabled', false);
            clearSendingBtn.prop('disabled', false);
            makeInformer('success', 'Успешно', 'Отправка остановлена');
            waitingMessages = $('b.mailing-status');
            $(this).find('span').text('Продолжить рассылку').removeClass('text-danger').addClass('text-info');
            return;
        }
        cancelMailingBtn.prop('disabled', 'disabled');
        clearSendingBtn.prop('disabled', 'disabled');
        $(this).find('span').text('Остановить отправку').removeClass('text-success').addClass('text-danger');
        if (waitingMessages) {
            // рекурсивно отправлю все сообщения
            recursiveSendMessages(waitingMessages, sendingCouner);
        } else {
            makeInformer('success', 'Завершено', 'Нет неотправленных сообщений');
            $(this).prop('disabled', false).text('Начать рассылку');
        }
    });
}

$(function () {
    handleMailingSchedule();
});