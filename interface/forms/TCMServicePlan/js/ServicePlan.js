function ServicePlan() {
	var self = this;

	this.dateOffset = function(date, offsetYears, offsetMonth, offsetDate) {
		date = new Date(date) || new Date();
	    offsetYears = offsetYears || 0;
	    offsetMonth = offsetMonth || 0;
	    offsetDate = offsetDate || 0;
		return new Date(date.getFullYear() + offsetYears, date.getMonth() + offsetMonth, date.getDate() + offsetDate);
	};
	this.dateString = function(date) {
		date = new Date(date) || new Date();
		// YYYY-MM-DD
		return date.getFullYear() + '-' +
			('0' + date.getMonth()).slice(-2) + '-' +
			('0' + date.getDate()).slice(-2);
	};
	this.dateTimeString = function(date) {
		date = new Date(date) || new Date();
		// YYYY-MM-DD HH:MM:ss
		return date.getFullYear() + '-' +
			('0' + date.getMonth()).slice(-2) + '-' +
			('0' + date.getDate()).slice(-2) + ' ' +
			('0' + date.getHours()).slice(-2) + ':' +
			('0' + date.getMinutes()).slice(-2) + ':' +
			('0' + date.getSeconds()).slice(-2);
	};

	// Assessment Id
	this.Id = ko.observable();
	this.AssessmentId = ko.observable();
	this.ServicePlanId = ko.observable();

	// Assessment Type
	this.Type = ko.observable();
	this.TypeDisplay = ko.computed(function() {
		return self.Type === 'UPDATE' ? 'Annual Update' : 'Initial';
	});

	// Assessment Form Information
	this.CaseManagerId = ko.observable();
	this.CaseManagerName = ko.observable();
	this.CaseManagerSupervisorId = ko.observable();
	this.CaseManagerSupervisorName = ko.observable();
	this.ClientId = ko.observable();
	this.ClientName = ko.observable();
	this.ClientBirth = ko.observable();
	this.MedicaidId = ko.observable();

	this.DateWritten = ko.observable();
	this.DateComplete = ko.observable();
	this.DiagnosisSource = ko.observable();
	this.DischargeRate = ko.observable();

	this.ManagerNote = ko.observable();
	this.ManagerNoteVisible = ko.observable(false);
	this.ManagerNoteVisibleToggle = function() {
		self.ManagerNoteVisible(!self.ManagerNoteVisible());
	};

	this.Diagnosis = ko.observableArray();
	this.DiagnosisActive = ko.computed(function() {
		return ko.utils.arrayFilter(
			self.Diagnosis(),
			function(diagnosis) { return !diagnosis._destroy; }
		);
	});
	this.DiagnosisAdd = function(data) {
		diagnosis = new DiagnosisRecord;
		diagnosis.Id(data.Id);
		diagnosis.ListsId(data.ListsId);
		diagnosis.ICD(data.ICD);
		diagnosis.Axis(data.Axis);
		diagnosis.Code(data.Code);
		diagnosis.Description(data.Description);
		self.Diagnosis.push(diagnosis);
	};
	this.DiagnosisAddNew = function() {
		self.Diagnosis.push(new DiagnosisRecord);
	};
	this.DiagnosisRemove = function(diagnosis) {
		if(diagnosis.Id() === undefined)
			self.Diagnosis.remove(diagnosis);
		else
			self.Diagnosis.destroy(diagnosis);
	};

	this.Problems = ko.observableArray();
	this.ProblemsSelected = ko.observable();
	this.ProblemsAdd = function(data) {
		problem = new ProblemRecord;
		problem.Id(data.Id);
		problem.Area(data.Area);
		problem.AreaId(data.AreaId);
		problem.Problem(data.Problem);
		problem.Activities(data.Activities);
		data.Goals.forEach(function(goal) {
			problem.GoalsAdd(goal);
		});
		data.Agents.forEach(function(agent) {
			problem.AgentsAdd(agent);
		});
		problem.Type(ServicePlanVM.FunctionalTypes().filter(
			function(func) {
				return func.Id() === problem.AreaId();
			}
		).slice(-1)[0]);
		self.Problems.push(problem);
	};
	this.ProblemsAddNew = function() {
		if(self.ProblemsSelected() !== undefined) {
			problem = new ProblemRecord;
			problem.Type(self.ProblemsSelected());
			problem.AreaId(problem.Type().Id());
			problem.Area(problem.Type().Label());
			self.Problems.push(problem);
			self.ProblemsSelected(undefined);
		}
	};
	this.ProblemsRemove = function(problem) {
		if(problem.Id() === undefined)
			self.Problems.remove(problem);
		else
			self.Problems.destroy(problem);
	};
}