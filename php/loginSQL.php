<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php 
	session_start();

	$login_id = $_POST['login_id'];
	$password = $_POST['password'];
	$message = "";
	$nextPage = "";

	//log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
	$conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database) 
		or die("Can't access DB");

	mysqli_set_charset($conn, "utf8mb4");

	$query = "SELECT * FROM Users WHERE login_id = '".$login_id."';";
	$resultSet = mysqli_query($conn, $query);

	if(mysqli_num_rows($resultSet) > 0) //해당 아이디가 DB에 존재할 때
	{
		$result = mysqli_fetch_array($resultSet);

		if(strcmp($password, $result['password']) == 0) //비밀번호 일치
		{
			$_SESSION['user_id'] = $result['user_id'];
			$_SESSION['login_id'] = $result['login_id'];
			writeLog("로그인 성공 - 유저 아이디: ".$login_id);
			$message = " * 로그인되었습니다.";
			$nextPage = "../Frontend/search-result.html";
		}
		else // 비밀번호 불일치
		{
			writeLog("로그인 실패 - 비밀번호 불일치");
			$message = " * 비밀번호가 올바르지 않습니다.";
			$nextPage = "../Frontend/login.html";
		}
	}
	else //아이디가 DB에 없음
	{
		writeLog("로그인 실패 - 아이디 불일치");
		$message = " * 아이디가 올바르지 않습니다.";
		$nextPage = "../Frontend/login.html";
	}

	mysqli_free_result($resultSet);
	mysqli_close($conn);
?>

<?php
$jsMessage = json_encode($message, JSON_UNESCAPED_UNICODE);
$jsNextPage = json_encode($nextPage, JSON_UNESCAPED_UNICODE);
?>
<script>
	alert(<?php echo $jsMessage; ?>);
	window.location.href = <?php echo $jsNextPage; ?>;
</script>
