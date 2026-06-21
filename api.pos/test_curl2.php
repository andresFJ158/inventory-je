<?php
$ch = curl_init('http://localhost:8081/ajax/pos.ajax.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'loginLabUser' => 'ok',
    'email' => 'vendedor2@pos.com',
    'password' => '123456' // I need the real password... wait, I don't have it.
]));
// Actually I can't login without the password.
