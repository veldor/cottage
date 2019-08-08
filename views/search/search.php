<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 12:48
 */

use app\assets\SearchAsset;
use app\models\CashHandler;
use app\models\SearchCottages;
use app\widgets\MembershipStatisticWidget;
use app\widgets\PowerStatisticWidget;
use app\widgets\TargetStatisticWidget;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $settings \app\models\Search */
/* @var $searchTariffs \app\models\Search */
/* @var $searchCottages SearchCottages */
/* @var $activeSearch string */
$this->title = 'Выборки';
SearchAsset::register($this);
ShowLoadingAsset::register($this);

$tabs = ['cashSearch' => 'active in', 'tariffsSearch' => '', 'cottagesSearch' => ''];

if (!empty($activeSearch)) {
	foreach ($tabs as $key => $tab) {
		if ($key === $activeSearch) {
			$tabs[$key] = 'active in';
		}
		else {
			$tabs[$key] = '';
		}
	}
}

?>

<ul class="nav nav-tabs tabs no-print">
    <li class="<?= $tabs['cashSearch'] ?>"><a href="#cashSearch" data-toggle="tab">Денежные средства</a></li>
    <li class="<?= $tabs['tariffsSearch'] ?>"><a href="#tariffsSearch" data-toggle="tab">Тарифы</a></li>
    <li class="<?= $tabs['cottagesSearch'] ?>"><a href="#cottagesSearch" data-toggle="tab">Участки</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane margened fade <?= $tabs['cashSearch'] ?>" id="cashSearch">
        <div class="row">
			<?php
			$form = ActiveForm::begin(['id' => 'Search', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'enableAjaxValidation' => true, 'action' => ['/search']]);
			echo $form->field($settings, 'startDate', ['template' =>
				'<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
				->input('date')
				->label('С');
			echo $form->field($settings, 'finishDate', ['template' =>
				'<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
				->input('date')
				->label('По');
			echo $form->field($settings, 'searchType', ['template' =>
				'<div class="col-sm-12 text-center"> <div class="btn-group" data-toggle="buttons">{input}</div>{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-12']])->radioList($settings->searchTypeList, ['item' => function ($index, $label, $name, $checked, $value) {
				return "<label class='btn btn-info " . ($checked ? 'active' : '') . "'><input type='radio' value='$value' name='$name' " . ($checked ? 'checked' : '') . "/> $label</label>";
			}, 'tag' => false]);
			echo '<div class="col-sm-12 text-center"><button type="button" class="btn btn-default period-choose" data-period="day">За день</button><button type="button" class="btn btn-default period-choose" data-period="month">За месяц</button><button type="button" class="btn btn-default period-choose" data-period="year">За год</button></div>';
			echo "<div class='col-sm-12 text-center margened'>";
			echo Html::submitButton('Сформировать', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
			echo '</div>';
			ActiveForm::end();
			echo '</div>';
			if ($result !== null && $activeSearch === 'cashSearch' && $result['status'] === 1) {
				if (!empty($result['totalSumm'])) {
					$summ = CashHandler::toSmoothRubles($result['totalSumm']);
					echo "<h4>Всего: <span class='text-info'>{$summ}</span></h4>";
				}
				if (is_string($result['data'])) {
					echo $result['data'];
				}
                elseif (is_array($result['data'])) {
					?>
                    <p>
                        Отчет по платежам с <b><?= $_POST['Search']['startDate'] ?></b>
                        по <b><?= $_POST['Search']['finishDate'] ?></b>
                    </p>

                    <table class="table table-bordered table-condensed little-text small-text">
                        <thead>
                        <tr>
                            <th rowspan="2" class="text-center vertical-top">Дата</th>
                            <th rowspan="2" class="text-center vertical-top">№</th>
                            <th rowspan="2" class="text-center vertical-top">Уч.</th>
                            <th colspan="2" class="text-center">Членские</th>
                            <th colspan="3" class="text-center">Электричество</th>
                            <th colspan="2" class="text-center">Целевые</th>
                            <th colspan="2" class="text-center">Разовые</th>
                            <th colspan="2" class="text-center">Пени</th>
                            <th rowspan="2" class="text-center vertical-top">Скидка</th>
                            <th rowspan="2" class="text-center vertical-top">Депозит</th>
                            <th rowspan="2" class="text-center vertical-top">Итого</th>
                        </tr>
                        <tr>
                            <th class="text-center">Поквартально</th>
                            <th class="text-center">Итого</th>
                            <th class="text-center">Показания</th>
                            <th class="text-center">Всего</th>
                            <th class="text-center">Оплачено</th>
                            <th class="text-center">По годам</th>
                            <th class="text-center">Итого</th>
                            <th class="text-center">Отдельно</th>
                            <th class="text-center">Итого</th>
                            <th class="text-center">Отдельно</th>
                            <th class="text-center">Итого</th>
                        </tr>
                        </thead>
                        <tbody>
						<?php
						if (!empty($result['data'])) {
							foreach ($result['data'] as $item) {
								echo $item;
							}
						}
						?>
                        </tbody>
                    </table>
					<?php
				}
			}
			?>
        </div>
        <div class="tab-pane margened fade <?= $tabs['tariffsSearch'] ?>" id="tariffsSearch">
            <div class="row">
				<?php
				$form = ActiveForm::begin(['id' => 'searchTariffs', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action' => ['/search']]);
				echo $form->field($searchTariffs, 'startDate', ['template' =>
					'<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-lg-5 col-sm-6']])
					->input('date')
					->label('С');
				echo $form->field($searchTariffs, 'finishDate', ['template' =>
					'<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-lg-5 col-sm-6']])
					->input('date')
					->label('По');
				echo '<div class="col-sm-12 text-center"><button type="button" class="btn btn-default tariff-period-choose" data-period="month">За месяц</button><button type="button" class="btn btn-default tariff-period-choose" data-period="year">За год</button></div>';
				echo "<div class='col-sm-12 text-center margened'>";
				echo Html::submitButton('Сформировать', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
				echo '</div>';
				ActiveForm::end();
				echo '</div>';
				if ($result !== null && $activeSearch === 'tariffsSearch' && $result['status'] === 1) {
					if (!empty($result['data']['membership'])) {
						echo '<h2>Членские взносы</h2>';
						foreach ($result['data']['membership'] as $item) {
							echo MembershipStatisticWidget::widget(['quarterInfo' => $item]);
						}
					}
					if (!empty($result['data']['power'])) {
						echo '<h2>Электроэнергия</h2>';
						foreach ($result['data']['power'] as $item) {
							echo PowerStatisticWidget::widget(['monthInfo' => $item]);
						}
					}
					if (!empty($result['data']['target'])) {
						echo '<h2>Целевые взносы</h2>';
						foreach ($result['data']['target'] as $item) {
							echo TargetStatisticWidget::widget(['yearInfo' => $item]);
						}
					}
				}
				?>
            </div>
            <div class="tab-pane fade <?= $tabs['cottagesSearch'] ?>" id="cottagesSearch">
				<?php
				$form = ActiveForm::begin(['id' => 'searchCottages', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'action' => ['/search']]);
				if (!empty($searchCottages->options)) {
					if (!empty($result['conditions'])) {
						echo "<div id='savedConditons' class='hidden'>" . json_encode($result['conditions']) . '</div>';
					}
					echo "<div class='col-sm-12 margened inputs-group'><div class='col-sm-4'>";
					echo "<select id='cottages_columns_0' data-counter='0' name='SearchCottages[columns][0]' class='form-control cottage-columns'><option disabled selected>Выберите параметр</option>";
					foreach ($searchCottages->options as $key => $option) {
						echo "<option value='$key' data-type='{$option['type']}'>{$option['comment']}</option>";
					}
					echo '</select></div>';
					echo '<div class="col-sm-3">
                          <select id="cottages_conditions_0" name=\'SearchCottages[conditions][0]\' class="form-control disabled cottage-conditions" disabled>
                          
</select>
</div>
                          <div class="col-sm-3">
                          <input type="text" id="cottages_values_0" name=\'SearchCottages[values][0]\' class="form-control cottage-values disabled" disabled/>
</div>
</div>
    <div class="col-sm-12 margened"><button type="button" id="addConditionBtn" class="btn btn-info">Добавить условие</button></div>

';
				}
				echo "<div class='text-center'>";
				echo Html::submitButton('Сформировать', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
				echo '</div>';
				ActiveForm::end();

				if ($result !== null && $activeSearch === 'cottagesSearch' && $result['status'] === 1) {
					if (!empty($result['data'])) {
						echo "<div class='col-sm-12'><ol>";
						foreach ($result['data'] as $key => $datum) {
							$url = Url::toRoute(['cottage/show', 'cottageNumber' => $key]);
							echo "<li><a href='$url'>Участок № $key</a></li>";
						}
						echo '</ol></div>';
					}
					else {
						echo "<div class='col-sm-12 text-center'><h2>Ничего не найдено</h2></div>";
					}
				}

				?>
            </div>
        </div>
    </div>