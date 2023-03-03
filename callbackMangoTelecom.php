<?
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
ini_set('error_reporting', E_ALL);

$deal = $_REQUEST['deal'];
$phone = $_REQUEST['phone'];
$cnt = $_REQUEST['cnt'];
$prioritet = $_REQUEST['prioritet'];

/*
Запуск скрипта обсуществляется из БП на сделках в самом первом этапе.
Если скрипт запускается первый раз, то по умолчанию приходит приоритет 2 (звонок распределяется на стажеров).
Если скрипт запускается второй и более раз, то уже на опытных менеджеров.
 */

if ($prioritet == 2) {
    $fromNumber = 002; // внутренний номер группы стажеров на стороне Манго
} else {
    $fromNumber = 001; // внутренний номер опытных менеджеров на стороне Манго
}

require_once 'auth.php';

// Проверяем, что БП не запустился дважды.
$WFList = executeREST(
    'bizproc.workflow.instance.list',
    array(
        'select' => array(
            'ID', 'TEMPLATE_ID',
        ),
        'order' => array(
            'STARTED' => 'DESC',
        ),
        'filter' => array(
            'MODULE_ID' => 'crm',
            'ENTITY' => 'CCrmDocumentDeal',
            'DOCUMENT_ID' => 'DEAL_' . $deal,
            'TEMPLATE_ID' => 1623,
        ),
    ),
    $domain, $auth, $user);
/*
А если запустилось несколько экземпляров БП, то остановить 1 из них.
Проверка на несколько версий запускаемого БП предназанчена на тот случай, если клиент оставил заявку и сразу позвонил, но не дозвонился.
При пропущенном звонке также запускается callback.
 */

if ($WFList['total'] > 1) {
    $killWorkflow = executeREST(
        'bizproc.workflow.terminate',
        array(
            'ID' => $WFList['result'][1]['ID'],
        ),
        $domain, $auth, $user);
}

// Баг в системе передачи заявок в Б24 со сторонних ресурсов, бывает так, что сделка уже есть, а контакта в сделке нет, поэтому доп. проверка.
$isWhile = 'Y';
while ($isWhile == 'Y') {
    if (empty($cnt)) {
        sleep(5);
        $contactGet = executeREST(
            'crm.deal.get',
            array(
                'ID' => $deal,
            ),
            $domain, $auth, $user);
        $cnt = $contactGet['result']['CONTACT_ID'];

    } else {
        $phoneGet = executeREST(
            'crm.contact.get',
            array(
                'ID' => $cnt,
            ),
            $domain, $auth, $user);

        $phone = $phoneGet['result']['PHONE'][0]['VALUE'];

        break;
    }
    $count++;
    if ($count == 12) {
        // Если вдруг через 12 попыток телефон получить не удалось, значит выходим из выполнения.
        exit;
    }
}

// если номер есть, то переходим к авторизации в Манго

$api_key = '************************'; // Уникальный код вашей АТС. Вставьте значение за ЛК Манго.
$api_salt = '**********************'; // Ключ для создания подписи. Вставьте значение за ЛК Манго.
$url = 'https://app.mango-office.ru/vpbx/commands/callback_group'; // Запуск автоматического звонка группе менеджеров.
$data = array(
    'command_id' => 'cbk2',
    'from' => $fromNumber,
    "to" => $phone,
    "line_number" => "74950000000", // укажите номер телефона, с которого нужно чтобы совершался звонок. Номер можно взять в ЛК Манго.
);
$json = json_encode($data);
$sign = hash('sha256', $api_key . $json . $api_salt);
$postdata = array(
    'vpbx_api_key' => $api_key,
    'sign' => $sign,
    'json' => $json,
);
$post = http_build_query($postdata);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$response = curl_exec($ch);
curl_close($ch);

function executeREST($method, array $params, $domain, $auth, $user)
{
    $queryUrl = 'https://' . $domain . '/rest/' . $user . '/' . $auth . '/' . $method . '.json';
    $queryData = http_build_query($params);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    return json_decode(curl_exec($curl), true);
    curl_close($curl);
}

function writeToLog($data, $title = '')
{
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";
    file_put_contents(getcwd() . '/logs/callback.log', $log, FILE_APPEND);
    return true;
}
