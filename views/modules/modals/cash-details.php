<!-- =============================================
Modal: Ver Detalles de Caja
============================================= -->
<div class="modal fade" id="modalCashDetails" tabindex="-1" aria-labelledby="modalCashDetailsLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content rounded border-0 shadow">

			<!-- Header -->
			<div class="modal-header border-0" style="background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%);">
				<div>
					<h5 class="modal-title text-white mb-0" id="modalCashDetailsLabel">
						<i class="fas fa-cash-register me-2"></i>Detalles de Caja
					</h5>
					<small class="text-white-50" id="cashDetailsPeriod">Cargando...</small>
				</div>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
			</div>

			<!-- Body -->
			<div class="modal-body p-4" id="cashDetailsBody">
				<div class="text-center py-5">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Cargando...</span>
					</div>
					<p class="mt-2 text-muted small">Obteniendo datos de la sesión...</p>
				</div>
			</div>

			<!-- Footer -->
			<div class="modal-footer border-0 bg-light rounded-bottom">
				<button type="button" class="btn btn-secondary rounded" data-bs-dismiss="modal">Cerrar</button>
			</div>

		</div>
	</div>
</div>

<style>
.cash-summary-card {
	border: 0;
	border-radius: 12px;
	box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.cash-summary-card .card-body {
	padding: 1.25rem;
}
.cash-badge-success {
	background: linear-gradient(135deg, #11998e, #38ef7d);
	color: white;
}
.cash-badge-danger {
	background: linear-gradient(135deg, #eb3349, #f45c43);
	color: white;
}
.cash-badge-primary {
	background: linear-gradient(135deg, #4776E6, #8E54E9);
	color: white;
}
#cashDetailsBody table thead th {
	font-size: 0.78rem;
	text-transform: uppercase;
	letter-spacing: 0.05em;
	color: #6c757d;
	border-bottom: 2px solid #f0f0f0;
	padding: 0.65rem 0.75rem;
}
#cashDetailsBody table tbody td {
	font-size: 0.85rem;
	vertical-align: middle;
	padding: 0.6rem 0.75rem;
	border-bottom: 1px solid #f8f8f8;
}
#cashDetailsBody table tbody tr:hover {
	background: #f8f9ff;
}
.section-title {
	font-size: 0.95rem;
	font-weight: 700;
	letter-spacing: 0.03em;
}
</style>

<script>
$(document).on("click", ".viewCashDetails", function () {
	const idCash = $(this).attr("idCash");

	$("#cashDetailsBody").html(`
		<div class="text-center py-5">
			<div class="spinner-border text-primary" role="status"></div>
			<p class="mt-2 text-muted small">Obteniendo datos de la sesión...</p>
		</div>
	`);
	$("#cashDetailsPeriod").text("Cargando...");

	const modal = new bootstrap.Modal(document.getElementById("modalCashDetails"));
	modal.show();

	$.ajax({
		url: "/ajax/cash-details.ajax.php",
		method: "GET",
		data: { id_cash: idCash },
		success: function (res) {
			if (res.status !== 200) {
				$("#cashDetailsBody").html(`<div class="alert alert-warning">No se encontraron datos para esta caja.</div>`);
				return;
			}

			// Período
			const start = res.sessionStart ? res.sessionStart.substring(0, 16) : "â€”";
			const end   = res.sessionEnd   ? res.sessionEnd.substring(0, 16)   : "â€”";
			$("#cashDetailsPeriod").text(`Período: ${start} â†’ ${end}`);

			// Totales resumen
			const totalSales = parseFloat(res.totalSales || 0).toFixed(2);
			const totalBills = parseFloat(res.totalBills || 0).toFixed(2);
			const startCash  = parseFloat(res.cash?.start_cash || 0).toFixed(2);
			const diff       = (parseFloat(startCash) + parseFloat(totalSales) - parseFloat(totalBills)).toFixed(2);

			let html = `
			<div class="row g-3 mb-4">
				<div class="col-6 col-md-3">
					<div class="card cash-summary-card text-center">
						<div class="card-body cash-badge-primary rounded">
							<div class="fs-5 fw-bold">Bs ${parseFloat(startCash).toLocaleString('es-BO', {minimumFractionDigits:2})}</div>
							<div class="small mt-1 opacity-75">Apertura</div>
						</div>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="card cash-summary-card text-center">
						<div class="card-body cash-badge-success rounded">
							<div class="fs-5 fw-bold">Bs ${parseFloat(totalSales).toLocaleString('es-BO', {minimumFractionDigits:2})}</div>
							<div class="small mt-1 opacity-75">Total ventas</div>
						</div>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="card cash-summary-card text-center">
						<div class="card-body cash-badge-danger rounded">
							<div class="fs-5 fw-bold">Bs ${parseFloat(totalBills).toLocaleString('es-BO', {minimumFractionDigits:2})}</div>
							<div class="small mt-1 opacity-75">Total gastos</div>
						</div>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="card cash-summary-card text-center">
						<div class="card-body rounded" style="background:linear-gradient(135deg,#f7971e,#ffd200); color:#333;">
							<div class="fs-5 fw-bold">Bs ${parseFloat(diff).toLocaleString('es-BO', {minimumFractionDigits:2})}</div>
							<div class="small mt-1 opacity-75">Diferencia</div>
						</div>
					</div>
				</div>
			</div>
			`;

			// â”€â”€ Ventas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
			html += `<div class="mb-4">
				<div class="section-title mb-2">
					<i class="bi bi-cart-check-fill text-success me-1"></i> Ventas / Órdenes
					<span class="badge bg-success ms-2">${res.orders.length}</span>
				</div>`;

			if (res.orders.length === 0) {
				html += `<div class="alert alert-light text-muted small py-2">Sin ventas registradas en esta sesión.</div>`;
			} else {
				html += `<div class="table-responsive">
					<table class="table table-hover align-middle mb-0">
						<thead class="table-light">
							<tr>
								<th>ID</th>
								<th>Transacción</th>
								<th>Productos</th>
								<th>Método</th>
								<th>Estado</th>
								<th class="text-end">Total</th>
							</tr>
						</thead>
						<tbody>`;

				res.orders.forEach((o, idx) => {
					const productsList = o.sales && o.sales.length > 0
						? o.sales.map(s => {
							let overrideHtml = "";
							if(s.applied_price_type === 'manual' && s.override){
								overrideHtml = `<div class="mt-1 pt-1 border-top border-light" style="font-size: 0.75rem;"><span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Precio Editado:</span> Bs ${s.override.override_price} (Orig: Bs ${s.override.original_price})<br><span class="text-muted"><i class="bi bi-person-fill"></i> ${s.override.name_admin}: <i>"${s.override.reason_override}"</i></span></div>`;
							}
							return `<div class="mb-2 p-2 bg-light rounded"><span class="badge rounded-pill bg-white text-dark border me-1 mb-1">
								${decodeURIComponent((s.title_product || 'Producto').replace(/\+/g, ' '))}
								<small class="text-muted">x${s.qty_sale}</small>
							</span>${overrideHtml}</div>`
						}).join('')
						: '<span class="text-muted small">—</span>';

					const methodIcon = o.method_order === 'efectivo'
						? '<i class="fas fa-money-bill-wave text-success"></i>'
						: o.method_order === 'transferencia'
							? '<i class="fas fa-exchange-alt text-info"></i>'
							: '<i class="fas fa-credit-card text-primary"></i>';

					const statusBadge = o.status_order === 'Completada'
						? '<span class="badge bg-success rounded-pill">Completada</span>'
						: '<span class="badge bg-warning text-dark rounded-pill">Pendiente</span>';

					html += `<tr>
						<td class="text-muted">${idx + 1}</td>
						<td><code class="small">${o.transaction_order || 'â€”'}</code></td>
						<td style="max-width:280px;">${productsList}</td>
						<td>${methodIcon} <span class="small text-capitalize">${o.method_order || 'â€”'}</span></td>
						<td>${statusBadge}</td>
						<td class="text-end fw-semibold">Bs ${parseFloat(o.total_order || 0).toLocaleString('es-BO', {minimumFractionDigits:2})}</td>
					</tr>`;
				});

				html += `</tbody>
					<tfoot class="table-light">
						<tr>
							<td colspan="5" class="text-end fw-bold">Total ventas completadas</td>
							<td class="text-end fw-bold text-success">Bs ${parseFloat(totalSales).toLocaleString('es-BO', {minimumFractionDigits:2})}</td>
						</tr>
					</tfoot>
					</table></div>`;
			}
			html += `</div>`;

			// â”€â”€ Gastos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
			html += `<div class="mb-2">
				<div class="section-title mb-2">
					<i class="fas fa-money-bill-wave text-danger me-1"></i> Gastos registrados
					<span class="badge bg-danger ms-2">${res.bills.length}</span>
				</div>`;

			if (res.bills.length === 0) {
				html += `<div class="alert alert-light text-muted small py-2">Sin gastos registrados en esta sesión.</div>`;
			} else {
				html += `<div class="table-responsive">
					<table class="table table-hover align-middle mb-0">
						<thead class="table-light">
							<tr>
								<th>ID</th>
								<th>Concepto</th>
								<th>Fecha</th>
								<th class="text-end">Costo</th>
							</tr>
						</thead>
						<tbody>`;

				res.bills.forEach((b, idx) => {
					const dateStr = b.date_bill ? b.date_bill.substring(0, 16) : 'â€”';
					html += `<tr>
						<td class="text-muted">${idx + 1}</td>
						<td>${b.concept_bill || 'â€”'}</td>
						<td class="small text-muted">${dateStr}</td>
						<td class="text-end fw-semibold text-danger">Bs ${parseFloat(b.cost_bill || 0).toLocaleString('es-BO', {minimumFractionDigits:2})}</td>
					</tr>`;
				});

				html += `</tbody>
					<tfoot class="table-light">
						<tr>
							<td colspan="3" class="text-end fw-bold">Total gastos</td>
							<td class="text-end fw-bold text-danger">Bs ${parseFloat(totalBills).toLocaleString('es-BO', {minimumFractionDigits:2})}</td>
						</tr>
					</tfoot>
					</table></div>`;
			}
			html += `</div>`;

			$("#cashDetailsBody").html(html);
		},
		error: function () {
			$("#cashDetailsBody").html(`<div class="alert alert-danger">Error al cargar los detalles de la caja.</div>`);
		}
	});
});
</script>

