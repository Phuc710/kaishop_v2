<?php
require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';

$toz_nap = $ketnoi->query("SELECT * FROM `list_bank` WHERE `type` = 'ZLP' ")->fetch_array();
$token = $toz_nap['api_key'];
$dataPost = array(
 "Loai_api" => "lsgd",
 );
 $curl = curl_init();
 curl_setopt_array($curl, array(
 CURLOPT_URL => "https://api.dichvudark.vn/api/ApiZaloPay",
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
 //hiện kết quả
 print_r($response);

$data = json_decode($response, true);

foreach ($data['data'] as $transaction) {
    $tranId = $transaction['transid'];
    $amount = $transaction['amount'];
    $comment = $transaction['description'];
    $partnerId = $transaction['userid'];
    $partnerName = $transaction['username'];
    $idnap = parse_order_id($comment);
    $now = time();
    
    $toz_checkidnap = $ketnoi->query("SELECT * FROM `users` WHERE `id` = '$idnap' ")->fetch_array();
    if ($toz_checkidnap) {
        $total_trans = mysqli_fetch_assoc(mysqli_query($ketnoi, "SELECT COUNT(*) FROM `history_nap_bank` WHERE `trans_id` = '$tranId' ")) ['COUNT(*)'];
        if ($total_trans == 0) {
            if ($amount > 0) {
                $username = $toz_checkidnap['username'];
                $ketnoi->query("INSERT INTO `history_nap_bank` SET 
                    `trans_id` = '$tranId',
                    `username` = '$username',
                    `type` = 'ZaloPay',
                    `stk` = '$partnerId',
                    `ctk` = '$partnerName',
                    `thucnhan` = '$amount',
                    `status` = 'hoantat',
                    `time` = '$now' ");
                $create = mysqli_query($ketnoi, "UPDATE `users` SET `money`=`money`+ '$amount', `tong_nap` = `tong_nap` + '$amount' WHERE `username`='$username'");
            }
        }
    }
}

print_r($response);
?>
