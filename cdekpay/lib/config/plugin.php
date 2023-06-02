<?php
return array(
    'name'            => 'CDEK Pay',
    'description'     => 'Оплата картами Mir, VISA, MasterCard и Maestro через интернет-эквайринг банка CDEK Pay',
    'icon'            => 'img/cdekpay16.png',
    'logo'            => 'img/cdekpay.png',
    'vendor'          => 'webasyst',
    'version'         => '1.0.00',
    'type'            => waPayment::TYPE_ONLINE,
    'partial_refund'  => true,
    'partial_capture' => true,
    'fractional_quantity' => true,
    'stock_units'         => true,
);
