<?php 

/*=============================================
Asignar sucursal a un administrador general
=============================================*/

if($_SESSION["admin"]->id_office_admin == 0 && isset($_GET["offices"])){

    $_SESSION["admin"]->id_office_admin = explode("_",$_GET["offices"])[0];
    $_SESSION["admin"]->title_office = explode("_",$_GET["offices"])[1];

}

if(isset($_GET["offices"]) && $_SESSION["admin"]->id_office_admin > 0){

    $_SESSION["admin"]->id_office_admin = explode("_",$_GET["offices"])[0];
    $_SESSION["admin"]->title_office = explode("_",$_GET["offices"])[1];
   
}

/*=============================================
Variable para actualizar el stock
=============================================*/

$updateStock = false;

/*=============================================
Abrir la página correspondiente del Dashboard
=============================================*/

if (!empty($routesArray[0])){

    $url = "relations?rel=modules,pages&type=module,page&linkTo=url_page&equalTo=".$routesArray[0];

    /*=============================================
   Activar la actualización del stock cuando estamos en pos o productos
    =============================================*/

    if($routesArray[0] == "pos" || $routesArray[0] == "productos"){

        $updateStock = true;

    }

    /*=============================================
    Confirmar Anular Factura Electrónica
    =============================================*/

    if($routesArray[0] == "facturacion" && 
        isset($_GET["idOrder"]) &&
        isset($_GET["document"]) &&
        isset($_GET["cude"]) &&
        !isset($_GET["confirmation"])){

        echo '<script>
        fncSweetAlert("confirm","¿Está seguro de anular la factura '.$_GET["document"].'?","").then(resp=>{
            if(resp){
                window.location = "'.$_SERVER["REQUEST_URI"].'&confirmation=ok";
            }
        })
        </script>';

    } 

    /*=============================================
    Procesar Anulación de Factura Electrónica
    =============================================*/

    if($routesArray[0] == "facturacion" && 
        isset($_GET["idOrder"]) &&
        isset($_GET["document"]) &&
        isset($_GET["cude"]) &&
        isset($_GET["confirmation"])){

        require_once "controllers/notes.controller.php";

        $createNote = NotesController::createNote($_GET["idOrder"],$_GET["document"],$_GET["cude"]);  
       
        if($createNote == "ok"){

            echo '<script>
                fncSweetAlert("success", "La nota crédito ha sido completada con éxito", "/facturacion");
            </script>';
        
        }else{

            echo '<script>
                fncSweetAlert("error", "'.$createNote.'", "/facturacion");
            </script>';
        }

    }

}else{

    $url = "relations?rel=modules,pages&type=module,page&linkTo=order_page&equalTo=1";

    if($_SESSION["admin"]->id_office_admin == 0 && !isset($_GET["offices"])){

        echo '<script>

        setTimeout(()=>{

            $("#myOffices").modal("show");

        },100);

        </script>';
    }

    $updateStock = true;
}

$method = "GET";
$fields = array();

$modules = CurlController::request($url,$method,$fields);

if($modules->status == 200){

    $modules = $modules->results;
   

}else{

    $modules = array();

}

/*=============================================
Actualizar el stock
=============================================*/

if($updateStock && $_SESSION["admin"]->id_office_admin > 0){

    echo '<input type="hidden" id="updateStock" value="'.$_SESSION["admin"]->id_office_admin.'">';

}

/*=============================================
Buscar orden iniciada
=============================================*/

$url = "orders?linkTo=id_admin_order,status_order,id_office_order,date_created_order&equalTo=".$_SESSION["admin"]->id_admin.",Pendiente,".$_SESSION["admin"]->id_office_admin.",".date("Y-m-d");
$method = "GET";
$fields = array();

$order = CurlController::request($url,$method,$fields);

if($order->status == 200){

    $order = $order->results[0];
  
}else{

    $order = null;
}

$isPosPage = isset($routesArray[0]) && $routesArray[0] == "pos";
$isCashOpen = true; // default to true

if ($isPosPage) {
    $cashOffice = isset($_SESSION["admin"]->id_office_admin) ? (int)$_SESSION["admin"]->id_office_admin : 0;
    $urlCash = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".date("Y-m-d").",1,".$cashOffice."&select=id_cash";
    $cashReq = CurlController::request($urlCash, "GET", array());
    $isCashOpen = false;
    if(isset($cashReq->status) && $cashReq->status == 200 && !empty($cashReq->results)){
        $isCashOpen = true;
    }
}
?>
    
