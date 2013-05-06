<?php

/**
 * Description of TCMServicePlan
 *
 * @author Sam Likins
 */
class TCMServicePlan {

	public $formDirectory;
	public $sqlStatements;

	public $rootDirectory;
	public $encounterId;
	public $patientId;

	public $caseManagerId;

	public $servicePlanId;
	public $formData;
	public $formErrors = array();

	public function __construct($formDirectory) {
		global $rootdir, $encounter, $pid;

		$this->formDirectory = $formDirectory;

		$this->rootDirectory = $rootdir;
		$this->encounterId = $encounter;
		$this->patientId = $pid;
		$this->caseManagerId = $_SESSION['authUserID'];
		
	}

	public function loadExternal($fileName, $dataArray = null, $extractType = EXTR_OVERWRITE, $prefix = null) {
		if(!is_string($fileName))
			throw new \UnexpectedValueException('Provided fileName paramiter must be a string.');

		$fileName = $this->formDirectory.DIRECTORY_SEPARATOR.$fileName;
		if(!is_readable($fileName))
			throw new \InvalidArgumentException('Provided fileName is missing or not readable.');

		if(is_array($dataArray))
			extract($dataArray, $extractType, $prefix);

		return include($fileName);
	}

	public function loadSqlStatements($force = false) {
		if($this->sqlStatements === null || $force)
			$this->sqlStatements = $this->loadExternal('sql/servicePlan.config.php');
	}

	public function filterData($data, $keys, $map = false) {
		$data = array_intersect_key((array) $data, array_flip($keys));

		if($map === true) {
			$mapped = array();
			foreach($keys as $to => $from)
				if(array_key_exists($from, $data))
					$mapped[is_int($to) ? $from : $to] = $data[$from];
		} else {
			$mapped = $data;
		}

		return $mapped;
	}

	protected function reverseKeySort($a, $b) {
		$alen = strlen($a); $blen = strlen($b);
		return ($alen === $blen ? 0 : ($alen < $blen ? 1 : -1));
	}

	public function sqlStatement($query, $fields = null) {
		if(is_array($fields)) {
			uksort($fields, array($this, 'reverseKeySort'));

			foreach($fields as $fieldName => $fieldValue) {
				if($fieldValue === null)
					$fieldValue = 'NULL';
				elseif(!is_numeric($fieldValue))
					$fieldValue = '\''.mysql_real_escape_string($fieldValue).'\'';
				$query = str_replace(':'.$fieldName, $fieldValue, $query);
			}
		}

		// sqlStatement from library/sql.inc
		return sqlStatementNoLog($query);
	}

	public function sqlFetchArray($query, $fields = null) {
		$statment = $this->sqlStatement($query, $fields);

		// sqlFetchArray from library/sql.inc
		return sqlFetchArray($statment);
	}

	public function sqlFetchMultiArray($query, $fields = null) {
		$statment = $this->sqlStatement($query, $fields);
		$resultSet = array();

		// sqlFetchArray from library/sql.inc
		while($result = sqlFetchArray($statment))
			$resultSet[] = $result;

		return $resultSet;
	}

	public function sqlNewServicePlan() {
		if(!is_numeric($this->patientId))
			throw new \UnexpectedValueException('Patient Id must be an integer.');
		if(!is_numeric($this->caseManagerId))
			throw new \UnexpectedValueException('Case Manager Id must be an integer.');

		$this->loadSqlStatements();

		$this->formData['ServicePlan'] = $this->sqlFetchArray(
			$this->sqlStatements['NEW']['TCMServicePlan'],
			array(
				'patientId' => $this->patientId,
				'caseManagerId' => $this->caseManagerId,
			)
		);
		$this->formData['ServicePlans'] = $this->sqlFetchMultiArray(
			$this->sqlStatements['NEW']['PastTCMServicePlans'],
			array(
				'patientId' => $this->patientId,
			)
		);
		$this->formData['Assessments'] = $this->sqlFetchMultiArray(
			$this->sqlStatements['NEW']['PastTCMAssessments'],
			array(
				'patientId' => $this->patientId,
			)
		);
	}

