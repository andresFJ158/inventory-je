/*=============================================
Paginación
=============================================*/

function initPagination() {

	var totalPages = $('#pagination').attr("totalPages");

	var defaultOpts = {
		totalPages: totalPages,
		first: '<i class="fas fa-angle-double-left"></i>',
		last: '<i class="fas fa-angle-double-right"></i>',
		prev: '<i class="fas fa-angle-left"></i>',
		next: '<i class="fas fa-angle-right"></i>',
		onPageClick: function (event, page) {

			if (page == 1) {
				$(".page-item.first").css({ "color": "#fff !important" })
				$(".page-item.prev").css({ "color": "#fff !important" })
			}

			if (page == totalPages) {

				$(".page-item.next").css({ "color": "#aaa !important" })
				$(".page-item.last").css({ "color": "#aaa !important" })
			}


		}

	}

	$('#pagination').twbsPagination(defaultOpts).on("page", function (event, page) {

		var contentModule = $("#contentModule").val();
		var orderBy = $("#orderByTable").val();
		var orderMode = $("#orderModeTable").val();
		var limit = $("#limitTable").val();
		var page = page;
		var filter = "pagination";
		var search = $("#searchTable").val();
		var between1 = $("#between1").val();
		var between2 = $("#between2").val();

		loadAjaxTable(contentModule, orderBy, orderMode, limit, page, filter, search, between1, between2);

	});

}

initPagination();

/*=============================================
Cambio de límite de registros
=============================================*/

$(document).on("change", ".changeLimit", function () {

	var contentModule = $("#contentModule").val();
	var orderBy = $("#orderByTable").val();
	var orderMode = $("#orderModeTable").val();
	var limit = $(this).val();
	var page = 1;
	var filter = "limit";
	var search = $("#searchTable").val();
	var between1 = $("#between1").val();
	var between2 = $("#between2").val();

	/*=============================================
	Actualizamos el límite en el input oculto
	=============================================*/

	$("#limitTable").val(limit);

	loadAjaxTable(contentModule, orderBy, orderMode, limit, page, filter, search, between1, between2);

})


/*=============================================
Cambio de órden de registros
=============================================*/

$(document).on("click", ".orderFilter", function () {

	var contentModule = $("#contentModule").val();
	var orderBy = $(this).attr("orderBy");
	var orderMode = $(this).attr("orderMode");
	var limit = $("#limitTable").val();
	var page = 1;
	var filter = "order";
	var search = $("#searchTable").val();
	var between1 = $("#between1").val();
	var between2 = $("#between2").val();

	/*=============================================
	Actualizamos el orderBy y el orderMode en el input oculto
	=============================================*/

	$("#orderByTable").val(orderBy);
	$("#orderModeTable").val(orderMode);

	/*=============================================
	Cambiar dirección de flecha
	=============================================*/

	if (orderMode == "ASC") {

		$(this).attr("orderMode", "DESC");
		$(this).removeClass("bi-arrow-down-short");
		$(this).addClass("bi-arrow-up-short");

	} else {

		$(this).attr("orderMode", "ASC");
		$(this).addClass("bi-arrow-down-short");
		$(this).removeClass("bi-arrow-up-short");
	}

	loadAjaxTable(contentModule, orderBy, orderMode, limit, page, filter, search, between1, between2);

})

/*=============================================
Búsqueda de registros
=============================================*/

$(document).on("click", "#btnSearchItem", function () {

	var contentModule = $("#contentModule").val();
	var orderBy = $("#orderByTable").val();
	var orderMode = $("#orderModeTable").val();
	var limit = $("#limitTable").val();
	var page = 1;
	var filter = "search";
	var search = fncSearchTable($("#searchItem").val().toLowerCase());
	var between1 = $("#between1").val();
	var between2 = $("#between2").val();

	/*=============================================
	Actualizamos la búsqueda en el input oculto
	=============================================*/

	$("#searchTable").val(search);

	loadAjaxTable(contentModule, orderBy, orderMode, limit, page, filter, search, between1, between2);

})

$(document).on("keyup", "#searchItem", function (e) {
	if(e.keyCode == 13){
		$("#btnSearchItem").click();
	}
})

