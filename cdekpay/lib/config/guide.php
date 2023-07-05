<?php

return array(
    array(
        'value'       => '%RELAY_URL%%APP_ID%/%MERCHANT_ID%',
        'title'       => 'URL для нотификации по HTTP',
        'description' => 'Значение настройки URL для оповещения активного протокола<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в анкете подключения к приему платежей CDEK Pay</strong>',
    ),
    array(
        'value'       => '%RELAY_URL%?result=success&app_id=%APP_ID%&merchant_id=%MERCHANT_ID%',
        'title'       => 'Страница успешного платежа',
        'description' => 'URL возврата покупателя обратно на сайт после успешной оплаты<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в анкете подключения к приему платежей CDEK Pay</strong>',
    ),
    array(
        'value'       => '%RELAY_URL%?result=fail&app_id=%APP_ID%&merchant_id=%MERCHANT_ID%',
        'title'       => 'Страница ошибки оплаты',
        'description' => 'URL возврата покупателя обратно на сайт в случае ошибки оплаты<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в анкете подключения к приему платежей CDEK Pay</strong>',
    ),
);
