$(function () {
    handle();
});
function handle() {
    let dayInBtn = $('button#dayInBtn');
    let dayInContainer = $('b#dayIn');
    dayInBtn.on('click.show', function () {
        sendAjax('get', '/balance/show/day-in', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                dayInContainer.html(answer['summ'] + " &#8381;");
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let monthInBtn = $('button#monthInBtn');
    let monthInContainer = $('b#monthIn');
    monthInBtn.on('click.show', function () {
        sendAjax('get', '/balance/show/month-in', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                monthInContainer.html(answer['summ'] + " &#8381;");
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let yearInBtn = $('button#yearInBtn');
    let yearInContainer = $('b#yearIn');
    yearInBtn.on('click.show', function () {
        sendAjax('get', '/balance/show/year-in', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                yearInContainer.html(answer['summ'] + " &#8381;");
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let dayOutBtn = $('button#dayOutBtn');
    let dayOutContainer = $('b#dayOut');
    dayOutBtn.on('click.show', function () {
        sendAjax('get', '/balance/show/day-out', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                dayOutContainer.html(answer['summ'] + " &#8381;");
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let monthOutBtn = $('button#monthOutBtn');
    let monthOutContainer = $('b#monthOut');
    monthOutBtn.on('click.show', function () {
        sendAjax('get', '/balance/show/month-out', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                monthOutContainer.html(answer['summ'] + " &#8381;");
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let yearOutBtn = $('button#yearOutBtn');
    let yearOutContainer = $('b#yearOut');
    yearOutBtn.on('click.show', function () {
        sendAjax('get', '/balance/show/year-out', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                yearOutContainer.html(answer['summ'] + " &#8381;");
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let transContainer = $('div#transactonsList');
    let dayTransBtn = $('button#showDayTransactions');
    dayTransBtn.on('click.show', function () {
        sendAjax('get', '/balance/show-transactions/day', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                transContainer.html('<h3>' + answer['date'] + '</h3>' + answer['data']);
                transContainer.find('a').on('click.show', function (e) {
                    e.preventDefault();
                    editBill($(this).attr('data-bill-id'));
                });
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let monthTransBtn = $('button#showMonthTransactions');
    monthTransBtn.on('click.show', function () {
        sendAjax('get', '/balance/show-transactions/month', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                transContainer.html('<h3>' + answer['date'] + '</h3>' + answer['data']);
                transContainer.find('a').on('click.show', function (e) {
                    e.preventDefault();
                    editBill($(this).attr('data-bill-id'));
                });
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let yearTransBtn = $('button#showYearTransactions');
    yearTransBtn.on('click.show', function () {
        sendAjax('get', '/balance/show-transactions/year', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                transContainer.html('<h3>' + answer['date'] + '</h3>' + answer['data']);
                transContainer.find('a').on('click.show', function (e) {
                    e.preventDefault();
                    editBill($(this).attr('data-bill-id'));
                });
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });

    // собираю сводку за период
    let showDaySummary = $('button#showDaySummary');
    showDaySummary.on('click.show', function () {
        sendAjax('get', '/balance/show-summary/day', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                transContainer.html('<h3>' + answer['date'] + '</h3>' + answer['data']);
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let showMonthSummary = $('button#showMonthSummary');
    showMonthSummary.on('click.show', function () {
        sendAjax('get', '/balance/show-summary/month', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                transContainer.html('<h3>' + answer['date'] + '</h3>' + answer['data']);
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });
    let showYearSummary = $('button#showYearSummary');
    showYearSummary.on('click.show', function () {
        sendAjax('get', '/balance/show-summary/year', callback);
        function callback(answer) {
            if(answer['status'] === 1){
                transContainer.html('<h3>' + answer['date'] + '</h3>' + answer['data']);
            }
            else{
                makeInformer('danger', 'Ошибка', 'Не удалось получить данные. Сообщите мне об этом!');
            }
        }
    });

    function editBill(identificator) {
        let modal = makeModal('Информация о платеже');
        // запрошу сведения о платеже
        sendAjax('get', '/get-info/bill/' + identificator, callback);

        function callback(answer) {
            if (answer['status'] === 1) {
                modal.find('div.modal-body').append(answer['view']);
                // Обработаю функции кнопок
                const remindAboutPayBtn = modal.find('button#remindAbout');
                remindAboutPayBtn.on('click.remind', function () {
                    remind('/send/pay/' + identificator);
                });
            }
        }
    }
}