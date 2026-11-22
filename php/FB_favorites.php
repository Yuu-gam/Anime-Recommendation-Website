<?php
session_start();

require_once(__DIR__ . '/log.php');
require_once(__DIR__ . '/SQLconstants.php');

$conn = mysqli_connect(
    $mySQL_host,
    $mySQL_id,
    $mySQL_password,
    $mySQL_database
);

if (!$conn) {
    die("DB 연결에 실패했습니다.");
}

mysqli_set_charset($conn, "utf8mb4");

// 기존 PHP 페이지와 동일한 세션 방식으로 로그인 확인
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == "") {
    header("Location: ../Frontend/login.html");
    exit;
}

$userId = (int)$_SESSION['user_id'];


// 즐겨찾기 해제 및 평점 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $animeId = (int)($_POST['anime_id'] ?? 0);

    // 즐겨찾기 해제
    if ($action === 'remove_favorite' && $animeId > 0) {
        $deleteStmt = mysqli_prepare(
            $conn,
            "DELETE FROM Favorites WHERE user_id = ? AND anime_id = ?"
        );

        if ($deleteStmt) {
            mysqli_stmt_bind_param($deleteStmt, "ii", $userId, $animeId);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
        }

        header("Location: ./FB_favorites.php");
        exit;
    }

    
    // 평점 등록 및 수정
    if ($action === 'update_rating') {

        $rating = (float)($_POST['rating'] ?? 0);
        $rating = max(0, min(5, $rating));
        $rating = round($rating * 2) / 2;

        // 기존 History 기록 확인
        $checkStmt = mysqli_prepare(
            $conn,
            "SELECT history_id, status
             FROM History
             WHERE user_id = ? AND anime_id = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "ii", $userId, $animeId);
        mysqli_stmt_execute($checkStmt);

        $checkResult = mysqli_stmt_get_result($checkStmt);

        if ($row = mysqli_fetch_assoc($checkResult)) {
            // 기존 기록이 있으면 평점 UPDATE
            $upStmt = mysqli_prepare(
                $conn,
                "UPDATE History SET rating = ? WHERE history_id = ?"
            );

            mysqli_stmt_bind_param(
                $upStmt,
                "di",
                $rating,
                $row['history_id']
            );

            mysqli_stmt_execute($upStmt);
            mysqli_stmt_close($upStmt);

        } else {

            // 기존 기록이 없으면 INSERT
            $insStmt = mysqli_prepare(
                $conn,
                "INSERT INTO History (user_id, anime_id, rating, status)
                 VALUES (?, ?, ?, 1)"
            );

            mysqli_stmt_bind_param(
                $insStmt,
                "iid",
                $userId,
                $animeId,
                $rating
            );

            mysqli_stmt_execute($insStmt);
            mysqli_stmt_close($insStmt);
        }

        mysqli_stmt_close($checkStmt);

        header("Location: FB_favorites.php");
        exit;
    }
}

// 즐겨찾기 목록 조회 + 내 평점 조회
$sql = "
    SELECT
        a.anime_id,
        a.anime_name,
        a.anime_image,
        a.anime_type,
        a.release_year,
        a.anime_quarter,
        (
            SELECT h.rating
            FROM History h
            WHERE h.user_id = ? AND h.anime_id = a.anime_id
        ) AS my_rating
    FROM Favorites f
    INNER JOIN Animes a ON f.anime_id = a.anime_id
    WHERE f.user_id = ?
    ORDER BY a.anime_id DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("즐겨찾기 목록을 불러오지 못했습니다.");
}

mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$animes = [];

while ($row = mysqli_fetch_assoc($result)) {
    $animes[] = $row;
}

mysqli_stmt_close($stmt);

// 내가 남긴 평점 전체 조회
$ratingSql = "
    SELECT
        a.anime_id,
        a.anime_name,
        a.anime_image,
        a.anime_type,
        h.rating
    FROM History h
    INNER JOIN Animes a ON h.anime_id = a.anime_id
    WHERE h.user_id = ?
      AND h.rating IS NOT NULL
    ORDER BY h.rating DESC
";

$ratingStmt = mysqli_prepare($conn, $ratingSql);

if (!$ratingStmt) {
    die("평점 목록을 불러오지 못했습니다.");
}

mysqli_stmt_bind_param($ratingStmt, "i", $userId);
mysqli_stmt_execute($ratingStmt);

$ratingResult = mysqli_stmt_get_result($ratingStmt);
$ratings = [];

while ($row = mysqli_fetch_assoc($ratingResult)) {
    $ratings[] = $row;
}

mysqli_stmt_close($ratingStmt);
mysqli_close($conn);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Anime Template">
    <meta name="keywords" content="Anime, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Anime | 마이페이지</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="../Frontend/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/plyr.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="../Frontend/css/style.css" type="text/css">

    <style>
        .fav-star-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(11, 12, 42, 0.85);
            border: 1px solid rgba(243, 218, 53, 0.6);
            color: #f3da35;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 2;
        }

            .fav-star-badge:hover {
                background: #e53637;
                border-color: #e53637;
                color: #ffffff;
            }

        .fav-empty-box {
            background: #070720;
            border: 1px dashed rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            padding: 60px 20px;
            text-align: center;
            margin-top: 10px;
        }

            .fav-empty-box i {
                font-size: 40px;
                color: #f3da35;
                margin-bottom: 15px;
            }

            .fav-empty-box p {
                color: #b7b7b7;
                margin-bottom: 20px;
                font-size: 15px;
            }
    </style>
