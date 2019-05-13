<?php
namespace app\widgets;

//use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class MembershipFillWidget extends Widget{

    public $quarters;
    public $content = '';

    public function init(){
        if(!empty($this->quarters['quarters'])){
            foreach ($this->quarters['quarters'] as $key=>$quarter){
                echo "<div class='col-lg-12 leader' data-quarter='{$key}'>
                                    <div class='col-lg-4'>
                                        <label class='control-label'>{$quarter['quarterNumber']} квартал {$quarter['year']} года</label>
                                    </div>
                                    <div class='col-lg-2'>
                                        <label class='control-label' for='fixed-{$key}'> Фиксированная часть</label>
                                    </div>
                                    <div class='col-lg-2 form-group'>
                                        <div class='input-group'>
                                            <input type='text' id='fixed-{$key}' name='fixed-{$key}' autocomplete='off' class='form-control'>
                                            <span class='input-group-addon'>&#8381;</span>
                                        </div>
                                    </div>
                                    <div class='col-lg-2'>
                                        <label class='control-label' for='float-{$key}'>Оплата за сотку</label>
                                    </div>
                                    <div  class='col-lg-2 form-group'>
                                            <div class='input-group'>
                                                <input type='text' id='float-{$key}' name='float-{$key}' autocomplete='off' class='form-control'>
                                                <span class='input-group-addon'>&#8381;</span>
                                            </div>
                                    </div>
                                </div>";
            }
        }
    }
    public function run(){
        return Html::decode($this->content);
    }
}