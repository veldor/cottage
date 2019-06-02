<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Счёт на оплату</title>
    <style type="text/css">
        #main-table{
            max-width: 600px;
            width: 100%;
            margin:auto;
            padding: 0;
        }
        .text-center{
            text-align: center;
        }
        .social-icon{
            width: 30px;
            height: 30px;
            position: relative;
            margin-top:10px;
            top:10px;
        }
        img.logo-img{
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
                    В соответствии с Федеральным законом № 217-ФЗ оплата членских и целевых взносов, а также потребленной электроэнергии производится на расчетный счет садоводческого товарищества.<br/>
                    Квитанция на оплату прилагается к письму. Вы можете произвести оплату через интернет-банк, мобильное приложение, терминал оплаты.<br/>
                    При оплате взимается комиссия в соответствии с тарифами банка, но не менее  1%.</p>
                <?/*=$billInfo*/?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <hr/>
                <h3 class="text-center">Контактная информация</h3>
                <p>
                    Бухгалтер: <b>Кочеганова Наталья Николаевна</b><br/>
                    Телефон: <a href="tel:+79108730302"><b>+7 910 873-03-02</b></a>
                    <a href="viber://chat?number=+79108730302"><img width="30px" height="30px" class="social-icon" src="https://i.ibb.co/d4rbkvW/viber-micro.png" alt="viber"></a>
                </p>
                <p>
                    Техподдержка: <b>Кириллов Сергей Александрович</b><br/>
                    Телефон: <a href="tel:+79308184347"><b>+7 930 818-43-47</b></a>
                   <a href="viber://chat?number=+79308184347"><img width="30px" height="30px" class="social-icon" src="https://i.ibb.co/d4rbkvW/viber-micro.png" alt="viber"></a>
                </p>
                <p>
                    Официальная группа ВКонтакте: <a target='_blank' href='https://vk.com/club173020344'>Посетить</a><br/>
                    e-mail: <a href='mailto:oblepiha.snt@yandex.ru'>Написать</a>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
</body>
</html>



