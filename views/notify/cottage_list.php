<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.05.2019
 * Time: 11:14
 */

/* @var $this \yii\web\View */
/* @var $info array */

echo "<div class='btn-group-vertical margened'>
<button id='selectAllActivator' type='button' class='btn btn-info'>Выбрать всё</button>
<button id='selectNoneActivator' type='button' class='btn btn-info'>Сбросить выделение</button>
<button id='selectInvertActivator' type='button' class='btn btn-info'>Инвертировать выделение</button>
<button id='selectOwnersActivator' type='button' class='btn btn-info'>Всем владельцам</button>
<button id='selectContactersActivator' type='button' class='btn btn-info'>Всем к.л.</button>
</div>";

echo '<table class="table table-bordered table-striped table-condensed table-hover margened"><thead><tr><th>№ участка</th><th>Письмо владельцу</th><th>Письмо к.л.</th></thead><tbody>';

if(!empty($info)){
    foreach ($info['info'] as $item) {
        $index = !empty($item['double']) ? $item['cottageNumber'] . '-a' : $item['cottageNumber'];
        if(!empty($item['mail'])){
            $mailBlock = "<label class='btn btn-success'><input type='checkbox' name='cottage-{$index}' data-cottage-id='{$item['cottageNumber']}' data-double='{$item['double']}' class='owner-mail'/>Отправить владельцу</label>";
        }
        else{
            $mailBlock = '<span class="text-warning">Нет адреса</span>';
        }
        if(!empty($item['contacterMail'])){
            $cMailBlock = "<label class='btn btn-success'><input type='checkbox' class='contacter-mail' name='cottage-{$index}' data-cottage-id='{$item['cottageNumber']}' data-double='{$item['double']}'/>Отправить контактному лицу</label>";
        }
        else{
            $cMailBlock = '<span class="text-warning">Нет адреса</span>';
        }
        echo "<tr><td class='cottage-container' data-cottage-id='{$item['cottageNumber']}' data-double='{$item['double']}'>$index</td><td>$mailBlock</td><td>$cMailBlock</td></tr>";
    }
}
echo '</tbody></table>';
echo '<button id="startMailingActivator" class="btn btn-success">Отправить письма</button>';