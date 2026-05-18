/*=============================================
Formatear envío de formulario lado servidor
=============================================*/

function fncFormatInputs(){

	if(window.history.replaceState){
        window.history.replaceState( null, null, window.location.href );     
    }

}


/*=============================================
Alerta SweetAlert
=============================================*/

var _loadingTimeout = null; // Timeout de seguridad para "loading"

function fncSweetAlert(type, text, url){

	// Cancelar timeout anterior si existe
	if(_loadingTimeout){
		clearTimeout(_loadingTimeout);
		_loadingTimeout = null;
	}

	switch(type){

		case "success":

		if(url == ""){

			Swal.fire({

				icon: "success",
				title: "Correcto",
				text: text,
				showConfirmButton: false,
				timer: 3000,
				timerProgressBar: true,
				customClass: {
					popup: 'swal-premium',
				}
			})

		}else{

			Swal.fire({

				icon: "success",
				title: "Correcto",
				text: text,
				showConfirmButton: false,
				timer: 3000,
				timerProgressBar: true,
				customClass: {
					popup: 'swal-premium',
				}

			}).then((result)=>{

				if (result.value || result.dismiss){ 

					window.open(url, "_top");
				}

			})

		}	

		break;

		case "error":

		if(url == ""){

			Swal.fire({

				icon: "error",
				title: "Error",
				text: text,
				customClass: {
					popup: 'swal-premium',
					confirmButton: 'swal2-confirm',
					cancelButton: 'swal2-cancel'
				}

			})

		}else{

			Swal.fire({

				icon: "error",
				title: "Error",
				text: text,
				customClass: {
					popup: 'swal-premium',
					confirmButton: 'swal2-confirm',
					cancelButton: 'swal2-cancel'
				}

			}).then((result)=>{

				if (result.value){ 

					window.open(url, "_top");
				}

			})

		}	

		break;

		case "loading":

			Swal.fire({
            	allowOutsideClick: false,
            	allowEscapeKey: false,
            	icon: 'info',
            	text: text,
            	showConfirmButton: false,
            	customClass: {
            		popup: 'swal-premium',
            	}
          	});
          	Swal.showLoading();

          	// Timeout de seguridad: si el modal sigue abierto después de 60s, lo cierra con error
          	_loadingTimeout = setTimeout(function(){
          		if(Swal.isVisible() && Swal.isLoading()){
          			fncMatPreloader("off");
          			Swal.fire({
          				icon: 'error',
          				title: 'Tiempo de espera agotado',
          				text: 'La operación tardó demasiado. Verifica tu conexión e intenta de nuevo.',
          				confirmButtonText: 'Aceptar',
          				customClass: {
          					popup: 'swal-premium',
          					confirmButton: 'swal-btn-confirm'
          				}
          			});
          		}
          	}, 60000);

		break;

		case "confirm":

			return new Promise(resolve =>{

				Swal.fire({
					text: text,
					icon: "warning",
					showCancelButton: true,
					confirmButtonText: "¡Si, continuar!",
					cancelButtonText: 'No',
					customClass: {
						popup: 'swal-premium',
						confirmButton: 'swal2-confirm',
						cancelButton: 'swal2-cancel'
					}
				}).then((result) => {

					resolve(result.value);
					
				});

			});

		break;

		case "close":

			if(_loadingTimeout){
				clearTimeout(_loadingTimeout);
				_loadingTimeout = null;
			}
			Swal.close();

		break;
	}

}


/*=============================================
Alerta Toastr
=============================================*/

function fncToastr(type, text){

	var iconHtml = "";
	if(type == "success") iconHtml = '<i class="bi bi-check-circle-fill me-2 text-success"></i>';
	if(type == "error") iconHtml = '<i class="bi bi-x-circle-fill me-2 text-danger"></i>';
	if(type == "warning") iconHtml = '<i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>';
	if(type == "info") iconHtml = '<i class="bi bi-info-circle-fill me-2 text-info"></i>';

	var Toast = Swal.mixin({
		toast: true,
		position: 'top-end',
		showConfirmButton: false,
		showCloseButton: true,
		timer: 5000,
		timerProgressBar: true,
		didOpen: (toast) => {
		    toast.addEventListener('mouseenter', Swal.stopTimer)
		    toast.addEventListener('mouseleave', Swal.resumeTimer)
		  }

	})

	Toast.fire({
        title: '<div class="d-flex align-items-center">' + iconHtml + '<span>' + text + '</span></div>',
        customClass: {
        	popup: 'swal-premium'
        }
    })

}

/*=============================================
Alerta Línea Precarga
=============================================*/

function fncMatPreloader(type){

	var preloader = new $.materialPreloader({
		position: 'top',
        height: '5px',
        col_1: '#159756',
        col_2: '#da4733',
        col_3: '#3b78e7',
        col_4: '#fdba2c',
        fadeIn: 200,
        fadeOut: 200
	})

	if(type == "on"){

		preloader.on();
	
	}


	if(type == "off"){

		$(".load-bar-container").remove();
	}

}

/*=============================================
Función para alertar luego de un click
=============================================*/

function alertClick(text){
    
    fncMatPreloader("on");
    fncSweetAlert("loading", text, "");

}