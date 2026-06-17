<?php

class OrdersController{

	public function manageOrder(){

		if(!isset($_POST["idOrderPay"])) return;

		$db = LocalConnection::connect();

		try {
			$db->beginTransaction();

			$orderId      = intval($_POST["idOrderPay"]);
			$methodPay    = $_POST["methodPay"] ?? '';
			$transferPay  = $_POST["transferPay"] ?? '';
			$qrRef        = $_POST["qrRefOrder"] ?? '';

			// Obtener orden
			$stmtOrder = $db->prepare("SELECT * FROM orders WHERE id_order = :id LIMIT 1");
			$stmtOrder->execute([':id' => $orderId]);
			$order = $stmtOrder->fetch(PDO::FETCH_OBJ);

			if (!$order) {
				$db->rollBack();
				echo json_encode(["status" => 404, "message" => "Orden no encontrada"]);
				return;
			}

			// Determinar estado final según el rol del creador de la orden
			$stmtRole = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :id LIMIT 1");
			$stmtRole->execute([':id' => $order->id_admin_order ?? 0]);
			$creatorRole  = $stmtRole->fetchColumn() ?: '';
			$finalStatus  = ($creatorRole === 'vendedor') ? 'Pendiente Despacho' : 'Completada';

			// Actualizar orden con el estado correspondiente
			$stmtUpdate = $db->prepare("
				UPDATE orders SET
					status_order   = :status,
					method_order   = :method,
					transfer_order = :transfer,
					qr_ref_order   = :qr_ref,
					date_order     = :date
				WHERE id_order = :id
			");
			$stmtUpdate->execute([
				':status'   => $finalStatus,
				':method'   => $methodPay,
				':transfer' => $transferPay,
				':qr_ref'   => $qrRef,
				':date'     => date("Y-m-d H:i:s"),
				':id'       => $orderId,
			]);

			// Marcar todas las ventas como completadas (dispara trigger after_sale_update)
			$stmtSales = $db->prepare("UPDATE sales SET status_sale = 'Completada' WHERE id_order_sale = :id");
			$stmtSales->execute([':id' => $orderId]);

			$db->commit();

			// Sincronizar caja fuera de la transacción (no es crítico)
			$officeId = (int)($order->id_office_order ?? 0);
			if ($officeId <= 0 && isset($_SESSION["admin"]->id_office_admin)) {
				$officeId = (int)$_SESSION["admin"]->id_office_admin;
			}
			if ($officeId > 0) {
				self::syncCashTotals($db, $officeId, $orderId);
			}

			$transactionOrder = $order->transaction_order ?? "#$orderId";

			echo json_encode(["status" => 200, "transaction" => $transactionOrder]);

		} catch (Throwable $e) {
			if ($db->inTransaction()) $db->rollBack();
			error_log("manageOrder error: " . $e->getMessage());
			echo json_encode(["status" => 500, "message" => "Error al procesar el pago: " . $e->getMessage()]);
		}
	}

	private static function syncCashTotals($db, int $officeId, int $orderId){
		try {
			// Obtener caja abierta de la oficina
			$stmtCash = $db->prepare("SELECT id_cash FROM cashs WHERE id_office_cash = :office AND status_cash = 1 LIMIT 1");
			$stmtCash->execute([':office' => $officeId]);
			$cashId = $stmtCash->fetchColumn();
			if (!$cashId) return;

			// Sumar total de ventas completadas en esta sesión de caja
			$stmtTotal = $db->prepare("
				SELECT COALESCE(SUM(s.subtotal_sale), 0)
				FROM sales s
				JOIN orders o ON s.id_order_sale = o.id_order
				WHERE o.id_office_order = :office AND o.status_order = 'Completada'
				  AND o.date_order >= (SELECT date_created_cash FROM cashs WHERE id_cash = :cash)
			");
			$stmtTotal->execute([':office' => $officeId, ':cash' => $cashId]);
			$totalSales = (float)$stmtTotal->fetchColumn();

			$stmtUpd = $db->prepare("UPDATE cashs SET income_cash = :total WHERE id_cash = :id");
			$stmtUpd->execute([':total' => round($totalSales, 2), ':id' => $cashId]);
		} catch (Throwable $e) {
			error_log("syncCashTotals error: " . $e->getMessage());
		}
	}
}
