<?php


  require_once("../globals.php");
  require_once("$srcdir/patient.inc");
  require_once("$srcdir/sql-ledger.inc");
  require_once("$srcdir/acl.inc");
  require_once("$srcdir/formatting.inc.php");
  require_once "$srcdir/options.inc.php";
  require_once "$srcdir/formdata.inc.php";
require_once("../../library/sl_eob.inc.php");
require_once("../../library/invoice_summary.inc.php");



  $units=0;


  $form_use_edate  = $_POST['form_use_edate'];
  $form_from_date  = fixDate($_POST['form_from_date'], date('Y-m-01'));
  $form_to_date    = fixDate($_POST['form_to_date'], date('Y-m-d'));
?>
<html>
<head>
<?php if (function_exists('html_header_show')) html_header_show(); ?>
<style type="text/css">
/* specifically include & exclude from printing */
@media print {
    #report_parameters {
        visibility: hidden;
        display: none;
    }
    #report_parameters_daterange {
        visibility: visible;
        display: inline;
    }
    #report_results {
       margin-top: 30px;
    }
}

/* specifically exclude some from the screen */
@media screen {      N
    #report_parameters_daterange {
        visibility: hidden;
        display: none;
    }
}
</style>
<title><?xl('Unit Usage Report','e')?></title>
</head>

<body class="body_top">

<span class='title'><?php xl('Report','e'); ?> - <?php xl('Unit Usage Report','e'); ?></span>

<form method='post' action='units_report.php' id='theform'>

<div id="report_parameters">

<input type='hidden' name='form_refresh' id='form_refresh' value=''/>

<table>
 <tr>
  <td width='660px'>
	<div style='float:left'>

	<table class='text'>
		<tr>

<td class='label'>
			   <?php xl('Client','e'); ?>:
			</td>
			<td>
				<?php
				
					// Build a drop-down list of patients
					//
					$query = "select pid, lname, fname from patient_data where " .
						"theprogram !='Closed' order by lname, fname";
					$res = sqlStatement($query);
					echo "   &nbsp;<select name='form_patient'>\n";
					echo "    <option value=''>-- " . xl('All Open Cases', 'e') . " --\n";
					while ($row = sqlFetchArray($res)) {
						$provid = $row['pid'];
						echo "    <option value='$provid'";
						if ($provid == $_POST['form_patient']) echo " selected";
						echo ">" . $row['lname'] . ", " . $row['fname'] . "\n";
					}
					echo "   </select>\n";
				
			?>
			</td>		
		
		
		
		
		
				
			<td>
			   <select name='form_use_edate'>
				<option value='0'><?php xl('Payment Date','e'); ?></option>
				<option value='1'<?php if ($form_use_edate) echo ' selected' ?>><?php xl('Invoice Date','e'); ?></option>
			   </select>
			</td>
		</tr>
		<tr>
			<td class='label'>
			   <?php xl('From','e'); ?>:
			</td>
			<td>
			   <input type='text' name='form_from_date' id="form_from_date" size='10' value='<?php  echo $form_from_date; ?>'
				title='Date of appointments mm/dd/yyyy' >
			   <img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
				id='img_from_date' border='0' alt='[?]' style='cursor:pointer'
				title='<?php xl('Choose starting date','e'); ?>'>
			</td>
			<td class='label'>
			   <?php xl('To','e'); ?>:
			</td>
			<td>
			   <input type='text' name='form_to_date' id="form_to_date" size='10' value='<?php  echo $form_to_date; ?>'
				title='Optional end date mm/dd/yyyy' >
			   <img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
				id='img_to_date' border='0' alt='[?]' style='cursor:pointer'
				title='<?php xl('Choose end date','e'); ?>'>
			</td>
			<td>&nbsp;</td>
		</tr>
		
	</table>

	</div>

  </td>
  <td align='left' valign='middle' height="100%">
	<table style='border-left:1px solid; width:100%; height:100%' >
		<tr>
			<td>
				<div style='margin-left:15px'>
					<a href='#' class='css_button' onclick='$("#form_refresh").attr("value","true"); $("#theform").submit();'>
					<span>
						<?php xl('Submit','e'); ?>
					</span>
					</a>

					<?php if ($_POST['form_refresh']) { ?>
					<a href='#' class='css_button' onclick='window.print()'>
						<span>
							<?php xl('Print','e'); ?>
						</span>
					</a>
					<?php } ?>
				</div>
			</td>
		</tr>
	</table>
  </td>
 </tr>
