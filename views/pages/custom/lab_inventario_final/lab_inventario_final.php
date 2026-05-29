<?php
$id_office = $_SESSION["admin"]->id_office_admin;

// Fetch products for this office via product_inventory JOIN products
try {
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $dbName = getenv("DB_NAME") ?: "u228744577_pos";
    $user = getenv("DB_USER") ?: "root";
    $pass = getenv("DB_PASS") ?: "";
    $port = getenv("DB_PORT") ?: "3306";
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
    $db->exec("set names utf8");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT p.*, pi.stock_inventory as stock_product
        FROM products p
        INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product
        WHERE pi.id_office_inventory = :office AND pi.status_inventory = 1 AND p.is_compound_product = 1 AND p.status_product = 1
        ORDER BY p.id_product DESC
    ");
    $stmt->execute([':office' => $id_office]);
    $products = $stmt->fetchAll(PDO::FETCH_CLASS);
} catch (Exception $e) {
    $products = array();
}
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-box-open"></i> Inventario de Productos Finales</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted">Aquí se muestran los productos que han salido de producción (empacados o a granel) listos para la venta o distribución.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="finalInventoryTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Resumen de Inventario</th>
                                    <th class="text-end">Costo Unitario</th>
                                    <th class="text-end">Valor Total del Lote</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach($products as $prod): 
                                    if ($prod->stock_product <= 0) continue;
                                    // Ignorar productos a granel usando sus unidades base
                                    if (in_array(strtolower($prod->unit_product), ['l', 'ml', 'kg', 'g'])) continue;
                                    
                                    $estimatedValue = $prod->stock_product * $prod->rte_product;
                                    $count++;
                                ?>
                                <tr>
                                    <td class="align-middle text-muted"><?php echo $count ?></td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 text-center me-3" style="min-width: 80px;">
                                                <span class="d-block fs-5 fw-bold text-success"><?php echo number_format($prod->stock_product, 0) ?></span>
                                                <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?php echo $prod->unit_product ?></small>
                                            </div>
                                            <div>
                                                <span class="text-muted small">De:</span><br>
                                                <strong class="text-uppercase text-primary fs-6"><?php echo $prod->title_product ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-end">Bs <?php echo number_format($prod->rte_product, 2) ?></td>
                                    <td class="align-middle text-end fw-bold text-success">Bs <?php echo number_format($estimatedValue, 2) ?></td>
                                    <td class="align-middle text-center">
                                        <button class="btn btn-sm btn-info text-white" onclick="viewLots(<?php echo $prod->id_product ?>, '<?php echo addslashes($prod->title_product) ?>')" title="Ver Lotes">
                                            <i class="fas fa-eye"></i> Lotes
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#finalInventoryTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
        "order": [[2, "desc"]] // Ordenar por stock por defecto
    });
});
</script>

<!-- Modal Historial de Lotes -->
<div class="modal fade" id="modalLots" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white">Historial de Producción: <span id="lots_product_name"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped border">
                <thead class="table-light">
                    <tr>
                        <th>ID Orden</th>
                        <th>Fecha de Producción</th>
                        <th class="text-end">Cant. Producida</th>
                        <th class="text-end">Costo Unitario (Real)</th>
                        <th class="text-end">Costo Total del Lote</th>
                    </tr>
                </thead>
                <tbody id="lots_tbody">
                    <!-- Dinámico -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewLots(id_product, product_name) {
    $('#lots_product_name').text(product_name.toUpperCase());
    $('#lots_tbody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando lotes...</td></tr>');
    $('#modalLots').modal('show');

    $.post("/ajax/pos.ajax.php", { getProductionLots: "ok", id_packaged_product: id_product }, function(res) {
        try {
            let data = JSON.parse(res);
            let html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-muted">No se encontraron lotes de producción para este producto.</td></tr>';
            } else {
                data.forEach(lote => {
                    let d = new Date(lote.date_updated_production);
                    let formattedDate = d.toLocaleDateString() + ' ' + d.toLocaleTimeString();
                    
                    html += `<tr>
                        <td><strong>#${lote.id_production}</strong></td>
                        <td>${formattedDate}</td>
                        <td class="text-end">${parseFloat(lote.total_qty_production).toLocaleString()}</td>
                        <td class="text-end text-primary fw-bold">Bs ${parseFloat(lote.real_unit_cost).toFixed(2)}</td>
                        <td class="text-end">Bs ${parseFloat(lote.real_total_cost).toFixed(2)}</td>
                    </tr>`;
                });
            }
            $('#lots_tbody').html(html);
        } catch(e) {
            console.error(e);
            $('#lots_tbody').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar lotes.</td></tr>');
        }
    });
}
</script>
