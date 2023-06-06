<?php

/**
 *
 * @author      Webasyst
 * @name cdekPay
 * @description cdekpay Payments Standard Integration
 *
 * @link        https://oplata.cdekpay.ru/develop/api/payments/
 *
 * @property-read        $terminal_key
 * @property-read        $terminal_password
 * @property-read        $currency_id
 * @property-read        $two_steps
 * @property-read        $testmode
 * @property-read int $check_data_tax
 * @property-read string $taxation
 * @property-read string $payment_ffd
 * @property-read string $payment_object_type_product
 * @property-read string $payment_object_type_service
 * @property-read string $payment_object_type_shipping
 * @property-read string $payment_method_type
 *
 */
class cdekpayPayment extends waPayment implements waIPayment, waIPaymentRefund, waIPaymentRecurrent, waIPaymentCancel, waIPaymentCapture
{
    private $order_id;
    private $receipt;
    protected $orderModel;

    private static $currencies = array(
        'RUR' => 643,
        'TST' => 840,
    );

    const CHESTNYZNAK_PRODUCT_CODE = 'chestnyznak';

    /**
     * Получение формы для платежа
     * @return string get payment form url
     */
    protected function getEndpointUrl()
    {
        return $this->cdekpay_testmode
            ? 'https://secure.cdekfin.ru/merchant_api/test_payment_orders'
            : 'https://secure.cdekfin.ru/merchant_api/payment_orders';
    }

    /**
     * Разрешенные валюты
     * @return array|string|string[]|null
     */
    public function allowedCurrency()
    {
        return "RUB";
    }

