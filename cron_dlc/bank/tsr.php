<?php require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';?>

<?php
$toz_nap = $connection->query("SELECT * FROM `list_bank` WHERE `type` = 'TSR' ")->fetch_array();
$token = $toz_nap['api_key'];
    $dataPost = array(
 "Loai_api" => "lsgd",
 );
 $curl = curl_init();
 curl_setopt_array($curl, array(
 CURLOPT_URL => "https://api.dichvudark.vn/api/ApiTsr",
 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_SSL_VERIFYPEER => false,
 CURLOPT_MAXREDIRS => 10,
 CURLOPT_TIMEOUT => 30,
 CURLOPT_CUSTOMREQUEST => "POST",
 CURLOPT_POSTFIELDS => $dataPost,
 CURLOPT_HTTPHEADER => array(
 "Code: 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",
 "Token: $token")
 ));

    $response = curl_exec($curl);
    curl_close($curl);

    $data2 = json_decode($response, true);

    foreach ($data2['tranList'] as $mm) {
        $tranId = $mm['tranId'];
        $amount = $mm['amount'];
        $partnerName = $mm['username_send_or_receive'];
        $comment = $mm['comment'];
        $time = $mm['time'];
        $now = time();
        $status = $mm['trangthai'];
        $type = $mm['type'];

        if ($status == "ok" && $type == "nhantien") {
            $idnap = parse_order_id($comment);
            $toz_checkidnap = $connection->query("SELECT * FROM `users` WHERE `id` = '$idnap' ")->fetch_array();
            if ($toz_checkidnap) {
                $total_trans = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) FROM `history_nap_bank` WHERE `trans_id` = '$tranId' ")) ['COUNT(*)']; 
                if ($total_trans == 0) {
                    $username =  $toz_checkidnap['username'];
                    $connection->query("INSERT INTO `history_nap_bank` SET 
                        `trans_id` = '$tranId',
                        `username` = '$username',
                        `type` = 'TheSieuRe',
                        `stk` = '$partnerName',
                        `ctk` = '$partnerName',
                        `thucnhan` = '$amount',
                        `status` = 'hoantat',
                        `time` = '$now' ");
                        sendTele($username." Nạp TheSieuRe Thành Công ".$amount."VND");
                    $create = mysqli_query($connection, "UPDATE `users` SET `money`=`money`+ '$amount', `tong_nap` = `tong_nap` + '$amount' WHERE `username`='$username'");
                }
            }
        }
    }
echo $response;
?>

