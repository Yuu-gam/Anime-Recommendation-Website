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

    // 검색 폼에 출력할 데이터 목록 조회 (년도, 분기, 유형)
    $years = [];
    if ($res = mysqli_query($conn, "SELECT DISTINCT release_year FROM Animes ORDER BY release_year DESC")) {
        while ($row = mysqli_fetch_assoc($res)) $years[] = $row['release_year'];
        mysqli_free_result($res);
    }

    $quarters = [];
    if ($res = mysqli_query($conn, "SELECT DISTINCT anime_quarter FROM Animes ORDER BY anime_quarter")) {
        while ($row = mysqli_fetch_assoc($res)) $quarters[] = $row['anime_quarter'];
        mysqli_free_result($res);
    }

    $types = [];
    if ($res = mysqli_query($conn, "SELECT DISTINCT anime_type FROM Animes ORDER BY anime_type")) {
        while ($row = mysqli_fetch_assoc($res)) $types[] = $row['anime_type'];
        mysqli_free_result($res);
    }

    mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>애니메이션 검색</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; max-width: 900px; }
        fieldset { border: 1px solid #ddd; border-radius: 8px; margin: 18px 0; padding: 16px; }
        legend { font-weight: bold; padding: 0 8px; }
        .choices { display: flex; flex-wrap: wrap; gap: 12px 20px; }
        label { cursor: pointer; }
        input[type="text"], select { padding: 9px; min-width: 180px; }
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        button, a.button { padding: 10px 16px; border: 0; border-radius: 6px; background: #333; color: white; text-decoration: none; cursor: pointer; }
        a.button { background: #777; }
    </style>
</head>
<body>
<h1>애니메이션 검색</h1>

<form method="get" action="./searchSQL.php">
    <fieldset>
        <legend>작품 제목</legend>
        <input type="text" name="anime_name" placeholder="애니메이션 제목 입력">
    </fieldset>

    <fieldset>
        <legend>영상 유형</legend>
        <div class="choices">
        <?php foreach ($types as $type): ?>
            <label>
                <input type="checkbox" name="anime_type[]" value="<?= h($type) ?>">
                <?= h($type) ?>
            </label>
        <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset>
        <legend>방영년도</legend>
        <select name="anime_year">
            <option value="">전체 년도</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= h($year) ?>"><?= h($year) ?>년</option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <fieldset>
        <legend>방영분기</legend>
        <select name="anime_quarter">
            <option value="">전체 분기</option>
            <?php foreach ($quarters as $quarter): ?>
                <option value="<?= h($quarter) ?>"><?= h($quarter) ?>분기</option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <div class="actions">
        <button type="submit">검색</button>
        <a class="button" href="./main.php">메인으로</a>
    </div>
</form>
</body>
</html>