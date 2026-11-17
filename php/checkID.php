<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php
	session_start();

	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	$login_id = $_POST['login_id']; //회원가입 하려는 id
	$message = "";

	//log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
	$conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
		or die("Can't access DB");

	mysqli_set_charset($conn, "utf8mb4");

	if($login_id == "") //아이디 공란일 경우
	{
		writeLog("회원가입 - 아이디 입력칸 공란");
		echo "<script>alert('아이디를 입력해주세요.'); history.back();</script>";
		exit;
	}

	$login_id = mysqli_real_escape_string($conn, $login_id);

	$query = "SELECT * FROM users WHERE login_id = '".$login_id."';";
	$resultSet = mysqli_query($conn, $query);

	if(mysqli_num_rows($resultSet) > 0) //아이디 중복일 경우
	{
		writeLog("회원가입 - 기존 아이디와 중복");
		echo "<script>alert('이미 사용 중인 아이디입니다.'); history.back();</script>";
		exit;
	}
	else{ //비밀번호 입력받는 화면 출력
		writeLog("회원가입 - 사용 가능한 아이디");
		echo "<script>alert('사용 가능한 아이디입니다.'); </script>";
?>
<HTML>
	<BODY>
		<br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
		<b> - 비밀번호 입력 - </b>
		<br>

		<form name="formm" method="post" action="./signupSQL.php">
			<!-- 아이디 넘기기 -->
			<input type="hidden" name="login_id"
				value="<?php echo htmlspecialchars($login_id); ?>">

			<br> 비밀번호 :
			<INPUT TYPE="password" name="password" SIZE="40" maxlength="50">

			<br> 비밀번호 확인 :
			<INPUT TYPE="password" name="password_check" SIZE="40">

			<br><br>
			<INPUT TYPE="submit" value="회원가입">
		</form>
	</BODY>
</HTML>
<?php
	}

	mysqli_free_result($resultSet);
	mysqli_close($conn);
?>
