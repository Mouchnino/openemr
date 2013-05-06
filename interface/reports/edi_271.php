<?php
// Copyright (C) 2010 MMF Systems, Inc>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

        //SANITIZE ALL ESCAPES
        $sanitize_all_escapes=true;
        //

        //STOP FAKE REGISTER GLOBALS
        $fake_register_globals=false;
        //

	//	START - INCLUDE STATEMENTS
	include_once(dirname(__file__)."/../globals.php");
	include_once("$srcdir/forms.inc");
	include_once("$srcdir/billing.inc");
	include_once("$srcdir/pnotes.inc");
	include_once("$srcdir/patient.inc");
	include_once("$srcdir/report.inc");
	include_once("$srcdir/calendar.inc");
	include_once("$srcdir/classes/Document.class.php");
	include_once("$srcdir/classes/Note.class.php");
	include_once("$srcdir/sqlconf.php");
	include_once("$srcdir/edi.inc");

	// END - INCLUDE STATEMENTS 


	//  File location (URL or server path) 
	
	$target			= $GLOBALS['edi_271_file_path']; 

	if(isset($_FILES) && !empty($_FILES))
	{

			$target		= $target .time().basename( $_FILES['uploaded']['name']);
	
			$FilePath	= $target;
			
			if ($_FILES['uploaded']['size'] > 350000) 
			{ 
				$message .= htmlspecialchars( xl('Your file is too large'), ENT_NOQUOTES)."<br>";
				
			}
			
			if ($_FILES['uploaded']['type']!="text/plain") 
			{ 
				 $message .= htmlspecialchars( xl('You may only upload .txt files'), ENT_NOQUOTES)."<br>"; 				 
			} 
			if(!isset($message))
			{
				if(move_uploaded_file($_FILES['uploaded']['tmp_name'], $target)) 
				{ 
					$message	= htmlspecialchars( xl('The following EDI file has been uploaded').': "'. basename( $_FILES['uploaded']['name']).'"', ENT_NOQUOTES); 
					
					// Stores the content of the file    
					$Response271= file_get_contents($FilePath);

					// Counts the number of lines       
					$LineCount	= count($Lines);
					
					//This will be a two dimensional array 
					//that holds the content nicely organized 

					$DataSegment271 = array();
					$Segments271	= array();
					
					// We will use this as an index 
					$i			=	0;
					$j			=	0;
					$patientId	= "";

					
					$DataSegment271 = explode("~", $Response271);

					if(count($DataSegment271)<6){
						$DataSegment271 = explode("^", $Response271);
					}
					
					if(count($DataSegment271)<6)
					{
						$messageEDI	= true;
						$message = "";
						if(file_exists($target))
						{
							unlink($target);
						}
					}
					else
					{
						$forCount	=	1;

						foreach ($DataSegment271 as $datastrings)
						{							
							
							$Segments271[$j] = explode("*", $datastrings);
							
							$segment		 = $Segments271[$j][0];

							
							// Switch Case for Segment 
							
							switch ($segment) 
							{
								case 'ISA':
									
											$x12PartnerId = $Segments271[0][6];
											break;

								case 'NM1':
											
											$patientLastName	= "";
											$patientMidName		= "";
											$patientFirstName	= "";

											if($Segments271[$j][1] == "IL" && $Segments271[$j][2] == "1"){
												$patientLastName  = $Segments271[$j][3];
												$patientFirstName = $Segments271[$j][4];
												$patientMidName	  = $Segments271[$j][5];
											}
											break;

								case 'DMG':
									
											$patientDOB	=	"";
											$patientGEN	=	"";

											if(isset($Segments271[$j][2]) && !empty($Segments271[$j][2])){
												$patientDOB = $Segments271[$j][2];
											}
											if(isset($Segments271[$j][3]) && !empty($Segments271[$j][3])){
												$patientGEN = ($Segments271[$j][3] == 'F' ? 'Female' : 'Male');
											}

											break;

								case 'REF':

											$REFVAL	=	$Segments271[$j][1];	

											Switch($EBVAL)
											{
													
												case "EJ":
															$patientId	= $Segments271[$j][2];
															$idType		= "ID";
															break;

												case "18":  /* Contract Number followed by Plan Number */
															$patientId	= $Segments271[$j][2];
															$idType		= "PLANNO";
															break;

												case "IG":  /* Insurance Policy Number */
															$patientId	= $Segments271[$j][2];
															$idType		= "POLICYNO";
															break;

												case "49":  /* Family ID Number */
															$patientId	= $Segments271[$j][2];
															$idType		= "FAMILYID";
															break;
															
												case "SY":  /* Reference ID Qualifier - SSN */
															$patientId	= $Segments271[$j][2];
															$idType		= "SSN";
															break;

												case "F6":  /* Health Insurance Claim Number - Medicare ID Number*/
															$patientId	= $Segments271[$j][2];
															$idType		= "MEDID";
															break;
											}
											
											break;
								case 'EB':
								
											$EBVAL	=	$Segments271[$j][1];

											
											Switch($EBVAL)
											{
												
												case '1': 
															$segmentVal		= "Status";
															$segmentValData = "Active Coverage";
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case '6': 
															$segmentVal		= "Status"." :";
															$segmentValData = "Subscriber is Not Eligible"; 
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;

												case 'B': 
															if($Segments271[$j][3] == "47"){
																$segmentVal		= "Hospital Copayment Days Remaining"; 
																$segmentValData = $Segments271[$j][10];
															}	
															else if($Segments271[$j][3] == "AG"){
																$segmentVal		= "SNF Copayment Days Remaining"; 
																$segmentValData = $Segments271[$j][10];
															}	
															else{
																$segmentVal		= "Hospital Copayment Days Remaining"; 
																$segmentValData	= $Segments271[$j][10];
															}
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case 'C':	
															if($Segments271[$j][4] == "MA"){
																$segmentVal		= "Part A Deductible Remaining";
																$segmentValData = $Segments271[$j][7];
															}
															else if($Segments271[$j][4] == "MB"){
																$segmentVal		= "Part B Deductible Remaining";
																$segmentValData = $Segments271[$j][7];
															}
															else if($Segments271[$j][2] == "IND"){
																$segmentVal		= "Blood Deductible - Number of Units Remaining";
																$segmentValData = $Segments271[$j][10];
															}
															else{
																$segmentVal		=	"Deductible Remaining"; 
																$segmentValData	=	$Segments271[$j][7];
															}
															
															
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;

												case 'D': 	if($Segments271[$j][3] == "44"){
																$segmentVal		= "Number of Home Health Visits Remaining";
																$segmentValData = $Segments271[$j][10];
															}
															else if($Segments271[$j][4] == "HM"){
																$segmentVal		= "Mental Health Services - PAID";
																$segmentValData = $Segments271[$j][5];
															} 
															else if($Segments271[$j][3] == "67"){
																$segmentVal				= "Next Eligible Smoking Counseling Date";
																$segmentValDataArray		= explode("*",$DataSegment271[$forCount]);
																$segmentValDataArrayDates	= "";
																$segmentValDataArr			= array();
																$segmentValDataArrayDates	=   explode("-",$segmentValDataArray[3]);
																foreach($segmentValDataArrayDates as $date){
																	$timeX = strtotime( $date );
																	$segmentValDataArr[]	=	date( 'j F Y', $timeX );
																}	
																$segmentValData = implode("-", $segmentValDataArr);
															} 
															else {
																$segmentVal				=	"Preventive Care with the same Professional(HCPCS Code)"; 
																$segmentValDataArray	=	explode(":", $Segments271[$j][13]);
																$segmentValData			=	$segmentValDataArray[1];		
															} 
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												
												case 'F':	if($Segments271[$j][3] == "AD"){
																$segmentVal		= "Occupational Therapy - Therapy Capitation Amount Remaining";
																$segmentValData = $Segments271[$j][7];
															}
															else if($Segments271[$j][3] == "AE"){
																$segmentVal		= "Therapy Capitation Amount Remaining";
																$segmentValData =  $Segments271[$j][7];
															}	
															else if($Segments271[$j][3] == "47"){
																$segmentVal		="Hospital Full Days Remaining"; 
																$segmentValData =  $Segments271[$j][10];
															}	
															else if($Segments271[$j][3] == "AG"){
																$segmentVal		= "SNF Full Days Remaining"; 
																$segmentValData =  $Segments271[$j][10];
															}	
															else if($Segments271[$j][3] == "67"){
																$segmentVal		= "Number of Sessions Remaining"; 
																$segmentValData =  $Segments271[$j][10];
															}
															else{
																$segmentVal		= "Physical and Speech Therapy - Amount Remaining"; 
																$segmentValData =  $Segments271[$j][7];
															}
															
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case 'J': 
															if($Segments271[$j][3] == "13"){
																$segmentVal		= "Number of Ambulatory Visits Remaining";
																$segmentValData = $Segments271[$j][10];
															} 
															else if($Segments271[$j][3] == "33"){
																$segmentVal		= "Number of Chiropractic Visits Remaining";
																$segmentValData = $Segments271[$j][10];
															} 
															else{
																$segmentVal = "Cost Containment";
																$segmentValData = $Segments271[$j][10];
															}
															 
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case 'K': 
															if($Segments271[$j][4] == "MA"){
																$segmentVal = "Part A Lifetime Days Remaining";  
																$segmentValData =  $Segments271[$j][10];
															}	
															else if($Segments271[$j][3] == "AG"){
																$segmentVal		= "SNF Full Days Remaining"; 
																$segmentValData =  $Segments271[$j][10];
															}	
															else{ 
																$segmentVal		= "Lifetime Days Remaining";
																$segmentValData =  $Segments271[$j][10];		
															}
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case 'L': 
															
															$segmentVal		= "RSP Code Description"; 
															$segmentValData =  $Segments271[$j][5];
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case 'R': 
															if($Segments271[$j][4] == "MA"){
																$segmentVal = "MEDICARE PART A DESC" ;  
																$segmentValData =  $Segments271[$j][5];
															}	
															else if($Segments271[$j][4] == "MB"){
																$segmentVal		= "MEDICARE PART B DESC"; 
																$segmentValData =  $Segments271[$j][5];
															}
															else if($Segments271[$j][4] == "OT"){
																$segmentVal				=	"Policy Description"; 
																$segmentValDataArray	=   explode("*",$DataSegment271[$forCount]);
																$segmentValData			=	$segmentValDataArray[2];
															}
															
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
												case 'X': 
															if($Segments271[$j][4] == "MA" && $Segments271[$j][3] == "42"){
																$segmentVal				= "Home Health Dates"; 
																$segmentValDataArray		=   explode("*",$DataSegment271[$forCount]);
																$segmentValDataArrayDates	= "";
																$segmentValDataArr			= array();
																$segmentValDataArrayDates	=   explode("-",$segmentValDataArray[3]);
																foreach($segmentValDataArrayDates as $date){
																	$timeX = strtotime( $date );
																	$segmentValDataArr[]	=	date( 'j F Y', $timeX );
																}	
																$segmentValData = implode("-", $segmentValDataArr);
															}
															else if($Segments271[$j][4] == "MA" && $Segments271[$j][3] == "45"){
																$segmentVal = "Hospice Care Dates"; 
																$segmentValDataArray		=   explode("*",$DataSegment271[$forCount]);
																$segmentValDataArrayDates	= "";
																$segmentValDataArr			= array();
																$segmentValDataArrayDates	=   explode("-",$segmentValDataArray[3]);
																foreach($segmentValDataArrayDates as $date){
																	$timeX					= strtotime( $date );
																	$segmentValDataArr[]	=	date( 'j F Y', $timeX );
																}	
																$segmentValData = implode("-", $segmentValDataArr);
															}
															
															eligibility_response_save($segmentVal,$x12PartnerId);
															eligibility_verification_save($segmentVal,$x12PartnerId,$patientId,$idType,$patientDOB,$patientGEN,$patientLastName,$patientMidName,$patientFirstName,$segmentValData);
															break;
											}											
											break;

								case 'MSG':
											eligibility_response_save($Segments271[$j][1],$x12PartnerId);
											eligibility_verification_save($Segments271[$j][1],$x12PartnerId,$patientId);
											break;
						
							}
						   
						   /******* Increase the line index ***************/ 
						   $j++; $forCount++;
						}
					}
				   /******* Increase the line index *******************/ 
				   $i++;
				}				
			} 
			else 
			{ 
				$message .= "Sorry, there was a problem uploading your file". "<br><br>"; 
			}  
	}
	
