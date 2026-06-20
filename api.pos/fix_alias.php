<?php
require 'ajax/lib/LocalConnection.php';
$db = LocalConnection::connect();
$db->query("UPDATE columns SET alias_column = REPLACE(alias_column, ' (SOLO PARA AJUSTE)', '') WHERE alias_column LIKE '%SOLO PARA AJUSTE%'");
echo "UPDATED\n";
