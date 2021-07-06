/*global sendAjax, simpleAnswerHandler, makeInformer, makeModal, normalReload, loadForm, handleAjaxActivators */
function globalOptions() {
    // отправка бекапа
    const sendBackupBtn = $('button#sendBackupButton');
    sendBackupBtn.on('click.send', function () {
        sendAjax('post', '/backup/send', simpleAnswerHandler)
    });

    // сохранение настроек почты
    let mailSettingsForm = $('form#mail-settings-form');
    mailSettingsForm.on('submit', function (e) {
        e.preventDefault();
        sendAjax('post',
            '/mail-settings-edit',
            function () {
                location.reload();
            },
            mailSettingsForm,
            true);
    });

}

function sendRecursive(number) {
    sendAjax('post',
        '/utils/synchronize/' + number,
        function (answer) {
            console.log(answer)
            if (number < 180) {
                makeInformer('success', 'Успешно', 'Отправлены данные по участку ' + number + '!');
                sendRecursive(++number)
            }
            else{
                makeInformer('success', 'Успешно', 'Все данные отправлены!');
            }
        });
}

function handleLoadToApi() {
    let btn = $('button#syncronizeToApi');
    btn.on('click.sendRecursive', function () {
        sendRecursive(1);
    });
}

$(function () {
    "use strict";
    enableTabNavigation();
    globalOptions();
    handleAjaxActivators();
    handleLoadToApi();
});