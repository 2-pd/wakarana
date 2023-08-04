<?php
/*Wakarana config.php*/
require_once(dirname(__FILE__)."/common.php");

define("WAKARANA_CONFIG_ORIGINAL",
    array(
            "display_errors" => TRUE,
            
            "use_sqlite" => TRUE,
            "sqlite_db_file" => "wakarana.db",
            
            "pg_host" => "localhost",
            "pg_user" => "postgres",
            "pg_pass" => "",
            "pg_db" => "wakarana",
            "pg_port" => 5432,
            
            "allow_weak_password" => FALSE,
            
            "allow_duplicate_email_address" => FALSE,
            "verification_email_expire" => 1800,
            
            "login_token_cookie_name" => "wakarana_login_token",
            "cookie_domain" => "",
            
            "login_tokens_per_user" => 4,
            "login_token_expire" => 2592000,
            "one_time_tokens_per_user" => 8,
            "one_time_token_expire" => 43200,
            
            "min_attempt_interval" => 5,
            "attempt_logs_per_user" => 20,
            "attempt_log_retention_time" => 1209600,
            
            "password_reset_token_expire" => 1800,
            
            "totp_pin_expire" => 1,
            "totp_temporary_token_expire" => 600,
            
            "proxy_count" => 0
        )
    );


