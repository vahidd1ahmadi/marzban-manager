<?php
require_once 'config.php';

// تابع برای دریافت توکن
function token_panel($url, $username, $password) {
    $url_get_token = $url . '/api/admin/token';
    $data_token = ['username' => $username, 'password' => $password];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data_token),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'accept: application/json']
    ];
    $curl_token = curl_init($url_get_token);
    curl_setopt_array($curl_token, $options);
    $token = curl_exec($curl_token);
    if (curl_error($curl_token)) {
        send_telegram_log("خطا در دریافت توکن: " . curl_error($curl_token));
        return ['error' => curl_error($curl_token)];
    }
    curl_close($curl_token);
    return json_decode($token, true);
}

// تابع برای بررسی تغییرات کاربران
function check_user_changes($panel_url, $access_token) {
    $url = $panel_url . "/api/users";
    $headers = ["Content-Type: application/json", "Authorization: Bearer " . $access_token];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_error($ch)) {
        send_telegram_log("خطا در دریافت لیست کاربران: " . curl_error($ch));
        return ['error' => curl_error($ch)];
    }
    curl_close($ch);
    return json_decode($response, true);
}

// تابع برای بررسی تغییرات نودها
function check_node_changes($panel_url, $access_token) {
    $url = $panel_url . "/api/nodes";
    $headers = ["Content-Type: application/json", "Authorization: Bearer " . $access_token];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_error($ch)) {
        send_telegram_log("خطا در دریافت لیست نودها: " . curl_error($ch));
        return ['error' => curl_error($ch)];
    }
    curl_close($ch);
    return json_decode($response, true);
}

// تابع برای ارسال لاگ به تلگرام
function send_telegram_log($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => LOG_CHANNEL_ID ?: TELEGRAM_ADMIN_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
?>