/*=============================================
función de búsqueda general
=============================================*/

function fncSearchTable(search){

	search = search.replace(/[#\\;\\$\\&\\%\\=\\(\\)\\:\\,\\'\\"\\.\\¿\\¡\\!\\?\\]/g, "");
	
	search = search.replace(/[á]/g, "a");
	search = search.replace(/[é]/g, "e");
	search = search.replace(/[í]/g, "i");
	search = search.replace(/[ó]/g, "o");
	search = search.replace(/[ú]/g, "u");
	search = search.replace(/[ñ]/g, "n");

	search = search.replace(/[ ]/g, "_");

	return search;
	
}

/*=============================================
Filtrar por fechas
=============================================*/

$('#daterange-btn').daterangepicker({
	"locale": {
		"format": "YYYY-MM-DD",
		"separator": " - ",
		"applyLabel": "Aplicar",
		"cancelLabel": "Cancelar",
		"fromLabel": "Desde",
		"toLabel": "Hasta",
		"customRangeLabel": "Rango Personalizado",
		"daysOfWeek": [
			"Do",
			"Lu",
			"Ma",
			"Mi",
			"Ju",
			"Vi",
			"Sa"
		],
		"monthNames": [
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		],
		"firstDay": 1
	},
	ranges: {
		'Hoy': [moment(), moment()],
		'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
		'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
		'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
		'Este Mes': [moment().startOf('month'), moment().endOf('month')],
		'Último Mes': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
		'Este Año': [moment().startOf('year'), moment().endOf('year')],
		'Último Año': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
	},
	startDate: moment($("#between1").val()),
	endDate: moment($("#between2").val())

},
	function (start, end) {

		var contentModule = $("#contentModule").val();
		var orderBy = $("#orderByTable").val();
		var orderMode = $("#orderModeTable").val();
		var limit = $("#limitTable").val();
		var page = 1;
		var filter = "range";
		var search = $("#searchTable").val();
		var between1 = start.format('YYYY-MM-DD');
		var between2 = end.format('YYYY-MM-DD');

		/*=============================================
		Actualizando el selector de fechas
		=============================================*/

		$("#startDate").html(between1);
		$("#endDate").html(between2);

		/*=============================================
		Actualizando las fechas de los input ocultos
		=============================================*/

		$("#between1").val(between1);
		$("#between2").val(between2);

		loadAjaxTable(contentModule, orderBy, orderMode, limit, page, filter, search, between1, between2);

	}

);

/*=============================================
Cargar tabla con Ajax
=============================================*/

function loadAjaxTable(contentModule, orderBy, orderMode, limit, page, filter, search, between1, between2) {

	if ("filter" != "search") {

		fncSweetAlert("loading", "Cargando información...", "");
	}

	var data = new FormData();
	data.append("contentModule", contentModule);
	data.append("orderBy", orderBy);
	data.append("orderMode", orderMode);
	data.append("limit", limit);
	data.append("page", page);
	data.append("rolAdmin", $("#rolAdmin").val());
	data.append("search", search);
	data.append("between1", between1);
	data.append("between2", between2);
	data.append("idOffice", $("#idOffice").val())

	$.ajax({
		url: "/ajax/dynamic-tables.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
		processData: false,
		success: function (response) {

			if ("filter" != "search") {

				fncSweetAlert("close", "", "");

			}

			/*=============================================
			Limpiar la selección de items
			=============================================*/

			$("#checkItems").val("");
			$(".checkAllItems").attr("mode", "false");

			if (JSON.parse(response).HTMLTable != "") {

				/*=============================================
				Aparecer filtros y paginación
				=============================================*/

				$(".blockFooter").show();

				/*=============================================
				Actualizamos la tabla
				=============================================*/

				$("#loadTable").html(JSON.parse(response).HTMLTable);

				if (filter == "limit" || filter == "order" || filter == "search" || filter == "range") {

					/*=============================================
					Actualizamos la paginación
					=============================================*/

					$("#cont-pagination").html(`

						<ul id="pagination" 
						class="pagination pagination-sm rounded" 
						totalPages="${JSON.parse(response).totalPages}">
			        	</ul>

					`)

					initPagination();

				}

				/*=============================================
				Actualizamos los registros
				=============================================*/

				$("#startItems").html(((page - 1) * limit) + 1);

				if ((Number($("#startItems").html()) - 1) + Number(limit) > JSON.parse(response).totalData) {

					$("#endItems").html(JSON.parse(response).totalData);

				} else {

					$("#endItems").html((Number($("#startItems").html()) - 1) + Number(limit));

				}

				$("#totalItems").html(JSON.parse(response).totalData);

			} else {

				/*=============================================
				Actualizamos la tabla
				=============================================*/

				$("#loadTable").html(`

					<tr>
						<td colspan="${$("thead th").length}" class="text-center py-3">No hay registros disponibles</td>
					</tr>

				 `);

				/*=============================================
				Esconder filtros y paginación
				=============================================*/

				$(".blockFooter").hide();

			}

		}

	})
}

/*=============================================
Seleccionar Item Individual
=============================================*/

$(document).on("change", ".checkItem", function () {

	var idItem = $(this).attr("idItem");

	if ($("#checkItems").val() == "") {
		var checkItems = [];
	} else {
		var checkItems = $("#checkItems").val().split(",");
	}

	var typeCheck = $(this).prop("checked");

	if (typeCheck) {

		checkItems.push(idItem);

	} else {

		checkItems.forEach((e, i) => {

			if (e == idItem) {

				checkItems.splice(i, 1);
			}

		})
	}

	$("#checkItems").val(checkItems.toString());


})

/*=============================================
Seleccionar masiva de items
=============================================*/

$(document).on("click", ".checkAllItems", function () {

	var mode = $(this).attr("mode");
	var checkItem = $(".checkItem");
	var formCheck = $(".formCheck");

	if ($("#checkItems").val() == "") {
		var checkItems = [];
	} else {
		var checkItems = $("#checkItems").val().split(",");
	}

	if (mode == "false") {

		$(this).attr("mode", "true");

		checkItem.each((i) => {

			if (!$(checkItem[i]).prop("checked")) {

				$(checkItem[i]).attr("checked", true);

				checkItems.push($(checkItem[i]).attr("idItem"));

				$("#checkItems").val(checkItems.toString());

			}

		})

	} else {

		$(this).attr("mode", "false");

		checkItem.each((i) => {

			var idItem = $(checkItem[i]).attr("idItem");

			$(checkItem[i]).remove();

			$(formCheck[i]).html(`<input class="form-check-input checkItem" type="checkbox" idItem="${idItem}">`)

			$(checkItem[i]).attr("checked", false);

			checkItems.forEach((e, f) => {

				if (e == idItem) {

					checkItems.splice(f, 1);

				}

			})

			$("#checkItems").val(checkItems.toString());

		})
	}

})


/*=============================================
Eliminar Item Individual
=============================================*/

$(document).on("click", ".deleteItem", function () {

	var idItem = $(this).attr("idItem");
	var table = $(this).attr("table");
	var suffix = $(this).attr("suffix");

	fncSweetAlert("confirm", "¿Está seguro de borrar este registro?", "").then(resp => {

		if (resp) {

			fncMatPreloader("on");
			fncSweetAlert("loading", "Eliminando registro...", "");

			var data = new FormData();
			data.append("idItemDelete", idItem);
			data.append("tableDelete", table);
			data.append("suffixDelete", suffix);
			data.append("token", localStorage.getItem("tokenAdmin"));

			$.ajax({

				url: "/ajax/dynamic-tables.ajax.php",
				method: "POST",
				data: data,
				contentType: false,
				cache: false,
				processData: false,
				success: function (response) {

					fncMatPreloader("off");

					if (response == 200) {

						fncSweetAlert("success", "El registro ha sido eliminado con éxito", setTimeout(() => location.reload(), 1250))

					} else {

						// Intentar parsear como JSON si no es 200
						try {
							var jsonResponse = typeof response === 'string' ? JSON.parse(response) : response;

							if (jsonResponse.status === "error") {

								var errorMessage = "No se pudo eliminar el registro:\n\n";
								if (Array.isArray(jsonResponse.errors)) {
									errorMessage += jsonResponse.errors.join("\n");
								} else {
									errorMessage += jsonResponse.errors;
								}

								fncSweetAlert("error", errorMessage, "");

							} else if (jsonResponse.status === "partial") {

								fncSweetAlert("warning", "Solo se eliminaron " + jsonResponse.deleted + " de " + jsonResponse.total + " registro(s)", "");

							} else {

								fncSweetAlert("error", "Error al eliminar el registro", "");
							}

						} catch (e) {

							fncSweetAlert("error", "Error al eliminar el registro", "");
						}
					}
				},
				error: function (xhr, status, error) {

					fncMatPreloader("off");
					fncSweetAlert("error", "Error al procesar la solicitud", "");
				}

			})

		}

	})

})

/*=============================================
Eliminar items de forma masiva
=============================================*/

$(document).on("click", ".deleteAllItems", function () {

	var idItems = $("#checkItems").val();

	if (idItems == "") {

		fncToastr("error", "No hay ningún registro seleccionado");
		return;

	}

	var table = $("#checkItems").attr("table");
	var suffix = $("#checkItems").attr("suffix");

	fncSweetAlert("confirm", "¿Está seguro de borrar estos registros?", "").then(resp => {

		if (resp) {

			fncMatPreloader("on");
			fncSweetAlert("loading", "Eliminando registros...", "");

			var data = new FormData();
			data.append("idItemDelete", idItems);
			data.append("tableDelete", table);
			data.append("suffixDelete", suffix);
			data.append("token", localStorage.getItem("tokenAdmin"));

			$.ajax({

				url: "/ajax/dynamic-tables.ajax.php",
				method: "POST",
				data: data,
				contentType: false,
				cache: false,
				processData: false,
				success: function (response) {

					fncMatPreloader("off");

					if (response == 200) {

						fncSweetAlert("success", "Los registros han sido eliminados con éxito", setTimeout(() => location.reload(), 1250))

					} else {

						// Intentar parsear como JSON si no es 200
						try {
							var jsonResponse = typeof response === 'string' ? JSON.parse(response) : response;

							if (jsonResponse.status === "error") {

								var errorMessage = "No se pudieron eliminar algunos registros:\n\n";
								if (Array.isArray(jsonResponse.errors)) {
									errorMessage += jsonResponse.errors.join("\n");
								} else {
									errorMessage += jsonResponse.errors;
								}

								if (jsonResponse.deleted > 0) {
									errorMessage += "\n\nSe eliminaron exitosamente: " + jsonResponse.deleted + " registro(s)";
								}

								fncSweetAlert("error", errorMessage, "");

							} else if (jsonResponse.status === "partial") {

								fncSweetAlert("warning", "Solo se eliminaron " + jsonResponse.deleted + " de " + jsonResponse.total + " registro(s)", "");

							} else {

								fncSweetAlert("error", "Error al eliminar los registros", "");
							}

						} catch (e) {

							fncSweetAlert("error", "Error al eliminar los registros", "");
						}
					}
				},
				error: function (xhr, status, error) {

					fncMatPreloader("off");
					fncSweetAlert("error", "Error al procesar la solicitud", "");
				}

			})

		}

	})

})

/*=============================================
Cambiar estado de un registro boolean
=============================================*/

$(document).on("click", ".changeBoolean", function () {

	var bool = $(this).prop("checked");

	if (!bool) {

		$(this).parent().find(".form-check-label").html("OFF");

	} else {

		$(this).parent().find(".form-check-label").html("ON");
	}

	var idItem = $(this).attr("idItem");
	var table = $(this).attr("table");
	var suffix = $(this).attr("suffix");
	var column = $(this).attr("column");

	var data = new FormData();
	data.append("boolChange", bool);
	data.append("idItemChange", idItem);
	data.append("tableChange", table);
	data.append("suffixChange", suffix);
	data.append("columnChange", column);
	data.append("token", localStorage.getItem("tokenAdmin"));

	$.ajax({

		url: "/ajax/dynamic-tables.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
		processData: false,
		success: function (response) {

			if (response == 200) {

				fncToastr("success", "El registro ha sido actualizado con éxito");
			}

		}

	})

})

/*=============================================
Cerrar caja
=============================================*/

$(document).on("click", ".closeCash", function () {

	var idItem = $(this).attr("idItem");
	var table = $(this).attr("table");
	var suffix = $(this).attr("suffix");
	var column = $(this).attr("column");
	var diffCash = $(this).attr("diffCash");

	Swal.fire({
		title: "Cerrar caja",
		text: "Ingresa el dinero final contado para cerrar la caja",
		input: "number",
		inputValue: "",
		inputPlaceholder: diffCash,
		inputAttributes: {
			step: "any",
			min: 0,
			placeholder: diffCash
		},
		showCancelButton: true,
		confirmButtonText: "Cerrar",
		cancelButtonText: "Cancelar",
		customClass: {
			popup: 'swal-premium',
			confirmButton: 'swal2-confirm',
			cancelButton: 'swal2-cancel'
		},
		didOpen: () => {
			const input = Swal.getInput();
			if (input) {
				input.placeholder = diffCash;
			}
		},
		preConfirm: (value) => {
			if (value === null || value === "" || isNaN(Number(value))) {
				Swal.showValidationMessage("Ingresa un monto válido (sugerido: " + diffCash + ")");
				return false;
			}
			return value;
		}
	}).then((result) => {

		if(!result.isConfirmed){
			return;
		}

		var endCash = result.value;

		fncMatPreloader("on");
		fncSweetAlert("loading", "Cerrando caja...", "");

		var data = new FormData();
		data.append("endCashChange", endCash);
		data.append("diffCashChange", diffCash);
		data.append("idItemCashClose", idItem);
		data.append("tableCashClose", table);
		data.append("suffixCashClose", suffix);
		data.append("columnCashClose", column);
		data.append("token", localStorage.getItem("tokenAdmin"));

		$.ajax({

			url: "/ajax/dynamic-tables.ajax.php",
			method: "POST",
			data: data,
			contentType: false,
			cache: false,
			processData: false,
			success: function (response) {

				fncMatPreloader("off");
				fncSweetAlert("close", "", "");

				if (response == 200) {
					fncToastr("success", "La caja ha sido cerrada con éxito");
					setTimeout(() => location.reload(), 1250);
				} else {
					fncSweetAlert("error", "No se pudo cerrar la caja", "");
				}

			},
			error: function () {

				fncMatPreloader("off");
				fncSweetAlert("close", "", "");
				fncSweetAlert("error", "Error al procesar el cierre de caja", "");
			}

		})

	})

})

/*=============================================
Abrir caja
=============================================*/

$(document).on("click", ".openCash", function () {

	Swal.fire({
		title: "Abrir caja",
		text: "Ingresa el dinero inicial para abrir la caja",
		input: "number",
		inputValue: "",
		inputPlaceholder: "0.00",
		inputAttributes: {
			step: "any",
			min: 0,
			placeholder: "0.00"
		},
		showCancelButton: true,
		confirmButtonText: "Abrir",
		cancelButtonText: "Cancelar",
		confirmButtonColor: "#28a745",
		customClass: {
			popup: 'swal-premium',
			confirmButton: 'swal2-confirm',
			cancelButton: 'swal2-cancel'
		},
		didOpen: () => {
			const input = Swal.getInput();
			if (input) input.placeholder = "0.00";
		},
		preConfirm: (value) => {
			if (value === null || value === "" || isNaN(Number(value)) || Number(value) < 0) {
				Swal.showValidationMessage("Ingresa un monto válido mayor o igual a 0");
				return false;
			}
			return value;
		}
	}).then((result) => {

		if (!result.isConfirmed) return;

		fncMatPreloader("on");
		fncSweetAlert("loading", "Abriendo caja...", "");

		var data = new FormData();
		data.append("startCashOpen", result.value);
		data.append("tableCashOpen", "cashs");
		data.append("token", localStorage.getItem("tokenAdmin"));

		$.ajax({
			url: "/ajax/dynamic-tables.ajax.php",
			method: "POST",
			data: data,
			contentType: false,
			cache: false,
			processData: false,
			success: function (response) {
				fncMatPreloader("off");
				fncSweetAlert("close", "", "");
				if (response == 200) {
					fncToastr("success", "Caja abierta con éxito");
					setTimeout(() => location.reload(), 1250);
				} else if (response == "already_open") {
					fncSweetAlert("error", "Ya existe una caja abierta para esta sucursal hoy", "");
				} else {
					fncSweetAlert("error", "No se pudo abrir la caja", "");
				}
			},
			error: function () {
				fncMatPreloader("off");
				fncSweetAlert("close", "", "");
				fncSweetAlert("error", "Error al procesar la apertura de caja", "");
			}
		});

	})

})

/*=============================================
Cambiar estado boleano masivo
=============================================*/

$(document).on("click", ".myBooleans", function () {

	var idItems = $("#checkItems").val();

	if (idItems == "") {

		fncToastr("error", "No hay ningún registro seleccionado");
		return;

	}

	var table = $("#checkItems").attr("table");
	var suffix = $("#checkItems").attr("suffix");
	var column = $(this).attr("column");

	$("#myBooleans").modal("show");

	$("#myBooleans").on('shown.bs.modal', function () {

		$(document).on("click", ".changeBooleans", function () {

			var bool = $("#valueBoolean").val();

			fncMatPreloader("on");
			fncSweetAlert("loading", "Cambiando registros...", "");

			var data = new FormData();
			data.append("boolChange", bool);
			data.append("idItemChange", idItems);
			data.append("tableChange", table);
			data.append("suffixChange", suffix);
			data.append("columnChange", column);
			data.append("token", localStorage.getItem("tokenAdmin"));

			$.ajax({

				url: "/ajax/dynamic-tables.ajax.php",
				method: "POST",
				data: data,
				contentType: false,
				cache: false,
				processData: false,
				success: function (response) {

					if (response == 200) {

						fncSweetAlert("success", "los registros han sido actualizado con éxito", setTimeout(() => location.reload(), 1250));

					}

				}

			})

		})


	})

})

/*=============================================
Cambiar selección masiva
=============================================*/

$(document).on("click", ".mySelects", function () {

	var idItems = $("#checkItems").val();

	if (idItems == "") {

		fncToastr("error", "No hay ningún registro seleccionado");
		return;

	}

	var table = $("#checkItems").attr("table");
	var suffix = $("#checkItems").attr("suffix");
	var column = $(this).attr("column");
	var matrix = $(this).attr("matrix").split(",");

	$("#mySelects").modal("show");

	$("#mySelects").on('shown.bs.modal', function () {

		matrix.forEach((e, i) => {

			$("#valueSelect").append(`<option value="${e}">${e}</option>`)

		})

		$(document).on("click", ".changeSelects", function () {

			var select = $("#valueSelect").val();

			fncMatPreloader("on");
			fncSweetAlert("loading", "Cambiando registros...", "");

			var data = new FormData();
			data.append("itemSelect", select);
			data.append("idItemSelect", idItems);
			data.append("tableSelect", table);
			data.append("suffixSelect", suffix);
			data.append("columnSelect", column);
			data.append("token", localStorage.getItem("tokenAdmin"));

			$.ajax({

				url: "/ajax/dynamic-tables.ajax.php",
				method: "POST",
				data: data,
				contentType: false,
				cache: false,
				processData: false,
				success: function (response) {

					if (response == 200) {

						fncSweetAlert("success", "los registros han sido actualizado con éxito", setTimeout(() => location.reload(), 1250));

					}

				}

			})

		})


	})

})

/*=============================================
Cambiar el orden de un registro
=============================================*/

$(document).on("change", ".changeOrder", function () {

	var num = $(this).val();
	var idItem = $(this).attr("idItem");
	var table = $(this).attr("table");
	var suffix = $(this).attr("suffix");
	var column = $(this).attr("column");

	var data = new FormData();
	data.append("numOrder", num);
	data.append("idItemOrder", idItem);
	data.append("tableOrder", table);
	data.append("suffixOrder", suffix);
	data.append("columnOrder", column);
	data.append("token", localStorage.getItem("tokenAdmin"));

	$.ajax({

		url: "/ajax/dynamic-tables.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
		processData: false,
		success: function (response) {

			if (response == 200) {

				fncToastr("success", "El registro ha sido actualizado con éxito");
			}

		}

	})

})