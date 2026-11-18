<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php $message = $_POST['message'] ?? ""  ?>
<HTML>
	<HEAD>
		<script language = "javascript">
			function showMessage(message)
			{
				if ((message != null)&&(message != ""))
				{
					alert(message);
				}
			}
		</script>
	</HEAD>

	<BODY onLoad = "showMessage('<?php echo $message; ?>');">
		<br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
		<b> 아이디와 비밀번호를 입력해주세요. </b>
		<br>

		<form name = "formm" method = "post" action = "./loginSQL.php">
			<br> 아이디 : &nbsp; <INPUT TYPE = "text" name = "login_id" SIZE="20">
			<br> 비밀번호 : <INPUT TYPE = "password" name = "password" SIZE="20">
		</form>

		&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
		<INPUT TYPE="button" value="로그인" onClick="javascript:document.formm.submit();">
		<INPUT TYPE="button" value="회원가입" onClick="location.href='/animeInfo/signup.php';"> <!-- 경로 파일 이름 따라서 꼭! 바꾸기 (animeInfo부분) -->
	</BODY>
</HTML>
