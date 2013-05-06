function AssessmentViewModel() {
	var self = this;

	this.Configuration = {
		AjaxUri: '',
		WebRoot: '',
		ExitUri: '',
		AssessmentId: ''
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

    // Non-editable catalog data - Comes from the server
	this.SafetyTypes = ko.observableArray();
	this.SafetyTypesAdd = function(data) {
		type = new SafetyType();
		type.Id(data.Id);
		type.Label(data.Label);
		type.Priority(data.Priority);
		type.Disabled(data.Disabled);
		self.SafetyTypes.push(type);
	};

	// Non-editable catalog data - Comes from the server
	this.CurrentServicesTypes = ko.observableArray();
	this.CurrentServicesTypesAdd = function(data) {
		type = new CurrentServicesType();
		type.Id = data.Id;
		type.Label = data.Label;
		type.Priority = data.Priority;
		type.Disabled = data.Disabled;
		self.CurrentServicesTypes.push(type);
	};

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

	// Assessment form data - Comes from the server
	this.Assessment = new Assessment;
	this.AssessmentCreate = function(data) {
		assessment = self.Assessment;
		assessment.Id(data.Id);
		assessment.Type(data.Type);
		assessment.CaseManagerId(data.CaseManagerId);
		assessment.CaseManagerName(data.CaseManagerName);
		assessment.CaseManagerSupervisorId(data.CaseManagerSupervisorId);
		assessment.CaseManagerSupervisorName(data.CaseManagerSupervisorName);
		assessment.ClientId(data.ClientId);
		assessment.ClientName(data.ClientName);
		assessment.ClientBirth(data.ClientBirth);
		assessment.MedicaidId(data.MedicaidId);
		assessment.AdmitDate(data.AdmitDate);
		assessment.HomeVisitDates(data.HomeVisitDates);
		assessment.ManagerNote(data.ManagerNote);
		assessment.ManagerNoteVisible(data.ManagerNote === '');
		assessment.Encounter.ReportDate(data.Encounter.ReportDate.slice(0, 10));
		assessment.Encounter.ProblemReason(data.Encounter.ProblemsReason);
		assessment.Sources.removeAll();
		data.Sources.forEach(function(source) {
			assessment.SourcesAdd(source);
		});
		assessment.FamilyHistory(data.FamilyHistory);
		assessment.TreatmentHistory.removeAll();
		data.TreatmentHistory.forEach(function(data) {
			assessment.TreatmentHistoryAdd(data);
		});
		assessment.MedicalHistory.removeAll();
		data.MedicalHistory.forEach(function(data) {
			assessment.MedicalHistoryAdd(data);
		});
		assessment.Medications.removeAll();
		data.Medications.forEach(function(data) {
			assessment.MedicationsAdd(data);
		});
		assessment.EducationalHistory.removeAll();
		data.EducationalHistory.forEach(function(data) {
			assessment.EducationalHistoryAdd(data);
		});
		assessment.SubstanceAbuse.removeAll();
		data.SubstanceAbuse.forEach(function(data) {
			assessment.SubstanceAbuseAdd(data);
		});
		assessment.VocationalHistory(data.VocationalHistory);
		assessment.LegalDependencyHistory(data.LegalDependencyHistory);
		assessment.SignificantRelationships(data.SignificantRelationships);
		assessment.PotentialStrengths.Client(data.PotentialStrengths.Client);
		assessment.PotentialStrengths.Parent(data.PotentialStrengths.Parent);
		assessment.PotentialStrengths.Family(data.PotentialStrengths.Family);
		assessment.ResourcesAvailable(data.ResourcesAvailable);
		assessment.Safety.Other(data.Safety.Other);
		assessment.Safety.Description(data.Safety.Description);
		assessment.Safety.Plan(data.Safety.Plan);
		assessment.Safety.Types.removeAll();
		self.SafetyTypes().forEach(function(type) {
			safeData = data.Safety.Types.filter(function(safe) {
				return safe.TypeId === type.Id();
			});
			if(safeData.length > 0) {
				assessment.SafetyAdd({
					Id: safeData[0].Id,
					TypeId: type.Id(),
					Type: type,
					Selected: true
				});
			} else {
				assessment.SafetyAdd({
					Id: null,
					TypeId: type.Id(),
					Type: type,
					Selected: false
				});
			}
		});
		assessment.CurrentServices.removeAll();
		data.CurrentServices.forEach(function(data) {
			assessment.CurrentServicesAdd(data);
		});
		assessment.Functional.removeAll();
		self.FunctionalTypes().forEach(function(type) {
			funcData = data.Functional.filter(function(func) {
				return func.TypeId === type.Id();
			});
			if(funcData.length > 0) {
				funcData = funcData[0];
				funcData.Willingness = (funcData.Willingness === '1');
			} else {
				funcData = {
					Id: undefined,
					TypeId: type.Id(),
					ServiceNeeds: 'no',
					Willingness: false,
					NeedSummary: null,
					ActivityRecommendations: null
				};
			}
			assessment.FunctionalAdd(funcData);
		});
	};

	this.Load = function(configuration) {
		if(configuration !== undefined)
			self.Configuration = configuration;

		var AjaxUri = self.Configuration.AjaxUri
			+ '?action=read&id='
			+ self.Configuration.AssessmentId;

		if('TestMode' in self.Configuration && self.Configuration.TestMode === true)
			AjaxUri = AjaxUri + '&test=true';

		self.ActivityWait('Loading Data . . .');
		$.ajax({
			url: AjaxUri,
			dataType: 'json',
			success: function(response) {
				if('status' in response && response.status === 'success') {
					if('SafetyTypes' in response.data) {
						self.SafetyTypes.removeAll();
						response.data.SafetyTypes.forEach(
							function(type) { self.SafetyTypesAdd(type); }
						);
					}
					if('CurrentServicesTypes' in response.data) {
						self.CurrentServicesTypes.removeAll();
						response.data.CurrentServicesTypes.forEach(
							function(type) { self.CurrentServicesTypesAdd(type); }
						);
						self.CurrentServicesTypesAdd({
							Id: "0",
							Label: "Other",
							Priority: "100",
							Disabled: "0"							
						});
					}
					if('FunctionalTypes' in response.data) {
						self.FunctionalTypes.removeAll();
						response.data.FunctionalTypes.forEach(
							function(type) { self.FunctionalTypesAdd(type); }
						);
					}
					if('Assessment' in response.data) {
						self.AssessmentCreate(response.data.Assessment);
					}
				} else {
					self.ErrorAlertSet('Assessment could not be retrieved from the server.');
				}
				self.ActivityWait(false);
			},
			error: function() {
				self.ErrorAlertSet('Assessment could not be retrieved from the server.');
				self.ActivityWait(false);
			}
		});
	};

	this.ConvertForSave = function() {
		self.ActivityWait('Converting Data . . .');

		data = ko.toJS(self.Assessment);

		delete data.dateOffset;
		delete data.dateString;
		delete data.dateTimeString;
		data.CurrentServices.forEach(function(obj) {
			if(obj.Type !== '0') {
				obj.TypeOther = '';
			}
			delete obj.TypeToggle;
		});
		delete data.CurrentServicesActive;
		delete data.CurrentServicesAdd;
		delete data.CurrentServicesAddNew;
		delete data.CurrentServicesRemove;
		delete data.EducationalHistoryActive;
		delete data.EducationalHistoryAdd;
		delete data.EducationalHistoryAddNew;
		delete data.EducationalHistoryRemove;
		delete data.EducationalHistorySources;
		data.FunctionalNew = [];
		data.Functional.forEach(function(obj) {
			delete obj.Type;
			if(obj.ServiceNeeds === 'some'
				|| obj.ServiceNeeds === 'immediate'
			) {
				if(obj.Willingness === false) {
					obj.NeedSummary = '';
					obj.ActivityRecommendations = '';
				}
				data.FunctionalNew.push(obj);
			} else if(obj.Id !== undefined) {
				obj._destroy = true;
				data.FunctionalNew.push(obj);
			}
		});
		data.Functional = data.FunctionalNew;
		delete data.FunctionalNew;
		delete data.FunctionalAdd;
		delete data.FamilyHistorySources;
		delete data.LegalDependencyHistorySources;
		delete data.MedicalHistoryActive;
		delete data.MedicalHistoryAdd;
		delete data.MedicalHistoryAddNew;
		delete data.MedicalHistoryRemove;
		delete data.MedicalHistorySources;
		delete data.MedicationsActiveCurrent;
		delete data.MedicationsActivePast;
		delete data.MedicationsAdd;
		delete data.MedicationsAddNewCurrent;
		delete data.MedicationsAddNewPast;
		delete data.MedicationsCurrent;
		delete data.MedicationsCurrentSources;
		delete data.MedicationsPast;
		delete data.MedicationsPastSources;
		delete data.MedicationsRemove;
		data.Safety.Types.forEach(function(obj) {
			delete obj.Type;
			if("_change" in obj && obj._change === true) {
				obj._destroy = true;
				delete obj._change;
			}
		});
		delete data.SafetyAdd;
		data.Sources.forEach(function(obj) {
			delete obj.LinksActive;
			delete obj.LinksAdd;
			delete obj.LinksRemove;
			delete obj.OptionsText;
		});
		delete data.SourcesActive;
		delete data.SourcesAdd;
		delete data.SourcesAddNew;
		delete data.SourcesRemove;
		delete data.SubstanceAbuseAge;
		delete data.SubstanceAbuseActive;
		delete data.SubstanceAbuseAdd;
		delete data.SubstanceAbuseAddNew;
		delete data.SubstanceAbuseRemove;
		delete data.SubstanceAbuseSources;
		delete data.TreatmentHistoryActive;
		delete data.TreatmentHistoryAdd;
		delete data.TreatmentHistoryAddNew;
		delete data.TreatmentHistoryRemove;
		delete data.TreatmentHistorySources;
		delete data.VocationalHistorySources;
		return data;
	};

	this.Save = function() {
		var AjaxUri = self.Configuration.AjaxUri
			+ '?action=update&id='
			+ self.Configuration.AssessmentId;

		if('TestMode' in self.Configuration && self.Configuration.TestMode === true)
			AjaxUri = AjaxUri + '&test=true';

		assessmentJson = ko.toJSON(self.ConvertForSave());

		self.ActivityWait('Saving Data . . .');

		$.post(AjaxUri,
			assessmentJson,
			function(response) {
				response = ko.utils.parseJson(response);
				if('status' in response && response.status === 'success') {
				} else if ('message' in response) {
					self.ErrorAlertSet('Assessment could not be saved: ' + response.message);
				} else {
					self.ErrorAlertSet('Assessment could not be saved.');
				}
				self.ActivityWait(false);
			}
		);
	};

	this.Exit = function() {
		document.location.href = self.Configuration.ExitUri;
	};
};