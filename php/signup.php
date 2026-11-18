<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<?php
	session_start();

	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');

	$login_id = "";
	$is_id_checked = false; // 아이디 중복확인 통과 여부

	// [아이디 중복 확인] 버튼을 눌렀을 때 실행
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_id'])) {
		$login_id = trim($_POST['login_id']);

		if ($login_id == "") {
			writeLog("회원가입 - 아이디 입력칸 공란");
			echo "<script>alert('아이디를 입력해주세요.');</script>";
		} else {
			$conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
				or die("Can't access DB");
			mysqli_set_charset($conn, "utf8mb4");

			$escaped_id = mysqli_real_escape_string($conn, $login_id);
			$query = "SELECT * FROM users WHERE login_id = '" . $escaped_id . "';";
			$resultSet = mysqli_query($conn, $query);

			if (mysqli_num_rows($resultSet) > 0) {
				writeLog("회원가입 - 기존 아이디와 중복");
				echo "<script>alert('이미 사용 중인 아이디입니다.');</script>";
				$login_id = ""; // 중복일 경우 입력칸 비움
			} else {
				writeLog("회원가입 - 사용 가능한 아이디");
				echo "<script>alert('사용 가능한 아이디입니다.');</script>";
				$is_id_checked = true; // 중복확인 통과!
			}

			mysqli_free_result($resultSet);
			mysqli_close($conn);
		}
	}
?>
<HTML>
	<BODY>
		<br>            
		<b> - 회원가입 - </b>
		<br><br>

		<!-- 1단계: 아이디 입력 및 중복 확인 폼 (자기 자신에게 POST) -->
		<form name="checkForm" method="post" action="./signup.php">
			아이디 : 
			<INPUT TYPE="text" name="login_id" SIZE="30" maxlength="50" 
				value="<?php echo htmlspecialchars($login_id); ?>" 
				<?php if ($is_id_checked) echo "readonly"; ?>>
			
			<?php if (!$is_id_checked): ?>
				<INPUT TYPE="submit" name="check_id" value="아이디 중복 확인">
			<?php else: ?>
				<span style="color: green; font-size: 0.9em;">✔ 확인완료</span>
				<a href="./signup.php" style="font-size: 0.8em;">[아이디 변경]</a>
			<?php endif; ?>
		</form>

		<!-- 2단계: 중복 확인이 완료된 경우에만 비밀번호 및 나이 입력 폼 활성화 -->
		<?php if ($is_id_checked): ?>
		<hr style="width: 400px; margin-left: 0;">
		<form name="signupForm" method="post" action="./signupSQL.php">
			<!-- 확인된 아이디를 hidden으로 함께 전송 -->
			<input type="hidden" name="login_id" value="<?php echo htmlspecialchars($login_id); ?>">

			비밀번호 : 
			<INPUT TYPE="password" name="password" SIZE="40" maxlength="50">
			<br><br>

			비밀번호 확인 : 
			<INPUT TYPE="password" name="password_check" SIZE="40" maxlength="50">
			<br><br>

			나이 : 
			<INPUT TYPE="number" name="age" min="1" max="150" placeholder="숫자만 입력">
			<br><br>

			<INPUT TYPE="submit" value="회원가입 완료">
		</form>
		<?php endif; ?>

	</BODY>
</HTML>