</head>

<body>
    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>

    <!-- Header Section Begin -->
    <header class="header">
        <div class="container">
            <div class="row">
                <div class="col-lg-2">
                    <div class="header__logo">
                        <a href="./FB_main.php">
                            <img src="../Frontend/img/logo.png" alt="Logo">
                        </a>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="header__nav">
                        <nav class="header__menu mobile-menu">
                            <ul>
                                <li class="active">
                                    <a href="./FB_main.php">홈</a>
                                </li>
                                <li>
                                    <a href="./FB_searchSQL.php">검색</a>
                                </li>
                                <?php if (!empty($_SESSION['user_id'])): ?>
                                    <li>
                                        <a href="./FB_favorites.php">
                                            <?= h($_SESSION['login_id'] ?? '사용자') ?> 님
                                        </a>
                                        <ul class="dropdown">
                                            <li><a href="./FB_favorites.php">마이페이지</a></li>
                                            <li><a href="./logout.php">로그아웃</a></li>
                                        </ul>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a href="../Frontend/login.html">
                                            로그인 / 마이페이지
                                            <span class="arrow_carrot-down"></span>
                                        </a>
                                        <ul class="dropdown">
                                            <li><a href="../Frontend/login.html">로그인</a></li>
                                            <li><a href="./FB_signup.php">회원가입</a></li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="header__right">
                        <a href="#" class="search-switch" title="빠른 검색">
                            <span class="icon_search"></span>
                        </a>
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <a href="./FB_favorites.php" title="마이페이지">
                                <span class="icon_profile"></span>
                            </a>
                        <?php else: ?>
                            <a href="../Frontend/login.html" title="로그인">
                                <span class="icon_profile"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="mobile-menu-wrap"></div>
        </div>
    </header>
    <!-- Header End -->
    <!-- Breadcrumb Begin -->
    <div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="./FB_main.php"><i class="fa fa-home"></i> 홈</a>
                        <span>마이페이지</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->
    <!-- Favorites Section Begin -->
    <section class="product-page spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product__page__content">
                        <div class="product__page__title">
                            <div class="row">
                                <div class="col-lg-8 col-md-8 col-sm-6">
                                    <div class="section-title">
                                        <h4><i class="fa fa-star" style="color: #f3da35; margin-right: 8px;"></i>내 즐겨찾기 목록</h4>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-6 text-right">
                                    <span id="fav-count-text" style="color: #b7b7b7; font-size: 14px; line-height: 36px;">
                                        총 <strong style="color: #ffffff;"><?php echo count($animes); ?></strong>개 작품
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- [BACKEND] PHP 변환 시 처리 흐름:
                             1. 비로그인 상태(!isset($_SESSION['user_id']))면 login.php로 이동시키거나 아래 .fav-empty-box 출력
                             2. 로그인 상태면 favorites 테이블과 작품 테이블을 JOIN하여 현재 유저가 즐겨찾기한 작품 목록 조회
                             3. 조회 결과가 0건이면 .fav-empty-box 출력, 1건 이상이면 아래 카드 반복 출력 -->
                        <div class="row" id="favorites-grid">
                            <?php foreach ($animes as $anime): ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 fav-card-item">
                                    <div class="product__item">
                                        <div class="product__item__pic set-bg"
                                             data-setbg="<?php echo h(!empty($anime['anime_image']) ? $anime['anime_image'] : '../Frontend/img/trending/trend-1.jpg'); ?>">
                                            <form method="POST" action="./FB_favorites.php"
                                                  onsubmit="return confirm('이 작품을 즐겨찾기에서 해제하시겠습니까?');">
                                                <input type="hidden" name="action" value="remove_favorite">
                                                <input type="hidden" name="anime_id" value="<?php echo (int)$anime['anime_id']; ?>">
                                                <button type="submit" class="fav-star-badge" title="즐겨찾기 해제">
                                                    <i class="fa fa-star"></i> 해제
                                                </button>
                                            </form>
                                        </div>
                                            <div class="product__item__text">
                                                <ul>
                                                    <li><?php echo h($anime['anime_type']); ?></li>
                                                    <?php if (!empty($anime['release_year'])): ?>
                                                        <li><?php echo h($anime['release_year']); ?></li>
                                                    <?php endif; ?>
                                                </ul>

                                                <h5>
                                                    <a href="./FB_animeDetail.php?anime_id=<?php echo (int)$anime['anime_id']; ?>">
                                                        <?php echo h($anime['anime_name']); ?>
                                                    </a>
                                                </h5>

                                                <?php $currentRating = isset($anime['my_rating']) ? (float)$anime['my_rating'] : 0; ?>

                                                <div style="margin-top: 12px; color: #b7b7b7;">
                                                    <form method="POST" action="./FB_favorites.php">
                                                        <input type="hidden" name="action" value="update_rating">
                                                        <input type="hidden" name="anime_id"
                                                               value="<?php echo (int)$anime['anime_id']; ?>">

                                                        <label style="display: block; margin-bottom: 6px;">
                                                            내 평점:
                                                            <strong style="color: #f3da35;">
                                                                <span id="rating-value-<?php echo (int)$anime['anime_id']; ?>">
                                                                    <?php echo $currentRating; ?>
                                                                </span>점
                                                            </strong>
                                                        </label>

                                                        <div class="rating-picker" id="ratingPicker-<?php echo (int)$anime['anime_id']; ?>">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <span class="rating-pick-item">
                                                                    <i class="fa fa-star-o"
                                                                       data-index="<?= $i ?>"></i>
                                                                    <button type="button"
                                                                            class="rating-hit left"
                                                                            data-value="<?= $i - 0.5 ?>"></button>
                                                                    <button type="button"
                                                                            class="rating-hit right"
                                                                            data-value="<?= $i ?>"></button>
                                                                </span>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <input type="hidden"
                                                               name="rating"
                                                               id="ratingValue-<?php echo (int)$anime['anime_id']; ?>"
                                                               value="<?= h($currentRating) ?>">

                                                        <button type="submit" class="site-btn"
                                                                style="margin-top: 8px; padding: 6px 14px; font-size: 12px;">
                                                            평점 저장
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                            <!-- 즐겨찾기 목록이 비어 있을 때 표시되는 안내 박스 -->
                            <div id="fav-empty-box" class="fav-empty-box" style="<?php echo count($animes) === 0 ? 'display: block;' : 'display: none;'; ?>">
                                <i class="fa fa-star-o"></i>
                                <p>아직 즐겨찾기한 작품이 없습니다.<br>작품 상세 페이지에서 별(★) 버튼을 눌러 관심 작품을 추가해보세요!</p>
                                <a href="./FB_searchSQL.php" class="site-btn">작품 둘러보기</a>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Favorites Section End -->
    <!-- Footer Section Begin -->
    <footer class="footer">
        <div class="page-up">
            <a href="#" id="scrollToTopButton"><span class="arrow_carrot-up"></span></a>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="footer__logo">
                        <a href="./FB_main.php"><img src="../Frontend/img/logo.png" alt="Logo"></a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="footer__nav">
                        <ul>
                            <li><a href="./FB_main.php">홈</a></li>
                            <li><a href="./FB_searchSQL.php">검색</a></li>
                            <li>
                                <?php if (!empty($_SESSION['user_id'])): ?>
                                    <a href="./FB_favorites.php">마이페이지</a>
                                <?php else: ?>
                                    <a href="../Frontend/login.html">로그인</a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3">
                    <p>
                        Copyright &copy;
                        <script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="fa fa-heart" aria-hidden="true"></i> by <a href="https://ahmedmaher-portfolio.vercel.app/" target="_blank">Ahmed Maher</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Section End -->
    <!-- Search model Begin -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch"><i class="icon_close"></i></div>
            <form action="./FB_searchSQL.php" method="GET" class="search-model-form">
                <input type="text" name="anime_name" id="search-input" placeholder="작품 제목을 입력하세요..." autocomplete="off">
            </form>
        </div>
    </div>
    <!-- Search model end -->
    <!-- Js Plugins -->
    <script src="../Frontend/js/jquery-3.3.1.min.js"></script>
    <script src="../Frontend/js/bootstrap.min.js"></script>
    <script src="../Frontend/js/player.js"></script>
    <script src="../Frontend/js/jquery.nice-select.min.js"></script>
    <script src="../Frontend/js/mixitup.min.js"></script>
    <script src="../Frontend/js/jquery.slicknav.js"></script>
    <script src="../Frontend/js/owl.carousel.min.js"></script>
    <script src="../Frontend/js/main.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.rating-picker').forEach(function (picker) {

        const animeId = picker.id.replace('ratingPicker-', '');
        const ratingInput = document.getElementById('ratingValue-' + animeId);

        if (!ratingInput) return;

        const stars = picker.querySelectorAll('[data-index]');
        const buttons = picker.querySelectorAll('.rating-hit');

        function updateRating(value) {
            value = Math.max(0, Math.min(5, value));
            value = Math.round(value * 2) / 2;

            ratingInput.value = value.toFixed(1);

            const ratingText = document.getElementById('rating-value-' + animeId);

            if (ratingText) {
                ratingText.textContent = value;
            }

            stars.forEach(function (star) {
                const index = Number(star.dataset.index);

                star.classList.remove(
                    'fa-star',
                    'fa-star-half-o',
                    'fa-star-o'
                );

                if (value >= index) {
                    star.classList.add('fa-star');
                } else if (value >= index - 0.5) {
                    star.classList.add('fa-star-half-o');
                } else {
                    star.classList.add('fa-star-o');
                }
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                updateRating(Number(this.dataset.value));
            });
        });

        updateRating(Number(ratingInput.value) || 0);
    });

});
    </script>
</body>

</html>