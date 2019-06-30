<?php

use app\priv\Info;

?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Счёт на оплату</title>
    <style type="text/css">
        #main-table {
            max-width: 600px;
            width: 100%;
            margin: auto;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }

        .social-icon {
            width: 30px;
            height: 30px;
            position: relative;
            margin-top: 10px;
            top: 10px;
        }

        img.logo-img {
            width: 50%;
            margin-left: 25%;
        }
    </style>
</head>
<body>
<table id="main-table">
    <tbody>
    <tr>
        <td colspan="2">
            <img style="width:50%" class="logo-img" src="https://i.ibb.co/S3RTDG3/logo-mini.png" alt="logo">
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <h1>Добрый день, %USERNAME%</h1>
            <p>Вам выставлен счёт на оплату от садоводческого некоммерческого товарищества «Облепиха».<br/>
                В соответствии с Федеральным законом № 217-ФЗ оплата членских и целевых взносов, а также потребленной
                электроэнергии производится на расчетный счет садоводческого товарищества.<br/>
                Квитанция на оплату прилагается к письму. Вы можете произвести оплату через интернет-банк, мобильное
                приложение, терминал оплаты.<br/>
                При оплате взимается комиссия в соответствии с тарифами банка, но не менее 1%.</p>
            <?= $billInfo ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <!--Футер-->
            <hr/>
            <h3 class="text-center">Контактная информация</h3>
            <p>
                Председатель: <b><?= Info::CHAIRMAN_NAME_FULL ?></b><br/>
                Телефон: <a href="tel:<?= Info::CHAIRMAN_PHONE ?>"><b><?= Info::CHAIRMAN_SMOOTH_PHONE ?></b></a>
                <a href="viber://chat?number=<?= Info::CHAIRMAN_PHONE ?>"><img width="30px" height="30px"
                                                                               class="social-icon"
                                                                               src="https://i.ibb.co/d4rbkvW/viber-micro.png"
                                                                               alt="viber"></a>
            </p>
            <p>
                Бухгалтер: <b><?= Info::BOOKER_NAME_FULL ?></b><br/>
                Телефон: <a href="tel:<?= Info::BOOKER_PHONE ?>"><b><?= Info::BOOKER_SMOOTH_PHONE ?></b></a>
                <a href="viber://chat?number=<?= Info::BOOKER_PHONE ?>"><img width="30px" height="30px"
                                                                             class="social-icon"
                                                                             src="https://i.ibb.co/d4rbkvW/viber-micro.png"
                                                                             alt="viber"></a>
            </p>
            <p>
                Техподдержка: <b><?= Info::TECH_NAME ?></b><br/>
                Телефон: <a href="tel:<?= Info::TECH_PHONE ?>"><b><?= Info::TECH_SMOOTH_PHONE ?></b></a>
                <a href="viber://chat?number=<?= Info::TECH_PHONE ?>"><img width="30px" height="30px"
                                                                           class="social-icon"
                                                                           src="https://i.ibb.co/d4rbkvW/viber-micro.png"
                                                                           alt="viber"></a>
            </p>
            <p>
                Официальная группа ВКонтакте: <a target='_blank' href='<?= Info::VK_GROUP_URL ?>'>Посетить</a><br/>
                e-mail: <a href='mailto:<?= Info::MAIL_ADDRESS ?>'>Написать</a>
            </p>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>



