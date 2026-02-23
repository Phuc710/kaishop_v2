<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../database/connection.php');
require_once('UrlHelper.php');
require_once('SwalHelper.php');


require_once __DIR__ . '/../app/Helpers/EnvHelper.php';
EnvHelper::load(dirname(__DIR__) . '/.env');

// ║  Localhost:   .env -> APP_DIR=/kaishop_v2                       ║
// ║  Production:  .env -> APP_DIR=                                  ║

define('APP_DIR', EnvHelper::get('APP_DIR', ''));

// File System Paths (auto-configured based on APP_DIR)
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . APP_DIR);
define('HETHONG_PATH', ROOT_PATH . '/hethong');
define('AJAX_PATH', ROOT_PATH . '/ajax');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ADMIN_PATH', ROOT_PATH . '/admin');


$version = "V1.0";
date_default_timezone_set('Asia/Ho_Chi_Minh');
$_SESSION['session_request'] = time();
$time = date('h:i d-m-Y');
$chungapi = $connection->query("SELECT * FROM `setting` ")->fetch_array();

// Helper: đọc setting từ $chungapi với giá trị mặc định
if (!function_exists('get_setting')) {
    function get_setting($key, $default = '')
    {
        global $chungapi;
        return isset($chungapi[$key]) && $chungapi[$key] !== '' ? $chungapi[$key] : $default;
    }
}

include_once('SMTP/class.smtp.php');
include_once('SMTP/PHPMailerAutoload.php');
include_once('SMTP/class.phpmailer.php');
$file = 'install.php';
if (file_exists($file)) {
    header('Location: /install.php');
    exit();
}
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip_address = $_SERVER['REMOTE_ADDR'];
}

if (isset($_SESSION['session'])) {
    $session = $_SESSION['session'];
    $user = $connection->query("SELECT * FROM `users` WHERE `session` = '$session' ")->fetch_array();

    if (empty($user['id'])) {
        session_destroy();
        header('location: /');
        exit;
    }

    if ($user['bannd'] == 1) {
        $_SESSION['banned_reason'] = $user['ban_reason'] ?: '';
        header('location: ' . APP_DIR . '/NotFound.php');
        exit;
    }

    // Check Fingerprint Device Ban
    if (!empty($user['fingerprint'])) {
        $fpHash = mysqli_real_escape_string($connection, $user['fingerprint']);
        $bf = $connection->query("SELECT * FROM `banned_fingerprints` WHERE `fingerprint_hash` = '$fpHash'")->fetch_assoc();
        if ($bf) {
            session_destroy();
            session_start();
            $_SESSION['banned_reason'] = $bf['reason'] ?: 'Bạn đã bị từ chối truy cập do vi phạm chính sách.';
            header('location: ' . APP_DIR . '/NotFound.php');
            exit;
        }
    }

    $username = $user['username'];
    if ($user['level'] == 9) {
        $_SESSION['admin'] = $username;
    }

    // Update Device Information on Every Access
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $now = date('Y-m-d H:i:s');
    $safe_ip = mysqli_real_escape_string($connection, $ip_address);
    $safe_ua = mysqli_real_escape_string($connection, $user_agent);

    $connection->query("UPDATE `users` SET `ip_address` = '$safe_ip', `user_agent` = '$safe_ua', `last_login` = '$now' WHERE `id` = '{$user['id']}'");

} else {
    $username = "";
}

//Mail auto
require_once __DIR__ . '/../app/Helpers/EnvHelper.php';
$smtp_server = !empty($chungapi['smtp']) ? $chungapi['smtp'] : EnvHelper::get('SMTP_HOST', 'smtp.gmail.com');
$smtp_port = !empty($chungapi['port_smtp']) ? $chungapi['port_smtp'] : EnvHelper::get('SMTP_PORT', '587');
$site_gmail_momo = !empty($chungapi['email_auto']) ? $chungapi['email_auto'] : EnvHelper::get('SMTP_USER', '');
$site_pass_momo = !empty($chungapi['pass_mail_auto']) ? $chungapi['pass_mail_auto'] : EnvHelper::get('SMTP_PASS', '');
$site_ten_nguoi_gui = !empty($chungapi['ten_nguoi_gui']) ? $chungapi['ten_nguoi_gui'] : EnvHelper::get('EMAIL_FROM_NAME', 'KaiShop');

