<?php
require __DIR__ . '/../../hethong/config.php';

// Retrieve API key and bank account information
$toz_nap = $connection->query("SELECT * FROM `list_bank` WHERE `type` = 'MBBank' ")->fetch_array();
$token = $toz_nap['api_key'];
$tk = $toz_nap['stk'];

// Prepare data for API request
$dataPost = array(
    "Loai_api" => "lsgdv2",
);
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.dichvudark.vn/api/ApiMbBank",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $dataPost,
    CURLOPT_HTTPHEADER => array(
        "Code: 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",
        "Token: $token",
        "Stk: $tk"
    )
));
$response = curl_exec($curl);
curl_close($curl);
$data = json_decode($response, true);

foreach ($data['transactionHistoryList'] as $transaction) {
    $tranId = $transaction['refNo'];
    $amount = $transaction['creditAmount'];
    $comment = $transaction['description'];
    $partnerId = $transaction['benAccountNo'];
    $partnerName = $transaction['benAccountName'];
    $idnap = '';
    $now = time();

    // Extract the user ID from comment using parse_order_id function
    $idnap = parse_order_id($comment);

    if ($idnap !== null) {
        // Check if the transaction ID is already processed
        $total_trans = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) FROM `history_nap_bank` WHERE `trans_id` = '$tranId' "))['COUNT(*)'];

        if ($total_trans == 0) {
            if ($amount > 0) {
                $toz_checkidnap = $connection->query("SELECT * FROM `users` WHERE `id` = '$idnap' ")->fetch_array();

                if ($toz_checkidnap) {
                    $username = $toz_checkidnap['username'];

                    // Insert the transaction into the database
                    $connection->query("INSERT INTO `history_nap_bank` SET 
                        `trans_id` = '$tranId',
                        `username` = '$username',
                        `type` = 'MB Bank',
                        `stk` = '$partnerId',
                        `ctk` = '$partnerName',
                        `thucnhan` = '$amount',
                        `status` = 'hoantat',
                        `time` = '$now' ");

                    // Update user's money and total deposit
                    $create = mysqli_query($connection, "UPDATE `users` SET `money`=`money`+ '$amount', `tong_nap` = `tong_nap` + '$amount' WHERE `username`='$username'");
                    sendTele($username . " Nạp Tiền Qua Mb Bank Thành Công " . $amount . "VND");
                }
            }
        }
    }
}
echo json_encode($response);
// ... (Rest of the code)
?>