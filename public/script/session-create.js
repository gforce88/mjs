$().ready(function() {
	$("#studentId").on('blur', function(e) {
		var sid = $("#studentId").val();
		if($.trim(sid)!=""){
			$.getJSON("/student/findstujson?inx=" + this.value, function(json) {
			if (json.err==0) {
				alert("学生のアカウントが存在しないか、または一時停止!");
				$("#firstName").val("");
				$("#lastName").val("");
				$("#phone").val("");
				$("#email").val("");
				$("#studentId").val("");
			} else {
				$("#firstName").val(json.firstName);
				$("#lastName").val(json.lastName);
				$("#phone").val(json.phone);
				$("#email").val(json.email);
			}
		});
		}
		
	});
});

function deletesession(inx){
	var r = confirm("この指導セッションをキャンセルしたいのですか？");
	if (r==true)
    {
    	$.getJSON("/session/delete?inx=" + inx, function(json) {
		if (json.err==0) {
			alert("始まったセッションを削除することはできない");
		} else {
			window.location.reload();
		}
	});
    }
  	else
    {
    	
    }
	
}