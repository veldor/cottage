<?php
namespace app\widgets;

//use Yii;
use app\models\Filling;
use app\models\TimeHandler;
use yii\base\Widget;
use yii\helpers\Html;

class AllCottagesWidget extends Widget{

    /**
     * @var $info Filling
     */
    public $info;
    private $cottagesQuantity = 180;
    public $content = '';

    /**
     *
     */
    public function init(){
            $this->content .= '<h1>Заполнение показаний датчиков электроэнергии за ' . TimeHandler::getPreviousMonth() . "</h1>
                                <p>Лимит льготного использования электроэнергии- <b class='text-info tariff-power-limit'>{$this->info['tariff']['powerLimit']}</b> кВТ∙ч,
                                Цена киловатта энергии в пределах льготного лимита-  <b class='text-info tariff-power-cost'>{$this->info['tariff']['powerCost']}</b> &#8381;
                                Цена киловатта энергии за пределами льготного лимита- <b class='text-info tariff-power-overcost'>{$this->info['tariff']['powerOvercost']}</b> &#8381;</p>";
            $i = 1;
            $month = TimeHandler::getPreviousShortMonth();
            while($i <= $this->cottagesQuantity){
                if(isset($this->info['cottages'][$i])){
                    $additional = '';
                    if(isset($this->info['cottages'][$i]['additionalData'])){
                        $compact = '';
                        if(!empty($this->info['cottages'][$i]['filled'])){
                            $compact = 'compact';
                        }
                        if(isset($this->info['cottages'][$i]['additionalFilled'])){
                            $additional = "<b class='text-success'>Заполнено</b><br><b class='text-primary'>{$this->info['cottages'][$i]['additionalData']} кВт.ч</b>";
                        }
                        else{
                            $additional = "<input type='text' autocomplete='off' id='power-cottage-additional-$i' name='power-cottage-additional-$i' class='form-control power-fill popovered input-sm {$compact}'  data-container='body' data-toggle='popover' data-placement='top' data-content='Дополнительный участок<br/>Последние показания счётчика -<br/><b class=\"text-success\" >{$this->info['cottages'][$i]['additionalData']}</b> кВТ∙ч' data-cottage='{$i}' data-additional='1' data-previous='{$this->info['cottages'][$i]['additionalData']}' data-month='{$month}'>";
                        }
                    }
                    if(!empty($this->info['cottages'][$i]['filled'])){
                        // Если данные за предыдущий месяц заполнены- отображаю как заполненные
                        $this->content .= "<div class='col-lg-1 col-sm-2 color-salad text-center margened cottage-card'><b class='text-info'> $i</b><br/><b class='text-success'>Заполнено</b><br/><b class='text-primary'>{$this->info['cottages'][$i]['currentData']} кВт.ч</b><br/>{$additional}</div>";
                    }
                    else{
                        $this->content .= "<div class='col-lg-1 col-sm-2 color-pinky text-center margened cottage-card'><b class='text-info'> $i</b><input type='text' autocomplete='off' id='power-cottage-$i' name='power-cottage-$i' class='input-sm form-control power-fill popovered'  data-container=\"body\" data-toggle=\"popover\" data-placement=\"top\" data-content=\"Последние показания счётчика- <br><b class='text-success'>{$this->info['cottages'][$i]['currentData']}</b> кВТ∙ч\"
data-cottage='$i' data-previous='{$this->info['cottages'][$i]['currentData']}' data-month='$month'>{$additional}</div>";
                    }
                }
                else {
                    $this->content .= "<div class='col-lg-1 col-sm-2 text-center margened cottage-card'><b class='text-info'>$i</b><br>--</div>";
                }
                if($i % 12 === 0){
                    $this->content .= "<div class='clearfix'></div>";
                }
                $i++;
            }
    }
    public function run():string
    {
        return Html::decode($this->content);
    }
}