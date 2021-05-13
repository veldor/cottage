<?php

/* @var $this yii\web\View */

use app\assets\IndexAsset;
use app\models\Reminder;
use app\models\Table_cottages;
use app\widgets\CottagesShowWidget;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Url;

/** @var Table_cottages $existedCottages */
/* @var $this yii\web\View */
ShowLoadingAsset::register($this);
IndexAsset::register($this);

if (Reminder::requreRemind()) {
    Yii::$app->session->addFlash('info', 'Пора напомнить о членских взносах! <a href="' . Url::toRoute('/membership/remind') . '" target="_blank" class="btn btn-default"><span class="text-info">Напомнить</span></a>');
}

$this->title = 'Центр управления';
?>
<div class="row">
    <div class="col-lg-12 text-center">
        <p>
            <!--<button type="button" id="addCottageBtn" class="btn btn-success">Добавить участок</button>-->
            <a class="activator btn btn-default" data-action="utils/refresh-main-data"><span class="text-success">Актуализировать данные</span></a>
        </p>
    </div>
    <div class="col-lg-12">
        <?php try {
            echo CottagesShowWidget::widget(['cottages' => $existedCottages]);
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        } ?>
    </div>
</div>
