<?php

/*
 * For information on Form Development view: README
 */

// openemr/interface/globals.php
include_once('../../globals.php');

// $src = path to openemr/library directory
include_once($srcdir.'/api.inc');
//include_once($srcdir.'/options.inc.php');
//include_once($srcdir.'/forms.inc');

include(dirname(__FILE__).'/classes/TCMAssessment.php');

$tcmAssessment = new TCMAssessment(dirname(__FILE__));

if(array_key_exists('id', $_GET))
	$tcmAssessment->assessmentId = $_GET['id'];

//$tcmAssessment->sqlViewAssessment();

$tcmAssessment->displayViewAssessment();