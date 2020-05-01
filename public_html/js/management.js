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

$(function () {
    "use strict";
    enableTabNavigation();
    globalOptions();
    handleAjaxActivators();
});