    /**
     * Разрешенные операции
     * @return array
     */
    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_AUTH_ONLY,
            self::OPERATION_CHECK,
            self::OPERATION_CAPTURE,
            self::OPERATION_REFUND,
            self::OPERATION_CANCEL,
            self::OPERATION_RECURRENT,
        );
    }

    /**
     * Непосредственно формирование формы на оплату
     * @param $payment_form_data
     * @param $order_data
     * @param $auto_submit
     * @return string|null
     * @throws SmartyException
     * @throws waException
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order_data = waOrder::factory($order_data);

        if (empty($order_data['description_en'])) {
            $order_data['description_en'] = 'Order '.$order_data['order_id'];
        }

        $c = new waContact($order_data['customer_contact_id']);
        $email = $c->get('email', 'default');

        if (empty($email)) {
            $email = $this->getDefaultEmail();
        }

        $post = [
            'login' => $this->getSettings('cdekpay_merchant_login'),
            'payment_order' => [
                'pay_amount' => round($order_data['amount'] * 100), // сумма заказа в копейках
                'pay_for' => $this->getSettings('cdekpay_pay_for').$order_data['order_id'],
                'user_phone' => '',
                'user_email' => $email,
                'currency' => $this->getSettings('cdekpay_currency_id'),
                'return_url_success' => $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, []),
                'return_url_fail' => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, []),
                'pay_for_details' => [
                    'payment_type' => '',
                    'payment_base_type' => '',
                    'payment_base' => '',
                    'payment_id' => '',
                ],
            ],
        ];

        if ($phone = $c->get('phone', 'default')) {
            $post['payment_order']['user_phone'] = $phone;
        }

        if ($this->getSettings('cdekpay_check_data_tax')) {
            $post['payment_order']['receipt_details'] = $this->getReceiptData($order_data);
            if (!$post['payment_order']['receipt_details']) {
                return 'Данный вариант платежа недоступен. Воспользуйтесь другим способом оплаты.';
            }
        }

        if ($this->getSettings('cdekpay_testmode')) {
            $secretKey = $this->getSettings('cdekpay_test_secret_key');
        } else {
            $secretKey = $this->getSettings('cdekpay_secret_key');
        }

        try {

            $signed = $this->signWithSignature($post, $secretKey);

            $url = $this->getEndpointUrl();

            $this->logger($url, '$url');
            $this->logger(json_encode($signed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'json_encode');

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($signed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response_json = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response_json, true);

            $this->logger($response_json, '$response_json');

            $payment_url = $response['link'] ?? '';

            $this->orderModel = new shopOrderParamsModel();

            if (isset($response['order_id'])) {
                $this->orderModel->setOne($order_data['order_id'], 'cdekpay_order_id', $response['order_id']);
            }

            if (isset($response['access_key'])) {
                $this->orderModel->setOne($order_data['order_id'], 'cdekpay_access_key', $response['access_key']);
            }

            if (!$payment_url) {
                return null;
            }
        } catch (Exception $ex) {
            return 'Данный вариант платежа недоступен. Воспользуйтесь другим способом оплаты.';
        }
        $view = wa()->getView();

        $view->assign('plugin', $this);
        $view->assign('form_url', $payment_url);
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');

    }


    /**
     * Моя любимая функция Logger
     *
     * @param  [type] $var  [description]
     * @param  string  $text  [description]
     *
     * @return [type]       [description]
     */
    public function logger($var, $text = '')
    {
        // Название файла
        $loggerFile = __DIR__.'/logger.log';
        if (is_object($var) || is_array($var)) {
            $var = (string) print_r($var, true);
        } else {
            $var = (string) $var;
        }
        $string = date("Y-m-d H:i:s")." - ".$text.' - '.$var."\n";
        file_put_contents($loggerFile, $string, FILE_APPEND);
    }


    /**
     * Генерация подписи
     * @param $data
     * @param $secret
     * @return mixed
     */
    public function signWithSignature($data, $secret)
    {
        $forSign = $data;
        unset($forSign['login']);
        $str = $this->concatString($forSign);
        $data['signature'] = strtoupper(hash('sha256', $str.$secret));
        return $data;
    }

    /**
     * Контатинация строк
     * @param $data
     * @return string
     */
    private function concatString($data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $str .= $this->concatString($value);
            } else {
                if (is_scalar($value)) {
                    $str .= $value.'|';
                }
            }
        }
        return $str;
    }

    protected function callbackInit($request)
    {
        $request = $this->sanitizeRequest($request);

        $pattern = '/^([a-z]+)_(\d+)_(.+)$/';
        if (!empty($request['order_id']) && preg_match($pattern, $request['order_id'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        }
        return parent::callbackInit($request);
    }


    /**
     * IPN (Instant Payment Notification)
     * @param $data  - get from gateway
     * @return array|void
     * @throws waPaymentException
     * @throws waException
     */
    protected function callbackHandler($data)
    {
        $data = $this->sanitizeRequest($data);

        if (!isset($data['payment']['access_key'])) {
            self::log($this->id, 'Error: missed request parameter "Access key"');
            return;
        }

        $this->orderModel = new shopOrderParamsModel();

        $orderId = $this->orderModel->getByField(array(
            'name' => 'cdekpay_access_key',
            'value' => $data['payment']['access_key'],
        ), 'order_id');

        if (!$orderId) {
            self::log($this->id, 'Error: cant find  parameter "Order Id"');
            return;
        }

        $this->order_id = $orderId;

        $order = (new shopOrderModel)->getOrder($orderId);

        $transaction_data = $this->formalizeData($data);

        if ($this->getSettings('cdekpay_testmode')) {
            $secretKey = $this->getSettings('cdekpay_test_secret_key');
        } else {
            $secretKey = $this->getSettings('cdekpay_secret_key');
        }

        $data_str = $this->concatString($data['payment']);
        $signature = strtoupper(hash('sha256', $data_str.$secretKey));

        $app_payment_method = self::CALLBACK_CONFIRMATION;

        if ($app_payment_method && $signature != $data['signature']) {
            $method = $this->isRepeatedCallback($app_payment_method, $transaction_data);
            if ($method == $app_payment_method) {
                //Save transaction and run app callback only if it not repeated callback;
                $transaction_data = $this->saveTransaction($transaction_data, $data);
                $this->execAppCallback($app_payment_method, $transaction_data);
            } else {
                $log = array(
                    'message' => 'silent skip callback as repeated',
                    'method' => __METHOD__,
                    'app_id' => $this->app_id,
                    'callback_method' => $method,
                    'original_callback_method' => $app_payment_method,
                    'transaction_data' => $transaction_data,
                );

                static::log($this->id, $log);
            }
        }
    }

    protected function formalizeDataState($data)
    {
        $state = null;
        switch (ifset($data['Status'])) {
            case 'AUTHORIZED':
                $state = self::STATE_AUTH;
                break;

            case 'CONFIRMED':
                $state = self::STATE_CAPTURED;
                break;

            case 'PARTIAL_REFUNDED':
                $state = self::STATE_PARTIAL_REFUNDED;
                break;

            case 'REFUNDED':
                $state = self::STATE_REFUNDED;
                break;

            case 'REJECTED':
                $state = self::STATE_DECLINED;
                break;

            case 'REVERSED':
                $state = self::STATE_DECLINED;
                break;

            default:
                throw new waException('Invalid transaction status');
        }

        return $state;
    }

    /**
     * @param  array<int, array>  $item_product_codes  - array of product code records indexed by id of record
     *  id => [
     *      int      'id'
     *      string   'code'
     *      string   'name' [optional]
     *      string   'icon' [optional]
     *      string   'logo' [optional]
     *      string[] 'values' - promo code item value for each instance of product item
     *  ]
     * @return array - chestnyznak values
     */
    protected function getChestnyznakCodeValues(array $item_product_codes)
    {
        $values = [];
        foreach ($item_product_codes as $product_code) {
            if (isset($product_code['code']) && $product_code['code'] === self::CHESTNYZNAK_PRODUCT_CODE) {
                if (isset($product_code['values'])) {
                    $values = $product_code['values'];
                    break;
                }
            }
        }

        return $values;
    }

    /**
     * Split one product item to several items because chestnyznak marking code must be related for single product instance
     * Extend each new item with 'fiscal_code' value from $values and converted to fiscal code
     * Invariant $item['quantity'] === count($values)
     * @param  array  $item  - order item
     * @param  array  $values  - chestnyznak values
     * @return array[] - array of items. Each item has 'product_code'
     */
    protected function splitItem(array $item, array $values)
    {
        $quantity = (int) ifset($item, 'quantity', 0);
        $items = [];
        for ($i = 0; $i < $quantity; $i++) {
            $value = isset($values[$i]) ? $values[$i] : '';
            $item['fiscal_code'] = $this->convertToFiscalCode($value);
            $item['quantity'] = 1;
            $item['total'] = $item['price'];
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Конвертация из DataMatrix кода (Честный знак) в код фискализации
     * @param $uid
     * @return bool|string
     */
    protected function convertToFiscalCode($uid)
    {
        if (!class_exists('shopChestnyznakPluginCodeParser')) {
            return false;
        }

        $code = shopChestnyznakPluginCodeParser::convertToFiscalCode($uid, [
            'with_tag_code' => false
        ]);
        if (!$code) {
            return false;
        }

        return $code;
    }


    /**
     * Convert transaction raw data to formatted data
     * @param  array  $data  - transaction raw data
     * @return array $transaction_data
     * @throws waException
     */
    protected function formalizeData($data)
    {
        $transaction_data = parent::formalizeData(null);

        $transaction_data['native_id'] = ifset($data['id']);

        $transaction_data['state'] = self::STATE_CAPTURED;
        $transaction_data['parent_id'] = ifset($data['order_id']);;
        $parent_transaction = null;

        $transaction_data['type'] = self::OPERATION_CAPTURE;

        $transaction_data['amount'] = ifset($data['payment']['pay_amount']) / 100;
        $transaction_data['currency_id'] = 'RUB';
        $transaction_data['order_id'] = $this->order_id;
        $transaction_data['result'] = 1;
        $error_code = intval(ifset($data['ErrorCode']));

        $transaction_data['error'] = $this->translateError($error_code);
        if (!empty($transaction_data['error'])) {
            $transaction_data['view_data'] = isset($transaction_data['view_data']) ? ($transaction_data['view_data'].'; ') : '';
            $transaction_data['view_data'] .= $transaction_data['error'];
        }

        return $transaction_data;
    }

    private function translateError($error_code)
    {
        $errors = [
            0 => null,
            7 => 'Покупатель не найден',
            53 => 'Обратитесь к продавцу',
            100 => 'Повторите попытку позже',
            101 => 'Не пройдена идентификация 3DS',
            102 => 'Операция отклонена, пожалуйста обратитесь в интернет-магазин или воспользуйтесь другой картой',
            103 => 'Повторите попытку позже',
            119 => 'Превышено кол-во запросов на авторизацию',
            1001 => 'Свяжитесь с банком, выпустившим карту, чтобы провести платеж',
            1003 => 'Неверный merchant ID',
            1004 => 'Карта украдена. Свяжитесь с банком, выпустившим карту',
            1005 => 'Платеж отклонен банком, выпустившим карту',
            1006 => 'Свяжитесь с банком, выпустившим карту, чтобы провести платеж',
            1007 => 'Карта украдена. Свяжитесь с банком, выпустившим карту',
            1012 => 'Такие операции запрещены для этой карты',
            1013 => 'Повторите попытку позже',
            1014 => 'Карта недействительна. Свяжитесь с банком, выпустившим карту',
            1015 => 'Попробуйте снова или свяжитесь с банком, выпустившим карту',
            1030 => 'Повторите попытку позже',
            1033 => 'Истек срок действия карты. Свяжитесь с банком, выпустившим карту',
            1034 => 'Попробуйте повторить попытку позже',
            1041 => 'Карта утеряна. Свяжитесь с банком, выпустившим карту',
            1043 => 'Карта украдена. Свяжитесь с банком, выпустившим карту',
            1051 => 'Недостаточно средств на карте',
            1054 => 'Истек срок действия карты',
            1057 => 'Такие операции запрещены для этой карты',
            1058 => 'Такие операции запрещены для этой карты',
            1059 => 'Подозрение в мошенничестве. Свяжитесь с банком, выпустившим карту',
            1061 => 'Превышен дневной лимит платежей по карте',
            1062 => 'Платежи по карте ограничены',
            1063 => 'Операции по карте ограничены',
            1065 => 'Превышен дневной лимит транзакций',
            1075 => 'Превышено число попыток ввода ПИН-кода',
            1082 => 'Неверный CVV',
            1088 => 'Ошибка шифрования. Попробуйте снова',
            1089 => 'Попробуйте повторить попытку позже',
            1091 => 'Банк, выпустивший карту недоступен для проведения авторизации',
            1093 => 'Подозрение в мошенничестве. Свяжитесь с банком, выпустившим карту',
            1094 => 'Системная ошибка',
            1096 => 'Повторите попытку позже',
            9999 => 'Внутренняя ошибка системы',
        ];

        return array_key_exists($error_code, $errors) ? $errors[$error_code] : 'Неизвестная ошибка ('.$error_code.').';
    }

    private function getParentTransaction($native_id)
    {
        $tm = new waTransactionModel();
        $search = array(
            'native_id' => $native_id,
            'app_id' => $this->app_id,
            'plugin' => $this->id,
            'type' => array(
                self::OPERATION_AUTH_ONLY,
                self::OPERATION_AUTH_CAPTURE,
            ),
        );

        $transactions = $tm->getByFields($search);
        return $transactions ? reset($transactions) : null;
    }

    /**
     * @param  waOrder  $order
     * @return array|null
     */
    private function getReceiptData(waOrder $order)
    {
        $items = [];

        foreach ($order->items as $item) {

            $item['amount'] = $item['price'] - ifset($item['discount'], 0.0);
            if ($item['price'] > 0 && $item['quantity'] > 0) {

                $item_type = ifset($item['type']);

                switch ($item_type) {
                    case 'shipping':
                        $item['payment_object_type'] = $this->getSettings('cdekpay_payment_object_shipping');
                        break;
                    case 'service':
                        $item['payment_object_type'] = $this->getSettings('cdekpay_payment_object_service');
                        break;
                    case 'product':
                    default:
                        $item['payment_object_type'] = $this->getSettings('cdekpay_payment_object');
                        break;
                }

                $items_data = [$item];
                if ($item_type === 'product') {

                    // typecast workaround for old versions of framework where 'product_codes' key is missing
                    $product_codes = isset($item['product_codes']) && is_array($item['product_codes']) ? $item['product_codes'] : [];

                    $values = $this->getChestnyznakCodeValues($product_codes);
                    if ($values) {
                        $items_data = $this->splitItem($item, $values);
                    }
                }

                foreach ($items_data as $item_data) {
                    $receipt_item = [
                        'name' => preg_replace("/[^(\w)|(\x7F-\xFF)|(\s)]/", "", $item_data['name']),
                        'price' => (int) round($item_data['amount'] * 100),
                        'quantity' => (int) $item_data['quantity'],
                        'sum' => (int) round($item_data['amount'] * $item_data['quantity'] * 100),
                        'payment_object' => (int) $item['payment_object_type'],
                    ];
                    if (isset($item_data['fiscal_code'])) {
                        $receipt_item['mark_code'] = $item_data['fiscal_code'];
                        $receipt_item['mark_code_type'] = 'ean13';
                    }

                    $items[] = $receipt_item;
                }
            }
        }
        return $items;
    }


    /**
     * Очищает запрос от callback
     * @param $request
     * @return mixed
     */
    protected function sanitizeRequest($request)
    {
        if (count($request) <= 1) {
            $json = json_decode(html_entity_decode(file_get_contents('php://input')), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $json;
            }
        }
        return $request;
    }

    protected function getDefaultEmail()
    {
        $mail = new waMail();
        $from = $mail->getDefaultFrom();
        return key($from);
    }

    public function saveSettings($settings = array())
    {
        $settings['terminal_key'] = trim($settings['terminal_key']);
        $settings['terminal_password'] = trim($settings['terminal_password']);
        return parent::saveSettings($settings);
    }


    public function refund($transaction_raw_data)
    {
    }

    public function recurrent($order_data)
    {
    }

    public function cancel($transaction_raw_data)
    {
        try {
            $transaction = $transaction_raw_data['transaction'];
            $args = array(
                'PaymentId' => $transaction['native_id'],
            );

            $data = $this->apiQuery('Cancel', $args);
            $transaction_data = $this->formalizeData($data);

            $this->saveTransaction($transaction_data, $data);

            return array(
                'result' => 0,
                'data' => $transaction_data,
                'description' => '',
            );

        } catch (Exception $ex) {
            $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
            self::log($this->id, $message);
            return array(
                'result' => -1,
                'description' => $ex->getMessage(),
            );
        }
    }

    public function capture($data)
    {
        $args = array(
            'PaymentId' => $data['transaction']['native_id'],
            'Amount' => $data['transaction']['amount'] * 100,
        );

        if (!empty($data['order_data'])) {
            $order = waOrder::factory($data['order_data']);

            if ($data['transaction']['currency_id'] != $order->currency) {
                throw new waPaymentException(sprintf('Currency id changed. Expected %s, but get %s.',
                    $data['transaction']['currency_id'], $order->currency));
            }

            $args['Amount'] = round($order->total * 100);

            if ($this->getSettings('check_data_tax')) {
                $args['Receipt'] = $this->getReceiptData($order);
            }
        }

        // Callbacks from Tinkoff API are pretty fast and often come before
        // the call to /Confirm endpoint returns.
        // We create wa_transaction record beforehand so that
        // the callback is ignored
        $datetime = date('Y-m-d H:i:s');
        $transaction_model = new waTransactionModel();
        $transaction = $this->saveTransaction([
            'native_id' => $data['transaction']['native_id'],
            'type' => self::OPERATION_CAPTURE,
            'result' => 'unfinished',
            'order_id' => $data['transaction']['order_id'],
            'customer_id' => $data['transaction']['customer_id'],
            'amount' => $args['Amount'] / 100,
            'currency_id' => $data['transaction']['currency_id'],
            'parent_id' => $data['transaction']['id'],
            'create_datetime' => $datetime,
            'update_datetime' => $datetime,
            'state' => $data['transaction']['state'],
        ]);

        try {
            $res = $this->apiQuery('Confirm', $args);

            $response = array(
                'result' => 0,
                'description' => '',
            );

            $status = ifset($res, 'Status', '');

            if ($status != 'CONFIRMED') {
                $transaction['state'] = self::STATE_DECLINED;
                $transaction['result'] = 0;
                $transaction['error'] = ifset($res['Message']); // $this->translateError(isset($res['ErrorCode']))
                $transaction['view_data'] = ifset($res['Details']);
                $response['result'] = -1;
                $response['description'] = $transaction['error'];
            } else {
                $transaction['result'] = 1;
                $transaction['state'] = self::STATE_CAPTURED;
            }

            $transaction['parent_state'] = $transaction['state'];

            $transaction_model->deleteById($transaction['id']);
            unset($transaction['id']);
            $response['data'] = $this->saveTransaction($transaction, $res);

            return $response;
        } catch (Exception $ex) {
            if (isset($transaction['id'])) {
                $transaction_model->deleteById($transaction['id']);
            }
            return null;
        }
    }
}
