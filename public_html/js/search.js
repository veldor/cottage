$(function () {
    baseFunctional();
});

let additionalFilterCounters = 0;

function baseFunctional() {
    const start = $('input#search-startdate');
    const finish = $('input#search-finishdate');
    // подключу действие кнопкам выбора даты
    let dataChooseBtns = $('button.period-choose');
    dataChooseBtns.on('click.choosePeriod', function () {
        let period = $(this).attr('data-period');
        let date = new Date;
        let day = ('0' + date.getDate()).slice(-2);
        let month = ('0' + (date.getMonth() + 1)).slice(-2);
        let year = date.getFullYear();
        if (period === 'day') {
            start.val(year + '-' + month + '-' + day);
            finish.val(year + '-' + month + '-' + day);
        }
        else if (period === 'month') {
            start.val(year + '-' + month + '-01');
            finish.val(year + '-' + month + '-' + getLastDayOfMonth(year, month - 1));
        }
        else if (period === 'year') {
            start.val(year + '-01-01');
            start.trigger('change');
            start.trigger('input');
            start.trigger('blur');
            finish.val(year + '-12-31');
            finish.trigger('change');
        }
    });
    $('a.bill-info').on('click.show-info', function (e) {
        e.preventDefault();
        showBill($(this).attr('data-bill-id'));
    });
    const startT = $('input#searchtariffs-startdate');
    const finishT = $('input#searchtariffs-finishdate');
    // подключу действие кнопкам выбора даты
    let dataTariffChooseBtns = $('button.tariff-period-choose');
    dataTariffChooseBtns.on('click.choosePeriod', function () {
        let period = $(this).attr('data-period');
        let date = new Date;
        let day = ('0' + date.getDate()).slice(-2);
        let month = ('0' + (date.getMonth() + 1)).slice(-2);
        let year = date.getFullYear();
        if (period === 'day') {
            startT.val(year + '-' + month + '-' + day);
            finishT.val(year + '-' + month + '-' + day);
        }
        else if (period === 'month') {
            console.log(month);
            startT.val(year + '-' + month + '-01');
            finishT.val(year + '-' + month + '-' + getLastDayOfMonth(year, month - 1));
        }
        else if (period === 'year') {
            startT.val(year + '-01-01');
            startT.trigger('change');
            startT.trigger('input');
            startT.trigger('blur');
            finishT.val(year + '-12-31');
            finishT.trigger('change');
        }
    });

    function setSelector(selector, options) {
        selector.removeClass('disabled').prop('disabled', false).html('');
        selector.append('<option disabled selected>Выберите условие</option>');
        for (let i in options) {
            if (options.hasOwnProperty(i))
                selector.append('<option value="' + i + '">' + options[i] + '</option>');
        }
        setTimeout(function () {
            selector.trigger('focus');
        }, 500);

    }

    let booleanOptions = {
        'true': 'Существует',
        'false': 'Отсутствует'
    };
    let textOptions = {
        'contains': 'Содержит',
        'no-contains': 'Не содержит',
        'equal': 'Равно',
        'not_equal': 'Не равно',
        'more': 'Больше',
        'more_or_equal': 'Больше или равно',
        'less': 'Меньше',
        'less_or_equal': 'Меньше или равно'
    };
    let numberOptions = {
        'equal': 'Равно',
        'not_equal': 'Не равно',
        'more': 'Больше',
        'more_or_equal': 'Больше или равно',
        'less': 'Меньше',
        'less_or_equal': 'Меньше или равно'
    };

    // восстановлю настройки поиска

    function autofill(elem, settings) {
        elem.find('option[value="' + settings[0] + '"]').prop('selected', true);
        let conditionsSelector = elem.parents('div.inputs-group').eq(0).addClass('ready').find('select.cottage-conditions');
        let selectedOption = elem.find('option:selected');
        switch (selectedOption.attr('data-type')) {
            case 'int':
            case 'float':
                setSelector(conditionsSelector, numberOptions);
                break;
            case 'tinyint':
                setSelector(conditionsSelector, booleanOptions);
                break;
            default:
                setSelector(conditionsSelector, textOptions);
        }
        conditionsSelector.prop('disabled', false).find('option[value="' + settings[1] + '"]').prop('selected', true);

        let valueInput = elem.parents('div.inputs-group').eq(0).find('input.cottage-values');
        if (settings[2]) {
            valueInput.removeClass('disabled').prop('disabled', false).val(settings[2]);
        }
    }

    let searchCottagesForm = $('form#searchCottages');
    let addConditionBtn = $('button#addConditionBtn');
    let searchCottagesOptions = searchCottagesForm.find('select.cottage-columns');
    let searchSettings = $('div#savedConditons');
    let text = searchSettings.text();
    if (text){
        // раскодирую JSON
        text = JSON.parse(text);
        console.log(text);
        // ради теста заполню данными первое поле
        autofill(searchCottagesOptions, text[0]);
        if (text.length > 1) {
            let counter = 1;
            while ((text[counter])) {
                let clone = searchCottagesOptions.parents('div.inputs-group').eq(0).clone();
                addFunctional(clone.find('select.cottage-columns').attr('id', 'cottages_columns' + counter).attr('name', 'SearchCottages[columns][' + counter + ']'));
                clone.find('select.cottage-columns').find('option').eq(0).prop('selected', true);
                clone.find('select.cottage-conditions').html('').addClass('disabled').prop('disabled', true).attr('id', 'cottages_conditions' + counter).attr('name', 'SearchCottages[conditions][' + counter + ']');
                clone.find('input.cottage-values').val('').addClass('disabled').prop('disabled', true).attr('id', 'cottages_values' + counter).attr('name', 'SearchCottages[values][' + counter + ']');
                let btn = $("<button type='button' class='btn btn-danger glyphicon glyphicon-remove-sign close-clone'></button>");
                clone.append(btn);
                btn.on('click.delete', function () {
                    btn.parents('div.inputs-group').eq(0).remove();
                });
                let selectTypeSwitcher;
                // добавлю серектор склеивания
                if (text[counter][3] === 'or') {
                    selectTypeSwitcher = $("<div class='col-sm-2'><select id='cottages_merge" + counter + "' class='form-control' name='SearchCottages[merge][" + counter + "]'><option value='and'>И</option><option value='or' selected>Или</option></select></div>");
                }
                else {
                    selectTypeSwitcher = $("<div class='col-sm-2'><select id='cottages_merge" + counter + "' class='form-control' name='SearchCottages[merge][" + counter + "]'><option value='and' selected>И</option><option value='or'>Или</option></select></div>");
                }
                clone.prepend(selectTypeSwitcher);
                clone.insertBefore(addConditionBtn).parent();
                autofill(clone.find('select.cottage-columns'), text[counter]);
                counter++;
            }
            additionalFilterCounters = counter;
        }
    }
    addFunctional(searchCottagesOptions);

    function addFunctional(elem) {
        let conditionsSelector = elem.parents('div.inputs-group').eq(0).find('select.cottage-conditions');
        let valueInput = elem.parents('div.inputs-group').eq(0).find('input.cottage-values');
        elem.on('change', function () {
            valueInput.addClass('disabled').prop('disabled', true).val('');
            elem.parents('div.inputs-group').eq(0).removeClass('ready');
            // найду выбранный пункт и определю его тип
            let selectedOption = $(this).find('option:selected');
            switch (selectedOption.attr('data-type')) {
                case 'int':
                case 'float':
                    setSelector(conditionsSelector, numberOptions);
                    break;
                case 'tinyint':
                    setSelector(conditionsSelector, booleanOptions);
                    break;
                default:
                    setSelector(conditionsSelector, textOptions);
            }
        });
        conditionsSelector.on('change', function () {
            let selectedOption = $(this).find('option:selected');
            switch (selectedOption.val()) {
                case 'true':
                case 'false':
                    valueInput.addClass('disabled').prop('disabled', true).val('');
                    elem.parents('div.inputs-group').eq(0).addClass('ready');
                    break;
                default:
                    valueInput.removeClass('disabled').prop('disabled', false).val('').trigger('focus');
                    elem.parents('div.inputs-group').eq(0).removeClass('ready');

            }
        });
        valueInput.on('change', function () {
            if (!$(this).prop('disabled') && $(this).val()) {
                elem.parents('div.inputs-group').eq(0).addClass('ready');
            }
        });
    }

    // отработаю добавление условия
    addConditionBtn.on('click.add', function () {
        ++additionalFilterCounters;
        let clone = searchCottagesOptions.parents('div.inputs-group').eq(0).clone();
        addFunctional(clone.find('select.cottage-columns').attr('id', 'cottages_columns' + additionalFilterCounters).attr('name', 'SearchCottages[columns][' + additionalFilterCounters + ']'));
        clone.find('select.cottage-columns').find('option').eq(0).prop('selected', true);
        clone.find('select.cottage-conditions').html('').addClass('disabled').prop('disabled', true).attr('id', 'cottages_conditions' + additionalFilterCounters).attr('name', 'SearchCottages[conditions][' + additionalFilterCounters + ']');
        clone.find('input.cottage-values').val('').addClass('disabled').prop('disabled', true).attr('id', 'cottages_values' + additionalFilterCounters).attr('name', 'SearchCottages[values][' + additionalFilterCounters + ']');
        let btn = $("<button type='button' class='btn btn-danger glyphicon glyphicon-remove-sign close-clone'></button>");
        btn.on('click.delete', function () {
            btn.parents('div.inputs-group').eq(0).remove();
        });
        // добавлю селектор исключающего или дополняющего поиска
        let selectTypeSwitcher = $("<div class='col-sm-2'><select id='cottages_merge" + additionalFilterCounters + "' class='form-control' name='SearchCottages[merge][" + additionalFilterCounters + "]'><option value='and' selected>И</option><option value='or'>Или</option></select></div>");
        clone.prepend(selectTypeSwitcher);
        clone.append(btn);
        clone.insertBefore($(this).parent());
    });

    searchCottagesForm.on('submit', function (e) {
        if (searchCottagesForm.find('div.inputs-group').not('.ready').length > 0) {
            e.preventDefault();
            makeInformer('info', 'Недостаточно данных', 'Введите условия поиска');
        }
    })

}

function showBill(identificator) {
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