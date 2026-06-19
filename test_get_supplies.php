<?php
$apiBase = 'http://127.0.0.1:8081';
$url = $apiBase . '/api/lab_supplies?linkTo=id_office_supply&equalTo=7';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
echo curl_exec($ch);
curl_close($ch);
