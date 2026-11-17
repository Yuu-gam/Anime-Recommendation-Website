<?php
	session_start();

	//로그인 안된채 favorite.php 접근시
	if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == "") {
        echo "<script>
            alert('로그인 후 이용해주세요.');
            location.href='./login.php';
        </script>";
        exit;
    }

	// 로그인한 사용자의 정보 확인
	$user_id = $_SESSION['user_id'];
	$login_id = $_SESSION['login_id'];

	//log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
	$conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
		or die("Can't access DB");

	mysqli_set_charset($conn, "utf8mb4");

	// 즐겨찾기 목록 제목
	echo "<BR><b>".$login_id." 님의 즐겨찾기 목록입니다.</b><BR><BR>";
?>

	
	<INPUT TYPE='button' value='즐겨찾기 목록 보기' onClick ="location.href='./search.php';"><BR><BR>
<?php
	// 현재 사용자가 즐겨찾기한 책 검색
	$query = "SELECT book.*
			  FROM favorites
			  JOIN book ON favorites.book_id = book.id
			  WHERE favorites.user_id = '".$user_id."';";

	$resultSet = mysqli_query($conn, $query);

	// 즐겨찾기한 책 출력
	while($result = mysqli_fetch_array($resultSet))
	{
		echo "\n<BR>ID : ".$result['id'];
		echo "\n<BR>책제목 : ".$result['title'];
		echo "\n<BR>저자 : ".$result['author'];
		echo "\n<BR>출판사 : ".$result['publisher'];
		echo "\n<BR>출판일 : ".$result['date'];
		echo "\n<BR><img src='".$result['image']."' height='280' width='180'><br>";
	}

	// MySQL 드라이버 연결 해제
	mysqli_free_result($resultSet);
	mysqli_close($conn);
?>
