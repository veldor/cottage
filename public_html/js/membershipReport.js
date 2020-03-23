let currentDebtor;
let currentSwitcher;
$(function () {
    handle();
});

function handle() {
    // найду всех должников
    let debtors = $('tr.debtor');
    // поочерёдно отправлю всем письма
    let counter = 0;
    let debtorsSize = debtors.length;
    let button = $('button#sendBtn');
    button.on('click.send', function () {
       $(this).prop('disabled', 'disabled');
        sendDebt(debtors, counter, debtorsSize);
    });
    let resetBtn = $('button#clearBtn');
    resetBtn.on('click.reset', function () {
        $('input[type="checkbox"]').prop('checked', false);
    });

}

function sendDebt(debtors, counter, debtorsSize) {
    currentDebtor = debtors.eq(counter);
    ++counter;
    // если отправка согласована
    currentSwitcher = currentDebtor.find('input.accept-send');
    if(currentSwitcher.prop('checked')){
        currentDebtor.find('td.status').html('<span class="text-info">В процессе отправки</span>');
        sendSilentAjax('post', '/membership/remind/' + currentDebtor.attr('data-cottage'), function (answer) {
            if(answer['status'] === 1){
                currentDebtor.find('td.status').html('<span class="text-success">Отправлено</span>');
                currentSwitcher.prop('checked', false);
            }
            else{
                currentDebtor.find('td.status').html('<span class="text-danger">Ошибка отправки</span>');
            }
            if(counter < debtorsSize){
                sendDebt(debtors, counter, debtorsSize);
            }
            else{
                // отправка завершена
                sendSilentAjax('post', '/membership/remind-finished');
            }
        });
    }
    else{
        currentDebtor.find('td.status').html('<span class="text-info">Пропущено</span>');
        if(counter < debtorsSize){
            sendDebt(debtors, counter, debtorsSize);
        }
        else{
            // отправка завершена
            // отправка завершена
            sendSilentAjax('post', '/membership/remind-finished');
        }
    }
}