/*global sendAjax, simpleAnswerHandler, makeInformer, makeModal, normalReload, loadForm, handleAjaxActivators */
function handleUpdate() {
    const updateButton = $('button#createUpdateButton');
    updateButton.on('click.create', function () {
        const modal = makeModal("Настройка обновления сайта");
        loadForm('/update/create/form', modal, '/update/create');
    });
    const updateCheckButton = $('button#checkUpdateButton');
    updateCheckButton.on('click.check', function () {
        function handleUpdatesInfo(data) {
            /**
             * @param {{information:Object}} information
             * @param {{update_version:string}} update_version
             */
            if(data.status === 1){
                const modal = makeModal("Доступные обновления");
                let content = '<div class="text-center">';
                for(let i in data.information){
                    if(data.information.hasOwnProperty(i)){
                        content += '<h3>' + data.information[i].update_version + '</h3><p>' + data.information[i].description + '</p>';
                    }

                }
                content += '<button id="installUpdatesButton" class="btn btn-primary">Установить обновления</button></div>';
                modal.find('div.modal-body').append($(content));
                modal.find('button#installUpdatesButton').on('click.install', function () {
                    $(this).text('Устанавливаю обновления, подождите!').addClass('disabled').prop('disabled', true);
                    function handleInstallationStatus(data) {
                        if(data.status === 1){
                            normalReload();
                            location.reload();
                        }
                        else{
                            makeInformer('danger', 'Ошибка!', 'Что-то пошло не так :(')
                        }
                    }
                    sendAjax('post', '/updates/install', handleInstallationStatus);
                });
            }
            else{
                makeInformer('success', 'Обновления не требуются', 'Вы используете актуальную версию ПО.')
            }
        }
        sendAjax('get', '/updates/check', handleUpdatesInfo);
    })
}

function globalOptions() {
    const sendBackupBtn = $('button#sendBackupButton');
    sendBackupBtn.on('click.send', function () {
       sendAjax('post', '/backup/send', simpleAnswerHandler)
    });
}

$(function () {
    "use strict";
    handleUpdate();
    globalOptions();
    handleAjaxActivators();
});