if (!function_exists('sendCSM')) {
    function sendCSM($mail_nhan, $ten_nhan, $chu_de, $noi_dung, $bcc = '')
    {
        global $site_gmail_momo, $site_pass_momo, $smtp_server, $smtp_port, $site_ten_nguoi_gui;
        // PHPMailer Modify
        $mail = new PHPMailer();
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = "html";
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $site_gmail_momo; // GMAIL STMP
        $mail->Password = $site_pass_momo; // PASS STMP
        $mail->SMTPSecure = 'tls';
        $mail->Port = $smtp_port;
        $mail->setFrom($site_gmail_momo, $site_ten_nguoi_gui); // Sử dụng Tên Gửi Mail từ env mặc định
        $mail->addAddress($mail_nhan, $ten_nhan);
        $mail->addReplyTo($site_gmail_momo, $site_ten_nguoi_gui);
        $mail->isHTML(true);
        $mail->Subject = $chu_de;
        $mail->Body = $noi_dung;
        $mail->CharSet = 'UTF-8';
        $send = $mail->send();
        return $send;
    }
}
if (!function_exists('hideName')) {
    function hideName($name)
    {
        $length = strlen($name);

        // Kiểm tra nếu chiều dài tên ngắn hơn hoặc bằng 4, không cần ẩn
        if ($length <= 4) {
            return $name;
        }

        // Lấy 4 ký tự đầu tiên của tên
        $firstPart = substr($name, 0, 4);

        // Tạo chuỗi với ký tự '*' nằm sau
        $hiddenPart = str_repeat('*', $length - 4);

        // Kết hợp phần đầu và phần ẩn
        $hiddenName = $firstPart . $hiddenPart;

        return $hiddenName;
    }
}
if (!function_exists('BASE_URL')) {
    function BASE_URL($url)
    {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];
        return $base_url . '/' . $url;
    }
}
if (!function_exists('randomip')) {
    function randomip()
    {
        // Generate four random numbers in the range 1-255
        $octet1 = rand(1, 255);
        $octet2 = rand(1, 255);
        $octet3 = rand(1, 255);
        $octet4 = rand(1, 255);

        // Concatenate the numbers to form the IP address
        $ipAddress = $octet1 . '.' . $octet2 . '.' . $octet3 . '.' . $octet4;

        return $ipAddress;
    }
}

if (!function_exists('giftcode')) {
    function giftcode($code, $type)
    {
        global $connection;
        $check = $connection->query("SELECT * FROM `gift_code` WHERE `giftcode` = '$code' AND `type` = '$type'AND `soluong` - `dadung` >0 AND `status` = 'ON' ")->fetch_array();
        if (empty($check)) {
            $giamgia = 0;
        } else {
            $giamgia = $check['giamgia'];
        }
        return $giamgia;
    }
}
if (!function_exists('update_code')) {
    function update_code($code)
    {
        global $connection;
        $connection->query("UPDATE `gift_code` SET `dadung` = `dadung` + 1 WHERE `giftcode` = '" . $code . "' ");
    }
}
if (!function_exists('ngay')) {
    function ngay($date)
    {
        return date('h:i d-m-Y', $date);
    }
}
if (!function_exists('tien')) {
    function tien($price)
    {
        return str_replace(",", ".", number_format($price));
    }
}
$version = 'V1.0';

