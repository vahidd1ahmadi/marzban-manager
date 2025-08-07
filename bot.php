<?php
require_once 'vendor/autoload.php';
require_once 'marzban_api.php';

use Telegram\Bot\Api;

$telegram = new Api(TELEGRAM_BOT_TOKEN);

// ذخیره وضعیت قبلی کاربران و نودها
$previous_users = [];
$previous_nodes = [];

// دریافت توکن پنل
$token_response = token_panel(PANEL_URL, PANEL_USERNAME, PANEL_PASSWORD);
if (!isset($token_response['access_token'])) {
    send_telegram_log("خطا در دریافت توکن پنل: " . json_encode($token_response));
    exit;
}
$access_token = $token_response['access_token'];

// حلقه برای رصد تغییرات
while (true) {
    // بررسی کاربران
    $users = check_user_changes(PANEL_URL, $access_token);
    if (!isset($users['error'])) {
        foreach ($users as $user) {
            $username = $user['username'];
            if (!isset($previous_users[$username])) {
                send_telegram_log("کاربر جدید ساخته شد: $username");
            } elseif ($previous_users[$username] != $user) {
                send_telegram_log("کاربر ویرایش شد: $username\nجزئیات: " . json_encode($user, JSON_PRETTY_PRINT));
            }
            if ($user['status'] == 'expired') {
                send_telegram_log("کاربر منقضی شد: $username");
            }
        }
        // بررسی حذف کاربران
        foreach ($previous_users as $username => $data) {
            if (!isset($users[$username])) {
                send_telegram_log("کاربر حذف شد: $username");
            }
        }
        $previous_users = $users;
    }

    // بررسی نودها
    $nodes = check_node_changes(PANEL_URL, $access_token);
    if (!isset($nodes['error'])) {
        foreach ($nodes as $node) {
            $node_id = $node['id'];
            if (!isset($previous_nodes[$node_id])) {
                send_telegram_log("نود جدید اضافه شد: $node_id");
            } elseif ($previous_nodes[$node_id]['status'] != $node['status']) {
                send_telegram_log("وضعیت نود تغییر کرد: $node_id\nوضعیت جدید: " . $node['status']);
            }
        }
        // بررسی حذف نودها
        foreach ($previous_nodes as $node_id => $data) {
            if (!isset($nodes[$node_id])) {
                send_telegram_log("نود حذف شد: $node_id");
            }
        }
        $previous_nodes = $nodes;
    }

    // بررسی لاگین ادمین
    $login_url = PANEL_URL . "/api/admin/logins"; // فرضی
    $headers = ["Content-Type: application/json", "Authorization: Bearer " . $access_token];
    $ch = curl_init($login_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $logins = json_decode(curl_exec($ch), true);
    if (!isset($logins['error'])) {
        foreach ($logins as $login) {
            send_telegram_log("لاگین جدید: کاربر {$login['username']} در {$login['timestamp']}");
        }
    }
    curl_close($ch);

    sleep(60); // بررسی هر 60 ثانیه
}
?>
