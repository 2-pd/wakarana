<?php
include "main.php"; // このファイルはwakarana本体のmain.phpがあるフォルダで実行してください。

$base_dir = "."; // wakarana_config.iniがあるフォルダのパスを指定してください。


$config_path = $base_dir."/wakarana_config.ini";

print "wakarana_config.iniに新しいオプションを追加しています...\n";

$ini_data = file_get_contents($config_path);

$ini_data = preg_replace('/^allow_nonunique_email_address/m', "use_argon2_for_password_hashing = true\nargon2_memory_cost = 19456\nargon2_time_cost = 2\nargon2_parallelism = 1\ndummy_password_hash = null\n\nallow_nonunique_email_address", $ini_data);
$ini_data = preg_replace('/^login_token_cookie_name/m', "session_token_cookie_name", $ini_data);
$ini_data = preg_replace('/^login_tokens_per_user/m', "delete_session_on_ip_address_change = false\n\nsessions_per_user", $ini_data);
$ini_data = preg_replace('/^login_token_expire/m', "session_expire", $ini_data);

file_put_contents($config_path, $ini_data);


print "データベースを移行しています...\n";

$profile = wakarana_profile::of($base_dir);

$profile->connect_db();
$profile->begin_transaction();

try {
    $profile->db_obj->exec('ALTER TABLE "wakarana_users" RENAME COLUMN "password" TO "password_hash"');

    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec('ALTER TABLE `wakarana_users` ADD COLUMN `used_invite_code` TEXT');
    } else {
        $profile->db_obj->exec('ALTER TABLE "wakarana_users" ALTER COLUMN "password_hash" TYPE text');
        $profile->db_obj->exec('ALTER TABLE "wakarana_users" ADD COLUMN "used_invite_code" varchar(16)');
    }
    
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u4" ON "wakarana_users"("used_invite_code", "user_created")');
    
    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_recovery_codes`(`user_id` TEXT COLLATE NOCASE NOT NULL, `recovery_code` TEXT NOT NULL, PRIMARY KEY(`user_id`, `recovery_code`))");
    } else {
        $profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_recovery_codes"("user_id" varchar(60) NOT NULL, "recovery_code" varchar(24) NOT NULL, PRIMARY KEY("user_id", "recovery_code"))');
    }
    
    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_sessions`(`session_id` TEXT NOT NULL PRIMARY KEY, `token` TEXT NOT NULL UNIQUE, `user_id` TEXT COLLATE NOCASE NOT NULL, `token_created` TEXT NOT NULL, `ip_address` TEXT NOT NULL, `operating_system` TEXT, `browser_name` TEXT, `last_access` TEXT NOT NULL)");
    } else {
        $profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_sessions"("session_id" varchar(16) NOT NULL PRIMARY KEY, "token" varchar(43) NOT NULL UNIQUE, "user_id" varchar(60) NOT NULL, "token_created" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL, "operating_system" varchar(30), "browser_name" varchar(30), "last_access" timestamp NOT NULL)');
    }
    
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_s1" ON "wakarana_sessions"("token_created")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_s2" ON "wakarana_sessions"("user_id", "token_created")');

    $profile->db_obj->exec('INSERT INTO "wakarana_sessions" ("session_id", "token", "user_id", "token_created", "ip_address", "operating_system", "browser_name", "last_access") SELECT SUBSTR("token", 1, 16), "token", "user_id", "token_created", "ip_address", "operating_system", "browser_name", "last_access" FROM "wakarana_login_tokens"');
    
    $profile->db_obj->exec('DROP TABLE "wakarana_login_tokens"');
    $profile->db_obj->exec('DROP TABLE "wakarana_invite_codes"');
    
    if ($profile->get_config("use_sqlite")) {
        $profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_invite_codes`(`invite_code` TEXT NOT NULL PRIMARY KEY, `is_active` INTEGER NOT NULL, `user_id` TEXT COLLATE NOCASE, `code_created` TEXT NOT NULL, `code_expire` TEXT, `remaining_number` INTEGER, `usage_count` INTEGER NOT NULL)");
    } else {
        $profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_invite_codes"("invite_code" varchar(16) NOT NULL PRIMARY KEY, "is_active" boolean NOT NULL, "user_id" varchar(60), "code_created" timestamp NOT NULL, "code_expire" timestamp, "remaining_number" integer, "usage_count" integer NOT NULL)');
    }
    
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i1" ON "wakarana_invite_codes"("is_active", "code_expire")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i2" ON "wakarana_invite_codes"("code_created")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i3" ON "wakarana_invite_codes"("is_active", "code_created")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i4" ON "wakarana_invite_codes"("user_id", "code_created")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i5" ON "wakarana_invite_codes"("user_id", "is_active", "code_created")');
    $profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i6" ON "wakarana_invite_codes"("usage_count", "is_active")');
} catch (PDOException $err) {
    print "データベースの移行中にエラーが発生しました。\n".$err->getMessage();
    
    $profile->rollback_transaction();
    
    exit;
}

$profile->commit_transaction();

print "全ての処理が完了しました\n";
