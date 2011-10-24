<?php

require_once("Claim.class.php");

function gen_x12_837($pid, $encounter, &$log, $encounter_claim=false) {

  $today = time();
  $out = '';
  $claim = new Claim($pid, $encounter);
  $edicount = 0;

  $log .= "Generating claim $pid-$encounter for " .
    $claim->patientFirstName()  . ' ' .
    $claim->patientMiddleName() . ' ' .
    $claim->patientLastName()   . ' on ' .
    date('Y-m-d H:i', $today) . ".\n";

  $out .= "ISA" .
    "*" . $claim->x12gsisa01() .//ISA 01 configured in new x12 partner form
    "*" . $claim->x12gsisa02() .//ISA 02 configured in new x12 partner form
    "*" . $claim->x12gsisa03() .//ISA 03 configured in new x12 partner form
    "*" . $claim->x12gsisa04() .//ISA 04 configured in new x12 partner form
    "*" . $claim->x12gsisa05() .//ISA 05 configured in new x12 partner form
    "*" . $claim->x12gssenderid() .//ISA 06 (used elsewhere, thus the name difference)
    "*" . $claim->x12gsisa07() .//ISA 07
    "*" . $claim->x12gsreceiverid() .//ISA 08 (used elsewhere, thus the name difference)
    "*111018" . //ISA 09.  'Interchange Date' Not sure what date is really supposed to be used here, format is YYMMDD
    "*1630" .  //ISA 10.  'Interchange Time'  24Hour format.  Pretty sure that both of these fields should be configured for current date/time stamp.
    "*^" . //ISA 11.  Availity recommends this character here for 5010 instead of a "U".  This is now 'repitition seperator'
    "*00501" .  //ISA 12.  Standards version 5010
    "*000000001" . //ISA 13  Control number we assign.  I bet this is another useful field we are blowing off here...
    "*" . $claim->x12gsisa14() .  //ISA 14  'Acknowledgement Requested'  configured in new x12 partner form '1" or "0"
    "*" . $claim->x12gsisa15() .  //ISA 15  Test or Production.  T or P
    "*:" . //ISA 16 'Component Element Seperator'  Any character from basic set goes here.
    "~\n";  //ISA Segment Terminator.  Use Tilde~

  $out .= "GS" .  //Loop repeat is indicated to change here.  It went from "1" to ">1"
    "*HC" .  //GS 01  This is a Health Care claim "HC" for 837 format
    "*" . $claim->x12gsgs02() . //GS02  Configured in x12 partner form.  Availity recommends Vendor Partners used their assigned ID. 2-15  characters.
    "*" . trim($claim->x12gsreceiverid()) . //GS03  Availity value is 030240928.  'Code agreed to by trading partner'
    "*" . date('Ymd', $today) .  //GS04 Todays date
    "*" . date('Hi', $today) .  //GS05  The time
    "*1" .//GS06  This can be 1-9 numbers, and should be unique within a 6-mo period.  No leading zeros allowed. Obviously we are not doing that here....
    "*X" .//GS07   "x" stands for "X12 committee".  Bullshit field. 
    "*" . $claim->x12gsversionstring() .  //GS08 X12 version configured in x12 partner form.  Professional claims with 5010 use 005010X222A1
    "~\n";

  ++$edicount;
  $out .= "ST" .//ST segment identifier
    "*837" .//ST 01 Transaction set identifier (it's 837 format y'know?)
    "*0021" .//ST 02  Just a BS number from 4-9 characters
    "*005010X222" . //ST03  A new element.  This is the required element that replaces the REF segment.  It ain't like it does anything, but you gotta have it.
    "~\n";//  Segment terminator

  ++$edicount;
  $out .= "BHT" .//Beginning Hierarchial Transaction  Availity doesn't require this segment according to their companion guide.
    "*0019" . //BHT01  Always the same.
    "*00" .  //BHT02  Has to be 00 or 18
    "*0123" .  //BHT03  bullshit alphanumeric.  You can put in up to 50 characters here.  I don't like leading zeros, but it don't seem to matter here.
    "*" . date('Ymd', $today) .//BHT04 CCYYMMDD format.  
    "*1023" .//BHT05 Transaction set creation time.  Can be up to 8 number time format hhmmssdd, minimum hhmm.
    ($encounter_claim ? "*RP" : "*CH") .//BHT06 Claim or Encounter ID
    "~\n";//segment terminator

 // ++$edicount;
 // $out .= "REF" .//Reference Identification...Replaced by new element
  //  "*87" .
  //  "*" . $claim->x12gsversionstring() .
  //  "~\n";

  ++$edicount;
  //Field length is increased from 35 to 60 characters for billing facility name.
  $billingFacilityName=substr($claim->billingFacilityName(),0,60);
  $out .= "NM1" .       // Loop 1000A Submitter data
    "*41" .// NM101 Entity identifier code...we seem to be 41.  I like 42 better.  Too bad hunh?
    "*2" .// NM102 We are a type 2 personality.  Same for institutional.
    "*" . $billingFacilityName . //NM103  Up to 60 characters.  All the rest is chopped off.
    "*" .//NM104  Submitter First name...unused.
    "*" .//NM105  Middle name...unused as well.
    "*" .//NM106  Title...unused
    "*" .//NM107  Suffix...unused
    "*46"; //NM108  "46" goes here..
   if (trim($claim->x12gsreceiverid()) == '470819582') { // NM109  Kludge for if ECLAIMS EDI.  This should be configurable for other exceptions.
    $out  .=  "*" . $claim->clearingHouseETIN(); //We need to make sure this Kludge will still work for 5010 with ECLAiMS, 
   } else {                                      ///and include in x12 partner setup
    $out  .=  "*" . $claim->billingFacilityETIN();
   }
    $out .= "~\n";//Segment Terminator  There are elements NM110, NM111 and a new element that uses or repeats the billing facility name NM112.  
                  //This should be looked into more closely, but I have not found where anyone is using them.
  ++$edicount;
  $out .= "PER" .  //Submitter EDI contact information
    "*IC" .  //PER01 Contact Function Code
    "*" . $claim->billingContactName() .//PER02 Submitter Contact Name
    "*TE" .//PER03 type restricted to EM, FX, or TE...means email, fax,or Telephone.    We are using phone here.
    "*" . $claim->billingContactPhone();//PER04  Now increased to 256 characters, but we are using phone number.  We may want this configurable.
 // if ($claim->x12gsper06()) {
  //  $out .= "*ED*" . $claim->x12gsper06();//PER05 and PER06 no longer use an EDI access number for 5010
  //}
  $out .= "~\n";  //Segment Terminator

  ++$edicount;
  $out .= "NM1" .       // Loop 1000B Receiver
    "*40" .//NM101  Entity identifier code
    "*2" .//NM102  Entity type qualifier
    "*" . $claim->clearingHouseName() .//NM103  Increased to 60 characters
    "*" .//NM104 Name First  ...increased to 35 characters
    "*" .//NM105  Name Middle...also not used
    "*" .//NM106 Prefix
    "*" .//NM107 Suffix
    "*46" .//NM109 Identification code qualifier
    "*" . $claim->clearingHouseETIN() .  //NM110 Receiver Primary identifier
    "~\n";//Segment terminator.  New elementNM12 added, but we don't use it or NM111

  $HLcount = 1;

  ++$edicount;
  $out .= "HL" .        // Loop 2000A Billing/Pay-To Provider HL Loop
    "*$HLcount" .//HL01 Id number
    "*" .//HL02 Parent ID number not used
    "*20" .//HL03 Level code is 20.
    "*1" .//HL04 Child code is 1
    "~\n";//Segment terminator

  $HLBillingPayToProvider = $HLcount++;
///////////////////////////////////////////////Specialty provider stuff would go here if we used it...PRV Segment.  Currency stuff can go here too.
  ++$edicount;
  //Loop 2010AA  BillingProvider Name Suffix
  $billingFacilityName=substr($claim->billingFacilityName(),0,60);//increased to 60
  $out .= "NM1" .       // Loop 2010AA Billing Provider
    "*85" .//NM101  Entity Identifier code
    "*2" .//NM102 type qualifier
    "*" . $billingFacilityName .//NM103 increased to 60 characters
    "*" .//NM104  name elements that we don't use....lase first middle
    "*" .//NM105
    "*" .//NM106
    "*";//NM107
 if ($claim->billingFacilityNPI()) {
    $out .= "*XX*" . $claim->billingFacilityNPI();//NM108 and NM109
 } else {
    
  //  $out .= "*24*" . $claim->billingFacilityETIN();  Leaving this statement for the log, but only value XX with NPI is allowed.
  }
  $out .= "~\n";//Segment terminator

  ++$edicount;
  $out .= "N3" . //Billing Provider Address segment
    "*" . $claim->billingFacilityStreet() .//N301
    "~\n";//Segment Terminator

  ++$edicount;
  $out .= "N4" .///Billing Provider city,state,zip segment
    "*" . $claim->billingFacilityCity() .//N401
    "*" . $claim->billingFacilityState() .//N402
    "*" . $claim->billingFacilityZip() .//N403
    "~\n";///New country subdivision code supported here now (not used).

  // Add a REF*EI*<ein> segment if NPI was specified in the NM1 above.
  if ($claim->billingFacilityNPI() && $claim->billingFacilityETIN()) {
    ++$edicount;
    $out .= "REF" ;//Billing Provider Secondary information
	if($claim->federalIdType()){//REF01  This needs to change elsewhere obviously,  
      $out .= "*" . $claim->federalIdType();//..as only EI and SY are supported, all other codes are deleted.
	}
	else{
	  $out .= "*EI";//For dealing with the situation before adding selection for TaxId type In facility ie default to EIN.
	}
      $out .=  "*" . $claim->billingFacilityETIN() .
      "~\n";
  }
//UPIN stuff can be used here, but some companies crap out if this data is used ..
///////////////////////////////////////To the DMG segment, the following is situational only, so I will not comment the fields.
  if ($claim->providerNumberType() && $claim->providerNumber()) {//..no secondary data allowed by availity providers and Florida Health Partners.
    ++$edicount;
    $out .= "REF" .
      "*" . $claim->providerNumberType() .
      "*" . $claim->providerNumber() .
      "~\n";
  }
  else if ($claim->providerNumber()) {
    $log .= "*** Payer-specific provider insurance number is present but has no type assigned.\n";
  }

  ++$edicount;
  //Repeat code.  This needs to be turned into a function with arguments
  $billingFacilityName=substr($claim->billingFacilityName(),0,60);
  $out .= "NM1" .       // Loop 2010AB Pay-To Provider
    "*87" .
    "*2" .
    "*" . $billingFacilityName .
    "*" .
    "*" .
    "*" .
    "*";
  if ($claim->billingFacilityNPI()){
    $out .= "*XX*" . $claim->billingFacilityNPI();
 }else{
	  $out .= "*XX*" . $claim->billingFacilityNPI();////changed here (to bracket code and remove non-NPI options).
    $log .= "*** Billing facility has no NPI.\n";}
  $out .= "~\n";

  ++$edicount;
  $out .= "N3" .
    "*" . $claim->billingFacilityStreet() .
    "~\n";

  ++$edicount;
  $out .= "N4" .
    "*" . $claim->billingFacilityCity() .
    "*" . $claim->billingFacilityState() .
    "*" . $claim->billingFacilityZip() .
    "~\n";
/////////pay-To provider secondary info segment deleted///////////////////////////////***********************
  //if ($claim->billingFacilityNPI() && $claim->billingFacilityETIN()) {
 //   ++$edicount;
//    $out .= "REF" .
//      "*EI" .
 //     "*" . $claim->billingFacilityETIN() .
 //     "~\n";
 // }
//////////////////////////*******New Loop 2010AC  PAY-TO-PLAN is available for use here.........***************************/
////********************************************************************************************************************

  $PatientHL = 0;

  ++$edicount;
  $out .= "HL" .        // Loop 2000B Subscriber HL Loop
    "*$HLcount" .//HL01
    "*$HLBillingPayToProvider" .//HL02
    "*22" .//HL03
    "*$PatientHL" .//HL04
    "~\n";//Segment Terminator

  $HLSubscriber = $HLcount++;

  if (!$claim->payerSequence()) {
    $log .= "*** Error: Insurance information is missing!\n";
  }
  ++$edicount;
  $out .= "SBR" .       // Subscriber Information
    "*" . $claim->payerSequence() .//SRB01 payer Responsibility Sequence code.  Implimentation guide is...... 
                                   ////.....screwed up here.  Says deleted codes, but only ADDS codes
     "*" . $claim->insuredRelationship() .//SRB02  has to be 18, why do we use a value??????????  
    "*" . $claim->groupNumber() .//SRB03 increased to 50 char
    "*" . $claim->groupName() .//SRB04 needs trim to 60 char
    "*" . $claim->insuredTypeCode() . // //SRB05 applies for secondary medicare
    "*" .//SRB06
    "*" .//SRB07
    "*" .//SRB08    
    "*" . $claim->claimType() . // //SRB09  Now can use    11, 12, 13, 14, 15,16, 17, AM, BL,CH, CI, DS, FI,HM, LM, MA, MB,MC, OF, TV, VA,WC, ZZ
    "~\n";
///////////////////////////////////////////////////////PAT segment can go in here....////////////////////////////
  ++$edicount;
  $out .= "NM1" .       // Loop 2010BA Subscriber
    "*IL" .//NM101
    "*1" .//NM102
    "*" . $claim->insuredLastName() .//NM103
    "*" . $claim->insuredFirstName() .//NM104
    "*" . $claim->insuredMiddleName() .//NM105
    "*" .//NM106
    "*" .//NM106
    "*" .////////////////////////////Holy SHIT!!!!!!!!!  We have been missing NM107 all along!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    "*MI" .//NM108
    "*" . $claim->policyNumber() .//NM109  Good thing I caught the missing asterisk above, 'cause NM109 is now REQUIRED!!!!!!!!!!
    "~\n";///Segment terminator

  ++$edicount;
  $out .= "N3" .
    "*" . $claim->insuredStreet() .//N301  Can use two lines here if we want.....
    "~\n";//Segment Terminator

  ++$edicount;
  $out .= "N4" .
    "*" . $claim->insuredCity() .//n401
    "*" . $claim->insuredState() .//N402
    "*" . $claim->insuredZip() .//N403
    "~\n";//Segment Terminator

  ++$edicount;
  $out .= "DMG" .
    "*D8" .//DMG01
    "*" . $claim->insuredDOB() .//DMG02
    "*" . $claim->insuredSex() .//DMG03
    "~\n";//Segment TErminator

  ++$edicount;
  // a lot of new stuff can go in here as a new PER segnment for Casualty SUB.
  $payerName=substr($claim->payerName(),0,60);
  $out .= "NM1" .       // Loop 2010BB Payer
    "*PR" .//NM101
    "*2" .//NM102
    "*" . $payerName .//NM103
    "*" .//NM104
    "*" .//NM105
    "*" .//NM106
    "*" .//NM107
    "*PI" .//NM108
    "*" . ($encounter_claim ? $claim->payerAltID() : $claim->payerID()) .//NM109
    "~\n";//Segment TErminator

  

  ++$edicount;
  $out .= "N3" .
    "*" . $claim->payerStreet() .//N301
    "~\n";

  ++$edicount;
  $out .= "N4" .
    "*" . $claim->payerCity() .//N401
    "*" . $claim->payerState() .//N402
    "*" . $claim->payerZip() .//N403
    "~\n";//Segment Terminator
///////////////////////////////////////////////////////////Payer secondary can go here, but is not used/////////////////////////
///////////////////////////////////////////////////////////4010 responsible party stuff deleted....we did not use////////
///////////////////////////////////////////////////////////2000C REF segment deleted////////////////////////////////////////
  if (! $claim->isSelfOfInsured()) {
    ++$edicount;
    $out .= "HL" .        // Loop 2000C Patient Information
      "*$HLcount" .//HL01
      "*$HLSubscriber" .//HL02
      "*23" .//HL03  
      "*0" .//HL04
      "~\n";//Segment Terminator

    $HLcount++;

    ++$edicount;
    $out .= "PAT" .
      "*" . $claim->insuredRelationship() .//PAT01  Codes were deleted here
      "~\n";//Seg Term

    ++$edicount;
    $out .= "NM1" .       // Loop 2010CA Patient
      "*QC" .//NM01
      "*1" .//NM02
      "*" . $claim->patientLastName() .//NM03 increased to 60
      "*" . $claim->patientFirstName() .//NM04
      "*" . $claim->patientMiddleName() .//NM05
      "~\n";//Seg Term

    ++$edicount;
    $out .= "N3" .
      "*" . $claim->patientStreet() .//N301
      "~\n";//Seg Term

    ++$edicount;
    $out .= "N4" .
      "*" . $claim->patientCity() .//N401
      "*" . $claim->patientState() .//N402
      "*" . $claim->patientZip() .//N403
      "~\n";//Seg Term
///////////////////////////////////////////More property and Casualty stuff can go here..////////////////////
    ++$edicount;
    $out .= "DMG" .
      "*D8" .//DMG01
      "*" . $claim->patientDOB() .//DMG02
      "*" . $claim->patientSex() .//DMG03
      "~\n";
  } // end of patient different from insured

  $proccount = $claim->procCount();

  $clm_total_charges = 0;
  for ($prockey = 0; $prockey < $proccount; ++$prockey) {
    $clm_total_charges += $claim->cptCharges($prockey);
  }

  if (!$clm_total_charges) {
    $log .= "*** This claim has no charges!\n";
  }


  ++$edicount;
  $out .= "CLM" .       // Loop 2300 Claim
    "*$pid-$encounter" .//CLM01 Patient Account Number
    "*"  . sprintf("%.2f",$clm_total_charges) . //CLM02 Zirmed computes and replaces this
    "*"  .//CLM03
    "*"  .//CLM04
    "*" . sprintf('%02d', $claim->facilityPOS()) . "::" . $claim->frequencyTypeCode() . // CLM05  Changed to correct single digit output
    "*Y" .//CLM06
    "*A" .//CLM07
    "*"  . ($claim->billingFacilityAssignment() ? 'Y' : 'N') .//CLM08
    "*Y" .//CLM09
    "*P" .//CLM10  Only P is allowed here now
    "~\n"; //Seg Term.  RElated causes stuff can go in here////////////////////////////////////////////
    
  ++$edicount;
  $out .= "DTP" .       // Date of Onset
    "*431" .//DTP01
    "*D8" .//DTP02
    "*" . $claim->serviceDate() .//DTP03   !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!Check against original code for variable to use...May be modded for MH here!
    "~\n";//Seg Term

  if (strcmp($claim->facilityPOS(),'21') == 0) {
    ++$edicount;
    $out .= "DTP" .     // Date of Hospitalization  Still OK,lots of changes here.....
      "*435" .
      "*D8" .
      "*" . $claim->onsetDate() .
      "~\n";//Seg Term
  }

  $patientpaid = $claim->patientPaidAmount();
  if ($patientpaid != 0) {
    ++$edicount;
    $out .= "AMT" .     // Patient paid amount. 
      "*F5" .//AMT01
      "*" . $patientpaid .//AMT02
      "~\n";//Seg Term
  }

  if ($claim->priorAuth()) {
    ++$edicount;
    $out .= "REF" .     // Prior Authorization Number
      "*G1" .//REf01
      "*" . $claim->priorAuth() .//Ref02
      "~\n";//Seg Term
  }

  if ($claim->cliaCode() and $claim->claimType() === 'MB') {
    // Required by Medicare when in-house labs are done.
    ++$edicount;
    $out .= "REF" .     // Clinical Laboratory Improvement Amendment Number
      "*X4" .//REF01
      "*" . $claim->cliaCode() .//REF02 increase to 50
      "~\n";//Seg Term
  }

  // Note: This would be the place to implement the NTE segment for loop 2300.
  if ($claim->additionalNotes()) {
    // Claim note.
    ++$edicount;
    $out .= "NTE" .     // Claim Note Codes deleted.  Only ADD, CER, DCP, DGN, TPO now allowed
      "*" .//NTE01This is a required element....if you try to use the next element, bad news!  This needs looking at.....
      "*" . $claim->additionalNotes() .//NTE02  Needs a variable for NTE01!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      "~\n";//Seg Term
  }

  // Diagnoses, up to 8 per HI segment....!!!!!!!!!!!!!!!!Now we can go up to 12 diagnosis.......!!!!!!!!!!!!!!!!!!!Change????
  $da = $claim->diagArray();
  $diag_type_code = 'BK';//!!!!!!!!!!!!!!!!!!bk  and ABK now allowed.  
  $tmp = 0;
  foreach ($da as $diag) {
    if ($tmp % 8 == 0) {/////can go up to 12.  Do we need to change???
      if ($tmp) $out .= "~\n";
      ++$edicount;
      $out .= "HI";         // Health Diagnosis Codes  Code ABK is now added!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    }
    $out .= "*$diag_type_code:" . $diag;//HL01  Health Care Code Information
    $diag_type_code = 'BF';//HL01-1 add type ABF!!!!!!!!!!!!!!!!!!!!!!do we need this?????
    ++$tmp;
  }
  if ($tmp) $out .= "~\n";//Seg Term
  ////////////////////////////////////////////////////////////////////////////////////////////////!!!!!!!!!!!!!!!!!!!!!!!
  //////////////////////////////  There is a new segment for Anesthsia Related procedures (HI).  Do we want this??????????

  if ($claim->referrerLastName()) {
    // Medicare requires referring provider's name and UPIN. REFERRING PROVIDER NAME SEGMENT
    ++$edicount;
    $out .= "NM1" .     // Loop 2310A Referring Provider
      "*DN" .//NM101////////There can be P3 here.....
      "*1" .//NM102  Only a 1 may be used now
      "*" . $claim->referrerLastName() .//NM103
      "*" . $claim->referrerFirstName() .//NM104
      "*" . $claim->referrerMiddleName() .//NM105
      "*" .//NM106
      "*";//NM107
    if ($claim->referrerNPI()) { $out .=
      "*XX" .//NM108..........................Referrer must now have an NPI....only XX not 24/34 allowed.
      "*" . $claim->referrerNPI();//NM109
    } else {$log .= "*** Referrer has no NPI!.\n";/////add error to log
    }
    $out .= "~\n";//Seg Term

 //   if ($claim->referrerTaxonomy()) {//////////////////Segment DELETED from 5010
  //    ++$edicount;
  //    $out .= "PRV" .
   //     "*RF" . // ReFerring provider
   //     "*ZZ" .
   //     "*" . $claim->referrerTaxonomy() .
   //     "~\n";
  //  }

    if ($claim->referrerUPIN()) {
      ++$edicount;
      $out .= "REF" .   // Referring Provider Secondary Identification
        "*1G" .//REF01 many codes deleted...
        "*" . $claim->referrerUPIN() .//REF02
        "~\n";//Seg Term
    }
  }

  ++$edicount;
  $out .= "NM1" .       // Loop 2310B Rendering Provider
    "*82" .//NM101
    "*1" .//NM102
    "*" . $claim->providerLastName() .//NM103
    "*" . $claim->providerFirstName() .//NM104
    "*" . $claim->providerMiddleName() .//NM105
    "*" .//NM106
    "*";//NM107
  if ($claim->providerNPI()) { $out .=
    "*XX" .//NM108
    "*" . $claim->providerNPI();//NM109
  } else { $out .=
    "*XX" .//NM108
    "*" . $claim->providerNPI();//NM109 (again)
    $log .= "*** Rendering provider has no NPI.\n";
  }
  $out .= "~\n";//Seg Term

  if ($claim->providerTaxonomy()) {
    ++$edicount;
    $out .= "PRV" .//RENERING provider specialty information
      "*PE" . // PRV01 PErforming provider
      "*PCX" .//changed from ZZ
      "*" . $claim->providerTaxonomy() .///increased from 30-50.  Is this trimmed anywhere, like in the form or by the DB field size?!!!!!
      "~\n";//Seg Term
  }

  // REF*1C is required here for the Medicare provider number if NPI was
  // specified in NM109.  Not sure if other payers require anything here.
  // --- apparently ECLAIMS, INC wants the data in 2010 but NOT in 2310B - tony@mi-squared.com
  
  ////We need to look at this section again.  If this is Rendering Provider secondary info..............!!!!!!!!!!!!!!!!!!!!!!

   if (trim($claim->x12gsreceiverid()) != '470819582') { // if NOT ECLAIMS EDI
      if ($claim->providerNumber()) {
        ++$edicount;
        $out .= "REF" .
          "*" . $claim->providerNumberType() .//Ref01
          "*" . $claim->providerNumber() .//Ref02
          "~\n";//Seg Term
      }
   }

  // Loop 2310D is omitted in the case of home visits (POS=12).
  if ($claim->facilityPOS() != 12) {
    ++$edicount;
    $out .= "NM1" .       // Loop 2310D Service Location
      "*77" .//nm101  Only 77 allowed now
      "*2";//NM02
   //Field length is limited to 35. See nucc dataset page 77 www.nucc.org
	$facilityName=substr($claim->facilityName(),0,60); //NM103  Increased to 60
    if ($claim->facilityName() || $claim->facilityNPI() || $claim->facilityETIN()) { $out .=
      "*" . $facilityName;
    }
    if ($claim->facilityNPI() || $claim->facilityETIN()) { $out .=
      "*" .//NM104
      "*" .//NM105
      "*" .//NM106
      "*";//NM107
      if ($claim->facilityNPI()) { $out .=
        "*XX*" . $claim->facilityNPI();//NM108
      } else { $out .=
        "*XX*" . $claim->facilityNPI();//NM108////////////////Only NPI is allowed!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!11
        $log .= "*** Service location has no NPI.\n";//Looks like we are not using the situational NM109
      }
    }
    $out .= "~\n";//Seg Term
    if ($claim->facilityStreet()) {
      ++$edicount;
      $out .= "N3" .
        "*" . $claim->facilityStreet() .//N301
        "~\n";//Seg Term
    }
    if ($claim->facilityState()) {
      ++$edicount;
      $out .= "N4" .
        "*" . $claim->facilityCity() .//N401
        "*" . $claim->facilityState() .//N402
        "*" . $claim->facilityZip() .//N403
        "~\n";
    }
  }

  // Loop 2310E, Supervising Provider
  //
  if ($claim->supervisorLastName()) {
    ++$edicount;
    $out .= "NM1" .
      "*DQ" . //NM101 Supervising Physician
      "*1" .  //NM102 Person
      "*" . $claim->supervisorLastName() .//NM103
      "*" . $claim->supervisorFirstName() .//NM104
      "*" . $claim->supervisorMiddleName() .//NM105
      "*" .   // NM106 not used
      "*";    // //NM107 Name Suffix
    if ($claim->supervisorNPI()) { $out .=
      "*XX" .//NM108
      "*" . $claim->supervisorNPI();//NM109
    } else { $out .=
      "*XX" .//NM108  Gotta be XX now...only NPI
      "*" . $claim->supervisorNPI();//NM109 try it anyway....
      $log .= "*** Supervising provider has no NPI.\n";///add to log
    }
    $out .= "~\n";//Seg Term

    if ($claim->supervisorNumber()) {
      ++$edicount;
      $out .= "REF" .
        "*" . $claim->supervisorNumberType() .//REF01 type codes deleted
        "*" . $claim->supervisorNumber() .//Ref02
        "~\n";//Seg Term
    }
  }

  $prev_pt_resp = $clm_total_charges; // for computation below

  // Loops 2320 and 2330*, other subscriber/payer information.
  //
  for ($ins = 1; $ins < $claim->payerCount(); ++$ins) {
////////////////////!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
////!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!Claim type codes are changed!!!!!!!!!!!!!!!!!!!!!!
/////////!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!12, 13, 14, 15, 16, 41, 42, 43, 47 are allowed!!!!!!!!!!!!!!!!!!!!!!
////////////////////////////////////////Old values were AP, C1, CP, GP,HM, IP, LD, LT,MB, MC, MI, MP,OT, PP, SP!!!!!!!!!!!
////////////////////////////////////////Need a cross reference, change code, and then update DB values!!!!!!!!!!!!!!!!!!!!!!!
    $tmp1 = $claim->claimType($ins);
    $tmp2 = 'C1'; // Here a kludge. See page 321.
    if ($tmp1 === 'CI') $tmp2 = 'C1';///////////Ref https://www.cahabagba.com/part_b/msp/Providers_Electronic_Billing_Instructions.htm
    if ($tmp1 === 'AM') $tmp2 = 'AP';///////////..............for changes needed in this situational................
    if ($tmp1 === 'HM') $tmp2 = 'HM';
    if ($tmp1 === 'MB') $tmp2 = 'MB';
    if ($tmp1 === 'MC') $tmp2 = 'MC';
    if ($tmp1 === '09') $tmp2 = 'PP';
    ++$edicount;
    $out .= "SBR" . // Loop 2320, Subscriber Information - page 318
      "*" . $claim->payerSequence($ins) .///SBR01///////////////Many sequences added....we probably need this for responsible party stuff!!!!!
      "*" . $claim->insuredRelationship($ins) .//SBR02/Lots of relationships deleted!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      "*" . $claim->groupNumber($ins) .//SBR03
      "*" . $claim->groupName($ins) .//SBR04
      "*" . $tmp2 .//SBR05///////////////////////situational, and needed for medicare....and totally screwed up right now!!!!!!!!!!!!!!!!!!
      "*" .
      "*" .
      "*" .
      "*" . $claim->claimType($ins) .
      "~\n";//Term Seg

    // Things that apply only to previous payers, not future payers.
    //
    if ($claim->payerSequence($ins) < $claim->payerSequence()) {

      // Generate claim-level adjustments.
      $aarr = $claim->payerAdjustments($ins);
      foreach ($aarr as $a) {
        ++$edicount;
        $out .= "CAS" . // Previous payer's claim-level adjustments. 
          "*" . $a[1] .//CAS01
          "*" . $a[2] .//CAS02
          "*" . $a[3] .//CAS03
          "~\n";//Term Seg
      }

      $payerpaid = $claim->payerTotals($ins);
      ++$edicount;
      $out .= "AMT" . // Previous payer's paid amount. need COB total non covered amount new segment after this!!!!!!!!!!!!!!!!!!!!!!!!!!!
        "*D" .//AMT01
        "*" . $payerpaid[1] .//AMT02
        "~\n";//Term Seg

      // Patient responsibility amount as of this previous payer.
      $prev_pt_resp -= $payerpaid[1]; // reduce by payments
      $prev_pt_resp -= $payerpaid[2]; // reduce by adjustments

      ++$edicount;
      
 ///     COB TOTAL NONCOVERED AMOUNT New segment goes here, as well as REMAINING PATIENT LIABILITY SEGMENTS!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 ///!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!11
 //These two segments have been deleted and replaced.  Current variables need to be re-worked and evaluated!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 //////////////////////////////////////////I could be wrong if I am reading the brackets wrong for AMT and AAE segments -Art  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      $out .= "AMT" . // Allowed amount per previous payer. Page 334.
        "*B6" .
        "*" . sprintf('%.2f', $payerpaid[1] + $prev_pt_resp) .
        "~\n";

      ++$edicount;
      $out .= "AMT" . // Patient responsibility amount per previous payer. Page 335.
        "*F2" .
        "*" . sprintf('%.2f', $prev_pt_resp) .
        "~\n";

    } // End of things that apply only to previous payers.

    ++$edicount;
    $out .= "DMG" . // Other subscriber demographic information. Page 342.
      "*D8" .
     "*" . $claim->insuredDOB($ins) .
     "*" . $claim->insuredSex($ins) .
     "~\n";

    ++$edicount;
    $out .= "OI" .  // Other Insurance Coverage Information. Page 344.
      "*" .//OI01
      "*" .//OI02
      "*Y" .//OI03
      "*P" .//OI04   !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!Code deleted-- only P is allowed here now!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      "*" .//OI05
      "*Y" .//OI06
      "~\n";

    ++$edicount;
    $out .= "NM1" . // 2030A Other subscriber Name
      "*IL" .//NM101
      "*1" .//NM102
      "*" . $claim->insuredLastName($ins) .//NM103
      "*" . $claim->insuredFirstName($ins) .//NM104
      "*" . $claim->insuredMiddleName($ins) .//NM105
      "*" .//NM106
      "*" .//NM107
      "*MI" .//NM108
      "*" . $claim->policyNumber($ins) .//NM109
      "~\n";//Seg TErm

    ++$edicount;
    $out .= "N3" .
      "*" . $claim->insuredStreet($ins) .//N301
      "~\n";//Seg TErm

    ++$edicount;
    $out .= "N4" .
      "*" . $claim->insuredCity($ins) .//N401
      "*" . $claim->insuredState($ins) .//402
      "*" . $claim->insuredZip($ins) .//N403
      "~\n";//Seg TErm

    ++$edicount;
    //Field length is increased to 60
    $payerName=substr($claim->payerName($ins),0,60);
    $out .= "NM1" . // Loop 2330B Payer info for other insco.
      "*PR" .
      "*2" .
      "*" . $payerName .
      "*" .
      "*" .
      "*" .
      "*" .
      "*PI" .
      "*" . $claim->payerID($ins) .
      "~\n";

    // if (!$claim->payerID($ins)) {
    //   $log .= "*** CMS ID is missing for payer '" . $claim->payerName($ins) . "'.\n";
    // }

    // Payer address (N3 and N4) are added below so that Gateway EDI can
    // auto-generate secondary claims.  These do NOT appear in my copy of
    // the spec!  -- Rod 2008-06-12////They are in mine!!!!  --ARt 5010 update

  //2330B N3 and N4  5010 segments
      ++$edicount;
      $out .= "N3" .
        "*" . $claim->payerStreet($ins) .
        "~\n";
      //
      ++$edicount;
      $out .= "N4" .
        "*" . $claim->payerCity($ins) .
        "*" . $claim->payerState($ins) .
        "*" . $claim->payerZip($ins) .
        "~\n";
  

  } // End loops 2320/2330*.  The following is to draw attention to "Other Payer" elements that may be needed!!!!!!!!!!!!!!!!
  /////////////////////////////Around line 940 of this file, the correct code may already exist, but Ineed to evaluate it more closely
  //////////////////////////////or get more eyes on this..........................................
////////////////////////////!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
////!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!Need 'DATE-CLAIM CHECK OR REMITTANCE DATE' DTP SEGMENT HERE New Segment!!!!!!!!!!!!!
///!!!!!!!!!!!!!!!!     I have included the following commented-out lines of code, but we need the correct date variable for this segment!!!!!
 // ++$edicount;
 //   $out .= "DTP" .     // Date of remittance for Adjudication or Payment date
 //    "*573" .
 //     "*D8" .
 //     "*" . $claim->serviceDate() .////Need correct date variable here.........................!!!!!!!!!!!!!!!!!!!!!!!!!!
 //     "~\n";

////May need other payer segments here as well....unless I am completely wrong...-Art Eaton Art@OEMR.org
  $loopcount = 0;

  // Procedure loop starts here.
  //
  for ($prockey = 0; $prockey < $proccount; ++$prockey) {
    ++$loopcount;

    ++$edicount;
    $out .= "LX" .      // Loop 2400 LX Service Line. Page 398.
      "*$loopcount" .//LX01
      "~\n";//Seg Term

    ++$edicount;
    $out .= "SV1" .     // Professional Service. Page 400.
      "*HC:" . $claim->cptKey($prockey) .//SV101-1
      "*" . sprintf('%.2f', $claim->cptCharges($prockey)) .//SV102
      "*UN" .//SV103
      "*" . $claim->cptUnits($prockey) .//SV104
      "*" .//SV105
      "*" .//SV106 
      "*";///SV107////////////////COMPOSITE DIAGNOSIS CODE POINTER now REQUIRED.  What do we need here???!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $dia = $claim->diagIndexArray($prockey);
    $i = 0;
    foreach ($dia as $dindex) {
      if ($i) $out .= ':';
      $out .= $dindex;
      if (++$i >= 4) break;
    }
    $out .= "~\n";

    if (!$claim->cptCharges($prockey)) {
      $log .= "*** Procedure '" . $claim->cptKey($prockey) . "' has no charges!\n";
    }

    if (empty($dia)) {
      $log .= "*** Procedure '" . $claim->cptKey($prockey) . "' is not justified!\n";
    }

    ++$edicount;
    $out .= "DTP" .     // Date of Service. Page 435.
      "*472" .
      "*D8" .
      "*" . $claim->serviceDate() .
      "~\n";

    // AMT*AAE segment for Approved Amount from previous payer.
    // Medicare secondaries seem to require this.
    //
    for ($ins = $claim->payerCount() - 1; $ins > 0; --$ins) {
      if ($claim->payerSequence($ins) > $claim->payerSequence())
        continue; // payer is future, not previous
      $payerpaid = $claim->payerTotals($ins, $claim->cptKey($prockey));
      ++$edicount;
      $out .= "AMT" . // Approved amount per previous payer. Page 485.
        "*AAE" .
        "*" . sprintf('%.2f', $claim->cptCharges($prockey) - $payerpaid[2]) .
        "~\n";
      break;
    }

    // Loop 2410, Drug Information. Medicaid insurers seem to want this
    // with HCPCS codes.
    //
    $ndc = $claim->cptNDCID($prockey);
    if ($ndc) {
      ++$edicount;
      $out .= "LIN" . // Drug Identification. Page 500+ (Addendum pg 71).
        "*" .         // Per addendum, LIN01 is not used.
        "*N4" .
        "*" . $ndc .
        "~\n";

      if (!preg_match('/^\d\d\d\d\d-\d\d\d\d-\d\d$/', $ndc, $tmp)) {
        $log .= "*** NDC code '$ndc' has invalid format!\n";
      }

      ++$edicount;
      $tmpunits = $claim->cptNDCQuantity($prockey) * $claim->cptUnits($prockey);
      if (!$tmpunits) $tmpunits = 1;
      $out .= "CTP" . // Drug Pricing. Page 500+ (Addendum pg 74).
        "*" .
        "*" .
        "*" . sprintf('%.2f', $claim->cptCharges($prockey) / $tmpunits) .
        "*" . $claim->cptNDCQuantity($prockey) .
        "*" . $claim->cptNDCUOM($prockey) .
        "~\n";
    }

    // Loop 2420A, Rendering Provider (service-specific).
    // Used if the rendering provider for this service line is different
    // from that in loop 2310B.
    //
    if ($claim->providerNPI() != $claim->providerNPI($prockey)) {
      ++$edicount;
      $out .= "NM1" .       // Loop 2310B Rendering Provider
        "*82" .
        "*1" .
        "*" . $claim->providerLastName($prockey) .
        "*" . $claim->providerFirstName($prockey) .
        "*" . $claim->providerMiddleName($prockey) .
        "*" .
        "*";
      if ($claim->providerNPI($prockey)) { $out .=
        "*XX" .
        "*" . $claim->providerNPI($prockey);
      } else { $out .=
        "*XX" .
        "*" . $claim->providerNPI($prockey);/////////no ssn allowed!
        $log .= "*** Rendering provider has no NPI.\n";
      }
      $out .= "~\n";

      if ($claim->providerTaxonomy($prockey)) {
        ++$edicount;
        $out .= "PRV" .
          "*PE" . // PErforming provider
          "*ZZ" .
          "*" . $claim->providerTaxonomy($prockey) .
          "~\n";
      }

      // REF*1C is required here for the Medicare provider number if NPI was
      // specified in NM109.  Not sure if other payers require anything here.
      if ($claim->providerNumber($prockey)) {
        ++$edicount;
        $out .= "REF" .
          "*" . $claim->providerNumberType($prockey) .
          "*" . $claim->providerNumber($prockey) .
          "~\n";
      }
    }

    // Loop 2430, adjudication by previous payers.
    //
    for ($ins = 1; $ins < $claim->payerCount(); ++$ins) {
      if ($claim->payerSequence($ins) > $claim->payerSequence())
        continue; // payer is future, not previous

      $payerpaid = $claim->payerTotals($ins, $claim->cptKey($prockey));
      $aarr = $claim->payerAdjustments($ins, $claim->cptKey($prockey));

      if ($payerpaid[1] == 0 && !count($aarr)) {
        $log .= "*** Procedure '" . $claim->cptKey($prockey) .
          "' has no payments or adjustments from previous payer!\n";
        continue;
      }

      ++$edicount;
      $out .= "SVD" . // Service line adjudication. Page 554.
        "*" . $claim->payerID($ins) .
        "*" . $payerpaid[1] .
        "*HC:" . $claim->cptKey($prockey) .
        "*" .
        "*" . $claim->cptUnits($prockey) .
        "~\n";

      $tmpdate = $payerpaid[0];
      foreach ($aarr as $a) {
        ++$edicount;
        $out .= "CAS" . // Previous payer's line level adjustments. Page 558.
          "*" . $a[1] .
          "*" . $a[2] .
          "*" . $a[3] .
          "~\n";
        if (!$tmpdate) $tmpdate = $a[0];
      }

      if ($tmpdate) {
        ++$edicount;
        $out .= "DTP" . // Previous payer's line adjustment date. Page 566.
          "*573" .
          "*D8" .
          "*$tmpdate" .
          "~\n";
      }
    } // end loop 2430
  } // end this procedure

  ++$edicount;
  $out .= "SE" .        // SE Trailer
    "*$edicount" .
    "*0021" .
    "~\n";

  $out .= "GE" .        // GE Trailer
    "*1" .
    "*1" .
    "~\n";

  $out .= "IEA" .       // IEA Trailer
    "*1" .
    "*000000001" .
    "~\n";

  $log .= "\n";
  return $out;
}
?>
