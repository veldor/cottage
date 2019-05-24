<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.09.2018
 * Time: 16:06
 */

/* @var $this View */

/* @var $model Filling */

use app\assets\FillingAsset;
use app\models\CashHandler;
use app\models\Filling;
use app\models\small_classes\RegistryInfo;
use app\widgets\AllCottagesWidget;
use mihaildev\ckeditor\CKEditor;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
FillingAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Заполнение данных';

$tabs = ['power' => 'active in', 'bills' => '', 'registry' => '', 'mailing' => ''];

if (!empty($tab)) {
    foreach ($tabs as $key => $value) {
        if ($key === $tab) {
            $tabs[$key] = 'active in';
        } else {
            $tabs[$key] = '';
        }
    }
}
/** @var array $info */
?>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="<?= $tabs['power'] ?>"><a href="#power" data-toggle="tab">Электроэнергия</a></li>
    <li class="<?= $tabs['bills'] ?>"><a href="#bills" data-toggle="tab">Счета</a></li>
    <li class="<?= $tabs['registry'] ?>"><a href="#registry" data-toggle="tab">Регистр</a></li>
    <li class="<?= $tabs['mailing'] ?>"><a href="#mailing" data-toggle="tab">Рассылка</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <div class="tab-pane <?= $tabs['power'] ?>" id="power">
        <div class="row show-grid small-text">
            <?php try {
                echo AllCottagesWidget::widget(['info' => $info]);
            } catch (Exception $e) {
            } ?>
        </div>
    </div>
    <div class="tab-pane <?= $tabs['bills'] ?>" id="bills">
        <div class="btn-group-vertical margened">
            <button id="showAllBillsActivator" class="btn btn-success">Показать все неоплаченные счета</button>
            <button id="makeAllBillsActivator" class="btn btn-warning">Сформировать счета</button>
        </div>
        <div id="billsWrapper"></div>
    </div>
    <div class="tab-pane <?= $tabs['registry'] ?>" id="registry">

        <div class="row margened">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);
            echo $form->field($model, 'file[]', ['template' =>
                '<div class="col-sm-6 text-center">{label}{input}
									{error}{hint}</div><div class="col-sm-6"><button class="btn btn-success">Обработать</button></div>'])
                ->fileInput(['class' => 'hidden','multiple' => true, 'accept' => 'text/plain'])
                ->label('Выберите файл регистра.', ['class' => 'btn btn-info']);
            ActiveForm::end();
            if(!empty($errorMessage)){
                echo "<div class='col-sm-12'><b>$errorMessage</b></div>";
            } /** @var RegistryInfo $billDetails */
            elseif(!empty($billDetails)){
                echo "<div class='col-sm-12'>Распознан платёж по счёту № {$billDetails->billId} на сумму " . CashHandler::toSmoothRubles($billDetails->summ) . ", совершённый {$billDetails->date}  в {$billDetails->time}</div>";
            }
            ?>
        </div>
    </div>
    <div class="tab-pane <?= $tabs['mailing'] ?>" id="mailing">
        <div class="row">
            <div class="col-sm-12 margened">
                <div class="col-sm-5"><label for="mailingSubject" class="control-label">Тема рассылки</label></div>
                <div class="col-xs-7"><input class="form-control" id="mailingSubject" type="text" maxlength="100"/>
                </div>
            </div>
            <div class="col-sm-12 margened">
                <label for="w1"></label><textarea title="mailing text" id="w1" name="w1"></textarea>
                <?php
                try {
                    CKEditor::widget([
                        'name' => 'mailing',
                        'editorOptions' => [
                            'preset' => 'full', //разработанны стандартные настройки basic, standard, full данную возможность не обязательно использовать
                            'extraPlugins' => 'lexemes',
                        ]
                    ]);
                } catch (Exception $e) {
                }
                ?>
            </div>
            <div class="col-sm-12 margened">
                <button id="createMailingActivator" class="btn btn-success">Создать рассылку</button>
            </div>
        </div>
    </div>

</div>


