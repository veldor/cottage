$(function () {
    handle();
});

function handle() {
    let cottageNumber = $('span#cottageNumber').text();
    let sendReportBtn = $('button#sendReportButton');
    sendReportBtn.on('click.send', function () {
        sendAjax('post', '/report/send/' + cottageNumber, simpleAnswerHandler);
    })
}