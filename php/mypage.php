<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    // 로그인 확인
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == "") {
        echo "<script>
            alert('로그인 후 이용해주세요.');
            location.href='./login.php';
        </script>";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $login_id = $_SESSION['login_id'] ?? '-';

    // Log, DB 연결
    require_once(__DIR__ . '/log.php');
    include('./SQLconstants.php');

    $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
        or die("DB 연결에 실패했습니다.");
    mysqli_set_charset($conn, "utf8mb4");

    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    // 평점 등록/수정 요청 처리 (마이페이지에서 바로 평점을 변경할 경우)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_rating') {
        $target_anime_id = isset($_POST['anime_id']) ? (int)$_POST['anime_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        
        if ($rating < 0) $rating = 0;
        if ($rating > 10) $rating = 10;

        if ($target_anime_id > 0) {
            // History 테이블에 해당 유저/애니메이션 기록이 있는지 확인
            $checkStmt = mysqli_prepare($conn, "SELECT history_id FROM History WHERE user_id = ? AND anime_id = ?");
            mysqli_stmt_bind_param($checkStmt, "ii", $user_id, $target_anime_id);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if ($row = mysqli_fetch_assoc($checkResult)) {
                // 존재하면 평점 업데이트
                $upStmt = mysqli_prepare($conn, "UPDATE History SET rating = ? WHERE history_id = ?");
                mysqli_stmt_bind_param($upStmt, "di", $rating, $row['history_id']);
                mysqli_stmt_execute($upStmt);
                mysqli_stmt_close($upStmt);
            } else {
                // 없으면 새로 생성 (status 기본값 1 부여)
                $insStmt = mysqli_prepare($conn, "INSERT INTO History (user_id, anime_id, rating, status) VALUES (?, ?, ?, 1)");
                mysqli_stmt_bind_param($insStmt, "iid", $user_id, $target_anime_id, $rating);
                mysqli_stmt_execute($insStmt);
                mysqli_stmt_close($insStmt);
            }
            mysqli_stmt_close($checkStmt);

            writeLog("마이페이지 평점 수정 - user(" . $login_id . ") anime_id(" . $target_anime_id . ") 평점: " . $rating);
            
            echo "<script>alert('평점이 저장되었습니다.'); location.href='./favorite.php';</script>";
            exit;
        }
    }

    writeLog("마이페이지 접속 - user(" . $login_id . ")");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>마이페이지 - 즐겨찾기 및 평점 관리</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; max-width: 900px; }
        .anime-card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin: 14px 0; display: flex; gap: 20px; align-items: center; justify-content: space-between; }
        .anime-info-group { display: flex; gap: 16px; align-items: center; }
        .anime-card img { width: 80px; height: auto; border-radius: 4px; object-fit: cover; }
        .muted { color: #666; font-size: 14px; line-height: 1.6; }
        .actions { margin-bottom: 24px; display: flex; gap: 10px; }
        a.button, button { display: inline-block; padding: 10px 16px; background: #333; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; }
        a.button:hover, button:hover { background: #555; }
        .rating-form { display: flex; align-items: center; gap: 10px; }
        input[type="range"] { cursor: pointer; }
    </style>
</head>
<body>

<h1>마이페이지</h1>
<p><b><?= h($login_id) ?></b> 님의 즐겨찾기 및 평점 관리 페이지입니다.</p>

<div class="actions">
    <a class="button" href="./search.php">작품 검색</a>
    <a class="button" href="./main.php">메인으로</a>
</div>

<h2>1. 즐겨찾기 목록</h2>
<?php
    // favorites 테이블과 Animes 테이블 조인, History 테이블에서 평점 조인
    $fav_query = "SELECT a.anime_id, a.anime_name, a.anime_image, a.anime_type, a.release_year, a.anime_quarter,
                         (SELECT h.rating FROM History h WHERE h.user_id = ? AND h.anime_id = a.anime_id) as my_rating
                  FROM favorites f
                  JOIN Animes a ON f.anime_id = a.anime_id
                  WHERE f.user_id = ?";
    
    $stmt_fav = mysqli_prepare($conn, $fav_query);
    mysqli_stmt_bind_param($stmt_fav, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt_fav);
    $fav_result = mysqli_stmt_get_result($stmt_fav);

    if (mysqli_num_rows($fav_result) === 0):
?>
    <p>즐겨찾기한 작품이 없습니다.</p>
<?php else: ?>
    <?php while ($anime = mysqli_fetch_assoc($fav_result)): ?>
        <?php $currentRating = isset($anime['my_rating']) ? (int)$anime['my_rating'] : 0; ?>
        <article class="anime-card">
            <div class="anime-info-group">
                <?php if (!empty($anime['anime_image'])): ?>
                    <img src="<?= h($anime['anime_image']) ?>" alt="<?= h($anime['anime_name']) ?>">
                <?php endif; ?>
                <div>
                    <h3><?= h($anime['anime_name']) ?></h3>
                    <div class="muted">
                        유형: <?= h($anime['anime_type']) ?><br>
                        방영: <?= h($anime['release_year']) ?>년 <?= h($anime['anime_quarter']) ?>분기<br>
                        현재 내 평점: <strong><?= $currentRating ?>점</strong>
                    </div>
                </div>
            </div>

            <!-- 평점 조절 슬라이더 및 저장 폼 -->
            <div>
                <form method="post" class="rating-form">
                    <input type="hidden" name="action" value="update_rating">
                    <input type="hidden" name="anime_id" value="<?= h($anime['anime_id']) ?>">
                    <div>
                        <label style="font-size: 13px; display: block; margin-bottom: 4px;">평점: <span id="val_<?= h($anime['anime_id']) ?>"><?= $currentRating ?></span>점</label>
                        <input type="range" name="rating" min="0" max="10" value="<?= $currentRating ?>" 
                               oninput="document.getElementById('val_<?= h($anime['anime_id']) ?>').innerText = this.value">
                    </div>
                    <button type="submit">평점 저장</button>
                </form>
            </div>
        </article>
    <?php endwhile; ?>
<?php 
    endif;
    mysqli_stmt_close($stmt_fav);
?>

<h2>2. 내가 남긴 평점 전체 확인</h2>
<?php
    // History 테이블에서 평점이 등록된 작품 목록 조회
    $rating_query = "SELECT a.anime_id, a.anime_name, a.anime_image, a.anime_type, h.rating
                     FROM History h
                     JOIN Animes a ON h.anime_id = a.anime_id
                     WHERE h.user_id = ? AND h.rating IS NOT NULL
                     ORDER BY h.rating DESC";
    
    $stmt_rat = mysqli_prepare($conn, $rating_query);
    mysqli_stmt_bind_param($stmt_rat, "i", $user_id);
    mysqli_stmt_execute($stmt_rat);
    $rat_result = mysqli_stmt_get_result($stmt_rat);

    if (mysqli_num_rows($rat_result) === 0):
?>
    <p>작성한 평점이 없습니다.</p>
<?php else: ?>
    <?php while ($rated = mysqli_fetch_assoc($rat_result)): ?>
        <article class="anime-card">
            <div class="anime-info-group">
                <?php if (!empty($rated['anime_image'])): ?>
                    <img src="<?= h($rated['anime_image']) ?>" alt="<?= h($rated['anime_name']) ?>">
                <?php endif; ?>
                <div>
                    <h3><?= h($rated['anime_name']) ?></h3>
                    <div class="muted">
                        유형: <?= h($rated['anime_type']) ?>
                    </div>
                </div>
            </div>
            <div>
                <span style="font-size: 16px; color: #d9534f; font-weight: bold;">평점: <?= h($rated['rating']) ?>점</span>
            </div>
        </article>
    <?php endwhile; ?>
<?php 
    endif;
    mysqli_stmt_close($stmt_rat);
    mysqli_close($conn);
?>

</body>
</html>