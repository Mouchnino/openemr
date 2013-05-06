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

  function bucks($amount) {
    if ($amount) echo oeFormatMoney($amount);
  }


  if (! acl_check('acct', 'rep')) die(xl("Unauthorized access."));

  $INTEGRATED_AR = $GLOBALS['oer_config']['ws_accounting']['enabled'] === 2;

  if (!$INTEGRATED_AR) {
    SLConnect();
    $chart_id_cash = SLQueryValue("select id from chart where accno = '$sl_cash_acc'");
    if ($sl_err) die($sl_err);
  }
  $units=0;
  $c_pay=0;
  $form_use_edate  = $_POST['form_use_edate'];
  $form_cptcode    = trim($_POST['form_cptcode']);
  $form_modifier   = trim($_POST['form_modifier']);
  $form_icdcode    = trim($_POST['form_icdcode']);
  $form_procedures = empty($_POST['form_procedures']) ? 1 : 1;
  $form_from_date  = fixDate($_POST['form_from_date'], date('Y-m-01'));
  $form_to_date    = fixDate($_POST['form_to_date'], date('Y-m-d'));
  $form_facility   = $_POST['form_facility'];
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
<title><?xl('CBHA Supervisor Pay Report','e')?></title>
</head>

<body class="body_top">

<span class='title'><?php xl('Report','e'); ?> - <?php xl('CBHA Supervisor Pay Report','e'); ?></span>

<form method='post' action='CBHA_Super_pay_report.php' id='theform'>

<div id="report_parameters">

<input type='hidden' name='form_refresh' id='form_refresh' value=''/>

<table>
 <tr>
  <td width='660px'>
	<div style='float:left'>

	<table class='text'>
		<tr>
			<td class='label'>
				<?php xl('Facility','e'); ?>:
			</td>
			<td>
			<?php dropdown_facility(strip_escape_custom($form_facility), 'form_facility'); ?>
			</td>
			<td>
				<?php
				
					echo "<input type='hidden' name='form_doctor' value=''>";
				
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
				title='<?php xl('Click here to choose a date','e'); ?>'>
			</td>
			<td class='label'>
			   <?php xl('To','e'); ?>:
			</td>
			<td>
			   <input type='text' name='form_to_date' id="form_to_date" size='10' value='<?php  echo $form_to_date; ?>'
				title='Optional end date mm/dd/yyyy' >
			   <img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
				id='img_to_date' border='0' alt='[?]' style='cursor:pointer'
				title='<?php xl('Click here to choose a date','e'); ?>'>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<?php if (!$GLOBALS['simplified_demographics']) echo '&nbsp;' . xl('HCPCS', 'e') . ':'; ?>
			</td>
			<td>
			   <input type='text' name='form_cptcode' size='5' value='H0031'
				title='<?php xl('CBHA code','e'); ?>'
				<?php if ($GLOBALS['simplified_demographics']) echo "style='display:none'"; ?>>
			</td>
			<td>
			   <input type='text' name='form_modifier' size='2' value='HA'
				title='<?php xl('CBHA MODIFIER','e'); ?>'
				<?php if ($GLOBALS['simplified_demographics']) echo "style='display:none'"; ?>>
			</td>
			

			
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
   <?php xl('Counselor','e') ?>
  </th>
  <th>
   <?php xl('Date','e') ?>
  </th>
  <th>
   <?php xl('Invoice','e') ?>
  </th>
  <th>
   <?php xl('Insurance','e') ?>
  </th>
  <th>
   <?php xl('Procedure','e') ?>
  </th>
  <th align="right">
   <?php xl('PAY','e') ?>
  </th>
  <th align="right">
   <?php xl('Units','e') ?>
  </th>
 </thead>