function antixss($data)
{
    // Fix &entity\n;
    $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

    // Remove any attribute starting with "on" or xmlns
    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

    // Remove javascript: and vbscript: protocols
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"\']*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"\']*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"\']*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"\']*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"\']*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"\']*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

    // Remove namespaced elements (we do not need them)
    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

    do {
        // Remove really unwanted tags
        $old_data = $data;
        $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
    } while ($old_data !== $data);
    // we are done...
    $xoa = htmlspecialchars(addslashes(trim($data)));
    return $xoa;
}
function random($string, $int)
{
    return substr(str_shuffle($string), 0, $int);
}
function dv_the($web_gach_the, $parter)
{
    $url = 'https://' . $web_gach_the . '/chargingws/v2/getfee?partner_id=' . $parter;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

function capbac($data)
{
    if ($data == 9) {
        $show = 'Quản Trị viên';
    } elseif ($data == 3) {
        $show = 'Cộng tác viên';
    } else {
        $show = 'Thành Viên';
    }
    return $show;
}

function code($data)
{
    if ($data == "thanhcong") {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">Thành Công</span>';
    } else if ($data == "loi") {
        $show = '<span type="span" class="btn btn-danger">Lỗi</span>';
    } else if ($data == "ON") {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">ON</span>';
    } else if ($data == "OFF") {
        $show = '<span type="span" class="btn btn-warning">OFF</span>';
    } else {
        $show = '<span type="span" class="btn btn-warning">Khác</span>';
    }
    return $show;
}

function napthe($data)
{
    if ($data == "xuly") {
        $show = '<span type="span" class="">Đang Xử Lý</span>';
    } else if ($data == "hoantat") {
        $show = '<span type="span" class="">Thành Công</span>';
    } else if ($data == "thatbai") {
        $show = '<span type="span" class="">Thất Bại</span>';
    } else {
        $show = '<span type="span" class="">Khác</span>';
    }
    return $show;
}
function status($data)
{
    if ($data == "xuly") {
        $show = '<span type="span" class="btn btn-warning">Đang Xử Lý</span>';
    } else if ($data == "hoatdong") {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">Hoạt Động</span>';
    } else if ($data == "hoantat") {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">Thành Công</span>';
    } else if ($data == "ON") {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">ON</span>';
    } else if ($data == "OFF") {
        $show = '<span type="span" class="btn btn-warning">OFF</span>';
    } else if ($data == "KHOA") {
        $show = '<span type="span" class="btn btn-warning">KHOÁ</span>';
    } else if ($data == "loi") {
        $show = '<span type="span" class="btn btn-danger">LỖI</span>';
    } else if ($data == "hethan") {
        $show = '<span type="span" class="btn btn-warning">HẾT HẠN</span>';
    } else if ($data == "xoa") {
        $show = '<span type="span" class="btn btn-danger">Xoá</span>';
    } else if ($data == "thatbai") {
        $show = '<span type="span" class="btn btn-danger">Thất Bại</span>';
    } elseif ($data == "tamkhoa") {
        $show = '<span type="span" class="btn btn-warning">Tạm Khoá</span>';
    } else {
        $show = '<span type="span" class="btn btn-warning">Khác</span>';
    }
    return $show;
}
function host($data)
{
    if ($data == "xuly") {
        $show = '<span type="span" class="btn btn-warning">Đang Xử Lý</span>';
    } else if ($data == "hoatdong") {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">Hoạt Động</span>';
    } else if ($data == "reset") {
        $show = '<span type="span" class="btn btn-warning">Reset</span>';
    } else if ($data == "tamkhoa") {
        $show = '<span type="span" class="btn btn-warning">Tạm Khoá</span>';
    } else if ($data == "dangtao") {
        $show = '<span type="span" class="btn btn-dark">Đang Tạo</span>';
    } else if ($data == "xoa") {
        $show = '<span type="span" class="btn btn-warning">Đang Xoá</span>';
    } else if ($data == "daxoa") {
        $show = '<span type="span" class="btn btn-danger">Đã Xoá</span>';
    } else if ($data == "huy") {
        $show = '<span type="span" class="btn btn-warning">Đã Hủy Và Hoàn Tiền.</span>';
    } else if ($data == "loi") {
        $show = '<span type="span" class="btn btn-danger">Lỗi!!!</span>';
    } else {
        $show = '<span type="span" class="btn btn-warning">Khác</span>';
    }
    return $show;
}
function bannd($data)
{
    if ($data == 0) {
        $show = '<span type="span" class="btn btn-success btn-sm w-100 fs-13">Hoạt Động</span>';
    } else if ($data == 1) {
        $show = '<span type="span" class="btn btn-danger">Band</span>';
    } else {
        $show = '<span type="span" class="btn btn-warning">Khác</span>';
    }
    return $show;
}

function XoaDauCach($text)
{
    return trim(preg_replace('/\s+/', ' ', $text));
}

function xoadau($strTitle)
{
    $strTitle = strtolower($strTitle);
    $strTitle = trim($strTitle);
    $strTitle = str_replace(' ', '-', $strTitle);
    $strTitle = preg_replace("/(ò|ó|ọ|ỏ|õ|ơ|ờ|ớ|ợ|ở|ỡ|ô|ồ|ố|ộ|ổ|ỗ)/", 'o', $strTitle);
    $strTitle = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ|Ô|Ố|Ổ|Ộ|Ồ|Ỗ)/", 'o', $strTitle);
    $strTitle = preg_replace("/(à|á|ạ|ả|ã|ă|ằ|ắ|ặ|ẳ|ẵ|â|ầ|ấ|ậ|ẩ|ẫ)/", 'a', $strTitle);
    $strTitle = preg_replace("/(À|Á|Ạ|Ả|Ã|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ|Â|Ấ|Ầ|Ậ|Ẩ|Ẫ)/", 'a', $strTitle);
    $strTitle = preg_replace("/(ề|ế|ệ|ể|ê|ễ|é|è|ẻ|ẽ|ẹ)/", 'e', $strTitle);
    $strTitle = preg_replace("/(Ể|Ế|Ệ|Ể|Ê|Ễ|É|È|Ẻ|Ẽ|Ẹ)/", 'e', $strTitle);
    $strTitle = preg_replace("/(ừ|ứ|ự|ử|ư|ữ|ù|ú|ụ|ủ|ũ)/", 'u', $strTitle);
    $strTitle = preg_replace("/(Ừ|Ứ|Ự|Ử|Ư|Ữ|Ù|Ú|Ụ|Ủ|Ũ)/", 'u', $strTitle);
    $strTitle = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $strTitle);
    $strTitle = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'i', $strTitle);
    $strTitle = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $strTitle);
    $strTitle = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'y', $strTitle);
    $strTitle = str_replace('đ', 'd', $strTitle);
    $strTitle = str_replace('Đ', 'd', $strTitle);
    $strTitle = preg_replace("/[^-a-zA-Z0-9]/", '', $strTitle);
    return $strTitle;
}
$tele_token = '';
$tele_chatid = '';

