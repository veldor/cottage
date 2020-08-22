$(function () {
    handle();
});

function handle() {
    handleAjaxActivators();
    const createTargetPayBtn = $('button#createTargetPayment');
    createTargetPayBtn.on('click.create', function () {
      sendAjax('get', '/tariffs/create-target', callback);
      function callback(data) {
          let modal = makeModal('Создание целевого тарифа', data);
          modal.find('form').on('submit.normalReload', function () {
              normalReload();
          });
      }
    });
}
