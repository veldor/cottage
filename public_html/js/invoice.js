$(function () {
    window.print();
    $(window).on('afterprint',function () {
        window.close();
    })
});