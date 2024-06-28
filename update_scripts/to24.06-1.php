<?php
include "main.php";
include "config.php";

$base_dir = ".";


print "カスタムフィールド設定ファイルを更新しています...\n";

$custom_fields_path = $base_dir."/wakarana_custom_fields.json";

$custom_fields = json_decode(file_get_contents($custom_fields_path), TRUE);

$keys = array_keys($custom_fields);

foreach ($keys as $key) {
    if (!isset($custom_fields[$key]["is_numeric"])) {
        $custom_fields[$key]["is_numeric"] = FALSE;
    }
}

file_put_contents($custom_fields_path, json_encode($custom_fields));


print "メールドメインブラックリストを初期化しています...\n";

$wakarana_config = new wakarana_config($base_dir);


print "不要となるデータベーステーブルを削除しています...\n";

$wakarana = new wakarana($base_dir);

$wakarana->db_obj->exec('DROP TABLE IF EXISTS "wakarana_email_address_verification"');


print "新しいデータベーステーブルを追加しています...\n";

$wakarana_config->setup_db();


print "移行処理が完了しました。\n";
