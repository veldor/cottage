<?php
namespace app\widgets;

//use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class TargetsFillWidget extends Widget{

    public $tariffRates;
    public $disabled;
    public $formName;
    public $content = '';

    public function init(){
        if(!empty($this->tariffRates)){
            if($this->disabled)
                $this->disabled = 'disabled';
            $this->content .= "<div class='col-lg-12 text-center'><h2>Заполнение целевых взносов</h2></div>";
            foreach ($this->tariffRates as $key =>$rate){
                    $this->content .=  "<div class='form-group'>
<div class='col-lg-4'><label class='control-label'>" . $key . " год: Долг <b class='text-danger summ' data-fixed='{$rate['fixed']}' data-float='{$rate['float']}'>" . $rate['totalSumm'] . "</b>  &#8381;</label></div>
<div class='col-lg-5'>
<div class='btn-group' data-toggle='buttons'>
  <label class='btn btn-primary target-dependent {$this->disabled}'>
          <input type='radio' class='target-radio target-dependent' name='{$this->formName}[target][" . $key . "][payed-of]' value='full' data-year='{$key}' {$this->disabled}> Оплачен
        </label>
  <label class='btn btn-primary target-dependent {$this->disabled}'>
          <input type='radio' class='target-radio target-dependent' name='{$this->formName}[target][" . $key . "][payed-of]' value='no-payed' data-year='{$key}' {$this->disabled}> Не оплачен
        </label>
  <label class='btn btn-primary target-dependent {$this->disabled}'>
              <input type='radio' class='target-radio target-dependent' name='{$this->formName}[target][" . $key . "][payed-of]' value='partial' data-year='{$key}' {$this->disabled}> Частично
        </label>
</div>
        <div class='help-block'></div>
</div>
<div class='col-lg-3 text-input-parent'><div class='input-group'><input type='text' class='form-control target-input' id='addcottage-target_{$key}' name='{$this->formName}[target][" . $key . "][payed-summ]' value='0' autocomplete='off' aria-invalid='false' aria-required='false' disabled><span class='input-group-addon'> &#8381;</span></div><div class='help-block'></div></div>
</div>";
            }
        }
    }
    public function run(){
        return Html::decode($this->content);
    }
}