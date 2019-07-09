<?php

use app\models\AddCottage;
use app\models\CashHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin(['id' => 'changeCottageForm', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'validateOnSubmit' => false, 'action' => ['/change-cottage']]);
/** @var AddCottage $matrix */
echo "<fieldset class='color-salad'><legend>Сведения об участке</legend>";
echo $form->field($matrix, 'cottageNumber', ['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($matrix, 'fullChangable', ['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($matrix, 'haveRights', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Наличие справки о праве собственности на участок."></button></div>'])
    ->checkbox()
    ->label('Наличие прав на собственность.');
echo $form->field($matrix, 'cottageRegisterData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Наличие справки о праве собственности на участок."></button></div>'])
    ->checkbox()
    ->label('Наличие данных для реестра.');

echo $form->field($matrix, 'cottageRegistrationInformation', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Данные кадастрового номера участка"></button></div>'])
    ->textarea()
    ->label('Данные кадастрового номера участка.')
    ->hint("<b class='text-info'>Необязательное поле.</b> Буквы, пробелы и тире.");

echo "</fieldset>";
echo "<fieldset class='color-orange'><legend>Сведения о владельце</legend>";
echo $form->field($matrix, 'cottageOwnerPersonals', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Русскими буквами, данные владельца. Будут использованы, например, в автоматическом обращении в рассылках."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, Иванов Иван Иванович'])
    ->label('Фамилия имя и отчество владельца участка.')
    ->hint("<b class='text-success'>Обязательное поле.</b> Буквы, пробелы и тире.");
echo $form->field($matrix, 'cottageOwnerDescription', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Русскими буквами, данные владельца. Будут использованы, например, в автоматическом обращении в рассылках."></button></div>'])
    ->textarea()
    ->label('Дополнительная информация.')
    ->hint("<b class='text-info'>Необязательное поле.</b> Буквы, пробелы и тире.");


echo $form->field($matrix, 'passportData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Русскими буквами, данные владельца. Будут использованы, например, в автоматическом обращении в рассылках."></button></div>'])
    ->textarea()
    ->label('Паспортные данные.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'cottageRightsData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Русскими буквами, данные владельца. Будут использованы, например, в автоматическом обращении в рассылках."></button></div>'])
    ->textarea()
    ->label('Данные права собственности.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");


echo $form->field($matrix, 'cottageOwnerPhone', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="В перспективе- для звонков владельцу."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, 9201234567'])
    ->label(" Номер телефона владельца участка.")
    ->hint("<b class='text-info'>Необязательное поле.</b> В свободной форме");
echo $form->field($matrix, 'cottageOwnerEmail', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, vasya@yandex.ru'])
    ->label('Адрес электронной почты владельца участка.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'payerInfo', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, Брильц И. И.'])
    ->label('Данные владельцев для квитанции.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo "</fieldset>";
echo "<fieldset class='color-pinky'><legend>Почтовый адрес владельца</legend>";
echo $form->field($matrix, 'ownerAddressIndex', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['placeholder' => 'Например, 000000'])
    ->label('Почтовый индекс.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressTown', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['placeholder' => 'Например, Нижний Новгород'])
    ->label('Город проживания.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressStreet', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['placeholder' => 'Например, улица Минина'])
    ->label('Название улицы.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressBuild', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['placeholder' => 'Например, 23'])
    ->label('Номер дома.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressFlat', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['placeholder' => 'Например, 23'])
    ->label('Номер квартиры.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo "</fieldset><div class='color-sea'>";

echo $form->field($matrix, 'hasContacter', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div>'])
    ->checkbox()
    ->label('Добавить контактное лицо.');
if ($matrix->hasContacter == 1)
    echo "<fieldset id='contacterInfo' class=''><legend>Сведения о контактном лице</legend>";
else
    echo "<fieldset id='contacterInfo' class='hidden'><legend>Сведения о контактном лице</legend>";
echo $form->field($matrix, 'cottageContacterPersonals', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, Иванов Иван Иванович'])
    ->label('Фамилия имя и отчество контактного лица.')
    ->hint("<b class='text-success'>Обязательное поле.</b> Буквы, пробелы и тире.");
echo $form->field($matrix, 'cottageContacterPhone', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="В перспективе- для звонков владельцу."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, 9201234567'])
    ->label(" Номер телефона контактного лица.")
    ->hint("<b class='text-info'>Необязательное поле.</b> В свободной форме.");
echo $form->field($matrix, 'cottageContacterEmail', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, vasya@yandex.ru'])
    ->label('Адрес электронной почты контактного лица.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo "</fieldset></div>";
if ($matrix->fullChangable) {
    echo "<fieldset class='color-yellow'><legend>Платёжные данные</legend>";

    echo $form->field($matrix, 'cottageSquare', ['template' =>
        '<div class="col-lg-4">{label}</div><div class="col-lg-3"><div class="input-group">{input}<span class="input-group-addon">М<sup>2</sup></span></div> 
									{error}{hint}</div><div class="col-lg-1 col-lg-offset-4"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Для расчёта платежей. Обрати внимание, площадь не в сотках а в метрах."></button></div>'])
        ->textInput(['placeholder' => 'Например, 5000'])
        ->label('Площадь участка, в квадратных метрах.')
        ->hint("<b class='text-success'>Обязательное поле.</b>Целое число, в метрах.");
    echo $form->field($matrix, 'currentPowerData', ['template' =>
        '<div class="col-lg-4">{label}</div><div class="col-lg-4"><div class="input-group">{input}<span class="input-group-addon">кВт.ч</span></div>
									{error}{hint}</div><div class="col-lg-1 col-lg-offset-3"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Отсчёт пользования электроэнергии будет вестись начиная с данной цифры. Всё, что было до- считается оплаченным. Если есть долг по электичеству- проведи его разовым платежом"></button></div>'])
        ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, 9999'])
        ->label('Текущие показания счётчика электроэнергии, в киловаттах.')
        ->hint("<b class='text-success'>Обязательное поле.</b>Заполнять цифрами.");
    echo $form->field($matrix, 'lastPayedMonth', ['template' =>
        '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Последний оплаченный месяц. Долг будет считаться со следующего."></button></div>'])
        ->textInput(['placeholder' => 'Например, 2000-11'])
        ->label('Электроэнергия оплачена в:')
        ->hint("<b class='text-info'>Необязательное поле.</b> Последний оплаченный месяц. Год-месяц, цифрами. Год полностью, 4 цифры.");
    echo $form->field($matrix, 'membershipPayFor', ['template' =>
        '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Последний оплаченный квартал. Долг будет считаться со следующего, включая текущий."></button></div>'])
        ->textInput(['placeholder' => 'Например, 2000-4'])
        ->label('Членские взносы оплачены по:')
        ->hint("<b class='text-success'>Обязательное поле.</b> Последний оплаченный квартал. Год-квартал, цифрами. Год полностью, 4 цифры.");

    echo $form->field($matrix, 'deposit', ['template' =>
        '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Данная сумма будет находиться на счету участка и может быть использована для платежей за любые услуги."></button></div>'])
        ->textInput(['autocomplete' => 'off'])
        ->label('Сумма депозита на момент регистрации:')
        ->hint("<b class='text-success'>Обязательное поле.</b>В рублях.");
    if (!empty($matrix->existentTargets)) {
        echo "<div class='col-lg-12 text-center'><h2>Заполнение целевых взносов</h2></div>";
        $counter = 0;
        // для каждого из периодов проверю заполненность предыдущими данными
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($matrix->targetPaysDuty);
        $xpath = new DOMXPath($dom);
        foreach ($matrix->existentTargets as $key => $value) {
            // посчитаю максимальную сумму платежа
            $max = CashHandler::toRubles($value['fixed'] + ($value['float'] / 100 * $matrix->cottageSquare));
            // проверю состояние данного платежа по предыдущим данным
            $info = $xpath->query("//target[@year='$key']");
            if($info->length > 0){
                $payed = CashHandler::toRubles($info->item(0)->getAttribute('payed'));
                // если год в списке- по нему имеется задолженность.
                if($payed == 0){
                    // месяц полностью не оплачен
                    echo "<div class='form-group has-success'>
<div class='col-lg-4'><label class='control-label'>" . $key . " год: Долг <b class='text-danger summ' data-fixed='{$value['fixed']}' data-float='{$value['float']}'>" . $max . "</b>  &#8381;</label></div>
<div class='col-lg-5'>
<div class='btn-group' data-toggle='buttons'>
  <label class='btn btn-primary'>
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='full' data-year='{$key}'> Оплачен
        </label>
  <label class='btn btn-primary active'>
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='no-payed' data-year='{$key}' checked> Не оплачен
        </label>
  <label class=\"btn btn-primary\">
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='partial' data-year='{$key}'> Частично
        </label>
</div>
        <div class='help-block'></div>
</div>
<div class='col-lg-3 text-input-parent'><div class='input-group'><input type='text' class='form-control target-input' id='addcottage-target_{$key}' name='AddCottage[target][" . $key . "][payed-summ]' value='0' autocomplete='off' aria-invalid='false' aria-required='false' disabled><span class='input-group-addon'> &#8381;</span></div><div class='help-block'></div></div>
</div>";
                }
                else{
                    // месяц оплачен частично
                    echo "<div class='form-group has-success'>
<div class='col-lg-4'><label class='control-label'>" . $key . " год: Долг <b class='text-danger summ' data-fixed='{$value['fixed']}' data-float='{$value['float']}'>" . $max . "</b>  &#8381;</label></div>
<div class='col-lg-5'>
<div class='btn-group' data-toggle='buttons'>
  <label class='btn btn-primary'>
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='full' data-year='{$key}'> Оплачен
        </label>
  <label class='btn btn-primary'>
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='no-payed' data-year='{$key}'> Не оплачен
        </label>
  <label class=\"btn btn-primary active\">
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='partial' data-year='{$key}' checked> Частично
        </label>
</div>
        <div class='help-block'></div>
</div>
<div class='col-lg-3 text-input-parent'><div class='input-group'><input type='text' class='form-control target-input' id='addcottage-target_{$key}' name='AddCottage[target][" . $key . "][payed-summ]' value='$payed' autocomplete='off' aria-invalid='false' aria-required='false'><span class='input-group-addon'> &#8381;</span></div><div class='help-block'></div></div>
</div>";
                }
            }
            else{
                // иначе он полностью оплачен
                echo "<div class='form-group has-success'>
<div class='col-lg-4'><label class='control-label'>" . $key . " год: Долг <b class='text-danger summ' data-fixed='{$value['fixed']}' data-float='{$value['float']}'>" . $max . "</b>  &#8381;</label></div>
<div class='col-lg-5'>
<div class='btn-group' data-toggle='buttons'>
  <label class='btn btn-primary active'>
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='full' data-year='{$key}' checked> Оплачен
        </label>
  <label class='btn btn-primary'>
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='no-payed' data-year='{$key}'> Не оплачен
        </label>
  <label class=\"btn btn-primary\">
          <input type='radio' class='target-radio' name='AddCottage[target][" . $key . "][payed-of]' value='partial' data-year='{$key}'> Частично
        </label>
</div>
        <div class='help-block'></div>
</div>
<div class='col-lg-3 text-input-parent'><div class='input-group'><input type='text' class='form-control target-input' id='addcottage-target_{$key}' name='AddCottage[target][" . $key . "][payed-summ]' value='$max' autocomplete='off' aria-invalid='false' aria-required='false' readonly><span class='input-group-addon'> &#8381;</span></div><div class='help-block'></div></div>
</div>";
            }
            $counter++;
        }
    }
    echo $form->field($matrix, 'targetFilled', ['template' => "{input}"])->hiddenInput()->label(false);

    echo "</fieldset>";
}
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();