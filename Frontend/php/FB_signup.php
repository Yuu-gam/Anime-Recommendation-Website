<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/log.php');
include('./SQLconstants.php');

$login_id = "";
$age_val = "";
$is_id_checked = false;
$id_check_msg = "* 아이디 입력 후 중복 확인을 눌러주세요.";
$id_check_color = "#b7b7b7";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_id'])) {
    $login_id = trim($_POST['login_id'] ?? '');
    $age_val = trim($_POST['age'] ?? '');

    if ($login_id === "") {
        writeLog("회원가입 - 아이디 입력칸 공란");
        $id_check_msg = "* 아이디를 입력해주세요.";
        $id_check_color = "#e53637";
        echo "<script>alert('아이디를 입력해주세요.');</script>";
    } else {
        $conn = mysqli_connect($mySQL_host, $mySQL_id, $mySQL_password, $mySQL_database)
            or die("Can't access DB");
        mysqli_set_charset($conn, "utf8mb4");

        $escaped_id = mysqli_real_escape_string($conn, $login_id);
        $query = "SELECT * FROM Users WHERE login_id = '" . $escaped_id . "';";
        $resultSet = mysqli_query($conn, $query);

        if (mysqli_num_rows($resultSet) > 0) {
            writeLog("회원가입 - 기존 아이디와 중복");
            $login_id = "";
            $id_check_msg = "* 이미 사용 중인 아이디입니다.";
            $id_check_color = "#e53637";
            echo "<script>alert('이미 사용 중인 아이디입니다.');</script>";
        } else {
            writeLog("회원가입 - 사용 가능한 아이디");
            $is_id_checked = true;
            $id_check_msg = "* 사용 가능한 아이디입니다.";
            $id_check_color = "#4caf50";
            echo "<script>alert('사용 가능한 아이디입니다.');</script>";
        }

        mysqli_free_result($resultSet);
        mysqli_close($conn);
    }
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
    <title>Anime | 회원가입</title>

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

    <!-- Normal Breadcrumb Begin -->
    <section class="normal-breadcrumb set-bg" data-setbg="../Frontend/img/normal-breadcrumb.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="normal__breadcrumb__text">
                        <h2>회원가입</h2>
                        <p>간단한 정보 입력으로 계정을 생성할 수 있습니다.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Normal Breadcrumb End -->

    <!-- Signup Section Begin -->
    <section class="signup spad">
        <div class="container">
            <div class="row d-flex justify-content-center">
                <div class="col-lg-6">
                    <div class="login__form">
                        <h3>회원가입</h3>

                        <!-- 중복 확인 전에는 ./signup.php로, 완료 후에는 ./signupSQL.php로 제출 -->
                        <form id="signup-form" action="<?php echo $is_id_checked ? './signupSQL.php' : './FB_signup.php'; ?>" method="POST">
                            <!-- 1. 아이디 + 중복 확인 -->
                            <div class="d-flex justify-content-between align-items-center" style="max-width: 370px; margin-bottom: 6px;">
                                <div class="input__item" style="width: calc(100% - 115px); margin-bottom: 0;">
                                    <input
                                        type="text"
                                        id="login_id"
                                        name="login_id"
                                        placeholder="아이디"
                                        maxlength="50"
                                        value="<?php echo htmlspecialchars($login_id, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php if ($is_id_checked) echo 'readonly'; ?>
                                        required
                                    >
                                    <span class="icon_id"></span>
                                </div>

                                <?php if (!$is_id_checked): ?>
                                    <button
                                        type="submit"
                                        name="check_id"
                                        value="1"
                                        id="check-id-btn"
                                        formnovalidate
                                        class="site-btn"
                                        style="width: 105px; height: 50px; padding: 0; font-size: 13px;"
                                    >중복 확인</button>
                                <?php else: ?>
                                    <a
                                        href="./FB_signup.php"
                                        class="site-btn"
                                        style="width: 105px; height: 50px; line-height: 50px; text-align: center; padding: 0; font-size: 13px;"
                                    >아이디 변경</a>
                                <?php endif; ?>
                            </div>
                            <small id="id-check-msg" style="display: block; max-width: 370px; margin-bottom: 16px; color: <?php echo htmlspecialchars($id_check_color, ENT_QUOTES, 'UTF-8'); ?>;">
                                <?php echo htmlspecialchars($id_check_msg, ENT_QUOTES, 'UTF-8'); ?>
                            </small>

                            <!-- 2. 나이 -->
                            <div class="input__item">
                                <input
                                    type="text"
                                    id="age"
                                    name="age"
                                    placeholder="나이"
                                    value="<?php echo htmlspecialchars($age_val, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                                <span class="icon_profile"></span>
                            </div>

                            <!-- 3. 비밀번호 -->
                            <div class="input__item">
                                <input type="password" id="password" name="password" maxlength="50" placeholder="비밀번호" required>
                                <span class="icon_lock"></span>
                            </div>

                            <!-- 4. 비밀번호 확인 (2회 입력) -->
                            <div class="input__item" style="margin-bottom: 6px;">
                                <input type="password" id="password_check" name="password_check" maxlength="50" placeholder="비밀번호 확인" required>
                                <span class="icon_lock"></span>
                            </div>
                            <small id="pw-match-msg" style="display: block; max-width: 370px; margin-bottom: 20px; color: #b7b7b7;">
                                * 비밀번호를 한 번 더 입력하세요.
                            </small>

                            <button type="submit" class="site-btn">회원가입 완료</button>
                        </form>

                        <h5>이미 계정이 있으신가요? <a href="../Frontend/login.html">로그인!</a></h5>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Signup Section End -->

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
            <form action="FB_searchSQL.php" method="GET" class="search-model-form">
                <input type="text" name="keyword" id="search-input" placeholder="작품 제목을 입력하세요..." autocomplete="off">
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
    (function () {
        const isIdChecked = <?php echo $is_id_checked ? 'true' : 'false'; ?>;
        const pwInput = document.getElementById('password');
        const pwConfirmInput = document.getElementById('password_check');
        const pwMatchMsg = document.getElementById('pw-match-msg');
        const signupForm = document.getElementById('signup-form');

        function validatePasswordMatch() {
            const pw = pwInput.value;
            const pwConfirm = pwConfirmInput.value;

            if (!pwConfirm) {
                pwMatchMsg.textContent = '* 비밀번호를 한 번 더 입력하세요.';
                pwMatchMsg.style.color = '#b7b7b7';
                return false;
            }

            if (pw === pwConfirm) {
                pwMatchMsg.textContent = '* 비밀번호가 일치합니다.';
                pwMatchMsg.style.color = '#4caf50';
                return true;
            } else {
                pwMatchMsg.textContent = '* 비밀번호가 일치하지 않습니다.';
                pwMatchMsg.style.color = '#e53637';
                return false;
            }
        }

        pwInput.addEventListener('input', validatePasswordMatch);
        pwConfirmInput.addEventListener('input', validatePasswordMatch);

        signupForm.addEventListener('submit', function (e) {
            // 중복 확인 버튼 클릭 시에는 비밀번호 검증 스킵
            if (e.submitter && e.submitter.name === 'check_id') {
                return;
            }

            if (!isIdChecked) {
                e.preventDefault();
                alert('아이디 중복 확인을 먼저 진행해주세요.');
                return;
            }

            if (!validatePasswordMatch()) {
                e.preventDefault();
                alert('비밀번호와 비밀번호 확인이 일치하지 않습니다.');
                pwConfirmInput.focus();
            }
        });
    })();
    </script>
</body>

</html>