	public function sqlViewServicePlan() {
		if(!is_numeric($this->servicePlanId))
			throw new \UnexpectedValueException('Service Plan Id must be an integer.');

		$this->loadSqlStatements();

		$this->formData['ServicePlan'] = $this->sqlFetchArray(
			$this->sqlStatements['READ']['TCMServicePlan'],
			array(
				'servicePlanId' => $this->servicePlanId,
			)
		);

		$formData =& $this->formData['ServicePlan'];

		$formData['Diagnosis'] = $this->sqlFetchMultiArray(
			$this->sqlStatements['READ']['TCMSPDiagnosis'],
			array(
				'servicePlanId' => $this->servicePlanId,
			)
		);

		$formData['Problems'] = $this->sqlFetchMultiArray(
			$this->sqlStatements['READ']['BehavioralHealthProblems'],
			array(
				'planId' => $this->servicePlanId,
			)
		);

		foreach($formData['Problems'] as &$problem) {
			$problem['Goals'] = $this->sqlFetchMultiArray(
				$this->sqlStatements['READ']['BehavioralHealthGoals'],
				array(
					'behavioralHealthProblemId' => $problem['Id'],
				)
			);

			foreach($problem['Goals'] as &$goals) {
				$goals['Objectives'] = $this->sqlFetchMultiArray(
					$this->sqlStatements['READ']['BehavioralHealthObjectives'],
					array(
						'behavioralHealthGoalId' => $goals['Id'],
					)
				);
			}

			$problem['Agents'] = $this->sqlFetchMultiArray(
				$this->sqlStatements['READ']['BehavioralHealthAgents'],
				array(
					'behavioralHealthProblemId' => $problem['Id'],
				)
			);
		}

		$this->formData['FunctionalTypes'] = $this->sqlFetchMultiArray(
			$this->sqlStatements['READ']['TCMAFunctionalTypes']
		);

		$this->formData['AgentsTypes'] = $this->sqlFetchMultiArray(
			$this->sqlStatements['READ']['BehavioralHealthAgentsTypes']
		);

	}

	public function sqlReportServicePlan() {
		if(!is_numeric($this->servicePlanId))
			throw new \UnexpectedValueException('Service Plan Id must be an integer.');

		$this->loadSqlStatements();

		$servicePlan = $this->sqlFetchArray(
			$this->sqlStatements['READ']['TCMServicePlan'],
			array(
				'servicePlanId' => $this->servicePlanId,
			)
		);

		$this->formData['ServicePlan'] = $this->filterData(
			$servicePlan,
			array(
				'Id',
				'Type',
				'CaseManagerName',
				'CaseManagerSupervisorName',
				'DateWritten',
				'DateComplete',
			)
		);
	}

	public function saveNewServicePlan($userAuthorized = 0) {
		// formSubmit from library/api.inc
		$recordId = formSubmit(
			'form_TCMServicePlan',
			$this->filterData(
				$this->formData,
				array(
					'TCMAssessmentId' => 'Assessment',
					'TCMServicePlanId' => 'ServicePlan',
					'CaseManagerName',
					'CaseManagerSupervisorId',
					'CaseManagerSupervisorName',
					'ClientName',
					'MedicaidId',
					'Type',
				),
				true
			),
			null,
			$userAuthorized
		);

		addForm(
			$this->encounterId,
			'Targeted Case Management Service Plan',
			$recordId,
			'TCMServicePlan',
			$this->patientId,
			$userAuthorized
		);

		return $recordId;
	}

	public function sqlSaveServicePlan() {
		if(!is_numeric($this->servicePlanId))
			throw new \UnexpectedValueException('Service Plan Id must be an integer.');

		$this->loadSqlStatements();

//echo '[\'UPDATE\'][\'TCMServicePlan\']'.PHP_EOL;
		$this->sqlStatement(
			$this->sqlStatements['UPDATE']['TCMServicePlan'],
			array(
				'Id' => $this->formData->Id,
				'DateWritten' => $this->formData->DateWritten,
				'DateComplete' => $this->formData->DateComplete,
				'DiagnosisSource' => $this->formData->DiagnosisSource,
				'DischargeRate' => $this->formData->DischargeRate,
				'ManagerNote' => $this->formData->ManagerNote,
			)
		);
		$this->sqlSaveDiagnosis();
		$this->sqlSaveProblems();
	}

