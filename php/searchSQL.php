<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    //log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');

    $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
        or die("DB 연결에 실패했습니다.");
    mysqli_set_charset($conn, "utf8mb4");

    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    // 입력값 수집
    $name = trim($_GET['anime_name'] ?? '');
    $types = $_GET['anime_type'] ?? [];
    $year = $_GET['anime_year'] ?? '';
    $quarter = $_GET['anime_quarter'] ?? '';

    if (!is_array($types)) $types = [];
    $validTypes = array_filter($types, fn($t) => is_string($t) && $t !== '');

    // 동적 SQL 조건(WHERE) 및 바인딩 파라미터 구성
    $where = [];
    $params = [];
    $paramTypes = '';

    if ($name !== '') {
        $where[] = "anime_name LIKE ?";
        $params[] = '%' . $name . '%';
        $paramTypes .= 's';
    }

    if (!empty($validTypes)) {
        $placeholders = implode(',', array_fill(0, count($validTypes), '?'));
        $where[] = "anime_type IN ($placeholders)";
        foreach ($validTypes as $type) {
            $params[] = $type;
            $paramTypes .= 's';
        }
    }

    if ($year !== '' && ctype_digit((string)$year)) {
        $where[] = "release_year = ?";
        $params[] = (int)$year;
        $paramTypes .= 'i';
    }

    if ($quarter !== '' && ctype_digit((string)$quarter)) {
        $where[] = "anime_quarter = ?";
        $params[] = (int)$quarter;
        $paramTypes .= 'i';
    }

    // 최종 쿼리 조립
    $query = "SELECT anime_id, anime_name, anime_image, anime_type, release_year, anime_quarter FROM Animes";

    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }

    $query .= " ORDER BY anime_name";

    // Prepared Statement 실행
    $stmt = mysqli_prepare($conn, $query) or die("검색문을 준비하지 못했습니다: " . h(mysqli_error($conn)));

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>검색 결과</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; }
        .anime { border: 1px solid #ddd; border-radius: 8px; padding: 14px; margin: 12px 0; display: flex; gap: 16px; align-items: center; }
        .anime img { width: 70px; height: auto; border-radius: 4px; object-fit: cover; }
        .muted { color: #666; font-size: 14px; line-height: 1.7; }
        .actions { margin-bottom: 20px; }
        a.button { display: inline-block; padding: 10px 16px; background: #333; color: white; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
<h1>검색 결과</h1>
<p>검색 결과: <?= mysqli_num_rows($result) ?>개</p>
<div class="actions">
    <a class="button" href="./search.php">검색 조건 수정</a>
    <a class="button" href="./main.php">메인으로</a>
</div>

<?php if (mysqli_num_rows($result) === 0): ?>
    <p>조건에 맞는 작품이 없습니다.</p>
<?php else: ?>
    <?php while ($anime = mysqli_fetch_assoc($result)): ?>
        <article class="anime">
            <?php if (!empty($anime['anime_image'])): ?>
                <img src="<?= h($anime['anime_image']) ?>" alt="<?= h($anime['anime_name']) ?>">
            <?php endif; ?>
            <div>
                <h2><?= h($anime['anime_name']) ?></h2>
                <div class="muted">
                    유형: <?= h($anime['anime_type']) ?><br>
                    방영년도: <?= h($anime['release_year']) ?>년<br>
                    방영분기: <?= h($anime['anime_quarter']) ?>분기
                </div>
            </div>
        </article>
    <?php endwhile; ?>
<?php endif; ?>

</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>