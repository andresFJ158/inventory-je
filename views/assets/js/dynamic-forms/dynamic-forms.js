/*=============================================
Actualizar la matriz del select
=============================================*/

$(document).on("change", ".changeSelectType", function () {

	var matrix_column = $(this).val();
	var id_column = $(this).attr("idColumn");
	var title_column = $(this).attr("titleColumn");
	var pre_value = $(this).attr("preValue");

	var data = new FormData();
	data.append("matrix_column", matrix_column);
	data.append("id_column", id_column);
	data.append("pre_value", pre_value);
	data.append("token", localStorage.getItem("tokenAdmin"));

	$.ajax({
		url: "/ajax/dynamic-forms.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
		processData: false,
		success: function (response) {

			$("#" + title_column).html(response);

		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.error("Error en changeSelectType:", textStatus, errorThrown);
		}

	})

})

/*=============================================
Adicionar un nuevo objeto
=============================================*/

$(document).on("click", ".addObject", function () {

	var itemObjectLength = $(this).parent().find(".itemObject").length;

	$(this).parent().find(".itemsObject:last").append($(this).parent().find(".itemsObject .itemObject:first")[0].outerHTML.replace(/_0/g, "_" + itemObjectLength));

})


/*=============================================
Quitar un objeto
=============================================*/

function removeObject(column, position, event) {

	if (position == "_0") {

		fncToastr("error", "Debe existir un item de objeto");

		return;
	}

	$(event.target).parent().parent().parent().parent().remove();

	changeItemObject(column);

}

/*=============================================
Función cuando cambia el objeto
=============================================*/

