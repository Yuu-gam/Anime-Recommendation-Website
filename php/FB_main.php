<?php
    header('Content-Type: text/html; charset=UTF-8');
    session_start();

    //log, DB 연결
	require_once(__DIR__ . '/log.php');
	include('./SQLconstants.php');
    $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
        or die("DB 연결에 실패했습니다.");
    mysqli_set_charset($conn, "utf8mb4");

    // 최신 방영 작품 2개
    $heroQuery = "
    SELECT
        anime_id,
        anime_name,
        anime_image,
        anime_type,
        release_year
    FROM Animes
    WHERE release_year IS NOT NULL
    ORDER BY release_year DESC, anime_id DESC
    LIMIT 2
    ";

    $heroResult = mysqli_query($conn, $heroQuery)
    or die('대표 작품을 불러오지 못했습니다.');


    // 인기 작품 상위 6개: 평가 참여 수 → 평균 평점 순
    $popularQuery = "
        SELECT
            a.anime_id,
            a.anime_name,
            a.anime_image,
            a.anime_type,
            a.release_year,
            COUNT(h.rating) AS rating_count,
            COALESCE(AVG(h.rating), 0) AS average_rating
        FROM Animes a
        LEFT JOIN History h
            ON a.anime_id = h.anime_id
        GROUP BY
            a.anime_id,
            a.anime_name,
            a.anime_image,
            a.anime_type,
            a.release_year
        ORDER BY
            rating_count DESC,
            average_rating DESC,
            a.anime_id DESC
        LIMIT 6
    ";

    $popularResult = mysqli_query($conn, $popularQuery)
        or die("인기 작품을 불러오지 못했습니다.");

     
    // 최신 등록 작품 6개
    $recentQuery = "
        SELECT anime_id, anime_name, anime_image, anime_type,
               release_year, anime_quarter
        FROM Animes
        ORDER BY anime_id DESC
        LIMIT 6
    ";

    $recentResult = mysqli_query($conn, $recentQuery)
        or die("최신 작품을 불러오지 못했습니다.");


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
    <title>Anime | 홈</title>

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
    <!-- DB에 저장된 가장 최근에 방영된 애니를 보여줍니다. -->
    <section class="hero">
        <div class="container">
            <div class="hero__slider owl-carousel">
            <?php if (mysqli_num_rows($heroResult) === 0): ?>
                <div class="hero__items set-bg" data-setbg="../Frontend/img/hero/hero-1.jpg">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="hero__text">
                                <h2>등록된 작품이 없습니다.</h2>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php while ($anime = mysqli_fetch_assoc($heroResult)): ?>
                <div class="hero__items">
                    <img
                        class="hero-anime-image"
                        src="<?= h($anime['anime_image']) ?>"
                        referrerpolicy="no-referrer"
                        alt="<?= h($anime['anime_name']) ?>"
                    >
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="hero__text">
                                <div class="label">
                                    <?php echo h($anime['anime_type']); ?>
                                </div>
                                <h2>
                                    <?php echo h($anime['anime_name']); ?>
                                </h2>
                                <p>
                                    <?php echo h($anime['release_year']) . '년 작품';?>
                                </p>
                                <a href="./FB_animeDetail.php?anime_id=<?php echo (int)$anime['anime_id']; ?>"><span>상세 정보 보기</span> <i class="fa fa-angle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
            </div>
        </div>
    </section>
    <!-- Hero Section End -->
    <!-- Product Section Begin -->
    <section class="product spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- 1. 인기 작품 섹션 -->
                    <div class="trending__product">
                        <div class="row">
                            <div class="col-lg-8 col-md-8 col-sm-8">
                                <div class="section-title">
                                    <h4>인기 작품</h4>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <div class="btn__all">
                                    <a href="./FB_searchSQL.php" class="primary-btn">전체보기 <span class="arrow_right"></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (mysqli_num_rows($popularResult) === 0): ?>
                                <div class="col-12">
                                    <p>등록된 작품이 없습니다.</p>
                                </div>
                            <?php else: ?>

                                <?php while ($anime = mysqli_fetch_assoc($popularResult)): ?>

                                    <div class="col-lg-4 col-md-6 col-sm-6">
                                        <div class="product__item">

                                            <div class="product__item__pic set-bg"
                                                data-setbg="<?php echo h($anime['anime_image']); ?>">

                                                <div class="ep">
                                                    평점 <?php echo number_format((float)$anime['average_rating'], 1); ?>
                                                </div>

                                                <div class="comment">
                                                    <i class="fa fa-comments"></i>
                                                    <?php echo (int)$anime['rating_count']; ?>
                                                </div>

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
                                            </div>

                                        </div>
                                    </div>

                                <?php endwhile; ?>

                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. 최신 등록 작품 섹션 -->
                    <div class="recent__product">
                        <div class="row">
                            <div class="col-lg-8 col-md-8 col-sm-8">
                                <div class="section-title">
                                    <h4>최신 등록 작품</h4>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <div class="btn__all">
                                    <a href="./FB_searchSQL.php" class="primary-btn">
                                        전체보기 <span class="arrow_right"></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (mysqli_num_rows($recentResult) === 0): ?>
                                <div class="col-12">
                                    <p>등록된 작품이 없습니다.</p>
                                </div>
                            <?php else: ?>
                                <?php while ($anime = mysqli_fetch_assoc($recentResult)): ?>
                                    <div class="col-lg-4 col-md-6 col-sm-6">
                                        <div class="product__item">
                                            <div class="product__item__pic">
                                                <?php
                                                $imgSrc = $anime['anime_image'];
                                                ?>
                                                <img
                                                    class="anime-result-image"
                                                    src="<?= h($imgSrc) ?>"
                                                    referrerpolicy="no-referrer"
                                                    alt="<?= h($anime['anime_name']) ?>"
                                                >
                                                <?php if (!empty($anime['anime_quarter'])): ?>
                                                    <div class="ep">
                                                        <?= h($anime['anime_quarter']) ?>분기
                                                    </div>
                                                <?php endif; ?>
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
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
    <!-- Search model end -->
    <!-- Search model Begin (빠른 재검색 가능) -->
    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch"><i class="icon_close"></i></div>
            <form action="./FB_searchSQL.php" method="GET" class="search-model-form">
                <input type="text" name="anime_name" id="modal_anime_name" placeholder="작품 제목을 입력하세요..." autocomplete="off">
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

</body>

</html>

<?php
mysqli_free_result($resultSet);
mysqli_free_result($popularResult);
mysqli_close($conn);
?>