<div class="container-fluid py-3 p-lg-4">

    <?php if ($isPosPage && !$isCashOpen): ?>
        <div class="position-relative w-100 h-100 tw-min-h-[500px]" style="min-height: 500px;">
            <div style="filter: blur(8px) grayscale(100%); pointer-events: none; opacity: 0.65;">
    <?php endif ?>
          
        <div class="row">

        <?php if (!empty($modules)): ?>

            <?php foreach ($modules as $key => $value): $module = $value ?>

                <!--=========================================
                Cuando el módulo es un breadcrumb
                ===========================================-->

                <?php if ($module->type_module == "breadcrumbs"): ?>

                    <?php include "breadcrumbs/breadcrumbs.php" ?>
                    
                <?php endif ?>

                <!--=========================================
                Cuando el módulo es una métrica
                ===========================================-->

                <?php if ($module->type_module == "metrics"): ?>

                    <?php include "metrics/metrics.php" ?>
                    
                <?php endif ?>

                <!--=========================================
                Cuando el módulo es un gráfico
                ===========================================-->

                <?php if ($module->type_module == "graphics"): ?>

                    <?php include "graphics/graphics.php" ?>
                    
                <?php endif ?>

                <!--=========================================
                Cuando el módulo es una tabla
                ===========================================-->

                <?php if ($module->type_module == "tables"): ?>

                    <?php include "tables/tables.php" ?>
                    
                <?php endif ?>

                <!--=========================================
                Cuando el módulo es personalizado
                ===========================================-->

                <?php if ($module->type_module == "custom"): ?>

                    <?php include "custom/".str_replace(" ","_",$module->title_module)."/".str_replace(" ","_",$module->title_module).".php" ?>
                    
                <?php endif ?>
   
            <?php endforeach ?>
            
        <?php endif ?>

        <?php if ($_SESSION["admin"]->rol_admin == "superadmin"): ?>

                <div class="text-center <?php if (!empty($routesArray[1]) && $routesArray[1] == "manage"): ?> d-none  <?php endif ?>">
                
                    <button class="btn btn-default bg-white border rounded btn-sm ms-3 menu-text mt-1 py-2 px-3 myModule" idPage="<?php echo $page->results[0]->id_page ?>">Agregar Módulo</button>

                </div>
        
        <?php endif ?>

    </div>

    <?php if ($isPosPage && !$isCashOpen): ?>
            </div>
            <!-- Centered button overlay -->
            <div class="position-absolute d-flex flex-column justify-content-center align-items-center" style="top: 0; left: 0; right: 0; bottom: 0; z-index: 1050; background: rgba(0, 0, 0, 0.05);">
                <div class="card p-5 text-center shadow-lg border-0 rounded-4" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); max-width: 400px; width: 90%;">
                    <div class="mb-4">
                        <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #ffebee;">
                            <i class="bi bi-wallet2 text-danger" style="font-size: 40px;"></i>
                        </div>
                    </div>
                    <h4 class="font-weight-bold mb-3 text-dark">Caja Cerrada</h4>
                    <p class="text-muted mb-4" style="font-size: 14px; line-height: 1.6;">Para poder realizar ventas y utilizar el módulo POS, primero debe abrir la caja del día.</p>
                    <button type="button" class="btn btn-lg btn-success w-100 openCash py-3 rounded-pill font-weight-bold shadow-sm" style="transition: all 0.2s ease;">
                        <i class="bi bi-unlock-fill me-2"></i>Abrir Caja
                    </button>
                </div>
            </div>
        </div>
    <?php endif ?>

</div>

<?php if (!isset($_SESSION["admin"]->phone_office)): ?>

    <?php include "views/modules/modals/offices.php"; ?>
    
<?php endif ?>

<?php $posJsFile = __DIR__ . "/../../assets/js/pos/pos.js"; ?>
<script src="/views/assets/js/pos/pos.js?v=<?php echo is_file($posJsFile) ? filemtime($posJsFile) : "1"; ?>"></script>
