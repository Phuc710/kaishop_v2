<?php
require_once('ketnoi.php');
require_once('UrlHelper.php');


// ║  Localhost:   define('APP_DIR', '/kaishop_v2');                 ║
// ║  Production:  define('APP_DIR', '');                            ║

define('APP_DIR', '/kaishop_v2');

// File System Paths (auto-configured based on APP_DIR)
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . APP_DIR);
define('HETHONG_PATH', ROOT_PATH . '/hethong');
define('AJAX_PATH', ROOT_PATH . '/ajax');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('CRON_PATH', ROOT_PATH . '/cron_dlc');


$version = "V1.0";
date_default_timezone_set('Asia/Ho_Chi_Minh');
$_SESSION['session_request'] = time();
$time = date('h:i d-m-Y');
$chungapi = $ketnoi->query("SELECT * FROM `setting` ")->fetch_array();
include_once('SMTP/class.smtp.php');
include_once('SMTP/PHPMailerAutoload.php');
include_once('SMTP/class.phpmailer.php');
$file = 'install.php';
if (file_exists($file)) {
    header('Location: /install.php');
    exit();
}
if (isset($_SESSION['session'])) {
    $session = $_SESSION['session'];
    $user = $ketnoi->query("SELECT * FROM `users` WHERE `session` = '$session' ")->fetch_array();
    $username = $user['username'];
    if (empty($user['id'])) {
        session_start();
        session_destroy();
        header('location: /');
    }
    if ($user['bannd'] == 1) {
        session_start();
        session_destroy();
        header('location: /band.php');
    }
    if ($user['level'] == 9) {
        $_SESSION['admin'] = $username;
    }
} else {
    $username = "";
}

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip_address = $_SERVER['REMOTE_ADDR'];
}

//Mail auto
$smtp_server = $chungapi['smtp'];
$smtp_port = $chungapi['port_smtp'];
$site_gmail_momo = $chungapi['email_auto']; // NHẬP ĐỊA CHỈ EMAIL CỦA BẠN TẠI ĐÂY
$site_pass_momo = $chungapi['pass_mail_auto']; // NHẬP MK EMAIL CỦA BẠN TẠI ĐÂY
function sendCSM($mail_nhan, $ten_nhan, $chu_de, $noi_dung, $bcc)
{
    global $site_gmail_momo, $site_pass_momo, $smtp_server, $smtp_port;
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
    $mail->setFrom($site_gmail_momo, $bcc);
    $mail->addAddress($mail_nhan, $ten_nhan);
    $mail->addReplyTo($site_gmail_momo, $bcc);
    $mail->isHTML(true);
    $mail->Subject = $chu_de;
    $mail->Body = $noi_dung;
    $mail->CharSet = 'UTF-8';
    $send = $mail->send();
    return $send;
}
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
function BASE_URL($url)
{
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];
    return $base_url . '/' . $url;
}
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
function checkmien($domain)
{
    $url = 'https://tenten.vn/api/check/?domain=' . $domain;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}
function giftcode($code, $type)
{
    global $ketnoi;
    $check = $ketnoi->query("SELECT * FROM `gift_code` WHERE `giftcode` = '$code' AND `type` = '$type'AND `soluong` - `dadung` >0 AND `status` = 'ON' ")->fetch_array();
    if (empty($check)) {
        $giamgia = 0;
    } else {
        $giamgia = $check['giamgia'];
    }
    return $giamgia;
}
function update_code($code)
{
    global $ketnoi;
    $ketnoi->query("UPDATE `gift_code` SET `dadung` = `dadung` + 1 WHERE `giftcode` = '" . $code . "' ");
}
function ngay($date)
{
    return date('h:i d-m-Y', $date);
}
function tien($price)
{
    return str_replace(",", ".", number_format($price));
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
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

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
function parse_order_id($comment)
{
    // Define the pattern to match "DLC" followed by digits (user ID)
    $pattern = '/DLC(\d+)/i';

    // Use preg_match to find all matches in the comment
    preg_match($pattern, $comment, $matches);

    // Check if matches were found
    if (!empty($matches[1])) {
        // Return the last matched user ID (assuming the last one is the most relevant)
        return $matches[1];
    }

    // If no match is found, return null
    return null;
}

$tele_token = '';
$tele_chatid = '';
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