$(function () {
    handle();
});

function handle() {
    let cottageNumber = $('span#cottageNumber').text();
    let sendReportBtn = $('button#sendReportButton');
    sendReportBtn.on('click.send', function () {
        sendAjax(
            'post',
            '/report/send/' + cottageNumber,
            simpleAnswerInformerHandler,
            {'start' : $(this).attr('data-start'), 'finish' : $(this).attr('data-finish')}
        );
    })
}