<?php
include "main.php"; // このファイルはwakarana本体のmain.phpがあるフォルダで実行してください。

$base_dir = "."; // wakarana_config.iniがあるフォルダのパスを指定してください。


$config_path = $base_dir."/wakarana_config.ini";

print "wakarana_config.iniの設定項目を変更しています...\n";

$ini_data = file_get_contents($config_path);

$ini_data = preg_replace('/^minimum_authenticate_interval/m', "auth_initial_lockout_seconds", $ini_data);
$ini_data = preg_replace('/^authenticate_logs_per_user\s*=\s[0-9]+/m', "auth_max_lockout_seconds = 60", $ini_data);
$ini_data = preg_replace('/^authenticate_log_retention_time/m', "auth_failure_expiration_seconds = 1800\nauth_log_retention_seconds", $ini_data);

file_put_contents($config_path, $ini_data);


print "データベースを移行しています...\n";

$profile = wakarana_profile::of($base_dir);

$profile->connect_db();
$profile->begin_transaction();

try {
    $profile->db_obj->exec('DROP TABLE "wakarana_authenticate_logs"');
    
    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_authentication_logs`(`ip_address` TEXT NOT NULL, `authentication_id` TEXT COLLATE NOCASE, `authentication_type` TEXT NOT NULL, `succeeded` INTEGER, `failure_reason` TEXT, `authentication_datetime` TEXT NOT NULL)");
    } else {
        $profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_authentication_logs"("ip_address" varchar(39) NOT NULL, "authentication_id" varchar(254), "authentication_type" varchar(60) NOT NULL, "succeeded" boolean, "failure_reason" text, "authentication_datetime" timestamp NOT NULL)');
    }
    
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a1" ON "wakarana_authentication_logs"("authentication_id", "authentication_datetime")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a2" ON "wakarana_authentication_logs"("authentication_datetime")');
    
    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_failed_authentication_per_ip_address`(`ip_address` TEXT NOT NULL PRIMARY KEY, `failure_count` INTEGER NOT NULL, `last_authentication_datetime` TEXT NOT NULL)");
    } else {
        $profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_failed_authentication_per_ip_address"("ip_address" varchar(39) NOT NULL PRIMARY KEY, "failure_count" integer NOT NULL, "last_authentication_datetime" timestamp NOT NULL)');
    }
    
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_fa1" ON "wakarana_failed_authentication_per_ip_address"("last_authentication_datetime")');
} catch (PDOException $err) {
    print "データベースの移行中にエラーが発生しました。\n".$err->getMessage();
    
    $profile->rollback_transaction();
    
    exit;
}

$profile->commit_transaction();

print "全ての処理が完了しました\n";
