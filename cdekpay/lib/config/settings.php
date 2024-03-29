<?php

$optionsForObject = [
    '1' => 'о реализуемом товаре, за исключением подакцизного товара и товара, подлежащего маркировке средствами идентификации (наименование и иные сведения, описывающие товар)',
    '3' => 'о суммах произведенных расходов в соответствии со статьей 346.16 Налогового кодекса Российской Федерации, уменьшающих доход',
    '4' => 'об оказываемой услуге (наименование и иные сведения, описывающие услугу)',
    '5' => 'о приеме ставок при осуществлении деятельности по проведению азартных игр',
    '6' => 'о выплате денежных средств в виде выигрыша при осуществлении деятельности по проведению азартных игр',
    '7' => 'о приеме денежных средств при реализации лотерейных билетов, электронных лотерейных билетов, приеме лотерейных ставок при осуществлении деятельности по проведению лотерей',
    '9' => 'о предоставлении прав на использование результатов интеллектуальной деятельности или средств индивидуализации',
    '10' => 'об авансе, задатке, предоплате, кредите',
    '11' => 'о вознаграждении пользователя, являющегося платежным агентом (субагентом), банковским платежным агентом (субагентом), комиссионером, поверенным или иным агентом',
    '12' => 'о взносе в счет оплаты, пени, штрафе, вознаграждении, бонусе и ином аналогичном предмете расчета',
    '13' => 'о предмете расчета, не относящемуся к предметам расчета, которым может быть присвоено значение от «1» до «11» и от «14» до «26»',
    '14' => 'о передаче имущественных прав',
    '15' => 'о внереализационном доходе',
    '16' => 'о суммах расходов, платежей и взносов, указанных в подпунктах 2 и 3 пункта Налогового кодекса Российской Федерации, уменьшающих сумму налога',
    '17' => 'о суммах уплаченного торгового сбора',
    '18' => 'о курортном сбореа',
    '19' => 'о залоге',
    '20' => 'о суммах произведенных расходов в соответствии со статьей 346.16 Налогового кодекса Российской Федерации, уменьшающих доход',
    '21' => 'о страховых взносах на обязательное пенсионное страхование, уплачиваемых ИП, не производящими выплаты и иные вознаграждения физическим лицам',
    '22' => 'о страховых взносах на обязательное пенсионное страхование, уплачиваемых организациями и ИП, производящими выплаты и иные вознаграждения физическим лицам',
    '23' => 'о страховых взносах на обязательное медицинское страхование, уплачиваемых ИП, не производящими выплаты и иные вознаграждения физическим лицам',
    '24' => 'о страховых взносах на обязательное медицинское страхование, уплачиваемые организациями и ИП, производящими выплаты и иные вознаграждения физическим лицам',
    '25' => 'о приеме и выплате денежных средств при осуществлении казино и залами игровых автоматов расчетов с использованием обменных знаков игорного заведения',
    '27' => 'о выдаче денежных средств банковским платежным агентом',
    '32' => 'о реализуемом товаре, подлежащем маркировке средством идентификации, не имеющем кода маркировки, за исключением подакцизного товара',
    '33' => 'о реализуемом товаре, подлежащем маркировке средством идентификации, не имеющем кода маркировки, за исключением подакцизного товара',
];

return array(
    'cdekpay_testmode' => array(
        'value' => '',
        'title' => 'Тестовый режим',
        'description' => /*_wp*/
            ('Только для тестирования по старой схеме через платежный шлюз'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'cdekpay_merchant_login' => array(
        'value' => '',
        'title' => 'Логин для API и платёжной формы',
        'description' => /*_wp*/
            ('Логин продавца в личном кабинете <em>https://secure.cdekfin.ru</em>'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'cdekpay_secret_key' => array(
        'value' => '',
        'title' => /*_wp*/
            ('Секретный ключ для боевого режима'),
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'cdekpay_test_secret_key' => array(
        'value' => '',
        'title' => /*_wp*/
            ('Секретный ключ для тестового режима'),
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'cdekpay_currency_id' => array(
        'value' => '',
        'title' => /*_wp*/
            ('Валюта'),
        'description' => /*_wp*/
            ('Валюта, в которой будут выполняться платежи'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array('title' => 'Рубли RUB', 'value' => 'RUR'),
            array('title' => 'Тестовая TST', 'value' => 'TST'),
        ),
    ),
    'cdekpay_pay_for' => array(
        'value' => '',
        'title' => 'Основание для оплаты',
        'description' => /*_wp*/
            ('За что платим? можно просто написать "Оплата в интернет магазине site.ru за заказ №:" и у вас просто подставится номер заказа в конце'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'cdekpay_payment_object' => array(
        'value' => '',
        'title' => 'Объект продукта для оплаты',
        'control_type' => waHtmlControl::SELECT,
        'options' => $optionsForObject
    ),
    'cdekpay_payment_object_service' => array(
        'value' => '',
        'title' => 'Объект услуги для оплаты',
        'control_type' => waHtmlControl::SELECT,
        'options' => $optionsForObject
    ),
    'cdekpay_payment_object_shipping' => array(
        'value' => '',
        'title' => 'Объект доставки для оплаты',
        'control_type' => waHtmlControl::SELECT,
        'options' => $optionsForObject
    ),
    'cdekpay_check_data_tax' => array(
        'value' => '',
        'title' => /*_wp*/
            ('Передавать данные для формирования чека'),
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'Если включена интеграция с онлайн кассами, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 20% и <em>включен</em> в стоимость элементов заказа и стоимость доставки'.

            <<<HTML
<script type="text/javascript">
(function () {
    $(':input[name$="\[cdekpay_check_data_tax\]"]').unbind('change').bind('change', function (event) {
        var show = this.checked;
        var fast = !event.originalEvent;
        var name = [
            'taxation',
            'payment_object_type_product',
            'payment_object_type_service',
            'payment_object_type_shipping',
            'payment_method_type'
        ];
        var selector = [];
        for (var i = 0; i < name.length; i++) {
            selector.push(':input[name$="\[' + name[i] + '\]"]');
        }
        selector = selector.join(', ');
        $(this).parents('form').find(selector).each(function () {
            if (show) {
                $(this).parents('div.field').show(400);
            } else {
                if (fast) {
                    $(this).parents('div.field').hide();
                } else {
                    $(this).parents('div.field').hide(400);
                }
            }
        })
    }).trigger('change');
})();


</script>
HTML
    ,
    ),
);
