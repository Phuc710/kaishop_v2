<?php
require __DIR__ . '/../../hethong/config.php';

$email = antixss($_POST['email']);
$username = antixss($_POST['username']);
$password = antixss($_POST['password']);

$check_user = $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$username'");
$check_mail = $ketnoi->query("SELECT * FROM `users` WHERE `email` = '$email'");

$recaptchaResponse = antixss($_POST['recaptchaResponse']);

$recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
$recaptchaSecretKey = '6LfJcyQpAAAAANddbEO0p2hdR74E_dEtL9mGBbGt';
$recaptchaData = array(
    'secret' => $recaptchaSecretKey,
    'response' => $recaptchaResponse
);
$recaptchaOptions = array(
    'http' => array(
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($recaptchaData)
    )
);
$recaptchaContext = stream_context_create($recaptchaOptions);
$recaptchaResult = file_get_contents($recaptchaUrl, false, $recaptchaContext);
$recaptchaResultJson = json_decode($recaptchaResult);

// Bỏ qua reCAPTCHA cho local hoặc nếu cần
if ($recaptchaResultJson->success || $_SERVER['SERVER_NAME'] == 'localhost') {
    if ($username == '' || $password == '' || $email == '') {
        $response = array('success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin');
    } else if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
        $response = array('success' => false, 'message' => 'Tên đăng nhập không bao gồm các kí tự đặc biệt và có dấu');
    } else if ($check_user->num_rows != 0) {
        $response = array('success' => false, 'message' => 'Tên đăng nhập đã được sử dụng, vui lòng chọn tên khác');
    } else if ($check_mail->num_rows != 0) {
        $response = array('success' => false, 'message' => 'Email đã được sử dụng, vui lòng chọn tên khác');
    } else if ($username == $password) {
        $response = array('success' => false, 'message' => 'Tên Đăng Nhập Không Được Trùng Mật Khẩu!');
    } else if (!preg_match("/([a-z0-9_]+|[a-z0-9_]+\.[a-z0-9_]+)@(([a-z0-9]|[a-z0-9]+\.[a-z0-9]+)+\.([a-z]{2,4}))/i", $email)) {
        $response = array('success' => false, 'message' => 'Eamil Không Đúng Định Dạng!');
    } else {
        function generateRandomString($length = 32)
        {
            return bin2hex(random_bytes($length / 2));
        }
        $randomString = generateRandomString();
        $apiKey = md5($randomString);
        $new_pass = sha1(md5($password));
        $toz = $ketnoi->query("INSERT INTO `users` SET 
            `username` = '$username',
            `password` = '$new_pass',
            `email` = '$email',
            `level` = '0',
            `tong_nap` = '0',
            `money` = '0',
            `bannd` = '0',
            `ip` = '" . $ip_address . "',
            `otpcode` = '',
            `session` = '',
            `api_key` = '$apiKey',
            `time` = '" . $time . "' ");
        $guitoi = $email;
        $subject = 'server';
        $bcc = 'server';
        $hoten = 'server';

        $noi_dung = '
        <html>
        <body>
            <table cellspacing="0" cellpadding="0" width="400" style="border: 1px solid #ccc; border-radius: 30px; margin: 0 auto;">
                <tr>
                    <td style="text-align: center; font-family: Arial, sans-serif; padding: 20px;">
                        <h1 style="color: #FF5733;">Chào Mừng Bạn Đến Với Dịch Vụ Của Chúng Tôi</h1>
                        <p>Xin chào ' . $username . ',</p>
                        <p>Chúng tôi xin chân thành cảm ơn bạn đã lựa chọn chúng tôi làm đối tác trong hành trình kinh doanh của bạn.</p>
                        <p>Hãy để chúng tôi đồng hành cùng bạn trong việc phát triển và tạo nên những thành công đáng nhớ.</p>
                        <p>Chúng tôi xin trân trọng mời bạn sử dụng các dịch vụ của chúng tôi:</p>
                        <ul>
                            <li>Mời Bạn Trãi Nghiệm Dịch Vụ Hosting - Mã Nguồn- Domain - Tạo Logo, Website</li>
                            <li>Bạn sẽ nhận được hỗ trợ 24/7 cho dịch vụ tên miền.</li>
                            <li>Chúng tôi luôn sẵn sàng hỗ trợ bạn mọi lúc mọi nơi!</li>
                        </ul>
                        <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi và chúng tôi rất vui được phục vụ bạn!</p>
                        <p>Trân trọng,</p>
                        <p>' . $hoten . '</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';

        sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);

        if ($toz) {
            $now_ss = random('0123456789qwertyuiopasdfghjlkzxcvbnmQEWRWROIWCJHSCNJKFBJWQ', 32);
            $ketnoi->query("UPDATE `users` SET `session` = '$now_ss' WHERE `username` = '" . $username . "' ");
            $_SESSION['session'] = $now_ss;
            $response = array('success' => true);
        } else {
            $response = array('success' => false, 'message' => 'Đã có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
} else {
    $response = array('success' => false, 'message' => 'Vui lòng xác nhận reCAPTCHA');
}

echo json_encode($response);
?>