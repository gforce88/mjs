$().ready(function() {
	$("#studentId").on('input', function(e) {
		$.getJSON("/student/findstujson?inx=" + this.value, function(json) {
			if (json.err==0) {
				alert("student not exist");
				$("#firstName").val("");
				$("#lastName").val("");
				$("#phone").val("");
				$("#email").val("");

			} else {
				$("#firstName").val(json.firstName);
				$("#lastName").val(json.lastName);
				$("#phone").val(json.phone);
				$("#email").val(json.email);
			}
		});
	});
});