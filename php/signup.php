<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<HTML>
	<BODY>
		<br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
		<b> - 회원가입 - </b>
		<br>

		<form name="formm" method="post" action="./checkID.php">
			<br> 아이디 :
			<INPUT TYPE="text" name="login_id" SIZE="40" maxlength="50">
			<br>
			<br>
			<INPUT TYPE="submit" value="아이디 중복 확인">
		</form>
	</BODY>
</HTML>