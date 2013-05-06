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

 $form_from_date  = '2012-01-01';//fixDate($_POST['form_from_date'], date('Y-m-01'));
  $form_to_date    = '2012-09-18';//fixDate($_POST['form_to_date'], date('Y-m-d'));

  
  
  
  
  
$query = "SELECT p.pid,p.theprogram, CONCAT(p.lname, ', ', p.fname) AS client, (SELECT SUM(b.units) from billing AS b WHERE b.code_type='HCPCS'and b.date >= '$form_from_date 00:00:00' AND b.date <= '$form_to_date 23:59:59' AND b.pid=p.pid) AS ".
"unittotal  FROM patient_data AS p ".
"WHERE p.theprogram != 'Closed' AND p.theprogram != 'CBHA' ".
" GROUP BY p.pid ORDER BY p.lname"; 
	 
$result = mysql_query($query) or die(mysql_error());

// Print out result
while($row = mysql_fetch_array($result)){
	echo "Total for ". $row['client']. "   =   ". $row['unittotal']. " units ";
	echo "<br />";
}
?>
<?php
// $query = "SELECT f.id, f.date, f.pid, f.encounter, f.last_level_billed, " .
 //     "f.last_level_closed, f.last_stmt_date, f.stmt_count, f.invoice_refno, " .
 //     "p.fname, p.mname, p.lname, p.street, p.city, p.state, " .
 //     "p.postal_code, p.thecounselor, p.ss, p.genericname2, p.genericval2, " .
 //     "p.pid, p.DOB, CONCAT(u.lname, ', ', u.fname) AS referrer, " .
 //     "( SELECT SUM(b.fee) FROM billing AS b WHERE " .
 //     "b.pid = f.pid AND b.encounter = f.encounter AND " .
 //     "b.activity = 1 AND b.code_type != 'COPAY' ) AS charges, " .
 //     "( SELECT SUM(b.fee) FROM billing AS b WHERE " .
 //     "b.pid = f.pid AND b.encounter = f.encounter AND " .
 //     "b.activity = 1 AND b.code_type = 'COPAY' ) AS copays, " .
 //     "( SELECT SUM(s.fee) FROM drug_sales AS s WHERE " .
 //     "s.pid = f.pid AND s.encounter = f.encounter ) AS sales, " .
  //    "( SELECT SUM(a.pay_amount) FROM ar_activity AS a WHERE " .
  //    "a.pid = f.pid AND a.encounter = f.encounter ) AS payments, " .
  //    "( SELECT SUM(a.adj_amount) FROM ar_activity AS a WHERE " .
 //     "a.pid = f.pid AND a.encounter = f.encounter ) AS adjustments " .
  //    "FROM form_encounter AS f " .
  //    "JOIN patient_data AS p ON p.pid = f.pid " .
  //    "LEFT OUTER JOIN users AS u ON u.id = p.providerID " .
  //    "WHERE $where " .
 //     "ORDER BY f.pid, f.encounter";
      
 //     select s.name, a.date, a.total
 // from scene s
  //  join (select date, sid, sum(amount) total
  //          from arrangement
  //          group by date, sid) a
  //    on s.sid = a.sid;
  ?>