class wakarana_config extends wakarana_common {
    function save () {
        $file_h = @fopen($this->base_path."/wakarana_config.ini","w");
        
        if (empty($file_h)) {
            $this->print_error("設定ファイルを書き込みモードで開くことができませんでした。");
            return FALSE;
        }
        
        if ($this->config["display_errors"]) {
            fwrite($file_h,"display_errors=true\n");
        } else {
            fwrite($file_h,"display_errors=false\n");
        }
        fwrite($file_h,"\n");
        
        if ($this->config["use_sqlite"]) {
            fwrite($file_h,"use_sqlite=true\n");
        } else {
            fwrite($file_h,"use_sqlite=false\n");
        }
        fwrite($file_h,"sqlite_db_file=\"".$this->config["sqlite_db_file"]."\"\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"pg_host=\"".$this->config["pg_host"]."\"\n");
        fwrite($file_h,"pg_user=\"".$this->config["pg_user"]."\"\n");
        fwrite($file_h,"pg_pass=\"".$this->config["pg_pass"]."\"\n");
        fwrite($file_h,"pg_db=\"".$this->config["pg_db"]."\"\n");
        fwrite($file_h,"pg_port=".$this->config["pg_port"]."\n");
        fwrite($file_h,"\n");
        
        if ($this->config["allow_weak_password"]) {
            fwrite($file_h,"allow_weak_password=true\n");
        } else {
            fwrite($file_h,"allow_weak_password=false\n");
        }
        fwrite($file_h,"\n");
        
        if ($this->config["allow_duplicate_email_address"]) {
            fwrite($file_h,"allow_duplicate_email_address=true\n");
        } else {
            fwrite($file_h,"allow_duplicate_email_address=false\n");
        }
        fwrite($file_h,"verification_email_expire=".$this->config["verification_email_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"login_token_cookie_name=\"".$this->config["login_token_cookie_name"]."\"\n");
        fwrite($file_h,"cookie_domain=\"".$this->config["cookie_domain"]."\"\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"login_tokens_per_user=".$this->config["login_tokens_per_user"]."\n");
        fwrite($file_h,"login_token_expire=".$this->config["login_token_expire"]."\n");
        fwrite($file_h,"one_time_tokens_per_user=".$this->config["one_time_tokens_per_user"]."\n");
        fwrite($file_h,"one_time_token_expire=".$this->config["one_time_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"min_attempt_interval=".$this->config["min_attempt_interval"]."\n");
        fwrite($file_h,"attempt_logs_per_user=".$this->config["attempt_logs_per_user"]."\n");
        fwrite($file_h,"attempt_log_retention_time=".$this->config["attempt_log_retention_time"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"password_reset_token_expire=".$this->config["password_reset_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"totp_pin_expire=".$this->config["totp_pin_expire"]."\n");
        fwrite($file_h,"totp_temporary_token_expire=".$this->config["totp_temporary_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"proxy_count=".$this->config["proxy_count"]."\n");
        
        fclose($file_h);
        
        return TRUE;
    }
    
    
    function set_config_value ($key, $value, $save_now = TRUE) {
        if (!isset($value) || gettype(WAKARANA_CONFIG_ORIGINAL[$key]) !== gettype($value)) {
            $this->print_error("設定ファイルの変数値を変更できません。変数型が不正です。");
            return FALSE;
        }
        
        $this->config[$key] = $value;
        
        if ($save_now) {
            $this->save();
        }
        
        return TRUE;
    }
    
    
    function reset_config () {
        $this->config = WAKARANA_CONFIG_ORIGINAL;
        
        $this->save();
    }
    
    
    function setup_db () {
        $this->connect_db();
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_users`(`user_id` TEXT COLLATE NOCASE NOT NULL PRIMARY KEY, `password` TEXT NOT NULL, `user_name` TEXT COLLATE NOCASE, `email_address` TEXT, `user_created` TEXT NOT NULL, `last_updated` TEXT NOT NULL, `last_access` TEXT NOT NULL, `status` INTEGER NOT NULL, `totp_key` TEXT)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_users"("user_id" varchar(60) NOT NULL PRIMARY KEY, "password" varchar(128) NOT NULL, "user_name" varchar(240), "email_address" varchar(254), "user_created" timestamp NOT NULL, "last_updated" timestamp NOT NULL, "last_access" timestamp NOT NULL, "status" smallint NOT NULL, "totp_key" varchar(16))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_users の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE INDEX IF NOT EXISTS `wakarana_idx_u1` ON `wakarana_users`(`user_name`)");
            } else {
                $this->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_u0" ON "wakarana_users"((LOWER("user_id")))');
                $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u1" ON "wakarana_users"(LOWER("user_name"))');
            }
            
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u2" ON "wakarana_users"("email_address")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u3" ON "wakarana_users"("user_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_users のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_login_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE NOT NULL, `token_created` TEXT NOT NULL, `ip_address` TEXT NOT NULL, `operating_system` TEXT, `browser_name` TEXT, `last_access` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_login_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) NOT NULL, "token_created" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL, "operating_system" varchar(30), "browser_name" varchar(30), "last_access" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_login_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_l1" ON "wakarana_login_tokens"("user_id", "token_created")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_l2" ON "wakarana_login_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_login_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_roles`(`user_id` TEXT COLLATE NOCASE NOT NULL, `role_name` TEXT NOT NULL, PRIMARY KEY(`user_id`, `role_name`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_roles"("user_id" varchar(60) NOT NULL, "role_name" varchar(60) NOT NULL, PRIMARY KEY("user_id", "role_name"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_roles の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_r1" ON "wakarana_user_roles"("role_name", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_roles のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permission_values`(`role_name` TEXT NOT NULL, `permission_name` TEXT NOT NULL, `permission_value` INTEGER NOT NULL, PRIMARY KEY(`role_name`, `permission_name`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permission_values"("role_name" varchar(60) NOT NULL, "permission_name" varchar(120) NOT NULL, "permission_value" integer NOT NULL, PRIMARY KEY("role_name", "permission_name"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permission_values の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_p1" ON "wakarana_permission_values"("permission_name", "permission_value", "role_name")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permission_values のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_one_time_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE NOT NULL, `token_created` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_one_time_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) NOT NULL, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_one_time_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_o1" ON "wakarana_one_time_tokens"("user_id", "token_created")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_o2" ON "wakarana_one_time_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_one_time_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_attempt_logs`(`user_id` TEXT COLLATE NOCASE NOT NULL, `succeeded` INTEGER NOT NULL, `attempt_datetime` TEXT NOT NULL, `ip_address` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_attempt_logs"("user_id" varchar(60) NOT NULL, "succeeded" boolean NOT NULL, "attempt_datetime" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_attempt_logs の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a1" ON "wakarana_attempt_logs"("user_id", "attempt_datetime")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a2" ON "wakarana_attempt_logs"("ip_address", "attempt_datetime")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a3" ON "wakarana_attempt_logs"("attempt_datetime")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_attempt_logs のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_email_address_verification`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE UNIQUE, `email_address` TEXT NOT NULL, `token_created` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_email_address_verification"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) UNIQUE, "email_address" varchar(254) NOT NULL, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_email_address_verification の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_e1" ON "wakarana_email_address_verification"("email_address", "user_id")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_e2" ON "wakarana_email_address_verification"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_email_address_verification のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_password_reset_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE UNIQUE NOT NULL, `token_created` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_password_reset_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) UNIQUE NOT NULL, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_password_reset_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_pr1" ON "wakarana_password_reset_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_password_reset_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_totp_temporary_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE UNIQUE NOT NULL, `token_created` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_totp_temporary_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) UNIQUE NOT NULL, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_totp_temporary_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_t1" ON "wakarana_totp_temporary_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_totp_temporary_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->disconnect_db();
        return TRUE;
    }
}
