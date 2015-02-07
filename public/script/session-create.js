$().ready(function() {
	$("#studentId").on('blur', function(e) {
		$.getJSON("/student/findstujson?inx=" + this.value, function(json) {
			if (json.err==0) {
				alert("student account is not exist or suspend!");
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

function deletesession(inx){
	$.getJSON("/session/delete?inx=" + inx, function(json) {
		if (json.err==0) {
			alert("session which started can not be deleted");
		} else {
			window.location.reload();
		}
	});
}