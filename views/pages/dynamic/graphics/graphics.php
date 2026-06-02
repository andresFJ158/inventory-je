<?php 

$xAxis = array();
$yAxis = array();

$content = json_decode($module->content_module);

$suffix = explode("_",$content->yAxis);
$suffix = end($suffix);

if($_SESSION["admin"]->id_office_admin > 0){

	if($module->title_module == "gráfico de ventas diarias" && $module->id_page_module == 13){

		$url = $content->table."?linkTo=status_order,id_office_order&equalTo=Completada,".$_SESSION["admin"]->id_office_admin."&select=".$content->xAxis.",".$content->yAxis;

	}else if($module->title_module == "ventas por sucursal" || $module->title_module == "compras por sucursal"){

		$content->xAxis = "title_office";

		$url = "relations?rel=".$content->table.",offices&type=".$suffix.",office&linkTo=id_office_".$suffix."&equalTo=".$_SESSION["admin"]->id_office_admin."&select=".$content->xAxis.",".$content->yAxis;

	}else{

		$url = $content->table."?linkTo=id_office_".$suffix."&equalTo=".$_SESSION["admin"]->id_office_admin."&select=".$content->xAxis.",".$content->yAxis;
	}

}else{

	if($module->title_module == "gráfico de ventas diarias" && $module->id_page_module == 13){

		$url = $content->table."?linkTo=status_order&equalTo=Completada&select=".$content->xAxis.",".$content->yAxis;

	}else if($module->title_module == "ventas por sucursal" || $module->title_module == "compras por sucursal"){

		$content->xAxis = "title_office";

		$url = "relations?rel=".$content->table.",offices&type=".$suffix.",office&select=".$content->xAxis.",".$content->yAxis;

	}else{

		$url = $content->table."?select=".$content->xAxis.",".$content->yAxis;
	}

}

$method = "GET";
$fields = array();

$response = CurlController::request($url,$method,$fields);

if($response->status == 200){

	$graphic = $response->results;

	foreach (json_decode(json_encode($graphic),true) as $index => $item) {

		if($module->title_module == "gráfico de ventas mensuales"){

			array_push($xAxis, substr($item[$content->xAxis],0,-3));	
			$yAxis[substr($item[$content->xAxis],0,-3)] = 0;
		
		}else{

			array_push($xAxis, $item[$content->xAxis]);
			$yAxis[$item[$content->xAxis]] = 0;
		}
	
	}

	$xAxis = array_values(array_unique($xAxis));

	foreach (json_decode(json_encode($graphic),true) as $index => $item) {
		
		for($i = 0; $i < count($xAxis); $i++){

			if($module->title_module == "gráfico de ventas mensuales"){

				if($xAxis[$i] == substr($item[$content->xAxis],0,-3)){

					$yAxis[substr($item[$content->xAxis],0,-3)] +=  $item[$content->yAxis];
					
				}

			}else{

				if($xAxis[$i] == $item[$content->xAxis]){

					$yAxis[$item[$content->xAxis]] +=  $item[$content->yAxis];
					
				}
			}
		}

	}

}

?>

<div class="<?php if ($module->width_module == "100"): ?> col-lg-12 <?php endif ?><?php if ($module->width_module == "75"): ?> col-lg-9 <?php endif ?><?php if ($module->width_module == "50"): ?> col-lg-6 <?php endif ?><?php if ($module->width_module == "33"): ?> col-lg-4 <?php endif ?><?php if ($module->width_module == "25"): ?> col-lg-3 <?php endif ?> col-12 mb-3 position-relative">

	<?php if ($_SESSION["admin"]->rol_admin == "superadmin"): ?>

		<div class="position-absolute border rounded bg-white" style="top:0px; right:12px; z-index:100">
			
			<button type="button" class="btn btn-sm text-muted rounded m-0 px-1 py-0 border-0 myModule" item='<?php echo json_encode($module) ?>' idPage="<?php echo $page->results[0]->id_page ?>">
				<i class="bi bi-pencil-square"></i>
			</button>

			<button type="button" class="btn btn-sm text-muted rounded m-0 px-1 py-0 border-0 deleteModule" idModule=<?php echo base64_encode($module->id_module) ?> >
				<i class="bi bi-trash"></i>
			</button>


		</div>
		
	<?php endif ?>

	
	<div class="card rounded">
		
		<div class="card-header bg-white rounded-top h4 font-weight-bold text-capitalize py-3">
			<?php echo $module->title_module ?>
		</div>

		<div class="card-body p-4">
			<canvas id="chart-<?php echo str_replace(" ","_",$module->title_module) ?>" height="500"></canvas>
		</div>

	</div>

</div>

<script>
	
if($("#chart-<?php echo str_replace(" ","_",$module->title_module) ?>").length > 0){

	var graphicChart = $("#chart-<?php echo str_replace(" ","_",$module->title_module) ?>");
	var tagsChart = new Chart(graphicChart, {

		type: "<?php echo $content->type ?>",
		data: {
			labels:[

				<?php 

					foreach ($xAxis as $index => $item){

						echo "'".urldecode($item)."',";

					}

				?>

				],
			datasets:[
				{
					backgroundColor: 'rgba(<?php echo $content->color ?>,.55)',
					borderColor: 'rgb(<?php echo $content->color ?>)',
					data: [

						<?php 

							foreach ($xAxis as $index => $item){

								echo "'".$yAxis[$item]."',";

							}

						?>


					]
				}
			]
		},//close data
		options: {
	        maintainAspectRatio: false,
	        tooltips: {
	          mode: 'index',
	          intersect: true
	        },
	        hover: {
	          mode: 'index',
	          intersect: true
	        },
	        legend: {
	          display: false
	        },
	        scales: {
	        	yAxes: [{
        		 	display: true,
		            gridLines: {
		              display: true
		            },
		            ticks: $.extend({
         			  beginAtZero: true,
		              // Include a dollar sign in the ticks
		              callback: function (value) {
		                if (value >= 1000) {
		                  value /= 1000
		                  value += 'k'
		                }

		                return  value
		              }
		            }, 
		            {
	                  fontColor: '#495057',
	                  fontStyle: 'bold'
            		})

            	}],
            	xAxes: [{
		            display: true,
		            gridLines: {
		              display: true
		            },
		            ticks: {
	                  fontColor: '#495057',
	                  fontStyle: 'bold'
	                }
	          	}]

	        }//close scales

	    }//close options

	})
}

</script>