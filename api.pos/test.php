<?php
require 'models/post.model.php';
$data = [
    'id_order_sale' => 96,
    'id_product_sale' => 14,
    'tax_type_sale' => '0',
    'tax_sale' => '0',
    'discount_sale' => '0',
    'qty_sale' => '1',
    'subtotal_sale' => '100',
    'status_sale' => 'Pendiente',
    'id_admin_sale' => '1',
    'id_client_sale' => '1',
    'id_office_sale' => '3',
    'date_created_sale' => '2026-06-05'
];
print_r(PostModel::postData('sales', $data));
