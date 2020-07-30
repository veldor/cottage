<?php

/* @var $this yii\web\View */

use app\assets\TariffsAsset;
use app\models\MembershipHandler;
use app\models\PowerHandler;
use app\models\TimeHandler;
use app\widgets\MembershipStatisticWidget;
use app\widgets\PowerStatisticWidget;
use app\widgets\TargetStatisticWidget;
use nirvana\showloading\ShowLoadingAsset;
use onmotion\apexcharts\ApexchartsWidget;

/** @var app\models\TariffsKeeper $lastTariffs */

ShowLoadingAsset::register($this);
TariffsAsset::register($this);


$this->title = 'Центр управления';

echo '<h2 class="text-center">Потребление электроэнергии</h2>';
// получу потреблённую электроэнергию за каждый месяц года
$consumption = PowerHandler::getYearConsumption(TimeHandler::getThisYear());


echo ApexchartsWidget::widget([
    'type' => 'area', // default area
    'height' => '350', // default 350
    'chartOptions' => [
        'chart' => [
            'locales' => [
                [
                    "name" => "ru",
                    "options" => [
                        "months" => ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                        "shortMonths" => ["Янв", "Февр", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек"],
                        "days" => ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
                        "shortDays" => ["Вс", "Пон", "Вт", "Ср", "Чт", "Пт", "Сб"],
                        "toolbar" => [
                            "exportToSVG" => "Скачать SVG",
                            "exportToPNG" => "Скачать PNG",
                            "menu" => "Меню",
                            "selection" => "Выборка",
                            "selectionZoom" => "Увеличение выборки",
                            "zoomIn" => "Увеличить",
                            "zoomOut" => "Уменьшить",
                            "pan" => "Panning",
                            "reset" => "Сброс увеличения"
                        ]
                    ]
                ]
            ],
            'defaultLocale' => "ru",
            'toolbar' => [
                'show' => true,
                'autoSelected' => 'zoom'
            ],
        ],
        'xaxis' => [
            'type' => 'datetime',
            // 'categories' => $categories,
        ],
        'plotOptions' => [
            'bar' => [
                'horizontal' => false,
                'endingShape' => 'rounded'
            ],
        ],
        'dataLabels' => [
            'enabled' => true
        ],
        'stroke' => [
            'show' => true,
            'colors' => ['transparent']
        ],
        'legend' => [
            'verticalAlign' => 'bottom',
            'horizontalAlign' => 'left',
        ],
    ],
    'series' => [['name' => 'Потребление электроэнергии, Квт*ч', 'data' => $consumption]]
]);

echo '<h2 class="text-center">Оплата электроэнергии</h2>';

$powerPayments = PowerHandler::getYearPayments(TimeHandler::getThisYear());

echo ApexchartsWidget::widget([
    'type' => 'area', // default area
    'height' => '350', // default 350
    'chartOptions' => [
        'chart' => [
            'locales' => [
                [
                    "name" => "ru",
                    "options" => [
                        "months" => ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                        "shortMonths" => ["Янв", "Февр", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек"],
                        "days" => ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
                        "shortDays" => ["Вс", "Пон", "Вт", "Ср", "Чт", "Пт", "Сб"],
                        "toolbar" => [
                            "exportToSVG" => "Скачать SVG",
                            "exportToPNG" => "Скачать PNG",
                            "menu" => "Меню",
                            "selection" => "Выборка",
                            "selectionZoom" => "Увеличение выборки",
                            "zoomIn" => "Увеличить",
                            "zoomOut" => "Уменьшить",
                            "pan" => "Panning",
                            "reset" => "Сброс увеличения"
                        ]
                    ]
                ]
            ],
            'defaultLocale' => "ru",
            'toolbar' => [
                'show' => true,
                'autoSelected' => 'zoom'
            ],
        ],
        'xaxis' => [
            'type' => 'datetime',
            // 'categories' => $categories,
        ],
        'plotOptions' => [
            'bar' => [
                'horizontal' => false,
                'endingShape' => 'rounded'
            ],
        ],
        'dataLabels' => [
            'enabled' => true
        ],
        'stroke' => [
            'show' => true,
            'colors' => ['transparent']
        ],
        'legend' => [
            'verticalAlign' => 'bottom',
            'horizontalAlign' => 'left',
        ],
    ],
    'series' => $powerPayments
]);

$membershipPayments = MembershipHandler::getYearPayments(TimeHandler::getThisYear());

echo '<h2 class="text-center">Оплата членских взносов</h2>';

echo ApexchartsWidget::widget([
    'type' => 'area', // default area
    'height' => '350', // default 350
    'chartOptions' => [
        'chart' => [
            'locales' => [
                [
                    "name" => "ru",
                    "options" => [
                        "months" => ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                        "shortMonths" => ["Янв", "Февр", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек"],
                        "days" => ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
                        "shortDays" => ["Вс", "Пон", "Вт", "Ср", "Чт", "Пт", "Сб"],
                        "toolbar" => [
                            "exportToSVG" => "Скачать SVG",
                            "exportToPNG" => "Скачать PNG",
                            "menu" => "Меню",
                            "selection" => "Выборка",
                            "selectionZoom" => "Увеличение выборки",
                            "zoomIn" => "Увеличить",
                            "zoomOut" => "Уменьшить",
                            "pan" => "Panning",
                            "reset" => "Сброс увеличения"
                        ]
                    ]
                ]
            ],
            'defaultLocale' => "ru",
            'toolbar' => [
                'show' => true,
                'autoSelected' => 'zoom'
            ],
        ],
        'xaxis' => [
            'type' => 'datetime',
            // 'categories' => $categories,
        ],
        'plotOptions' => [
            'bar' => [
                'horizontal' => false,
                'endingShape' => 'rounded'
            ],
        ],
        'dataLabels' => [
            'enabled' => true
        ],
        'stroke' => [
            'show' => true,
            'colors' => ['transparent']
        ],
        'legend' => [
            'verticalAlign' => 'bottom',
            'horizontalAlign' => 'left',
        ],
    ],
    'series' => $membershipPayments
]);

echo '<h2>Электроэнергия</h2>';
echo PowerStatisticWidget::widget(['monthInfo' => $lastTariffs->power]);
echo '<h2>Членские взносы</h2>';
echo MembershipStatisticWidget::widget(['quarterInfo' => $lastTariffs->membership]);
echo '<h2>Целевые взносы</h2>';
if (!empty($lastTariffs->target)) {
    echo TargetStatisticWidget::widget(['yearInfo' => $lastTariffs->target]);
} else {
    echo "<button id='createTargetPayment' class='btn btn-success'>Создать целевой платёж</button>";
}
