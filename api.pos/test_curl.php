<?php
$ch = curl_init('http://localhost:8081/ajax/pos.ajax.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'getSubWarehouseStock' => 'true',
    'id_admin' => '24',
    'id_office' => '8',
    'role' => 'vendedor'
]));
$response = curl_exec($ch);
curl_close($ch);
echo substr($response, 0, 500);
