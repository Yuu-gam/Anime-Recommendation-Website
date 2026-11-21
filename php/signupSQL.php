<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php
	session_start();

	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	$login_id = $_POST['login_id'];
	$password = $_POST['password'];
	$password_check = $_POST['password_check'];
	$user_age = $_POST['age'];

	//log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
	$conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
		or die("Can't access DB");

	mysqli_set_charset($conn, "utf8mb4");

	$login_id = mysqli_real_escape_string($conn, $login_id);
	$password = mysqli_real_escape_string($conn, $password);
	$password_check = mysqli_real_escape_string($conn, $password_check);
	$user_age = (int)$user_age;

	if($password == "" || $password_check == "") //비밀번호 공란일 경우
	{
		writeLog("회원가입 - 비밀번호 공란");
		echo "<script>alert('비밀번호를 입력해주세요.'); history.back();</script>";
		exit;
	}
	else if($password != $password_check) //입력한 비밀번호가 일치하지 않을 경우
	{
		writeLog("회원가입 - 비밀번호 불일치");
		echo "<script>alert('비밀번호가 일치하지 않습니다.'); history.back();</script>";
		exit;
	}
	else //회원가입
	{
		$query = "INSERT INTO Users(login_id, password, age)
			VALUES ('".$login_id."', '".$password."', '".$user_age."');";

		$result = mysqli_query($conn, $query);

		if($result)
		{
			writeLog("회원가입 - user(" .$login_id. ")회원가입 성공");
			echo "<script>
				alert('회원가입이 완료되었습니다.');
				location.href='/Frontend/login.html'; 
			</script>"; //경로 파일 이름 따라서 꼭! 바꾸기 (animeInfo부분)
		}
		else
		{
			writeLog("회원가입 - 비밀번호 입력 이후 회원가입 실패");
			echo "<script>
				alert('회원가입에 실패했습니다. 아이디를 확인해주세요.');
				location.href='/Frontend/signup.html';
			</script>"; //경로 파일 이름 따라서 꼭! 바꾸기 (animeInfo부분)
		}
	}

	mysqli_close($conn);
?>
