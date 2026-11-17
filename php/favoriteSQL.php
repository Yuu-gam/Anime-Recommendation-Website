<?php header('content-Type: text/html; charset=UTF-8'); ?>
<?php
	session_start();

	$user_id = $_SESSION['user_id'];
	$book_id = $_GET['book_id'];
	$message = "";

	//log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
	$conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
		or die("Can't access DB");

	mysqli_set_charset($conn, "utf8mb4");

	// 책이 존재하는지 확인
	$query = "SELECT * FROM book WHERE id = '".$book_id."';";
	$resultSet = mysqli_query($conn, $query);

	if(mysqli_num_rows($resultSet) > 0)
	{
		$query = "SELECT * FROM favorites 
				  WHERE user_id = '".$user_id."' 
				  AND book_id = '".$book_id."';";
		$favoriteSet = mysqli_query($conn, $query);

		if(mysqli_num_rows($favoriteSet) == 0) //즐겨찾기 목록에 없으면 해당 작품 추가
		{
			$query = "INSERT INTO favorites (user_id, book_id)
					  VALUES ('".$user_id."', '".$book_id."');";

			mysqli_query($conn, $query);

			writeLog("작품 id(" .$book_id.")을 즐겨찾기 목록에 추가");
			$message = " * 즐겨찾기에 저장되었습니다.";
		}
		else
		{
			$message = " * 이미 즐겨찾기에 저장된 책입니다.";
		}

		mysqli_free_result($favoriteSet);
	}

	mysqli_free_result($resultSet);
	mysqli_close($conn);
?>

<form name="frm" method="post" action="./search.php">
	<input type="hidden" name="message" value="<?php echo $message; ?>">
</form>

<script language="javascript">
	document.frm.submit();
</script>
