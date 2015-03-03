$().ready(function() {
	$("#studentId").on('blur', function(e) {
		var sid = $("#studentId").val();
		if($.trim(sid)!=""){
			$.getJSON("/student/findstujson?inx=" + this.value, function(json) {
			if (json.err==0) {
				alert("未登録の生徒IDまたは休止中の生徒のため利用できません");
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
	var r = confirm("指定された予約をキャンセルします。よろしいですか？");
	if (r==true)
    {
    	$.getJSON("/session/delete?inx=" + inx, function(json) {
		if (json.err==0) {
			alert("予約の削除に失敗しました。\n既に実行やキャンセルされた予約は取り消しできません。");
		} else {
			window.location.reload();
		}
	});
    }
  	else
    {
    	
    }
	
}