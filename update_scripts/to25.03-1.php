<?php
include "main.php";
include "config.php";

$base_dir = ".";


print "設定ファイルを更新しています...\n";

$wakarana_config = new wakarana_config($base_dir);

$wakarana_config->set_config_value("verification_email_sendable_interval", 10);


print "不要なデータベーステーブルを削除しています...\n";

$wakarana = new wakarana($base_dir);

$wakarana->db_obj->exec('DROP TABLE "wakarana_email_address_verification_codes"');


print "新しいデータベーステーブルを追加しています...\n";

$wakarana_config->setup_db();


print "移行処理が完了しました。\n";
