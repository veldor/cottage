<?php

namespace app\widgets;

use app\models\CashHandler;
use app\models\selections\PenaltyItem;
use app\models\TimeHandler;
use yii\base\Widget;
use yii\helpers\Html;

class ShowPenaltiesWidget extends Widget
{
    private string $content = '';
    /**
     * @var PenaltyItem[]
     */
    private array $penalties;

    public function __construct($config = [])
    {
        parent::__construct([]);
        $this->penalties = $config['penalties'];
    }

    public function run()
    {
        if (!empty($this->penalties)) {
            foreach ($this->penalties as $penalty) {
                $this->content .= '<tr>';
                // тип
                $this->content .= '<td>' . $penalty->getType() . '</td>';
                // период
                $this->content .= '<td>' . TimeHandler::getFullFromShotMonth($penalty->getPeriod()) . '</td>';
                // статус
                if ($penalty->isRegistered()) {
                    if ($penalty->isActive()) {
                        $this->content .= "<td><b class='text-success'>Активно</b></td>";
                    } else {
                        $this->content .= "<td><b class='text-danger'>Неактивно</b></td>";
                    }
                } else {
                    $this->content .= "<td><b class='text-info'>Игнор</b></td>";
                }
                // срок оплаты
                $this->content .= '<td>' . TimeHandler::getDateFromTimestamp($penalty->getPayUp()) . '</td>';
                // дата оплаты
                $this->content .= '<td>' . ($penalty->getPayDate() > 0 ? TimeHandler::getDateFromTimestamp($penalty->getPayDate()) : '--') . '</td>';
                // дней просрочено
                $this->content .= '<td>' . $penalty->getDayDifference() . '</td>';
                // Величина задолженности
                $this->content .= '<td>' . CashHandler::toShortSmoothRubles($penalty->getArrears()) . '</td>';
                // Стоимость дня просрочки
                $this->content .= '<td>' . CashHandler::toShortSmoothRubles($penalty->getPayPerDay()) . '</td>';
                // Общая стоимость пени
                $this->content .= '<td>' . CashHandler::toShortSmoothRubles($penalty->getTotalAccrued()) . ($penalty->isLocked() ? '<span class="text-danger glyphicon glyphicon-lock"></span>' : '') .  '</td>';
                $this->content .= '<td>' . CashHandler::toShortSmoothRubles($penalty->getThisFinePayedSum()) . '</td>';
                $this->content .= '<td>' . ($penalty->isFullPayed() ? '<b class="text-success">Да</b>' : '<b>Нет</b>') . '</td>';
                $this->content .= '</tr>';
            }
        }
        return Html::decode($this->content);
    }
}