<?php
  if ($_POST['form_refresh']) {
    $form_doctor = $_POST['form_doctor'];
    $arows = array();

    if ($INTEGRATED_AR) {
      $ids_to_skip = array();
      $irow = 0;

     

    
      $query = "SELECT a.pid, a.encounter, a.post_time, a.code, a.modifier, " .
        "fe.date, fe.id AS trans_id, fe.counselor_id AS docid, fe.invoice_refno, s.deposit_date, s.payer_id, " .
        "b.units " .
        "FROM ar_activity AS a " .
        "JOIN form_encounter AS fe ON fe.pid = a.pid AND fe.encounter = a.encounter " .
        "LEFT OUTER JOIN ar_session AS s ON s.session_id = a.session_id " .
        "LEFT OUTER JOIN billing AS b ON b.pid = a.pid AND b.encounter = a.encounter AND " .
        "b.code = a.code  AND b.activity = 1 AND " .
        "b.code_type != 'COPAY' AND b.code_type != 'TAX' " .
        "WHERE a.pay_amount != 0 AND ( " .
        "a.post_time >= '$form_from_date 00:00:00' AND a.post_time <= '$form_to_date 23:59:59' " .
        "OR fe.date >= '$form_from_date 00:00:00' AND fe.date <= '$form_to_date 23:59:59' " .
        "OR s.deposit_date >= '$form_from_date' AND s.deposit_date <= '$form_to_date' )";
      // If a procedure code was specified.
      if ($form_cptcode) $query .= " AND a.code = '$form_cptcode'";
      if ($form_modifier) $query .= " AND a.modifier = '$form_modifier'";
      // If a facility was specified.
      if ($form_facility) $query .= " AND fe.facility_id = '$form_facility'";
      // If a doctor was specified.
      if ($form_doctor) {
        $query .= " AND ( b.counselor_id = '$form_doctor' " .
          " AND " .
          "fe.counselor_id = '$form_doctor' ) ";
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
        // If a diagnosis code was given then skip any invoices without
        // that diagnosis.
        if ($form_icdcode) {
          $tmp = sqlQuery("SELECT count(*) AS count FROM billing WHERE " .
            "pid = '$patient_id' AND encounter = '$encounter_id' AND " .
            "code_type = 'ICD9' AND code LIKE '$form_icdcode' AND " .
            "activity = 1");
          if (empty($tmp['count'])) {
            $ids_to_skip[$trans_id] = 1;
            continue;
          }
        }
        //
        $docid = empty($row['encounter_id']) ? $row['docid'] : $row['encounter_id'];
        $key = sprintf("%08u%s%08u%08u%06u", $docid, $thedate,
          $patient_id, $encounter_id, ++$irow);
        $arows[$key] = array();
        $arows[$key]['transdate'] = $thedate;
        $arows[$key]['units'] = $row['units'];
        $arows[$key]['docid'] = $docid;       
        $arows[$key]['project_id'] = empty($row['payer_id']) ? 0 : $row['payer_id'];
        $arows[$key]['memo'] = $row['code'].$row['modifier']."  ";
        $arows[$key]['invnumber'] = "$patient_id.$encounter_id";
        $arows[$key]['irnumber'] = $row['invoice_refno'];
      } // end while
    } // end $INTEGRATED_AR

    else {
      if ($form_cptcode) {
        $query = "SELECT acc_trans.amount, acc_trans.transdate, " .
          "acc_trans.memo, acc_trans.project_id, acc_trans.trans_id, " .
          "ar.invnumber, ar.employee_id, invoice.sellprice, invoice.qty " .
          "FROM acc_trans, ar, invoice WHERE " .
          "acc_trans.chart_id = $chart_id_cash AND " .
          "acc_trans.memo ILIKE '$form_cptcode' AND " .
          "ar.id = acc_trans.trans_id AND " .
          "invoice.trans_id = acc_trans.trans_id AND " .
          "invoice.serialnumber ILIKE acc_trans.memo AND " .
          "invoice.sellprice >= 0.00 AND " .
          "( invoice.description ILIKE 'CPT%' OR invoice.description ILIKE 'Proc%' ) AND ";
      }
      else {
        $query = "select acc_trans.amount, acc_trans.transdate, " .
          "acc_trans.memo, acc_trans.project_id,acc_trans.trans_id, " .
          "ar.invnumber, ar.employee_id from acc_trans, ar where " .
          "acc_trans.chart_id = $chart_id_cash and " .
          "ar.id = acc_trans.trans_id and ";
      }

      if ($form_use_edate) {
        $query .= "ar.transdate >= '$form_from_date' and " .
        "ar.transdate <= '$form_to_date'";
      } else {
        $query .= "acc_trans.transdate >= '$form_from_date' and " .
        "acc_trans.transdate <= '$form_to_date'";
      }

      $query .= " order by ar.invnumber";

       echo "<!-- $query -->\n"; // debugging

      $t_res = SLQuery($query);
      if ($sl_err) die($sl_err);

      $docname     = "";
      $docnameleft = "";
      $main_docid  = 0;
      $doctotal1   = 0;
      $grandtotal1 = 0;
     
      $last_trans_id = 0;
      $skipping      = false;

      for ($irow = 0; $irow < SLRowCount($t_res); ++$irow) {
        $row = SLGetRow($t_res, $irow);

        list($patient_id, $encounter_id) = explode(".", $row['invnumber']);

        // Under some conditions we may skip invoices that matched the SQL query.
        //
        if ($row['trans_id'] == $last_trans_id) {
          if ($skipping) continue;
          // same invoice and not skipping, do nothing.
        } else { // new invoice
          $skipping = false;
          // If a diagnosis code was given then skip any invoices without
          // that diagnosis.
          if ($form_icdcode) {
            if (!SLQueryValue("SELECT count(*) FROM invoice WHERE " .
              "invoice.trans_id = '" . $row['trans_id'] . "' AND " .
              "( invoice.description ILIKE 'ICD9:$form_icdcode %' OR " .
              "invoice.serialnumber ILIKE 'ICD9:$form_icdcode' )"))
            {
              $skipping = true;
              continue;
            }
          }
          // If a facility was specified then skip invoices whose encounters
          // do not indicate that facility.
          if ($form_facility) {
            $tmp = sqlQuery("SELECT count(*) AS count FROM form_encounter WHERE " .
              "pid = '$patient_id' AND encounter = '$encounter_id' AND " .
              "facility_id = '$form_facility'");
            if (empty($tmp['count'])) {
              $skipping = true;
              continue;
            }
          }
          // Find out who the practitioner is.
          
          $tmp = sqlQuery("SELECT counselor_id FROM form_encounter WHERE " .
            "pid = '$patient_id' AND encounter = '$encounter_id' " .
            "ORDER BY id DESC LIMIT 1");
          $main_docid = $tmp['counselor_id'] + 0;

          // If a practitioner was specified then skip other practitioners.
          if ($form_doctor) {
            if ($form_doctor != $main_docid) {
              $skipping = true;
              continue;
            }
          }
        } // end new invoice

        $row['docid'] = $main_docid;
        $key = sprintf("%08u%s%08u%08u%06u", $main_docid, $row['transdate'],
          $patient_id, $encounter_id, $irow);
        $arows[$key] = $row;
      }

    } // end not $INTEGRATED_AR

    ksort($arows);
    $docid = 0;

    foreach ($arows as $row) {

      // Get insurance company name
      $insconame = '';
      if ($row['project_id']) {
        $tmp = sqlQuery("SELECT name FROM insurance_companies WHERE " .
          "id = '" . $row['project_id'] . "'");
        $insconame = $tmp['name'];
      }
$amount1 = 0;
$amount2 = 0;
        $amount2 -= $row['amount'];
        $amount1 = $row['units']*4;
  if ($amount2<0){$amount1*= -1;}       
    
      $units = $row['units'];       
      
      if ($docid != $row['docid']) {
        if ($docid) {
	        
	        
          // Print doc totals.
?>

 <tr bgcolor="#ddddff">
  <td class="detail" colspan="<?php echo ($form_cptcode ? 4 : 4) + ($form_procedures ? 2 : 2); ?>">
   <? echo xl('Totals for ') . $docname ?>
  </td>
  <td align="right">
   <?php bucks($doctotal1) ?>
  </td>

 </tr>
<?php
        }
        $doctotal1 = 0;
        

        $docid = $row['docid'];
        $tmp = sqlQuery("SELECT lname, fname FROM users WHERE id = '$docid'");
        $docname = empty($tmp) ? 'Unknown' : $tmp['fname'] . ' ' . $tmp['lname'];

        $docnameleft = $docname;
      }

      
?>

 <tr>
  <td class="detail">
   <?php echo $docnameleft; $docnameleft = "&nbsp;" ?>
  </td>
  <td class="detail">
   <?php echo oeFormatShortDate($row['transdate']) ?>
  </td>
 
  <td class="detail">
   <a href="../billing/sl_eob_invoice.php?id=<?php echo empty($row['irnumber']) ? $row['invnumber'] : $row['irnumber']; ?>"
    target="_blank"><?php echo empty($row['irnumber']) ? $row['invnumber'] : $row['irnumber']; ?></a>
  </td>
  
  


  <td class="detail">
   <?php echo $insconame ?>
  </td>

  <td class="detail">
   <?php echo $row['memo'] ?>
  </td>
  <td class="detail" align="right">
   <?php bucks($amount1) ?>
  </td>

  
  <td class="detail" align="right">
   <?php echo $units ?>
  </td>

 </tr>
<?php
     
      $doctotal1   += $amount1;
      
      $grandtotal1 += $amount1;
      
    }
?>

 <tr bgcolor="#ddddff">
  <td class="detail" colspan="<?php echo ($form_cptcode ? 4 : 4) + ($form_procedures ? 2 : 2); ?>">
   <?echo xl('Totals for ') . $docname ?>
  </td>
  <td align="right">
   <?php bucks($doctotal1) ?>
  </td>

  

 </tr>

 <tr bgcolor="#ffdddd">
  <td class="detail" colspan="<?php echo ($form_cptcode ? 3 : 3) + ($form_procedures ? 3 : 3); ?>">
   <?php xl('CBHA Supervisor Pay Total This Period:','e') ?>
  </td>
  <td align="right">
   <?php bucks($grandtotal1) ?>
  </td>

  

 </tr>

<?php
  }
  if (!$INTEGRATED_AR) SLClose();
?>

</table>
</div>
<?php } else { ?>
<div class='text'>
 	<?php echo xl('Please input search criteria above, and click Submit to view results.', 'e' ); ?>
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
