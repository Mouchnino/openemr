function ServicePlanViewModel() {
	var self = this;

	this.Configuration = {
		AjaxUri: '',
		WebRoot: '',
		ExitUri: '',
		ServicePlanId: ''
	};

	this.ActivityWait = ko.observable('Loading . . .');
	this.ActivityWaitSet = function(message) {
		message = message || false;
		self.ActivityWait(message);
	};

	this.ErrorAlert = ko.observable(false);
	this.ErrorAlertSet = function(message) {
		message = message || false;
		self.ErrorAlert(message);
	};

	this.ObjectiveStatus = [
		{ Value: 'new', Label: 'New'},
		{ Value: 'ongoing', Label: 'Ongoing'},
		{ Value: 'deferred', Label: 'Deferred'},
		{ Value: 'achieved', Label: 'Achieved'}
	];

	this.AxisOptions = [
		{ Value: 'i', Label: 'Axis I'},
		{ Value: 'ii', Label: 'Axis II'},
		{ Value: 'iii', Label: 'Axis III'},
		{ Value: 'iv', Label: 'Axis IV'},
		{ Value: 'v', Label: 'Axis V'}
	];

	// Non-editable catalog data - Comes from the server
	this.FunctionalTypes = ko.observableArray();
	this.FunctionalTypesAdd = function(data) {
		type = new FunctionalType();
		type.Id(data.Id);
		type.Label(data.Label);
		type.Priority(data.Priority);
		type.Disabled(data.Disabled);
		type.Description(data.Description);
		self.FunctionalTypes.push(type);
	};

	// Non-editable catalog data - Comes from the server
	this.AgentsTypes = ko.observableArray();
	this.AgentsTypesAdd = function(data) {
		type = new AgentType();
		type.Id(data.Id);
		type.Label(data.Label);
		type.Priority(data.Priority);
		type.Disabled(data.Disabled);
		self.AgentsTypes.push(type);
	};

	this.ServicePlan = new ServicePlan;
	this.ServicePlanCreate = function(data) {
		servicePlan = self.ServicePlan;
		servicePlan.Id(data.Id);
		servicePlan.AssessmentId(data.AssessmentId);
		servicePlan.ServicePlanId(data.ServicePlanId);
		servicePlan.Type(data.Type);
		servicePlan.ClientId(data.ClientId);
		servicePlan.ClientName(data.ClientName);
		servicePlan.ClientBirth(data.ClientBirth);
		servicePlan.MedicaidId(data.MedicaidId);
		servicePlan.DateWritten(data.DateWritten);
		servicePlan.DateComplete(data.DateComplete);
		servicePlan.CaseManagerId(data.CaseManagerId);
		servicePlan.CaseManagerName(data.CaseManagerName);
		servicePlan.CaseManagerSupervisorId(data.CaseManagerSupervisorId);
		servicePlan.CaseManagerSupervisorName(data.CaseManagerSupervisorName);
		servicePlan.DiagnosisSource(data.DiagnosisSource);
		servicePlan.DischargeRate(data.DischargeRate);
		servicePlan.ManagerNote(data.ManagerNote);
		data.Diagnosis.forEach(function(diagnosis) {
			servicePlan.DiagnosisAdd(diagnosis);
		});
		while(servicePlan.Diagnosis().length < 5) {
			servicePlan.DiagnosisAddNew();
		};
		data.Problems.forEach(function(problem) {
			servicePlan.ProblemsAdd(problem);
		});
	};

	this.Load = function(configuration) {
		if(configuration !== undefined)
			self.Configuration = configuration;

		var AjaxUri = self.Configuration.AjaxUri
			+ '?action=read&id='
			+ self.Configuration.ServicePlanId;

		if('TestMode' in self.Configuration && self.Configuration.TestMode === true)
			AjaxUri = AjaxUri + '&test=true';

		self.ActivityWait('Loading Data . . .');
		$.ajax({
			url: AjaxUri,
			dataType: 'json',
			success: function(response) {
				if('status' in response &&
					response.status === 'success' &&
					'data' in response
				) {
					if('FunctionalTypes' in response.data) {
						self.FunctionalTypes.removeAll();
						response.data.FunctionalTypes.forEach(
							function(type) { self.FunctionalTypesAdd(type); }
						);
					}
					if('AgentsTypes' in response.data) {
						self.AgentsTypes.removeAll();
						response.data.AgentsTypes.forEach(
							function(type) { self.AgentsTypesAdd(type); }
						);
						self.AgentsTypesAdd({
							Id: "0",
							Label: "Other",
							Priority: "100",
							Disabled: "0"							
						});
					}
					if('ServicePlan' in response.data) {
						self.ServicePlanCreate(response.data.ServicePlan);
					}
				} else {
					self.ErrorAlertSet('Service Plan could not be retrieved from the server.');
				}
				self.ActivityWait(false);
			},
			error: function() {
				self.ErrorAlertSet('Service Plan could not be retrieved from the server.');
				self.ActivityWait(false);
			}
		});
	};

	this.ConvertForSave = function() {
		self.ActivityWait('Converting Data . . .');

		data = ko.toJS(self.ServicePlan);

		delete data.dateOffset;
		delete data.dateString;
		delete data.dateTimeString;

		delete data.DiagnosisActive;
		delete data.DiagnosisAdd;
		delete data.DiagnosisAddNew;
		delete data.DiagnosisRemove;
		data.DiagnosisNew = [];
		data.Diagnosis.forEach(function(obj) {
			if(obj.Id !== undefined
				|| !(obj.Axis === undefined
					|| obj.Axis === '')
				|| !(obj.Code === undefined
					|| obj.Code === '')
				|| !(obj.Description === undefined
					|| obj.Description === '')
			)
				data.DiagnosisNew.push(obj);
		});
		data.Diagnosis = data.DiagnosisNew;
		delete data.DiagnosisNew;

		delete data.ManagerNoteVisible;
		delete data.ManagerNoteVisibleToggle;

		delete data.ProblemsAdd;
		delete data.ProblemsAddNew;
		delete data.ProblemsRemove;
		delete data.ProblemsSelected;

		delete data.TypeDisplay;

		data.Problems.forEach(function(problem) {
			problem.Agents.forEach(function(agent) {
				delete agent.TypeToggle;
			});
			delete problem.AgentsActive;
			delete problem.AgentsAdd;
			delete problem.AgentsAddNew;
			delete problem.AgentsRemove;

			problem.Goals.forEach(function(goal) {
				delete goal.ObjectivesAdd;
				delete goal.ObjectivesAddNew;
				delete goal.ObjectivesRemove;
			});
			delete problem.GoalsAdd;
			delete problem.GoalsAddNew;
			delete problem.GoalsRemove;

			delete problem.Type;
		});
		self.ActivityWait(false);
		return data;
	};

	this.Save = function() {
		var AjaxUri = self.Configuration.AjaxUri
			+ '?action=update&id='
			+ self.Configuration.ServicePlanId;

		if('TestMode' in self.Configuration && self.Configuration.TestMode === true)
			AjaxUri = AjaxUri + '&test=true';
		servicePlanJson = ko.toJSON(self.ConvertForSave());
		self.ActivityWait('Saving Data . . .');

		$.post(AjaxUri,
			servicePlanJson,
			function(response) {
				try {
					response = ko.utils.parseJson(response);
				} catch(err) {
					self.ErrorAlertSet('Service Plan could not be saved, server error.');
				}
				if(typeof response === 'object') {
					if('status' in response && response.status === 'success') {
						self.Exit();
					} else if ('message' in response) {
						self.ErrorAlertSet('Service Plan could not be saved: ' + response.message);
					} else {
						self.ErrorAlertSet('Service Plan could not be saved.');
					}
				}
				self.ActivityWait(false);
			}
		);
	};

	this.Exit = function() {
		document.location.href = self.Configuration.ExitUri;
	};
}