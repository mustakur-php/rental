#!/usr/bin/env bash
#
# نشر التحديثات على السيرفر.
#
#   ssh root@76.13.45.201 bash /var/www/rental/deploy.sh
#
# بلا علامات اقتباس مفردة: cmd.exe على ويندوز لا يعتبرها أداة اقتباس بل
# يمررها حرفاً ضمن الأمر، فيصل السيرفر أمرٌ اسمه "'bash …deploy.sh'".
#
# vendor/ و node_modules/ و public/build مستثناة في .gitignore، فلا يكفي
# git pull وحده — الاعتماديات والأصول تُبنى هنا. قبل إضافة هذا الملف كانت
# أصول الواجهة تُبنى يدوياً، فبقيت أشهراً بلا تحديث وظهرت تنسيقات ناقصة.

set -euo pipefail

cd "$(dirname "$0")"

step() { printf '\n\033[1;34m▶ %s\033[0m\n' "$1"; }

step 'سحب الكود'
# عمليات chmod على storage/ وغيرها تُغيّر بتات الصلاحيات لملفات متتبَّعة، فيراها
# git تعديلات ويرفض الدمج — رغم أن المحتوى لم يتغيّر. في نسخة نشر المحتوى وحده
# هو المهم، والصلاحيات تضبطها خطوة الملكية أدناه.
git config core.fileMode false
git pull origin main

step 'اعتماديات PHP'
composer install --no-dev --optimize-autoloader --no-interaction

step 'اعتماديات الواجهة'
npm ci --no-audit --no-fund

step 'بناء الأصول'
npm run build

step 'ترحيلات قاعدة البيانات'
php artisan migrate --force

step 'إعادة بناء الكاش'
php artisan optimize

# الأوامر أعلاه تعمل بمستخدم root فتُنتج ملفات يعجز php-fpm (www-data) عن
# الكتابة عليها لاحقاً — لذلك نُعيد الملكية في كل مرة.
step 'ضبط الملكية'
chown -R www-data:www-data public/build storage bootstrap/cache

printf '\n\033[1;32m✅ اكتمل النشر\033[0m\n'
