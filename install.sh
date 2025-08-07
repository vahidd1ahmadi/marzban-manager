#!/bin/bash

# تابع برای نصب پروژه
install_project() {
    echo "=== نصب پروژه ==="
    # نصب پیش‌نیازها
    sudo apt update
    sudo apt install -y php php-curl composer

    # ایجاد پوشه src اگر وجود نداشته باشد
    mkdir -p src

    # بررسی نصب قبلی
    if [ -f "src/config.php" ]; then
        echo "پروژه از قبل نصب شده است. آیا می‌خواهید بازنصب کنید؟ (y/n)"
        read reinstall
        if [ "$reinstall" != "y" ]; then
            echo "نصب لغو شد."
            exit 1
        fi
    fi

    # دریافت اطلاعات از کاربر
    read -p "توکن ربات تلگرام را وارد کنید: " bot_token
    read -p "آیدی عددی ادمین را وارد کنید: " admin_id
    read -p "آدرس پنل مرزبان را وارد کنید (مثال: https://panel.example.com): " panel_url
    read -p "نام کاربری پنل را وارد کنید: " panel_username
    read -p "رمز عبور پنل را وارد کنید: " panel_password
    read -p "آیدی کانال لاگ (اختیاری، Enter برای رد شدن): " log_channel_id

    # اعتبارسنجی ورودی‌ها
    if [ -z "$bot_token" ] || [ -z "$admin_id" ] || [ -z "$panel_url" ] || [ -z "$panel_username" ] || [ -z "$panel_password" ]; then
        echo "خطا: همه فیلدهای اجباری باید پر شوند!"
        exit 1
    fi

    # ایجاد فایل config.php
    cat > src/config.php <<EOL
<?php
define('TELEGRAM_BOT_TOKEN', '$bot_token');
define('TELEGRAM_ADMIN_ID', '$admin_id');
define('PANEL_URL', '$panel_url');
define('PANEL_USERNAME', '$panel_username');
define('PANEL_PASSWORD', '$panel_password');
define('LOG_CHANNEL_ID', '$log_channel_id');
?>
EOL

    # نصب کتابخانه php-telegram-bot
    composer require telegram-bot/api

    # تنظیم وب‌هوک
    webhook_url="$panel_url/src/bot.php"
    curl -s "https://api.telegram.org/bot$bot_token/setWebhook?url=$webhook_url"
    echo -e "\nوب‌هوک با موفقیت تنظیم شد: $webhook_url"

    # اجرای ربات در پس‌زمینه
    nohup php src/bot.php > bot.log 2>&1 &
    echo "ربات با موفقیت نصب و اجرا شد. لاگ‌ها در bot.log ذخیره می‌شوند."
}

# تابع برای بررسی نصب
check_install() {
    echo "=== بررسی نصب ==="
    if [ -f "src/config.php" ]; then
        echo "پروژه نصب شده است."
        if pgrep -f "php src/bot.php" > /dev/null; then
            echo "ربات در حال اجرا است."
        else
            echo "ربات نصب شده اما اجرا نمی‌شود."
        fi
    else
        echo "پروژه نصب نشده است."
    fi
}

# تابع برای حذف پروژه
remove_project() {
    echo "=== حذف پروژه ==="
    read -p "آیا مطمئن هستید که می‌خواهید پروژه را حذف کنید؟ (y/n): " confirm
    if [ "$confirm" == "y" ]; then
        pkill -f "php src/bot.php"
        rm -rf src vendor composer.json composer.lock bot.log
        echo "پروژه با موفقیت حذف شد."
    else
        echo "حذف لغو شد."
    fi
}

# ایجاد فایل composer.json اگر وجود نداشته باشد
if [ ! -f "composer.json" ]; then
    cat > composer.json <<EOL
{
    "require": {
        "telegram-bot/api": "^2.3"
    }
}
EOL
fi

# منوی اصلی
while true; do
    echo "=== منوی نصب ربات تلگرام ==="
    echo "1. نصب پروژه"
    echo "2. بررسی نصب"
    echo "3. حذف پروژه"
    echo "4. خروج"
    read -p "گزینه را انتخاب کنید (1-4): " choice

    case $choice in
        1) install_project ;;
        2) check_install ;;
        3) remove_project ;;
        4) exit 0 ;;
        *) echo "گزینه نامعتبر!" ;;
    esac
done
