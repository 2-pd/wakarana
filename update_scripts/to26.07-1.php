<?php
include "main.php"; // このファイルはwakarana本体のmain.phpがあるフォルダで実行してください。

$base_dir = "."; // wakarana_config.iniがあるフォルダのパスを指定してください。


$config_path = $base_dir."/wakarana_config.ini";
$custom_fields_path = $base_dir."/wakarana_custom_fields.json";


print "wakarana_config.iniに設定項目を追加しています...\n";

$ini_data = file_get_contents($config_path);

$ini_data = preg_replace('/^display_errors/m', "use_config_cache = false\n\ndisplay_errors", $ini_data);

file_put_contents($config_path, $ini_data);


print "wakarana_custom_fields.jsonを変更しています...\n";

$custom_fields = json_decode(file_get_contents($custom_fields_path), TRUE);

foreach (array_keys($custom_fields) as $custom_field_name) {
    if ($custom_fields[$custom_field_name]["is_numeric"]) {
        if (!isset($custom_fields[$custom_field_name]["precision"])) {
            $custom_fields[$custom_field_name]["precision"] = 0;
        }
    }
    
    if (!isset($custom_fields[$custom_field_name]["trigger_user_last_updated"])) {
        $custom_fields[$custom_field_name]["trigger_user_last_updated"] = false;
    }
}

file_put_contents($custom_fields_path, json_encode($custom_fields));


print "データベースを移行しています...\n";

$profile = wakarana_profile::of($base_dir);

$profile->connect_db();
$profile->begin_transaction();

try {
    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec("ALTER TABLE `wakarana_user_custom_numerical_fields` RENAME TO `wakarana_user_custom_numerical_fields_tmp`;");
        
        $profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_custom_numerical_fields`(`user_id` TEXT COLLATE NOCASE NOT NULL, `custom_field_name` TEXT NOT NULL, `value_number` INTEGER NOT NULL, `custom_field_value` NUMERIC, PRIMARY KEY(`user_id`, `custom_field_name`, `value_number`))");
        $profile->db_obj->exec("INSERT INTO `wakarana_user_custom_numerical_fields`(`user_id`, `custom_field_name`, `value_number`, `custom_field_value`) SELECT `user_id`, `custom_field_name`, `value_number`, `custom_field_value` FROM `wakarana_user_custom_numerical_fields_tmp`");
        
        $profile->db_obj->exec("DROP TABLE `wakarana_user_custom_numerical_fields_tmp`");
        
        $profile->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_cn1" ON "wakarana_user_custom_numerical_fields"("user_id", "custom_field_name", "custom_field_value")');
        $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_cn2" ON "wakarana_user_custom_numerical_fields"("custom_field_name", "custom_field_value")');
    }
    
    $profile->db_obj->exec('DELETE FROM "wakarana_user_roles" WHERE "role_id" = \'__base__\'');
} catch (PDOException $err) {
    print "データベースの移行中にエラーが発生しました。\n".$err->getMessage();
    
    $profile->rollback_transaction();
    
    exit;
}

$profile->commit_transaction();


print "全ての処理が完了しました\n";
