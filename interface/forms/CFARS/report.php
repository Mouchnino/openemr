<?php
//by Art Eaton
include_once("../../globals.php");
include_once($GLOBALS["srcdir"]."/api.inc");
function cfars_report( $pid, $encounter, $cols, $id) {
$count = 0;
$obj = formFetch("form_cfars", $id);
?>

<html><head>
<? html_header_show();?>
<link rel=stylesheet href="<?echo $css_header;?>" type="text/css">
</head>
<body <?echo $top_bg_line;?> topmargin=0 rightmargin=0 leftmargin=2 bottommargin=0 marginwidth=2 marginheight=0>

<span class="title"><center><b>Functional Assessment Rating Scale</b></center></span>
<br></br>

<?php $res2 = sqlStatement("SELECT fname,mname,lname FROM patient_data WHERE pid = $pid");
$result = SqlFetchArray($res2); ?>
<b><u>Client and Service Information</u></b>
<br><br>
<b>Name:</b>&nbsp; <?php echo $result['fname'] . '&nbsp' . $result['mname'] . '&nbsp;' . $result['lname'];?>
<br>
<b>Purpose:</b>&nbsp;<? echo stripslashes($obj{"purpose"});?><br>
<? if ($obj{"atype"} == "2") {/*start FARS SECTION*/?>
<b>Assessment Type: FARS<br>
<? }else{?>
<b>Assessment Type: CFARS<br><? }?>
<b>Ethnicity:</b>&nbsp;<? echo stripslashes($obj{"species"});?><br>
<b>Substance Abuse Hx:</b>&nbsp;<? echo stripslashes($obj{"sa_hx"});?><br>
<b>Evaluator Education:</b>&nbsp;<? echo stripslashes($obj{"education"});?><br>

<? if ($obj{"atype"} == "2") {/*start FARS SECTION*/?>
<b>Evaluator FARS #::</b>&nbsp;<? echo stripslashes($obj{"license_num"});?><br>
<? }else{?>
<b>Evaluator CFARS #:</b>&nbsp;<? echo stripslashes($obj{"license_num"});?><br>
<? }?>


<b>County:</b>&nbsp;<? echo stripslashes($obj{"county"});?><br>
<b>Depression:</b>&nbsp;<? echo stripslashes($obj{"depression"});?><br>
<b>Anxiety:</b>&nbsp;<? echo stripslashes($obj{"anxiety"});?><br>
<b>Hyperactivity:</b>&nbsp;<? echo stripslashes($obj{"hyperactivity"});?><br>
<b>Thought process:</b>&nbsp;<? echo stripslashes($obj{"thought_process"});?><br>
<b>Cognitive:</b>&nbsp;<? echo stripslashes($obj{"cognitive"});?><br>
<b>Medical :</b>&nbsp;<? echo stripslashes($obj{"medical"});?><br>
<b>Stress:</b>&nbsp;<? echo stripslashes($obj{"stress"});?><br>
<b>Substance Abuse:</b>&nbsp;<? echo stripslashes($obj{"substance"});?><br>
<b>Interpersonal:</b>&nbsp;<? echo stripslashes($obj{"interpersonal"});?><br> 
<b>Home:</b>&nbsp;<? echo stripslashes($obj{"home"});?><br>
<b>ADL:</b>&nbsp;<? echo stripslashes($obj{"adl"});?><br>
<b>Legal:</b>&nbsp;<? echo stripslashes($obj{"legal"});?><br>
<b>Work School:</b>&nbsp;<? echo stripslashes($obj{"works"});?><br>
<b>Danger to Self:</b>&nbsp;<? echo stripslashes($obj{"self"});?><br>
<b>Danger to Others:</b>&nbsp;<? echo stripslashes($obj{"dothers"});?><br>
<b>Security:</b>&nbsp;<? echo stripslashes($obj{"security"});?><br>
<? if ($obj{"atype"} == "2") {/*start FARS SECTION*/?>
<b>Family:</b>&nbsp;<? echo stripslashes($obj{"family"});?><br>
<b>Self Care :</b>&nbsp;<? echo stripslashes($obj{"care"});?><br>
<?}/*END FARS SECTION*/?>
<b>Evaluator:</b>&nbsp;<? echo stripslashes($obj{"counselor_name"});?>
<? }?>