function changeItemObject(column) {

	var propertyObject = $(".propertyObject." + column);
	var valueObject = $(".valueObject." + column);

	var object = '{';

	propertyObject.each((i) => {

		object += '"' + $(propertyObject[i]).val() + '":"' + $(valueObject[i]).val().replace(/"/g, '\\"') + '",';

	})

	object = object.slice(0, -1);
	object += '}';

	$("#" + column).val(object);
}

/*=============================================
Adicionar un nuevo item para el json
=============================================*/

$(document).on("click", ".addJson", function () {

	var itemJsonLength = $(this).parent().find(".itemJson").length;

	$(this).parent().find(".itemsJson:last").append($(this).parent().find(".itemsJson .itemJson:first")[0].outerHTML.replace(/_0/g, "_" + itemJsonLength));

})

/*=============================================
Quitar un objeto
=============================================*/

function removeJson(column, position, event) {
	console.log("position", position);

	if (position == "_0") {

		fncToastr("error", "Debe existir un item de objeto");

		return;
	}

	$(event.target).parent().parent().parent().parent().remove();

	changeItemJson(column);

}

/*=============================================
Adicionar un grupo de objetos
=============================================*/

$(document).on("click", ".addJsonGroup", function () {

	var jsonGroupLength = $(this).parent().find(".jsonGroup").length;

	$(this).parent().find(".jsonGroup:last").after($(this).parent().find(".jsonGroup:first")[0].outerHTML.replace(/0_/g, jsonGroupLength + "_"));

})

/*=============================================
Remover un grupo de objetos
=============================================*/
function removeJsonGroup(column, position, event) {

	if (position == "0_") {

		fncToastr("error", "Debe existir un grupo de objetos");

		return;

	}

	$(event.target).parent().parent().remove();

	changeItemJson(column);

}

/*=============================================
Función cuando cambia el Json
=============================================*/

function changeItemJson(column) {

	var jsonGroup = $(".jsonGroup." + column);

	var jSon = '[';

	jsonGroup.each((f) => {

		var propertyJson = $("." + $(jsonGroup[f]).attr("position") + "propertyJson." + column);
		var valueJson = $("." + $(jsonGroup[f]).attr("position") + "valueJson." + column);

		jSon += '{';

		propertyJson.each((i) => {

			jSon += '"' + $(propertyJson[i]).val() + '":"' + $(valueJson[i]).val().replace(/"/g, '\\"') + '",';

		})

		jSon = jSon.slice(0, -1);
		jSon += '},';

	})

	jSon = jSon.slice(0, -1);
	jSon += ']';

	$("#" + column).val(jSon);
}

/*=============================================
Abrir ventana modal de archivos
=============================================*/

$(document).on("click", ".myFiles", function () {

	$("#myFiles").modal("show");

	var input = $(this).parent().find("input");

	$("#myFiles").on('shown.bs.modal', function () {

		$(".modal-body").find(".copyLink").append().html(`<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-bar-down" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M1 3.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 0 1h-13a.5.5 0 0 1-.5-.5M8 6a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 .708-.708L7.5 12.293V6.5A.5.5 0 0 1 8 6"/>
</svg> `)


		$(document).on("click", ".copyLink", function () {

			$(input).val($(this).attr("copy"));

			$("#myFiles").modal("hide");

		})
	})
})

/*=============================================
Cambiar la tabla de relaciones
=============================================*/

$(document).on("change", ".changeRelations", function () {

	var selectRelations = $(this).parent().find(".selectRelations");
	var columnName = $(selectRelations).attr("data-field-name") || $(selectRelations).attr("name");
	var matrix = $(selectRelations).attr("data-matrix") || $(this).val();

	$(selectRelations).html('');

	var table = $(this).val();
	var id_column = $(this).attr("idColumn") || $(this).attr("data-id-column");

	// Detectar si es el campo de sucursales en productos
	var isProductOffice = (window.location.pathname.includes("/productos") ||
		window.location.pathname.includes("/products")) &&
		columnName === "id_office_product" &&
		table === "offices" &&
		matrix === "offices";

	// Verificar que no estemos editando (no existe idItem hidden)
	var isCreating = !$("#idItem").length;

	// Agregar opción "Todas las Sucursales" si aplica (cualquier admin puede crear para todas las sucursales)
	if (isProductOffice && isCreating) {
		$(selectRelations).append('<option value="all">🌟 Todas las Sucursales</option>');
	}

	var data = new FormData();
	data.append("table", table);
	data.append("id_column", id_column);
	data.append("token", localStorage.getItem("tokenAdmin"));

	$.ajax({
		url: "/ajax/dynamic-forms.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
		processData: false,
		success: function (response) {

			if (JSON.parse(response).length > 0) {

				// Si es el campo de sucursal (offices), agregar opción inicial "Selecciona la sucursal"
				if (table === "offices" || matrix === "offices") {
					// Verificar si hay un valor actual guardado (editando)
					var hasCurrentValue = $(selectRelations).data('default-value') || false;
					var currentValue = hasCurrentValue ? $(selectRelations).data('default-value') : null;

					// Solo seleccionar "Selecciona la sucursal" si NO hay valor actual
					var selectedAttr = (!currentValue || currentValue === "") ? 'selected' : '';
					$(selectRelations).append('<option value="" ' + selectedAttr + '>Selecciona la sucursal</option>');
				}

				JSON.parse(response).forEach((e, i) => {
					var optionValue = Object.values(e)[0];
					var optionText = Object.values(e)[0] + ' - ' + Object.values(e)[1];

					// Verificar si este es el valor actual (editando)
					var currentValue = $(selectRelations).data('default-value');
					var isSelected = (currentValue && String(currentValue) === String(optionValue)) ? ' selected' : '';

					$(selectRelations).append(`

						<option value="${optionValue}"${isSelected}>${optionText}</option>

					 `)

				})

				// Si hay un valor predeterminado (editando), establecerlo
				var defaultValue = $(selectRelations).data('default-value');
				if (defaultValue && defaultValue !== "" && defaultValue !== null) {
					if ($(selectRelations).find('option[value="' + defaultValue + '"]').length > 0) {
						// Remover selected de "Selecciona la sucursal" antes de establecer el valor predeterminado
						$(selectRelations).find('option[value=""]').prop('selected', false);
						$(selectRelations).val(defaultValue);

						// Actualizar select2 si está inicializado
						if ($.fn.select2) {
							$(selectRelations).trigger('change.select2');
						}
					}
				} else {
					// Si no hay valor predeterminado, asegurar que "Selecciona la sucursal" esté seleccionado
					if (table === "offices" || matrix === "offices") {
						$(selectRelations).val('').trigger('change.select2');
					}
				}

				// Si es el campo de sucursal relacionado con productos, aplicar filtro después de cargar opciones
				if ((table === "offices" || matrix === "offices")) {
					// Si hay un valor predeterminado o si hay una opción seleccionada, cargar productos
					var hasDefaultValue = $(selectRelations).data('default-value');
					var hasSelectedOption = $(selectRelations).find('option:selected').length > 0 && $(selectRelations).find('option:selected').val() !== "";

					if (hasDefaultValue || hasSelectedOption) {
						setTimeout(function () {
							applyProductFilterForOffice();
						}, 500);
					}
				}

			}

		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.error("Error en changeRelations:", textStatus, errorThrown);
		}

	})

})

/*=============================================
Actualizar la matriz de ChatGPT
=============================================*/

$(document).on("change", ".changeChatGPT", function () {

	fncMatPreloader("on");
	fncSweetAlert("loading", "Esperando respuesta de ChatGPT...", "");

	var matrix_prompt = $(this).val();
	var id_prompt = $(this).attr("idPrompt");
	var title_prompt = $(this).attr("titlePrompt");

	var data = new FormData();
	data.append("matrix_prompt", matrix_prompt);
	data.append("id_prompt", id_prompt);
	data.append("token", localStorage.getItem("tokenAdmin"));

	$.ajax({
		url: "/ajax/dynamic-forms.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
		processData: false,
		success: function (response) {

			fncMatPreloader("off");
			fncSweetAlert("close", ".", "");

			$("#" + title_prompt).summernote('code', response);

		},
		error: function (jqXHR, textStatus, errorThrown) {
			fncMatPreloader("off");
			fncSweetAlert("error", "Error al obtener respuesta de ChatGPT", "");
			console.error("Error en changeChatGPT:", textStatus, errorThrown);
		}

	})

})

/*=============================================
Filtrar productos por sucursal en módulo de compras
=============================================*/

// Manejar cambios en cualquier select de sucursal que esté relacionado con productos
$(document).on("change", "select[data-matrix='offices'], select[name*='office'], select[id*='office']", function () {
	var officeSelect = $(this);
	var officeName = officeSelect.attr('name') || officeSelect.attr('id') || '';
	var selectedOffice = officeSelect.val();

	// Buscar el select de productos correspondiente
	var productSelect = null;

	// Intentar encontrar el select de productos por diferentes métodos
	var moduleSuffix = officeName.replace(/id_office_/, '').replace(/id_office/, '');
	if (moduleSuffix) {
		productSelect = $('select[name="id_product_' + moduleSuffix + '"], select[id="id_product_' + moduleSuffix + '"]');
	}

	// Si no se encontró, buscar cualquier select de productos en el mismo formulario
	if (!productSelect || productSelect.length === 0) {
		productSelect = officeSelect.closest('form').find('select[data-matrix="products"]');
	}

	// Verificar que exista el campo de productos y que se haya seleccionado una sucursal válida
	if (productSelect.length === 0 || !selectedOffice || selectedOffice === "" || selectedOffice === "all") {
		return;
	}

	// Verificar que el campo de productos esté relacionado con la tabla "products"
	var productMatrix = productSelect.attr("data-matrix");
	if (productMatrix !== "products") {
		return;
	}

	// Cargar productos usando la función genérica
	loadProductsByOffice(officeSelect, productSelect);
})

/*=============================================
Filtrar productos automáticamente cuando hay sucursal predeterminada en compras
=============================================*/

// Función para cargar productos filtrados por sucursal (genérica para cualquier módulo)
function loadProductsByOffice(officeSelect, productSelect) {
	var selectedOffice = officeSelect.val();

	// Si select2 está inicializado, intentar obtener el valor de select2
	if ($.fn.select2 && officeSelect.hasClass('select2-hidden-accessible')) {
		var select2Value = officeSelect.select2('val');
		if (select2Value && select2Value !== "") {
			selectedOffice = select2Value;
		}
	}

	// Verificar que se haya seleccionado una sucursal válida
	if (!selectedOffice || selectedOffice === "" || selectedOffice === "all") {
		return;
	}

	// Verificar que el campo de productos esté relacionado con la tabla "products"
	var productMatrix = productSelect.attr("data-matrix");
	if (productMatrix !== "products") {
		return;
	}

	// Guardar el valor actual seleccionado si existe
	var currentValue = productSelect.val();

	// Limpiar el select de productos y mostrar mensaje de carga
	productSelect.html('<option value="">Cargando productos...</option>').prop("disabled", true);

	// Construir la URL para obtener productos filtrados por sucursal
	var url = "relations?rel=products,categories&type=product,category" +
		"&linkTo=id_office_product" +
		"&equalTo=" + encodeURIComponent(selectedOffice) +
		"&select=id_product,title_product,sku_product";



	$.ajax({
		url: "https://api.desarrolloweb24siete.com/" + url,
		method: "GET",
		headers: {
			'Authorization': 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'
		},
		success: function (response) {
			// Limpiar el select
			productSelect.html('').prop("disabled", false);

			if (response && response.status === 200 && response.results && response.results.length > 0) {
				// Agregar opción vacía por defecto
				productSelect.append('<option value="">Seleccione un producto</option>');

				// Agregar cada producto al select
				response.results.forEach(function (product) {
					var arr = typeof product === 'object' ? Object.values(product) : [];
					if (arr.length >= 3) {
						var idProduct = arr[0]; // id_product
						var titleProduct = arr[1] || ''; // title_product (sin decodificar aún)
						var skuProduct = arr[2] || ''; // sku_product

						// Decodificar el título y reemplazar + con espacios
						var decodedTitle = '';
						try {
							// Primero reemplazar + con espacios antes de decodificar
							var titleWithSpaces = titleProduct.replace(/\+/g, ' ');
							// Luego decodificar URI
							decodedTitle = decodeURIComponent(titleWithSpaces);
						} catch (e) {
							// Si falla la decodificación, usar el título con + reemplazados
							decodedTitle = titleProduct.replace(/\+/g, ' ');
						}

						var selected = (currentValue && String(currentValue) === String(idProduct)) ? ' selected' : '';
						// Mostrar solo el título del producto
						var optionText = decodedTitle;

						productSelect.append('<option value="' + idProduct + '"' + selected + '>' + optionText + '</option>');
					}
				});

				// Si había un valor seleccionado previamente y no se encontró, seleccionar el primero
				if (currentValue && productSelect.find('option[value="' + currentValue + '"]').length === 0 && productSelect.find('option').length > 1) {
					productSelect.val(productSelect.find('option:not([value=""])').first().val());
				}

				// Actualizar select2 si está inicializado
				if ($.fn.select2) {
					productSelect.trigger('change.select2');
				}

			} else {
				// Si no hay productos, mostrar mensaje
				productSelect.html('<option value="">No hay productos disponibles en esta sucursal</option>');
			}

		},
		error: function (xhr, status, error) {
			console.error("Error al cargar productos:", error);
			productSelect.html('<option value="">Error al cargar productos</option>').prop("disabled", false);
		}
	});
}

// Función para aplicar filtro de productos por sucursal (genérica)
function applyProductFilterForOffice() {
	// Buscar todos los selects de sucursal que puedan estar relacionados con productos
	$('select[data-matrix="offices"], select[name*="office"], select[id*="office"]').each(function () {
		var officeSelect = $(this);
		var officeName = officeSelect.attr('name') || officeSelect.attr('id') || '';

		// Buscar el select de productos correspondiente
		// Puede ser por nombre (id_product_xxx, id_product_purchase, etc.) o por relación en el formulario
		var productSelect = null;

		// Intentar encontrar el select de productos por diferentes métodos
		// 1. Buscar por patrón de nombre (id_product_xxx)
		var moduleSuffix = officeName.replace(/id_office_/, '').replace(/id_office/, '');
		if (moduleSuffix) {
			productSelect = $('select[name="id_product_' + moduleSuffix + '"], select[id="id_product_' + moduleSuffix + '"]');
		}

		// 2. Si no se encontró, buscar cualquier select de productos en el mismo formulario
		if (!productSelect || productSelect.length === 0) {
			productSelect = officeSelect.closest('form').find('select[data-matrix="products"], select[name*="product"], select[id*="product"]');
		}

		// 3. Si aún no se encontró, buscar en toda la página
		if (!productSelect || productSelect.length === 0) {
			productSelect = $('select[data-matrix="products"]');
		}

		// Verificar que existan ambos selects
		if (officeSelect.length === 0 || !productSelect || productSelect.length === 0) {
			return;
		}

		// Verificar que el campo de productos esté relacionado con la tabla "products"
		var productMatrix = productSelect.attr("data-matrix");
		if (productMatrix !== "products") {
			return;
		}

		// Obtener el valor de la sucursal
		var selectedOffice = officeSelect.val();

		// También verificar si hay un valor predeterminado en data-default-value
		var defaultValue = officeSelect.data('default-value');
		if (defaultValue && (!selectedOffice || selectedOffice === "")) {
			selectedOffice = defaultValue;
		}

		// Verificar si hay una opción seleccionada en el HTML
		var selectedOption = officeSelect.find('option:selected');
		if (selectedOption.length > 0 && selectedOption.val() && selectedOption.val() !== "") {
			selectedOffice = selectedOption.val();
		}

		// Si select2 está inicializado, intentar obtener el valor de select2
		if ($.fn.select2 && officeSelect.hasClass('select2-hidden-accessible')) {
			var select2Value = officeSelect.select2('val');
			if (select2Value && select2Value !== "") {
				selectedOffice = select2Value;
			}
		}

		// Si hay una sucursal seleccionada válida
		if (selectedOffice && selectedOffice !== "" && selectedOffice !== "all") {
			var productOptions = productSelect.find('option').length;
			var hasEmptyOption = productSelect.find('option[value=""]').length > 0;
			var hasOnlyEmptyOrLoading = (productOptions <= 2 && hasEmptyOption) || productSelect.find('option:contains("Cargando")').length > 0;

			// Verificar si el select de productos ya tiene opciones filtradas (no solo la inicial)
			var hasFilteredOptions = productOptions > 1 && !hasOnlyEmptyOrLoading;

			// Si el select de productos está vacío, tiene solo la opción inicial, o muestra "Cargando"
			// entonces cargar los productos automáticamente
			if (!hasFilteredOptions && (hasOnlyEmptyOrLoading || productOptions <= 1)) {
				loadProductsByOffice(officeSelect, productSelect);
			}
		}
	});
}

// Función legacy para compras (mantiene compatibilidad)
function applyProductFilterForPurchase() {
	applyProductFilterForOffice();
}

// Ejecutar cuando el documento esté listo
$(document).ready(function () {
	// Esperar a que select2 se inicialice antes de aplicar el filtro
	setTimeout(function () {
		applyProductFilterForOffice();
	}, 800);

	// También ejecutar después de un delay adicional para asegurar que todo esté cargado
	setTimeout(function () {
		applyProductFilterForOffice();
	}, 1500);
})

// También ejecutar cuando select2 se inicializa (si está disponible)
$(document).on('select2:open', function () {
	setTimeout(function () {
		applyProductFilterForOffice();
	}, 100);
})

// Ejecutar cuando cambia la tabla de relaciones (para asegurar que se aplique después de cargar)
$(document).on("change", ".changeRelations", function () {
	// Si es el campo de sucursal en compras, aplicar filtro después de un delay
	var selectRelations = $(this).parent().find(".selectRelations");
	var columnName = $(selectRelations).attr("data-field-name") || $(selectRelations).attr("name");

	if (columnName === "id_office_purchase") {
		setTimeout(function () {
			applyProductFilterForPurchase();
		}, 800);
	}
})