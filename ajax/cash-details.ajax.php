<?php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["admin"])) {
    echo json_encode(["status" => 401, "message" => "No autorizado"]);
    exit;
}

if (!isset($_GET["id_cash"])) {
    echo json_encode(["status" => 400, "message" => "id_cash requerido"]);
    exit;
}

$idCash = (int) $_GET["id_cash"];

/*=============================================
Función para calcular los límites de la sesión de caja
(réplica de TemplateController::cashSessionTimeBounds sin dependencia de clase)
=============================================*/
function cashSessionBounds($cash) {
    $c = is_array($cash) ? $cash : (array) $cash;

    $dateCreated = isset($c["date_created_cash"]) ? trim((string) $c["date_created_cash"]) : date("Y-m-d");
    if ($dateCreated === "" || $dateCreated === "0000-00-00") {
        $dateCreated = date("Y-m-d");
    }

    $start = isset($c["date_start_cash"]) ? trim((string) $c["date_start_cash"]) : "";
    if ($start === "" || $start === "0000-00-00 00:00:00" || $start === "0000-00-00") {
        $start = $dateCreated . " 00:00:00";
    }

    $end    = isset($c["date_end_cash"]) ? trim((string) $c["date_end_cash"]) : "";
    $status = isset($c["status_cash"]) ? (int) $c["status_cash"] : 1;

    if ($status === 1 || $end === "" || $end === "0000-00-00 00:00:00" || $end === "0000-00-00") {
        $end = date("Y-m-d H:i:s");
    }

    if (strtotime($start) !== false && strtotime($end) !== false && strtotime($start) > strtotime($end)) {
        $end = date("Y-m-d H:i:s");
    }

    return [$start, $end];
}

/*=============================================
Función de petición a la API (réplica de CurlController::request)
=============================================*/
function apiRequest($endpoint) {
    $apiBaseUrl = getenv("API_BASE_URL") ?: "http://pos-api";
    $apiToken   = getenv("API_AUTHORIZATION") ?: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => rtrim($apiBaseUrl, '/') . '/' . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $apiToken],
    ]);

    $response  = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($curlError) {
        return (object)["status" => 500, "results" => null, "error" => $curlError];
    }

    $decoded = json_decode($response);
    if ($decoded === null) {
        return (object)["status" => $httpCode ?: 500, "results" => null, "raw" => $response];
    }
    if (!isset($decoded->status)) {
        $decoded->status = $httpCode ?: 200;
    }
    return $decoded;
}

/*=============================================
1. Obtener datos de la caja
=============================================*/
$cashResp = apiRequest("cashs?linkTo=id_cash&equalTo={$idCash}&select=id_cash,start_cash,date_created_cash,date_start_cash,date_end_cash,status_cash,id_office_cash");

if (!isset($cashResp->status) || $cashResp->status != 200 || empty($cashResp->results)) {
    echo json_encode([
        "status"     => 404,
        "message"    => "Caja no encontrada",
        "id_cash"    => $idCash,
        "api_status" => $cashResp->status ?? null,
        "api_raw"    => $cashResp->raw ?? null,
        "api_error"  => $cashResp->error ?? null,
    ]);
    exit;
}

$cash = (array) $cashResp->results[0];
list($sessionStart, $sessionEnd) = cashSessionBounds($cash);
$officeId = isset($cash["id_office_cash"]) ? (int) $cash["id_office_cash"] : 0;

/*=============================================
2. Obtener órdenes de la sesión
=============================================*/
$ordersRaw  = [];
$totalSales = 0;

if ($officeId > 0) {
    $ordersUrl  = "orders?linkTo=date_order&between1=" . rawurlencode($sessionStart) . "&between2=" . rawurlencode($sessionEnd) . "&filterTo=id_office_order&inTo={$officeId}&select=id_order,total_order,date_order,method_order,status_order,transaction_order";
    $ordersResp = apiRequest($ordersUrl);

    if (isset($ordersResp->status) && $ordersResp->status == 200 && !empty($ordersResp->results)) {
        foreach ($ordersResp->results as $order) {
            $o = (array) $order;

            if ((string)($o["status_order"] ?? "") === "Completada") {
                $totalSales += (float)($o["total_order"] ?? 0);
            }

            // Productos de la orden
            $salesUrl  = "relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=" . (int)$o["id_order"] . "&select=qty_sale,subtotal_sale,discount_sale,title_product,unit_product";
            $salesResp = apiRequest($salesUrl);
            $o["sales"] = [];
            if (isset($salesResp->status) && $salesResp->status == 200 && !empty($salesResp->results)) {
                foreach ($salesResp->results as $sale) {
                    $o["sales"][] = (array) $sale;
                }
            }

            $ordersRaw[] = $o;
        }
    }
}

/*=============================================
3. Obtener gastos de la sesión
=============================================*/
$bills      = [];
$totalBills = 0;

if ($officeId > 0) {
    $billsUrl  = "bills?linkTo=date_bill&between1=" . rawurlencode($sessionStart) . "&between2=" . rawurlencode($sessionEnd) . "&filterTo=id_office_bill&inTo={$officeId}&select=id_bill,concept_bill,cost_bill,date_bill";
    $billsResp = apiRequest($billsUrl);

    if (isset($billsResp->status) && $billsResp->status == 200 && !empty($billsResp->results)) {
        foreach ($billsResp->results as $bill) {
            $b = (array) $bill;
            $totalBills += (float)($b["cost_bill"] ?? 0);
            $bills[] = $b;
        }
    }
}

/*=============================================
Respuesta final
=============================================*/
echo json_encode([
    "status"       => 200,
    "cash"         => $cash,
    "sessionStart" => $sessionStart,
    "sessionEnd"   => $sessionEnd,
    "officeId"     => $officeId,
    "orders"       => $ordersRaw,
    "bills"        => $bills,
    "totalSales"   => $totalSales,
    "totalBills"   => $totalBills,
], JSON_UNESCAPED_UNICODE);
