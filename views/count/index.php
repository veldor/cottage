<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 22.10.2018
 * Time: 16:50
 */

use app\assets\CountAsset;
use nirvana\showloading\ShowLoadingAsset;

ShowLoadingAsset::register($this);

CountAsset::register($this);

$this->title = "Баланс садоводства";
/* @var $this \yii\web\View */
/* @var \app\models\Balance $model */
?>
<div class="row">
    <div class="col-sm-6 text-center">
        <h2>Наличные</h2>
        <h3>Баланс: <b class="text-success"><span id="cashBalanse"><?= $model->currentBalance ?></span> &#8381;</b></h3>
        <div class="row">
            <div class="col-sm-4"><h5>Приход за день:</h5>
                <button type="button" id="dayInBtn" class="btn btn-info">Показать</button>
                <br/>&nbsp;<b id="dayIn" class="text-success"></b></div>
            <div class="col-sm-4"><h5>Приход за месяц:</h5>
                <button type="button" id="monthInBtn" class="btn btn-info">Показать</button>
                <br/>&nbsp;<b id="monthIn" class="text-success"></b></div>
            <div class="col-sm-4"><h5>Приход за год:</h5>
                <button type="button" id="yearInBtn" class="btn btn-info">Показать</button>
                <br/>&nbsp;<b id="yearIn" class="text-success"></b></div>
            <div class="col-sm-4"><h5>Расход за день:</h5>
                <button type="button" id="dayOutBtn" class="btn btn-info">Показать</button>
                <br/>&nbsp;<b id="dayOut" class="text-success"></b></div>
            <div class="col-sm-4"><h5>Расход за месяц:</h5>
                <button type="button" id="monthOutBtn" class="btn btn-info">Показать</button>
                <br/>&nbsp;<b id="monthOut" class="text-success"></b></div>
            <div class="col-sm-4"><h5>Расход за год:</h5>
                <button type="button" id="yearOutBtn" class="btn btn-info">Показать</button>
                <br/>&nbsp;<b id="yearOut" class="text-success"></b></div>
            <div class="col-sm-12">
                <button class="btn btn-primary">Перевести на банковский счёт >></button>
            </div>
            <div class="col-sm-12">
                <br/>
                <button class="btn btn-success">Добавить сумму</button>
                <button class="btn btn-danger">Списать сумму</button>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 text-center">
                <br/>
                <div class="btn-group-vertical">
                    <button id="showDayTransactions" type="button" class="btn btn-info btn-block">Показать транзакции за
                        день
                    </button>
                    <button id="showMonthTransactions" type="button" class="btn btn-info btn-block">Показать транзакции
                        за месяц
                    </button>
                    <button id="showYearTransactions" type="button" class="btn btn-info btn-block">Показать транзакции
                        за год
                    </button>
                </div>
                <div class="btn-group-vertical">
                    <button id="showDaySummary" type="button" class="btn btn-info btn-block">Сводка за
                        день
                    </button>
                    <button id="showMonthSummary" type="button" class="btn btn-info btn-block">Сводка
                        за месяц
                    </button>
                    <button id="showYearSummary" type="button" class="btn btn-info btn-block">Сводка за год
                    </button>
                </div>
            </div>
            <div class="col-lg-12 text-left" id="transactonsList"></div>
        </div>
    </div>
    <div class="col-sm-6 text-center">
        <h2>Банковский счёт</h2>
        <h3>Баланс: <span id="cashlessBalanse">0</span> &#8381;</h3>
        <div class="row">
            <div class="col-lg-4"><h5>Приход за день: <span>0</span> &#8381;</h5></div>
            <div class="col-lg-4"><h5>Приход за месяц: <span>0</span> &#8381;</h5></div>
            <div class="col-lg-4"><h5>Приход за год: <span>0</span> &#8381;</h5></div>
            <div class="col-lg-4"><h5>Расход за день: <span>0</span> &#8381;</h5></div>
            <div class="col-lg-4"><h5>Расход за месяц: <span>0</span> &#8381;</h5></div>
            <div class="col-lg-4"><h5>Расход за год: <span>0</span> &#8381;</h5></div>
            <div class="col-lg-12">
                <button class="btn btn-primary"><< Перевести в наличные</button>
            </div>
            <div class="col-lg-12">
                <br/>
                <button class="btn btn-success">Добавить сумму</button>
                <button class="btn btn-danger">Списать сумму</button>
            </div>
        </div>
    </div>

</div>
