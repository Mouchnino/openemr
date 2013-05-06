<?php
 // Copyright (C) 2011 OEMR inc 501(c)3
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

 // This report lists clients of a particular age range, using
 //  assigned treatment program sorting.


 require_once("../globals.php");
 require_once("$srcdir/patient.inc");
 require_once("$srcdir/formatting.inc.php");


?>
<html>
<head>
<?php html_header_show();?>
<title><?php xl('Patient List by Age','e'); ?></title>
<script type="text/javascript" src="../../library/overlib_mini.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>

<link rel='stylesheet' href='<?php echo $css_header ?>' type='text/css'>
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
		margin-bottom: 10px;
    }
    #report_results table {
       margin-top: 0px;
    }
}

/* specifically exclude some from the screen */
@media screen {
	#report_parameters_daterange {
		visibility: hidden;
		display: none;
	}
	#report_results {
		width: 100%;
	}
}

</style>

</head>

<body class="body_top">



<span class='title'><?php xl('Report','e'); ?> - <?php xl('Client List by Age x','e'); ?></span>



<form name='theform' id='theform' method='post' action='patient_age.php'>

<div id="report_parameters">

<input type='hidden' name='form_refresh' id='form_refresh' value=''/>

<table >
 <tr>
  <td width='600px' >
	<div style='float:left'>

	<table class='text' >
		<td><tr>
			<td class='label'><?php echo htmlspecialchars(xl('Age Range'),ENT_NOQUOTES); ?>:</td>
						<td><? echo htmlspecialchars(xl('From'),ENT_NOQUOTES); ?> 
							<input name='age_from' class="numeric_only" type='text' id="age_from" value="<?php echo htmlspecialchars($age_from,ENT_QUOTES); ?>" size='3' maxlength='3' />
							 <? echo htmlspecialchars(xl('To'),ENT_NOQUOTES); ?> 
							<input name='age_to' class="numeric_only" type='text' id="age_to" value="<?php echo htmlspecialchars($age_to,ENT_QUOTES); ?>" size='3' maxlength='3' /></td></td>
			
<tr>
<td>
<?php

echo "<p>Program1?<br>";
echo "<select name='prog1'>";
echo "<option value=''>All</option>";
echo "<option value='ARS_Counseling'>ARS Counseling</option>";
echo "<option value='FES_Counseling'>FES Counseling</option>";
echo "<option value='CBHA'>CBHA</option>";
echo "<option value='Psychiatric'>Psychiatric</option>";
echo "<option value='Transitional_Youth_Program'>Transitional Youth Program</option>";
echo "<option value='Specialized_Therapy'>Specialized Therapy</option>";
echo "<option value='Specialized_Assessment_Services'>Specialized Assessment/Services</option>";
echo "<option value='Closed'>Closed</option>";
echo "</select>";
echo "</p>";


$prog1  = $_POST['prog1'];
echo $prog1
?>
</td>

<td>

<?php

echo "<p>Program2?<br>";
echo "<select name='prog2'>";
echo "<option value=''>All</option>";
echo "<option value='ARS_Counseling'>ARS Counseling</option>";
echo "<option value='FES_Counseling'>FES Counseling</option>";
echo "<option value='CBHA'>CBHA</option>";
echo "<option value='Psychiatric'>Psychiatric</option>";
echo "<option value='Transitional_Youth_Program'>Transitional Youth Program</option>";
echo "<option value='Specialized_Therapy'>Specialized Therapy</option>";
echo "<option value='Specialized_Assessment_Services'>Specialized Assessment/Services</option>";
echo "<option value='Closed'>Closed</option>";
echo "</select>";
echo "</p>";


$prog2  = $_POST['prog2'];
echo $prog2
?>
</td>	

<td>

<?php

