<?php

header("Content-Type: application/json; charset=UTF-8");

function sendResponse($statusCode, $response)
{
    http_response_code($statusCode);

    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}

function getRequestHeaders()
{
    if (function_exists("getallheaders")) {
        $headers = getallheaders();
        return is_array($headers) ? $headers : array();
    }

    return array();
}

function hasHeader($headers, $headerName)
{
    foreach ($headers as $name => $value) {
        if (strcasecmp($name, $headerName) === 0) {
            return true;
        }
    }

    return false;
}

function readJsonBody($bodyRequired)
{
    $rawBody = file_get_contents("php://input");

    if ($rawBody === false) {
        sendResponse(400, array(
            "success" => false,
            "error" => "تعذر قراءة جسم الطلب."
        ));
    }

    if (trim($rawBody) === "") {
        if ($bodyRequired) {
            sendResponse(400, array(
                "success" => false,
                "error" => "جسم الطلب مطلوب ويجب أن يحتوي على JSON."
            ));
        }

        return null;
    }

    $data = json_decode($rawBody);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(400, array(
            "success" => false,
            "error" => "بيانات JSON غير صالحة.",
            "details" => json_last_error_msg()
        ));
    }

    if (!is_object($data)) {
        sendResponse(400, array(
            "success" => false,
            "error" => "يجب أن تكون بيانات JSON على شكل كائن (Object)."
        ));
    }

    return $data;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$allowedMethods = array("GET", "POST", "PUT", "DELETE");

if (!in_array($requestMethod, $allowedMethods, true)) {
    header("Allow: GET, POST, PUT, DELETE");

    sendResponse(405, array(
        "success" => false,
        "error" => "طريقة HTTP غير مسموح بها.",
        "allowed_methods" => $allowedMethods
    ));
}


$requestHeaders = getRequestHeaders();

$authorizationPresent = hasHeader(
    $requestHeaders,
    "Authorization"
);


$bodyRequired = in_array(
    $requestMethod,
    array("POST", "PUT"),
    true
);

$requestData = readJsonBody($bodyRequired);


switch ($requestMethod) {

    case "GET":

        sendResponse(200, array(
            "success" => true,
            "method" => "GET",
            "message" => "تم استقبال طلب GET بنجاح.",
            "headers" => array(
                "authorization_present" => $authorizationPresent
            )
        ));

        break;

    case "POST":

        sendResponse(201, array(
            "success" => true,
            "method" => "POST",
            "message" => "تم استقبال البيانات بنجاح.",
            "received_data" => $requestData
        ));

        break;

    case "PUT":

        sendResponse(200, array(
            "success" => true,
            "method" => "PUT",
            "message" => "تم استقبال بيانات التحديث بنجاح.",
            "updated_data" => $requestData
        ));

        break;

    case "DELETE":

        sendResponse(200, array(
            "success" => true,
            "method" => "DELETE",
            "message" => "تم استقبال طلب الحذف بنجاح.",
            "received_data" => $requestData
        ));

        break;
}
?>