</table>
</div>

<?php
 if ($_POST['form_refresh']) {
?>
<div id="report_results">
<table border='0' cellpadding='1' cellspacing='2' width='98%'>
 <thead>
  <th>
   <?php xl('Client','e') ?>
  </th>
  <th>
   <?php xl('Insurance','e') ?>
  </th>
 <th>
   <?php xl('Contract Date','e') ?>
  </th>
  <th>
   <?php xl('Unit Limit','e') ?>
  </th>
  <th align="right">
   <?php xl('Units Used','e') ?>
  </th>
 </thead>
<?php
  if ($_POST['form_refresh']) {
    $form_patient = $_POST['form_patient'];
    $arows = array();
      $ids_to_skip = array();
      $irow = 0;

      
        $query = "SELECT b.units, " .
          "fe.date, fe.id AS trans_id, fe.pid AS pid " .
          "FROM billing AS b " .
          "JOIN form_encounter AS fe ON fe.pid = b.pid AND fe.encounter = b.encounter " .
          "WHERE b.code_type = 'COPAY' AND b.activity = 1 AND " .
          "fe.date >= '$form_from_date 00:00:00' AND fe.date <= '$form_to_date 23:59:59'";
        
        // If a patient was specified.
        if ($form_patient) {
          $query .= " AND fe.pid = '$form_patient'";
        }
        /************************************************************/
        //
        
        $res = sqlStatement($query);
        while ($row = sqlFetchArray($res)) {
          $trans_id = $row['trans_id'];
          $thedate = substr($row['date'], 0, 10);
          $patient_id = $row['pid'];
          $encounter_id = $row['encounter'];
          //
          if (!empty($ids_to_skip[$trans_id])) continue;
       
          $key = sprintf("%08u%s%08u%08u%06u", $row['pid'], $thedate,
            $patient_id, $encounter_id, ++$irow);
          $arows[$key] = array();
          $arows[$key]['units'] = $row['units'];
          $arows[$key]['pid'] = $row['pid'];
          $arows[$key]['project_id'] = 0;
        } 
        

    
      $query = "SELECT a.pid, a.encounter, a.post_time, " .
        "fe.date, fe.id AS trans_id, fe.invoice_refno, s.deposit_date, s.payer_id, " .
        " b.units " .
        "FROM ar_activity AS a " .
        "JOIN form_encounter AS fe ON fe.pid = a.pid AND fe.encounter = a.encounter " .
        "LEFT OUTER JOIN ar_session AS s ON s.session_id = a.session_id " .
        "LEFT OUTER JOIN billing AS b ON b.pid = a.pid AND b.encounter = a.encounter AND " .
        "b.code = a.code AND b.modifier = a.modifier AND b.activity = 1  " .
        "WHERE a.post_time >= '$form_from_date 00:00:00' AND a.post_time <= '$form_to_date 23:59:59' " .
        "OR fe.date >= '$form_from_date 00:00:00' AND fe.date <= '$form_to_date 23:59:59' " .
        "OR s.deposit_date >= '$form_from_date' AND s.deposit_date <= '$form_to_date' ";
      // If a patient was specified.
      if ($form_patient) {
        $query .= " AND ( b.pid = '$form_doctor' OR " .
          "( ( b.pid IS NULL OR b.pid = 0 ) AND " .
          "fe.pid = '$form_doctor' ) )";
      }
      /**************************************************************/
      //
      $res = sqlStatement($query);
      while ($row = sqlFetchArray($res)) {
        $trans_id = $row['trans_id'];
        $patient_id = $row['pid'];
        $encounter_id = $row['encounter'];
        //
        if (!empty($ids_to_skip[$trans_id])) continue;
        //
        if ($form_use_edate) {
          $thedate = substr($row['date'], 0, 10);
        } else {
          if (!empty($row['deposit_date']))
            $thedate = $row['deposit_date'];
          else
            $thedate = substr($row['post_time'], 0, 10);
        }
        if (strcmp($thedate, $form_from_date) < 0 || strcmp($thedate, $form_to_date) > 0) continue;
     
        //
        $pid = empty($row['encounter_id']) ? $row['pid'] : $row['encounter_id'];
        $key = sprintf("%08u%s%08u%08u%06u", $pid, $thedate,
          $patient_id, $encounter_id, ++$irow);
        $arows[$key] = array();
        $arows[$key]['transdate'] = $thedate;
        $arows[$key]['units'] = $row['units'];
        $arows[$key]['pid'] = $pid;        
        $arows[$key]['project_id'] = empty($row['payer_id']) ? 0 : $row['payer_id'];       
        $arows[$key]['irnumber'] = $row['invoice_refno'];
      } // end while
 

   
    ksort($arows);
    $pid = 0;

    foreach ($arows as $row) {

      // Get insurance company name
      $insconame = '';
      if ($row['project_id']) {
        $tmp = sqlQuery("SELECT name FROM insurance_companies WHERE " .
          "id = '" . $row['project_id'] . "'");
        $insconame = $tmp['name'];
      }

     
      $units = $row['units'];       
      if ($pid != $row['pid']) {
        if ($pid) {
	        
	        
          // Print patient totals.
?>

 <tr bgcolor="#ddddff">
  <td class="detail" >
   <? echo xl('Totals for ') . $pidname ?>
  </td>
  <td align="right">
   <?php echo $pidtotal1 ?>
  </td>

<?php  ?>
 </tr>
<?php
        }
        $pidtotal1 = 0;

        $pid = $row['pid'];
        $tmp = sqlQuery("SELECT lname, fname FROM patient_data WHERE pid = '$pid'");
        $pidname = empty($tmp) ? 'Unknown' : $tmp['fname'] . ' ' . $tmp['lname'];

        $pidnameleft = $pidname;
      }
     
?>

 <tr>
  <td class="detail">
   <?php echo $pidnameleft; $pidnameleft = "&nbsp;" ?>
  </td>
  
 
<?php echo "  </td>\n";?>

  <td class="detail">
   <?php echo $insconame ?>
  </td>
 
  <td class="detail" align="right">
   <?php echo $units ?>
  </td>

 </tr>
<?php    
      $pidtotal1 += $units;
       }///end for each row
?>

 <tr bgcolor="#ddddff">
  <td class="detail">
   <?echo xl('Totals for ') . $pidname ?>
  </td>
  <td align="right">
   <?php echo $pidtotal1 ?>
  </td>


 </tr>


<?php
  }

?>

</table>
</div>
<?php } else { ?>
<div class='text'>
 	<?php echo xl('Please input search stuff criteria above, and click Submit to view results.', 'e' ); ?>
</div>
<?php } ?>

</form>
</body>

<!-- stuff for the popup calendar -->
<link rel='stylesheet' href='<?php echo $css_header ?>' type='text/css'>
<style type="text/css">@import url(../../library/dynarch_calendar.css);</style>
<script type="text/javascript" src="../../library/dynarch_calendar.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
<script type="text/javascript" src="../../library/dynarch_calendar_setup.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>

<script language="Javascript">
 Calendar.setup({inputField:"form_from_date", ifFormat:"%Y-%m-%d", button:"img_from_date"});
 Calendar.setup({inputField:"form_to_date", ifFormat:"%Y-%m-%d", button:"img_to_date"});
</script>

</html>