if (!function_exists('timeAgo')) {
    function timeAgo($datetime, $full = false)
    {
        $now = new DateTime();
        try {
            if (is_numeric($datetime)) {
                $ago = new DateTime('@' . $datetime);
                $ago->setTimezone($now->getTimezone());
            } else {
                $date = DateTime::createFromFormat('H:i d-m-Y', $datetime);
                if ($date) {
                    $ago = $date;
                } else {
                    $ago = new DateTime($datetime);
                }
            }
        } catch (Exception $e) {
            return $datetime;
        }

        $diff = $now->diff($ago);

        // Trường hợp thời gian trong tương lai
        if ($diff->invert == 0 && ($diff->s > 0 || $diff->i > 0 || $diff->h > 0 || $diff->d > 0)) {
            return 'trong tương lai';
        }

        $days = (int) $diff->d;
        $weeks = (int) floor($days / 7);
        $remainingDays = $days % 7;

        $parts = [];
        if ($diff->y)
            $parts['y'] = $diff->y . ' năm';
        if ($diff->m)
            $parts['m'] = $diff->m . ' tháng';
        if ($weeks)
            $parts['w'] = $weeks . ' tuần';
        if ($remainingDays)
            $parts['d'] = $remainingDays . ' ngày';
        if ($diff->h)
            $parts['h'] = $diff->h . ' tiếng';
        if ($diff->i)
            $parts['i'] = $diff->i . ' phút';
        if ($diff->s)
            $parts['s'] = $diff->s . ' giây';

        if (!$full) {
            $parts = array_slice($parts, 0, 1);
        }
        return $parts ? implode(', ', $parts) . ' trước' : 'vừa xong';
    }
}

function sendTele($message)
{
    global $tele_token, $tele_chatid;
    $data = http_build_query([
        'chat_id' => $tele_chatid,
        'text' => "🌟 kaishop.id.vn
📝 Nội dung: " . $message .
            "
🕒 Thời gian: " .
            date('d/m/Y H:i:s'),
    ]);
    $url = 'https://api.telegram.org/bot' . $tele_token . '/sendMessage';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

?>