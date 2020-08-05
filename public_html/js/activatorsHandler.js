function handleActivators(){
    let activators = $('.ajax-activator');
    activators.on('click.doAction', function (e) {
        e.preventDefault();
       let url = $(this).attr('data-action');
       sendAjax('post', url, simpleAnswerInformerHandler);
    });
}
$(function () {
    handleActivators();
});