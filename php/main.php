<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    //log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
    $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
        or die("DB 연결에 실패했습니다.");
    mysqli_set_charset($conn, "utf8mb4");

    // 현재 DB에 등록된 애니메이션 목록
    $query = "SELECT * FROM Animes ORDER BY anime_id DESC";
    $resultSet = mysqli_query($conn, $query) or die("작품 목록을 불러오지 못했습니다.");

    function h($value) { 
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>애니메이션 목록</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; }
        header { display:flex; justify-content:space-between; align-items:center; gap:16px; }
        .anime-list { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:16px; margin-top:24px; }
        .anime { border:1px solid #ddd; border-radius:10px; padding:16px; }
        .anime h2 { font-size:18px; margin:0 0 12px; }
        .muted { color:#666; font-size:14px; line-height:1.7; }
        a.button { display:inline-block; padding:10px 16px; background:#333; color:white; text-decoration:none; border-radius:6px; }
    </style>
</head>
<body>
<header>
    <h1>애니메이션</h1>
    <a class="button" href="./search.php">작품 검색</a>
</header>

<p>등록된 애니메이션 목록입니다.</p>

<div class="anime-list">
<?php if (mysqli_num_rows($resultSet) === 0): ?>
    <p>등록된 작품이 없습니다.</p>
<?php else: ?>
    <?php while ($anime = mysqli_fetch_assoc($resultSet)): ?>
        <article class="anime">
            <h1><?= h($anime['anime_image']) ?></h1> <!-- 이미지 출력 -->
            <h2><?= h($anime['anime_name']) ?></h2> <!-- 제목 출력 -->
            <div class="muted"> <!--이 밑은 상세 정보여서 필요하면 쓰고, 출력에 필요없으면 가차없이 버려도 ok -->
                유형: <?= h($anime['anime_type']) ?><br>
                방영년도: <?= h($anime['release_year']) ?>년<br>
                방영분기: <?= h($anime['anime_quarter']) ?>분기
            </div>
        </article>
    <?php endwhile; ?>
<?php endif; ?>
</div>
</body>
</html>
<?php
mysqli_free_result($resultSet);
mysqli_close($conn);
?>
