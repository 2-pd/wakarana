<?php
include "main.php";
include "config.php";

$base_dir = ".";


print "特権管理者ユーザーをリストアップしています...\n";

$wakarana = new wakarana($base_dir);

$stmt = $wakarana->db_obj->query('SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_name" = \'__ADMIN__\'');
$admin_user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);


print "不要なテーブルを削除しています...\n";

$wakarana->db_obj->exec('DROP TABLE "wakarana_user_roles"');
$wakarana->db_obj->exec('DROP TABLE "wakarana_permission_values"');


print "新しいデータベーステーブルを追加しています...\n";

$wakarana_config = new wakarana_config($base_dir);

$wakarana_config->setup_db();


print "既存ユーザーにベースロールを割り当てています...\n";

$wakarana->db_obj->exec('INSERT INTO "wakarana_user_roles"("user_id", "role_id") SELECT "user_id", \''.WAKARANA_BASE_ROLE.'\' FROM "wakarana_users"');


print "特権管理者ロールを再割り当てしています...\n";

foreach ($admin_user_ids as $admin_user_id) {
    $admin_user = $wakarana->get_user($admin_user_id);
    $admin_user->add_role(WAKARANA_ADMIN_ROLE);
}


print "移行処理が完了しました。\n";
