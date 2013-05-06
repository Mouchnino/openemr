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

 $form_from_date  = fixDate($_POST['form_from_date'], date('Y-m-01'));
  $form_to_date    = fixDate($_POST['form_to_date'], date('Y-m-d'));
////start html header
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
						"theprogram !='Closed'AND theprogram !='CBHA' order by lname, fname";
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
      $irow = 0;  
  
  
$query = "SELECT p.pid,p.theprogram, CONCAT(p.lname, ', ', p.fname) AS client,".
" (SELECT SUM(b.units) from billing AS b WHERE  b.code_type='HCPCS' and b.date >= '$form_from_date 00:00:00' AND b.date <= '$form_to_date 23:59:59' AND b.pid=p.pid) AS unittotal".
" FROM patient_data AS p ".

"WHERE p.theprogram != 'Closed' AND p.theprogram != 'CBHA' ";

if ($form_patient) {
          $query .= " AND p.pid = '$form_patient'";
        }

$query .= " GROUP BY p.pid ORDER BY p.lname"; 
	 
$result = mysql_query($query) or die(mysql_error());

// Print out result
while($row = mysql_fetch_array($result)){
	echo "Total for ". $row['client']. "   =   ". $row['unittotal']. " units ";
	echo "<br />";
}}
?>
</table>
</div>
<?php } else { ?>
<div class='text'>
 	<?php echo xl('Input search criteria above, and click Submit to view results.', 'e' ); ?>
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