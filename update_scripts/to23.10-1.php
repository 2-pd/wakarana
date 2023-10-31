<?php
include "main.php";
include "config.php";

$base_dir = ".";

print "カスタムフィールド設定ファイルを生成しています...\n";
file_put_contents($base_dir."/wakarana_custom_fields.json", "{}");

print "設定ファイルを更新しています...\n";
$ini_data = file_get_contents($base_dir."/wakarana_config.ini");
$ini_data = str_replace(array("allow_duplicate_email_address", "verification_email_expire"), array("allow_nonunique_email_address", "email_addresses_per_user=5\nverification_email_expire"), $ini_data);
file_put_contents($base_dir."/wakarana_config.ini", $ini_data);

print "不要となるデータベースインデックスを削除しています...\n";
$wakarana = new wakarana($base_dir);
$wakarana->db_obj->exec('DROP INDEX "wakarana_idx_u2"');
$wakarana->db_obj->exec('DROP INDEX "wakarana_idx_e1"');
$wakarana->db_obj->exec('DROP INDEX "wakarana_idx_e2"');

print "新しいデータベーステーブルを追加しています...\n";
$wakarana_config = new wakarana_config($base_dir);
$wakarana_config->setup_db();

print "メールアドレスデータを新しいテーブルに移行しています...\n";
$stmt = $wakarana->db_obj->query('SELECT "user_id", "email_address" FROM "wakarana_users"');
while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user = $wakarana->get_user($data["user_id"]);
    $user->add_email_address($data["email_address"]);
}

print "不要となったメールアドレスデータを削除しています...\n";
if ($wakarana->get_config_value("use_sqlite")) {
    $wakarana->db_obj->exec('ALTER TABLE `wakarana_users` RENAME TO `wakarana_users_tmp`');
    $wakarana->db_obj->exec('DROP INDEX `wakarana_idx_u1`');
    $wakarana->db_obj->exec('DROP INDEX `wakarana_idx_u3`');
    $wakarana_config->setup_db();
    $wakarana->db_obj->exec('INSERT INTO `wakarana_users`(`user_id`, `password`, `user_name`, `user_created`, `last_updated`, `last_access`, `status`, `totp_key`) SELECT `user_id`, `password`, `user_name`, `user_created`, `last_updated`, `last_access`, `status`, `totp_key` FROM `wakarana_users_tmp`');
    $wakarana->db_obj->exec('DROP TABLE `wakarana_users_tmp`');
} else {
    $wakarana->db_obj->exec('ALTER TABLE "wakarana_users" DROP COLUMN "email_address"');
}

print "移行処理が完了しました。\n";
