<style>
.card
{
    width:98%;
    min-width: 98% !important;
}
.card-header
{
    padding: 0.0rem 1.25rem !important;
    border-bottom: none !important;
}
.card-header h5{
    padding: 2px 0 !important;
}
.btn-info {
    color: #fff !important;
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
}
input[type="text"]:disabled {
    opacity: 1 !important;
    color: #0a0a0a !important; 
}
.wp-admin .accordion select { 
    height: 40px !important;    
}
div.message {
    background: #fff;
    border-left: 4px solid #fff;
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    margin: 5px 15px 2px;
    padding: 1px 12px;
}
div.message.notice-success {
    border-left-color: #46b450;
}
div.message.notice-error {
    border-left-color: #cc0000;
}
</style>
<?php
	//AMR DB
	$dbServerName = "107.175.137.171";
    $dbUsername = "amruser";
    $dbPassword = "Hello468@429n!";
    $dbName = "amr";
	
	// create connection
    $amr_conn = new mysqli($dbServerName, $dbUsername, $dbPassword, $dbName);
	
	// check connection
    if ($amr_conn->connect_error) {
        die("Connection failed: " . $amr_conn->connect_error);
    }
	
	//Flatrate DB		
	global $wpdb;			
    $table_name = $wpdb->prefix."amr_cron_users";
    
    $paged = 1;
    if(isset($_GET['paged']))
    {
        $paged = $_GET['paged'];
    }

    /** get AMR data from remote database table **/
    $amr_sql = "SELECT `Alias`, `Code`, `Serial` , `Alarm`, `Alert`, `Model`, `Notes`, `CodInstall`, `iKey` FROM printers WHERE `Code` != '' ORDER BY `Code` ASC ";
    $amr_result = $amr_conn->query($amr_sql);
    $amr_users = array();

    if($amr_result->num_rows > 0) 
    {
        // output data of each row
        while($row = $amr_result->fetch_assoc()) 
        {
            $phone = $row["Code"];
			
			//Check in Base CRM if record exists or not
            $arr_response = execute_curl("https://api.getbase.com/v2/contacts?phone=".$phone,"","GET");  
            if(!empty($arr_response->items))
            { 
                foreach($arr_response->items as $arrbase)
                {
                    $baseid = $arrbase->data->contact_id; 
                    if($baseid != '')  
                    {
						$remove_cron_text = $selected_daily = $selected_monthly = $selected_yearly = "";
						
                        //Total managed printers
                        $totalmanage_sql = "SELECT `Code`,COUNT(*) FROM `printers` WHERE `Code` = '".$row["Code"]."'";
                        $totalmanage_count = $amr_conn->query($totalmanage_sql);
                        $row_totalmanage = $totalmanage_count->fetch_assoc();

                        //Fetch from the historic last table
                        $amr_historic_last_sql = "SELECT `Idr`, `BW`, `Color`,`Black`, `Cyan`, `Magenta`, `Yellow`, `CodInstall` FROM historic_last WHERE `Code` = '".$row["Code"]."' AND `Serial` ='".$row["Serial"]."'";
                        $amr_historiclast_result = $amr_conn->query($amr_historic_last_sql);
                        $row_historiclast = $amr_historiclast_result->fetch_assoc();
						
						
						$phone   = $row['Code'];
						$iKey    = $row['iKey'];
						$model   = $row['Model'];
						$serial  = $row['Serial'];
						$alias   = $row['Alias'];
						$alarm   = $row['Alarm'];
						$alert   = $row['Alert'];
						$notes   = $row['Notes'];						
						$color   = $row_historiclast["Color"];
						$bw      = $row_historiclast["BW"];
						$cyan    = $row_historiclast["Cyan"];
						$magenta = $row_historiclast["Magenta"];
						$black   = $row_historiclast["Black"];
						$yellow  = $row_historiclast["Yellow"];
						$Idr     = $row_historiclast["Idr"];
						$total_managed = $row_totalmanage["total_managed"];
              
						//fetch record
						$sqlchk = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE amr_code='".$phone."'");  
						$count = count($sqlchk);
						
						if($count > 0)
						{
							$cron_type = $sqlchk[0]->cron_type;
							
							if($cron_type == 'daily')
							{
								$selected_daily = "selected='selected'";
							}
							
							if($cron_type == 'monthly')
							{
								$selected_monthly = "selected='selected'";
							}
							
							if($cron_type == 'yearly')
							{
								$selected_yearly = "selected='selected'";
							} 
						} 
	
						
						$user_name = $arrbase->data->first_name.' '.$arrbase->data->last_name;
						$user_model = $model ? $model : '-';
						$user_email = $arrbase->data->email;
						$user_phone = $phone;
	
						$action = '<select class="amruser form-control cron_'.$iKey.'" data-base-id="'.$baseid.'" data-amr-id="'.$phone.'">
						<option value="">None</option>
						<option value="daily" '.$selected_daily.'>Daily</option>
						<option value="monthly" '.$selected_monthly.'>Monthly</option>
						<option value="yearly" '.$selected_yearly.'>Yearly</option>            
						</select>';
	
						//get amr_data from the custom table from flatrate server
						$table_name = "amr_data";
						$amrdata = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE ikey='".$iKey."'");
						
						$today = date('Y-m-d');
						$s_date = date('Y-m-d', strtotime($amrdata[0]->s_date));
						$end_date = $e_date = date('Y-m-d', strtotime($amrdata[0]->e_date));
					
					
						$datetime1 = new DateTime($today);
						$datetime2 = new DateTime($end_date);
						if(isset($datetime2))
						{
							$interval = $datetime2->diff($datetime1);
							$month_remaining = (($interval->format('%y') * 12) + $interval->format('%m'));  
						}
						else
						{
							$month_remaining = 0;   
						}
					
						if(isset($bw) && isset($amrdata[0]->bblack))
						{
							$total_black_prints = ($bw - $amrdata[0]->bblack)*0.01;   
						}
						else
						{
							$total_black_prints = 0;
						}
					
						if(isset($color) && isset($amrdata[0]->bcolor))
						{
							$total_color_prints = ($color - $amrdata[0]->bcolor)*0.05;  
						}
						else
						{
							$total_color_prints = 0;
						}
	
						$total_overage_cost = $total_black_prints + $total_color_prints;
					
						$totalCurrentMeter = $bw + $color;
					
						$bcolor = $bblack = 0;
					
						if(isset($amrdata[0]->bcolor))
						{
						   $bcolor = $amrdata[0]->bcolor;
						}
						if(isset($amrdata[0]->bblack))
						{
						   $bblack = $amrdata[0]->bblack;
						}
						if(!isset($bw))
							$bw = 0;
						if(!isset($color))
							$color = 0;
					
						$PreviousOverage = 0;
	
						if(isset($amrdata[0]->total_prints))
						{
							$total_prints_type = $amrdata[0]->total_prints;
							//echo $total_prints_type; 
							if($total_prints_type == "Monthly")
							{
								$mdate = date("Y-m-d", strtotime("-1 months", strtotime($e_date)));
					
								$totalm_sql = "SELECT `BW`, `Color` FROM `historic_month` WHERE `iKey` = '".$iKey."' AND (`Date` BETWEEN '".$s_date."' AND '".$mdate."') ";
								$totalm_sql_result = $amr_conn->query($totalm_sql);
								$row_totalm = $totalm_sql_result->fetch_assoc();
					
								$pb = $row_totalm['BW'];
								$pc = $row_totalm['Color'];
								
								$PreviousOverage = $pc / $pb;  
					
							}
							else if($total_prints_type == "Quarterly")
							{
								$qdate = date("Y-m-d", strtotime("-6 months", strtotime($e_date)));
					
								$totalm_sql = "SELECT `BW`, `Color` FROM `historic_month` WHERE `iKey` = '".$iKey."' AND (`Date` BETWEEN '".$qdate."' AND '".$e_date."') ";
								$totalm_sql_result = $amr_conn->query($totalm_sql);
								$row_totalm = $totalm_sql_result->fetch_assoc();
					
								$pb = $row_totalm['BW'];
								$pc = $row_totalm['Color'];
								
								$PreviousOverage = $pc / $pb;
					
							}
							else
							{
								$ydate = date("Y-m-d", strtotime("-12 months", strtotime($e_date)));
								
								$totalm_sql = "SELECT `BW`, `Color` FROM `historic_month` WHERE `iKey` = '".$iKey."' AND (`Date` BETWEEN '".$ydate."' AND '".$e_date."') ";
								$totalm_sql_result = $amr_conn->query($totalm_sql);
								$row_totalm = $totalm_sql_result->fetch_assoc();
					
								$pb = $row_totalm['BW'];
								$pc = $row_totalm['Color'];
								
								$PreviousOverage = $pc / $pb;  
							}
						}
						
						array_push($amr_users, array('ikey'=> $iKey, 'baseid'=>$baseid, 'user_name'=>$user_name, 'code' =>$phone, 'active_error_msg'=> $alarm, 'toners_alerts' => $alert, 'notes'=> $notes, 'printer_model' => $model, 'serial_no' => $serial, 'current_meter_color' => $color, 'current_meter_black' => $bw, 'toner_level_k'=> $black, 'toner_level_c'=> $cyan,'toner_level_m'=> $magenta,'toner_level_y'=> $yellow, 'action' =>$action,'alias'=>$amrdata[0]->alias,'finance_type'=>$amrdata[0]->finance_type,'s_date'=> $s_date,'e_date'=>$e_date,'total_prints'=>$amrdata[0]->total_prints,'ccolor'=>$amrdata[0]->ccolor,'cblack'=> $amrdata[0]->cblack,'bblack'=>$bblack,'bcolor'=>$bcolor,'total_managed' => $total_managed, 'month_remaining'=> $month_remaining, 'total_black_prints'=>$total_black_prints, 'total_color_prints' => $total_color_prints, 'total_overage_cost' => $total_overage_cost, 'totalCurrentMeter' => $totalCurrentMeter,'PreviousOverage'=>$PreviousOverage));
					
                    }
                }
            }        
        }
    }

 
	if(!empty($amr_users))
	{
?>
	<div class="accordion" id="accordionExample">
<?php
	$i = 0;
	foreach($amr_users as $item)
	{
		$i++;
	?>
	  <div class="card">
		<div class="card-header" id="headingOne">
		  <h5 class="mb-0">        
			<button class="btn btn-link text-capitalize" type="button" data-toggle="collapse" data-target="#collapse<?php echo $i;?>" aria-expanded="true" aria-controls="collapseOne">
			  <?php echo $item['user_name']; ?>
			</button>
		  </h5>
		</div>
	
		<div id="collapse<?php echo $i;?>" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample">
		  <div class="card-body">
	
			 
			<div class="form-row">
				<div class="col-md-12 mb-3">
					<label class="text-uppercase text-info">Client Profile</label>
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip01"><strong>Company name:</strong> <?php echo $item['company_name']; ?></label>                       
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip01"><strong>Customer Code:</strong> <?php echo $item['code']; ?></label>                       
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip01"><strong>Active Error Messages:</strong> <?php echo $item['active_error_msg']; ?></label>
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip01"><strong>Toners Alerts:</strong> <?php echo $item['toners_alerts']; ?></label>
				</div> 
			</div>
			<div class="form-row">
				<div class="col-md-12 mb-3">
					<label class="text-uppercase text-info">Machine Profile</label>
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip03"><strong>Notes:</strong> <?php echo $item['notes']; ?></label>      
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip04"><strong>Printer Model:</strong> <?php echo $item['printer_model']; ?></label>
				</div>
				<div class="col-md-4 mb-3">
					<label for="validationTooltip05"><strong>Serial Number:</strong> <?php echo $item['serial_no']; ?></label>
				</div>
			</div>
			<div class="form-row">
				<div class="col-md-12 mb-3">
					<label class="text-uppercase text-info">Current Meter</label>
				</div>
				<div class="col-md-6 mb-3">
					<label for="validationTooltip03"><strong>Color:</strong> <?php echo $item['current_meter_color']; ?></label>  
				</div>
				<div class="col-md-6 mb-3">
					<label for="validationTooltip04"><strong>Black:</strong> <?php echo $item['current_meter_black']; ?></label>
				</div>            
			</div>
			<div class="form-row">
				<div class="col-md-12 mb-3">
					<label class="text-uppercase text-info">Toner Levels</label>
				</div>
				<div class="col-md-3 mb-3">
					<label for="validationTooltip03"><strong>K:</strong> <?php echo $item['toner_level_k']; ?></label>
				</div>
				<div class="col-md-3 mb-3">
					<label for="validationTooltip04"><strong>C:</strong> <?php echo $item['toner_level_c']; ?></label>
				</div> 
				<div class="col-md-3 mb-3">
					<label for="validationTooltip03"><strong>M:</strong> <?php echo $item['toner_level_m']; ?></label>
				</div>
				<div class="col-md-3 mb-3">
					<label for="validationTooltip04"><strong>Y:</strong> <?php echo $item['toner_level_y']; ?></label>
				</div>           
			</div>    
			
			
			<div class="form-row mb-5 border-bottom pb-3">           
				<div class="col-md-3 mb-3">
					<label for="validationTooltip03">Set Cron</label>
					<?php echo $item['action']; ?>
				</div>
				<div class="col-md-3 mt-4">
					<button class="btn btn-info" type="submit" onclick="return save_values('<?php echo $item['ikey']; ?>', '<?php echo $item['code']; ?>','<?php echo $item['baseid']; ?>');">Save Cron</button>
				</div>      
                <div class="col-md-6 mt-4">
                	<div class="message msg_cron_<?php echo $item['ikey']; ?>"></div>
                </div>      
			</div>    
			
	
			<form id="amr_edit_form" class="frm_<?php echo $item['ikey']; ?>" action="javascript:void(0);" method="POST">
			
				<div class="form-row">
					<div class="col-md-12">
						<input type="hidden" name="ikey" value="<?php echo $item['ikey']; ?>" data-code="<?php echo $item['ikey']; ?>"/>
						<input type="hidden" name="code" value="<?php echo $item['code']; ?>" data-code="<?php echo $item['code']; ?>"/>
						<input type="hidden" value="<?php echo $item['serial_no']; ?>" name="serial" data-serial="<?php echo $item['serial_no']; ?>"/>
						<input type="hidden" value="<?php echo $item['printer_model']; ?>" name="model" data-model="<?php echo $item['printer_model']; ?>"/>
					</div>
					<div class="col-md-12 mb-3">
						<label class="text-uppercase text-info">Client Profile</label>
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip03">Alias</label>
						<input type="text" class="form-control" id="alias" name="alias" value="<?php echo $item['alias']; ?>">            
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04">Finance Company (types)</label>
						<input type="text" class="form-control" id="finance_type" name="finance_type" value="<?php echo $item['finance_type']; ?>">
					</div> 
					<div class="col-md-3 mb-3">
						<label for="validationTooltip03">Contract Start Date</label>
						<input type="date"  class="form-control" id="s_date" name="s_date" value="<?php echo $item['s_date']; ?>">            
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04">Contract End Date</label>
						<input type="date"  class="form-control" id="e_date" name="e_date" value="<?php echo $item['e_date']; ?>">
					</div>           
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
						<label class="text-uppercase text-info">Billing</label>
					</div>
					<div class="col-md-4 mb-3">                   
						<label for="Select">Total included prints</label>
						<select id="total_prints" name="total_prints" class="form-control">                       
							<option value="">---Select---</option>
							<option <?php if($item['total_prints'] == "Monthly"){?>selected="selected"<?php } ?>value="Monthly">Monthly</option>
							<option <?php if($item['total_prints'] == "Quarterly"){?>selected="selected"<?php } ?>value="Quarterly">Quarterly</option>
							<option <?php if($item['total_prints'] == "Annually"){?>selected="selected"<?php } ?>value="Annually">Annually</option>
						</select>            
					</div>
					<div class="col-md-4 mb-3">
						<label for="validationTooltip04">Color</label>
						<input type="text" class="form-control" id="bcolor" name="bcolor" value="<?php echo $item['bcolor']; ?>">
					</div> 
					<div class="col-md-4 mb-3">
						<label for="validationTooltip03">Black</label>
						<input type="text"  class="form-control" id="bblack" name="bblack" value="<?php echo $item['bblack']; ?>">            
					</div>                          
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
						<label class="text-uppercase text-info">Cost per Print</label>
					</div>                
					<div class="col-md-4 mb-3">
						<label for="validationTooltip04">Color</label>
						<input type="text" class="form-control" id="ccolor" name="ccolor" value="<?php echo $item['ccolor']; ?>">
					</div> 
					<div class="col-md-4 mb-3">
						<label for="validationTooltip03">Black</label>
						<input type="text"  class="form-control" id="cblack" name="cblack" value="<?php echo $item['cblack']; ?>">            
					</div>                          
				</div>
				
                <div class="form-row">
                	<div class="col-md-4 mb-3">
                    	<input type="submit" class="btn btn-info mb-5 border-bottom pb-3" value="update" onclick="save_subval('<?php echo $item['ikey']; ?>');">
                    </div>
                    <div class="col-md-8 mb-3">
                    	<div class="message msg_<?php echo $item['ikey']; ?>"></div>
                    </div>
                </div>
	
				<div class="form-row">
					<div class="col-md-12 mb-3">
						<label class="text-uppercase text-info">Current Usage</label>
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip03"><strong>Months Remaining:</strong> <?php echo $item['month_remaining']; ?></label>
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04"><strong>Total Managed:</strong> <?php echo $item['total_managed']; ?></label>
					</div> 
					<div class="col-md-3 mb-3">
						<label for="validationTooltip03"><strong>Total Current Meter:</strong> <?php echo $item['totalCurrentMeter']; ?></label>
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04"><strong>Total Color:</strong> <?php echo $item['current_meter_color']; ?></label>
					</div> 
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04"><strong>Total Black:</strong> <?php echo $item['current_meter_black']; ?></label>
					</div>
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04"><strong>Overage Value($):</strong> <?php echo $item['total_overage_cost']; ?></label>
					</div> 
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04"><strong>Current Overage:</strong> <?php echo $item['bblack'].' Black '.$item['bcolor'].' Color'; ?></label>
					</div> 
					<div class="col-md-3 mb-3">
						<label for="validationTooltip04"><strong>Previous Monthly Overage:</strong> <?php echo $item['PreviousOverage']; ?></label>
					</div>          
				</div> 
				
			</form>
	
		  </div>
		</div>
	  </div>
	<?php }
?>
	</div>
<?php } else { ?>
<div class="message notice-error">No record found.</div>
<?php } ?>  

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript">
	var jq = jQuery.noConflict();

    jQuery(document).ready(function(){
        jq(".message").hide();               
    });
	function save_values(ikey,amrid,baseid)
	{
        //debugger;
		var chk_selected_cron_type = jq('.cron_'+ikey).val();
		var chk_selected_amr_ids = amrid;
		var chk_selected_base_ids = baseid;
		
		jq(".msg_cron_"+ikey).hide();
		jq(".msg_cron_"+ikey).html("");
		jq(".msg_cron_"+ikey).removeClass("notice-success");
		jq(".msg_cron_"+ikey).removeClass("notice-error");
		 
		// This does the ajax request
		jq.ajax({
			method: "POST",
			url: ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
			data: {
				'action': 'save_amr_cron',
				'chk_selected_cron_type' : chk_selected_cron_type,
				'chk_selected_amr_ids' : chk_selected_amr_ids,
				'chk_selected_base_ids' : chk_selected_base_ids
			},
			success:function(data) {
				// This outputs the result of the ajax request  
				jq(".msg_cron_"+ikey).show();
				
				if(typeof(data.success) != "undefined" && data.success !== null)  
				{ 
					var vhtml = '<p><strong>'+data.message+'</strong></p>'; 
					jq(".msg_cron_"+ikey).addClass("notice-success is-dismissible");
					jq(".msg_cron_"+ikey).html(vhtml);
					jq(".msg_cron_"+ikey).fadeOut(6000);
				}
				else
				{
					var vhtml = '<p><strong>Error Occured</strong></p>';
					jq(".msg_cron_"+ikey).addClass("notice-error is-dismissible");
					jq(".msg_cron_"+ikey).html(vhtml);
				}				
			},
			error: function(errorThrown){ 
			
				var vhtml = '<p><strong>'+errorThrown+'</strong></p>';
					 
				jq(".msg_cron_"+ikey).show();
				jq(".msg_cron_"+ikey).addClass("notice-error is-dismissible");
				jq(".msg_cron_"+ikey).html(vhtml);
			}
		});  
	}
	
	function save_subval(ikey)
    { 
		var data = jq('.frm_'+ikey).serializeArray(); 

		// This does the ajax request
		jq.ajax({
			method: "POST",
			url: ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
			data: {
				'action': 'save_amr_data',            
				'data':data
			},
			success:function(data) {
				// This outputs the result of the ajax request  
				jq(".msg_"+ikey).show();
				
				if(typeof(data.success) != "undefined" && data.success !== null)  
				{ 
					var vhtml = '<p><strong>'+data.message+'</strong></p>'; 
					jq(".msg_"+ikey).addClass("notice-success");
					jq(".msg_"+ikey).html(vhtml);
					jq(".msg_"+ikey).fadeOut(6000);
				}
				else
				{
					var vhtml = '<p><strong>Error Occured</strong></p>';
					
					jq(".msg_"+ikey).addClass("notice-error");
					jq(".msg_"+ikey).html(vhtml);
				}
			},
			error: function(errorThrown){ 
			
				var vhtml = '<p><strong>'+errorThrown+'</strong></p>';
					
				jq(".msg_"+ikey).show();
				jq(".msg_"+ikey).addClass("notice-error");
				jq(".msg_"+ikey).html(vhtml);
			}
		});  
     }
</script>