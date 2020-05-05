<?php

/* @var $this yii\web\View */
use app\assets\IndexAsset;
use app\models\Reminder;
use nirvana\showloading\ShowLoadingAsset;
use app\widgets\CottagesShowWidget;

/** @var \app\models\Table_cottages $existedCottages */
/* @var $this yii\web\View */
ShowLoadingAsset::register($this);
IndexAsset::register($this);

if(Reminder::requreRemind()){
    Yii::$app->session->addFlash('info', 'Пора напомнить о членских взносах! <a href="' . \yii\helpers\Url::toRoute('/membership/remind') . '" target="_blank" class="btn btn-default"><span class="text-info">Напомнить</span></a>');
}

$this->title = 'Центр управления';
?>
<div class="row">
    <div class="col-lg-12">
        <p>
            <!--<button type="button" id="addCottageBtn" class="btn btn-success">Добавить участок</button>-->
        </p>
    </div>
    <div class="col-lg-12">
        <?php try {
            echo CottagesShowWidget::widget(['cottages' => $existedCottages]);
        } catch (Exception $e) {
        } ?>
    </div>
</div>