	public function sqlSaveDiagnosis() {
		if(property_exists($this->formData, 'Diagnosis')
			&& is_array($this->formData->Diagnosis)
		) {
			foreach($this->formData->Diagnosis as $record) {
				if(!(property_exists($record, 'Id') && $record->Id > 0)) {
					// CREATE
//echo '[\'CREATE\'][\'TCMSPDiagnosis\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['CREATE']['TCMSPDiagnosis'],
						array(
							'TCMServicePlanId' => $this->formData->Id,
							'ListsId' => $record->ListsId,
							'ICD' => $record->ICD,
							'Axis' => $record->Axis,
							'Code' => $record->Code,
							'Description' => $record->Description,
						)
					);
				} elseif(property_exists($record, '_destroy') && $record->_destroy === true) {
					// DELETE
//echo '[\'DELETE\'][\'TCMSPDiagnosis\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['TCMSPDiagnosis'],
						array(
							'Id' => $record->Id,
							'TCMServicePlanId' => $this->formData->Id,
						)
					);
				} else {
					// UPDATE
//echo '[\'UPDATE\'][\'TCMSPDiagnosis\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['UPDATE']['TCMSPDiagnosis'],
						array(
							'Id' => $record->Id,
							'TCMServicePlanId' => $this->formData->Id,
							'ListsId' => $record->ListsId,
							'ICD' => $record->ICD,
							'Axis' => $record->Axis,
							'Code' => $record->Code,
							'Description' => $record->Description,
						)
					);
				}
			}
		}
	}

	public function sqlSaveProblems() {
		if(property_exists($this->formData, 'Problems')
			&& is_array($this->formData->Problems)
		) {
			foreach($this->formData->Problems as $record) {
				if(!(property_exists($record, 'Id') && $record->Id > 0)) {
//echo '[\'CREATE\'][\'BehavioralHealthProblems\']'.PHP_EOL;
					// CREATE
					$this->sqlStatement(
						$this->sqlStatements['CREATE']['BehavioralHealthProblems'],
						array(
							'TCMServicePlanId' => $this->formData->Id,
							'Area' => $record->Area,
							'AreaId' => $record->AreaId,
							'Problem' => $record->Problem,
							'Activities' => $record->Activities,
						)
					);
					$record->Id = getSqlLastID();
				} elseif(property_exists($record, '_destroy') && $record->_destroy === true) {
					// DELETE
//echo '[\'DELETE\'][\'BehavioralHealthProblems\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthProblems'],
						array(
							'Id' => $record->Id,
							'TCMServicePlanId' => $this->formData->Id,
						)
					);
					// Remove All Problems Objectives
//echo '[\'CREATE\'][\'BehavioralHealthProblemsObjectives\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthProblemsObjectives'],
						array(
							'BehavioralHealthProblemId' => $record->Id,
						)
					);
					// Remove All Problems Goals
//echo '[\'CREATE\'][\'BehavioralHealthProblemsGoals\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthProblemsGoals'],
						array(
							'BehavioralHealthProblemId' => $record->Id,
						)
					);
					unset($record->Goals);
					// Remove All Problems Agents
//echo '[\'CREATE\'][\'BehavioralHealthProblemsAgents\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthProblemsAgents'],
						array(
							'BehavioralHealthProblemId' => $record->Id,
						)
					);
					unset($record->Agents);
				} else {
					// UPDATE
//echo '[\'UPDATE\'][\'BehavioralHealthProblems\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['UPDATE']['BehavioralHealthProblems'],
						array(
							'Id' => $record->Id,
							'TCMServicePlanId' => $this->formData->Id,
							'Area' => $record->Area,
							'AreaId' => $record->AreaId,
							'Problem' => $record->Problem,
							'Activities' => $record->Activities,
						)
					);
				}
				$this->sqlSaveProblemsGoals($record);
				$this->sqlSaveProblemsAgents($record);
			}
		}
	}

	public function sqlSaveProblemsGoals($problem) {
		if(property_exists($problem, 'Goals')
			&& is_array($problem->Goals)
		) {
			foreach($problem->Goals as $record) {
				if(!(property_exists($record, 'Id') && $record->Id > 0)) {
					// CREATE
//echo '[\'CREATE\'][\'BehavioralHealthGoals\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['CREATE']['BehavioralHealthGoals'],
						array(
							'BehavioralHealthProblemId' => $problem->Id,
							'Goal' => $record->Goal,
						)
					);
					$record->Id = getSqlLastID();
				} elseif(property_exists($record, '_destroy') && $record->_destroy === true) {
					// DELETE
//echo '[\'DELETE\'][\'BehavioralHealthGoals\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthGoals'],
						array(
							'Id' => $record->Id,
							'BehavioralHealthProblemId' => $problem->Id,
						)
					);
					// Remove All Problems Goals Objectives
//echo '[\'DELETE\'][\'BehavioralHealthGoalsObjectives\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthGoalsObjectives'],
						array(
							'BehavioralHealthGoalId' => $record->Id,
						)
					);
					unset($record->Objectives);
				} else {
					// UPDATE
//echo '[\'UPDATE\'][\'BehavioralHealthGoals\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['UPDATE']['BehavioralHealthGoals'],
						array(
							'Id' => $record->Id,
							'BehavioralHealthProblemId' => $problem->Id,
							'Goal' => $record->Goal,
						)
					);
				}
				$this->sqlSaveProblemsGoalsObjectives($record);
			}
		}
	}

	public function sqlSaveProblemsGoalsObjectives($goal) {
		if(property_exists($goal, 'Objectives')
			&& is_array($goal->Objectives)
		) {
			foreach($goal->Objectives as $record) {
				if(!(property_exists($record, 'Id') && $record->Id > 0)) {
					// CREATE
//echo '[\'CREATE\'][\'BehavioralHealthObjectives\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['CREATE']['BehavioralHealthObjectives'],
						array(
							'BehavioralHealthGoalId' => $goal->Id,
							'Objective' => $record->Objective,
							'TargetDate' => $record->TargetDate,
							'Status' => $record->Status,
							'ProgressRate' => $record->ProgressRate,
						)
					);
				} elseif(property_exists($record, '_destroy') && $record->_destroy === true) {
					// DELETE
//echo '[\'DELETE\'][\'BehavioralHealthObjectives\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthObjectives'],
						array(
							'Id' => $record->Id,
							'BehavioralHealthGoalId' => $goal->Id,
						)
					);
				} else {
					// UPDATE
//echo '[\'UPDATE\'][\'BehavioralHealthObjectives\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['UPDATE']['BehavioralHealthObjectives'],
						array(
							'Id' => $record->Id,
							'BehavioralHealthGoalId' => $goal->Id,
							'Objective' => $record->Objective,
							'TargetDate' => $record->TargetDate,
							'Status' => $record->Status,
							'ProgressRate' => $record->ProgressRate,
						)
					);
				}
			}
		}
	}

	public function sqlSaveProblemsAgents($problem) {
		if(property_exists($problem, 'Agents')
			&& is_array($problem->Agents)
		) {
			foreach($problem->Agents as $record) {
				if(!(property_exists($record, 'Id') && $record->Id > 0)) {
					// CREATE
//echo '[\'CREATE\'][\'BehavioralHealthAgents\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['CREATE']['BehavioralHealthAgents'],
						array(
							'BehavioralHealthProblemId' => $problem->Id,
							'Type' => $record->Type,
							'TypeOther' => $record->TypeOther,
							'Agency' => $record->Agency,
							'Agent' => $record->Agent,
						)
					);
				} elseif(property_exists($record, '_destroy') && $record->_destroy === true) {
					// DELETE
//echo '[\'DELETE\'][\'BehavioralHealthAgents\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['DELETE']['BehavioralHealthAgents'],
						array(
							'Id' => $record->Id,
							'BehavioralHealthProblemId' => $problem->Id,
						)
					);
				} else {
					// UPDATE
//echo '[\'UPDATE\'][\'BehavioralHealthAgents\']'.PHP_EOL;
					$this->sqlStatement(
						$this->sqlStatements['UPDATE']['BehavioralHealthAgents'],
						array(
							'Id' => $record->Id,
							'BehavioralHealthProblemId' => $problem->Id,
							'Type' => $record->Type,
							'TypeOther' => $record->TypeOther,
							'Agency' => $record->Agency,
							'Agent' => $record->Agent,
						)
					);
				}
			}
		}
	}

	public function checkSaveServicePlan() {
		if(!property_exists($this->formData, 'Id'))
			throw new \UnexpectedValueException('Service Plan Id is missing from save request.');

		if(!is_numeric($this->formData->Id))
			throw new \UnexpectedValueException('Service Plan Id must be an integer.');

		if($this->formData->Id != $this->servicePlanId)
			throw new \UnexpectedValueException('Service Plan Id is invalid.');
	}

	public function saveViewServicePlan() {
		$this->checkSaveServicePlan();
		$this->sqlSaveServicePlan();
	}

	public function displayNewServicePlan() {
		$this->loadExternal('templates/new.phtml');
	}

	public function displayViewServicePlan() {
		$this->loadExternal('templates/view.phtml');
	}

	public function displayReportServicePlan() {
		$this->loadExternal('templates/report.phtml');
	}

	public function displayFullReportServicePlan() {
		$this->loadExternal('templates/full-report.phtml');
	}

}