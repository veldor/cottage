$(function () {
    handleUpdate();
    handleFixes();
});
function handleFixes() {
    const refPower = $('button#refreshPowerData');
    refPower.on('click', function () {
        sendAjax('post', '/fix/refresh-power', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
    const fixBills = $('button#fixBillInfo');
    fixBills.on('click', function () {
        sendAjax('post', '/fix/bills', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
    const fixTargets = $('button#fixTargetInfo');
    fixTargets.on('click', function () {
        sendAjax('post', '/fix/targets', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
    const recPow = $('button#recalculatePowerTariffs');
    recPow.on('click', function () {
        sendAjax('post', '/fix/recalculate-power', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
    const recMem = $('button#recalculateMembershipTariffs');
    recMem.on('click', function () {
        sendAjax('post', '/fix/recalculate-membership', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
    const recTar = $('button#recalculateTargetTariffs');
    recTar.on('click', function () {
        sendAjax('post', '/fix/recalculate-target', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
    const recountPays = $('button#recountPayments');
    recountPays.on('click', function () {
        sendAjax('post', '/fix/recount-payments', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                makeInformer('success', 'Успешно.', 'Данные обновлены.');
            }
        }
    });
}
function handleUpdate() {
    const updateButton = $('button#createUpdateButton');
    updateButton.on('click.create', function () {
        const modal = makeModal("Настройка обновления сайта");
        loadForm('/update/create/form', modal, '/update/create');
    });
    const updateCheckButton = $('button#checkUpdateButton');
    updateCheckButton.on('click.check', function () {
        sendAjax('get', '/updates/check', handleUpdatesInfo);
        function handleUpdatesInfo(data) {
            if(data.status === 1){
                const modal = makeModal("Доступные обновления");
                let content = '<div class="text-center">';
                for(let i in data['information']){
                    if(data['information'].hasOwnProperty(i)){
                        content += '<h3>' + data['information'][i]['update_version'] + '</h3><p>' + data['information'][i]['description'] + '</p>';
                    }

                }
                content += '<button id="installUpdatesButton" class="btn btn-primary">Установить обновления</button></div>';
                modal.find('div.modal-body').append($(content));
                modal.find('button#installUpdatesButton').on('click.install', function () {
                    $(this).text('Устанавливаю обновления, подождите!').addClass('disabled').prop('disabled', true);
                    sendAjax('post', '/updates/install', handleInstallationStatus);
                    function handleInstallationStatus(data) {
                        if(data.status === 1){
                            normalReload();
                            location.reload();
                        }
                        else{
                            makeInformer('danger', 'Ошибка!', 'Что-то пошло не так :(')
                        }
                    }
                });
            }
            else{
                makeInformer('success', 'Обновления не требуются', 'Вы используете актуальную версию ПО.')
            }
        }
        // const modal = makeModal("Настройка обновления сайта", updateButton);
        // loadForm('/update/create/form', modal, '/update/create');
    })
}