echo "<p>Program3?<br>";
echo "<select name='prog3'>";
echo "<option value=''>All</option>";
echo "<option value='ARS_Counseling'>ARS Counseling</option>";
echo "<option value='FES_Counseling'>FES Counseling</option>";
echo "<option value='CBHA'>CBHA</option>";
echo "<option value='Psychiatric'>Psychiatric</option>";
echo "<option value='Transitional_Youth_Program'>Transitional Youth Program</option>";
echo "<option value='Specialized_Therapy'>Specialized Therapy</option>";
echo "<option value='Specialized_Assessment_Services'>Specialized Assessment/Services</option>";
echo "<option value='Closed'>Closed</option>";
echo "</select>";
echo "</p>";


$prog3  = $_POST['prog3'];
echo $prog3
?>
</td>
</tr>
<tr>
<td></td>
    <td>
<?php
echo "<select name='prog2set'>";
echo "<option value='AND'>AND</option>";
echo "<option value='OR'>OR</option>";
echo "</select>";
$prog2set  = $_POST['prog2set'];
echo $prog2set
?>
    </td>   
    
     <td>
<?php
echo "<select name='prog3set'>";
echo "<option value='AND'>AND</option>";
echo "<option value='OR'>OR</option>";
echo "</select>";
$prog3set  = $_POST['prog3set'];
echo $prog3set
?>
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
</div> <!-- end of parameters -->

<?php
 if ($_POST['form_refresh']) {
?>


<div id="report_results">
<table>
 <thead>
  <th> <?php xl('AGE','e'); ?> </th>
  <th> <?php xl('Client','e'); ?> </th>
  <th> <?php xl('ID','e'); ?> </th>
    <th> <?php xl('Primary','e'); ?> </th>
      <th> <?php xl('Secondary','e'); ?> </th>
        <th> <?php xl('Tertiary','e'); ?> </th>

 </thead>
 <tbody>
<?php
 {
  $totalpts = 0;
   $query = "SELECT theprogram, theprogram2, theprogram3, DOB, fname, mname, lname, pid FROM patient_data  WHERE";

   if ($prog1==''){ 
       $query .= " theprogram LIKE '%'";}
       else{ $query .= "  theprogram = '$prog1'";}
   if ($prog2!='') {$query .= " $prog2set  theprogram2 = '$prog2'";}
   if ($prog3!='') {$query .= " $prog3set  theprogram3 = '$prog3'";}
      
 $query .= " GROUP BY DOB, lname, fname, mname, pid, theprogram, theprogram2, theprogram3".
  "  ORDER BY DOB DESC, lname ASC, fname, mname, pid, theprogram, theprogram2, theprogram3";     
 $res = sqlStatement($query);
  

  $prevpid = 0;
  while ($row = sqlFetchArray($res)) {
	 
	$birthdate = $row['DOB']; 
	 $age = floor((time() - strtotime($birthdate))/(60*60*24*365.2425));
	 
	 if( $age>= $age_from && $age<= $age_to){
   if ($row['pid'] == $prevpid) continue;
   $prevpid = $row['pid'];

  /****/
  
?>
 <tr>
  <td>
   <?php echo $age ?>
  </td>
  <td>
    <?php echo htmlspecialchars( $row['lname'] . ', ' . $row['fname'] . ' ' . $row['mname'] ) ?>
  </td>
  <td>
   <?php echo $row['pid'] ?>
  </td>
    <td>
   <?php echo $row['theprogram'] ?>
  </td>
    <td>
   <?php echo $row['theprogram2'] ?>
  </td>
    <td>
   <?php echo $row['theprogram3'] ?>
  </td>
 </tr>
<?php
   ++$totalpts;
} }
?>

 <tr class="report_totals">
  <td colspan='9'>
   <?php xl('Total Number of Clients','e'); ?>
   :
   <?php echo $totalpts ?>
  </td>
 </tr>

<?php
 }
?>
</tbody>
</table>
</div> <!-- end of results -->
<?php } else { ?>
<div class='text'>
 	<?php echo xl('Select Search Parameters and click SUBMIT to see results.', 'e' ); ?>
</div>
<?php } ?>

</form>
</body>


</html>