?>
<html>
<head>
<?php html_header_show();?>
<title><?php echo "EDI-271 Response File Upload"; ?></title>
<link rel=stylesheet href="<?php echo $css_header;?>" type="text/css">
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
}

</style>

<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>

<script type="text/javascript">
		function edivalidation(){ 
			
			var mypcc = "<?php echo "Required Field Missing: Please choose the EDI-271 file to upload";?>";

			if(document.getElementById('uploaded').value == ""){
				alert(mypcc);
				return false;
			}
			else
			{
				$("#theform").submit();
			}
			
		}
</script>

</head>
<body class="body_top">

<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
	<?php 	if(isset($message) && !empty($message))
			{
	?>
				<div style="margin-left:25%;width:50%;color:RED;text-align:center;font-family:arial;font-size:15px;background:#ECECEC;border:1px solid;" ><?php echo $message; ?></div>
	<?php
				$message = "";
			}
			if(isset($messageEDI))
			{
	?>
				<div style="margin-left:25%;width:50%;color:RED;text-align:center;font-family:arial;font-size:15px;background:#ECECEC;border:1px solid;" >
					<?php echo "Please choose the proper formatted EDI-271 file" ?>
				</div>
	<?php
					$messageEDI = "";
			}
	?>
	
<div>

<span class='title'><?php echo htmlspecialchars( xl('EDI-271 File Upload'), ENT_NOQUOTES); ?></span>

<form enctype="multipart/form-data" name="theform" id="theform" action="edi_271.php" method="POST" onsubmit="return top.restoreSession()">

<div id="report_parameters">
	<table>
		<tr>
			<td width='550px'>
				<div style='float:left'>
					<table class='text'>
						<tr>
							<td style='width:125px;' class='label'> <?php echo 'Select EDI-271 file'; ?>:	</td>
							<td> <input name="uploaded" id="uploaded" type="file" size=37 /></td>
						</tr>
					</table>
				</div>
			</td>
			<td align='left' valign='middle' height="100%">
				<table style='border-left:1px solid; width:100%; height:100%' >
					<tr>
						<td>
							<div style='margin-left:15px'>
								<a href='#' class='css_button' onclick='return edivalidation(); '><span><?php echo 'Upload'; ?></span>
								</a>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</div> 


<input type="hidden" name="form_orderby" value="<?php echo htmlspecialchars( $form_orderby, ENT_QUOTES); ?>" />
<input type='hidden' name='form_refresh' id='form_refresh' value=''/>

</form>
</body>
</html>
