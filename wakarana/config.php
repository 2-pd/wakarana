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
            
            "allow_nonunique_email_address" => FALSE,
            "email_addresses_per_user" => 5,
            "verification_email_expire" => 1800,
            "verification_email_sendable_interval" => 10,
            
            "login_token_cookie_name" => "wakarana_login_token",
            "cookie_domain" => "",
            
            "login_tokens_per_user" => 4,
            "login_token_expire" => 2592000,
            "one_time_tokens_per_user" => 8,
            "one_time_token_expire" => 43200,
            
            "minimum_authenticate_interval" => 5,
            "authenticate_logs_per_user" => 20,
            "authenticate_log_retention_time" => 1209600,
            
            "password_reset_token_expire" => 1800,
            
            "totp_pin_expire" => 1,
            "two_step_verification_token_expire" => 600,
            
            "proxy_count" => 0
        )
    );


class wakarana_config extends wakarana_common {
    function __construct ($base_dir = NULL) {
        $this->update_base_path($base_dir);
        
        if (!file_exists($this->base_path."/wakarana_config.ini")) {
            $this->reset_config();
        }
        
        if (!file_exists($this->base_path."/wakarana_custom_fields.json")) {
            file_put_contents($this->base_path."/wakarana_custom_fields.json", "{}");
        }
        
        if (!file_exists($this->base_path."/wakarana_email_domain_blacklist.conf")) {
            touch($this->base_path."/wakarana_email_domain_blacklist.conf");
        }
        
        parent::__construct($base_dir);
    }
    
    protected function save () {
        $file_h = @fopen($this->base_path."/wakarana_config.ini","w");
        
        if (empty($file_h)) {
            $this->print_error("設定ファイルを書き込みモードで開くことができませんでした。");
            return FALSE;
        }
        
        fwrite($file_h,"display_errors = ".($this->config["display_errors"] ? "true" : "false")."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"use_sqlite = ".($this->config["use_sqlite"] ? "true" : "false")."\n");
        fwrite($file_h,"sqlite_db_file = \"".$this->config["sqlite_db_file"]."\"\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"pg_host = \"".$this->config["pg_host"]."\"\n");
        fwrite($file_h,"pg_user = \"".$this->config["pg_user"]."\"\n");
        fwrite($file_h,"pg_pass = \"".$this->config["pg_pass"]."\"\n");
        fwrite($file_h,"pg_db = \"".$this->config["pg_db"]."\"\n");
        fwrite($file_h,"pg_port = ".$this->config["pg_port"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"allow_weak_password = ".($this->config["allow_weak_password"] ? "true" : "false")."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"allow_nonunique_email_address = ".($this->config["allow_nonunique_email_address"] ? "true" : "false")."\n");
        fwrite($file_h,"email_addresses_per_user = ".$this->config["email_addresses_per_user"]."\n");
        fwrite($file_h,"verification_email_expire = ".$this->config["verification_email_expire"]."\n");
        fwrite($file_h,"verification_email_sendable_interval = ".$this->config["verification_email_sendable_interval"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"login_token_cookie_name = \"".$this->config["login_token_cookie_name"]."\"\n");
        fwrite($file_h,"cookie_domain = \"".$this->config["cookie_domain"]."\"\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"login_tokens_per_user = ".$this->config["login_tokens_per_user"]."\n");
        fwrite($file_h,"login_token_expire = ".$this->config["login_token_expire"]."\n");
        fwrite($file_h,"one_time_tokens_per_user = ".$this->config["one_time_tokens_per_user"]."\n");
        fwrite($file_h,"one_time_token_expire = ".$this->config["one_time_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"minimum_authenticate_interval = ".$this->config["minimum_authenticate_interval"]."\n");
        fwrite($file_h,"authenticate_logs_per_user = ".$this->config["authenticate_logs_per_user"]."\n");
        fwrite($file_h,"authenticate_log_retention_time = ".$this->config["authenticate_log_retention_time"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"password_reset_token_expire = ".$this->config["password_reset_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"totp_pin_expire = ".$this->config["totp_pin_expire"]."\n");
        fwrite($file_h,"two_step_verification_token_expire = ".$this->config["two_step_verification_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"proxy_count = ".$this->config["proxy_count"]."\n");
        
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
            return $this->save();
        } else {
            return TRUE;
        }
    }
    
    
    function reset_config () {
        $this->config = WAKARANA_CONFIG_ORIGINAL;
        
        return $this->save();
    }
    
    
    protected function save_custom_fields () {
        if (@file_put_contents($this->base_path."/wakarana_custom_fields.json", json_encode($this->custom_fields)) !== FALSE) {
            return TRUE;
        } else {
            $this->print_error("カスタムフィールド設定ファイルへの書き込みに失敗しました。");
            return FALSE;
        }
    }
    
    
    function add_custom_field ($custom_field_name, $maximum_length = 500, $records_per_user = 1, $allow_nonunique_value = TRUE, $save_now = TRUE) {
        if (!self::check_id_string($custom_field_name)) {
            $this->print_error("指定されたカスタムフィールド名が異常です。");
            return FALSE;
        }
        
        if ($maximum_length > 500 || $maximum_length < 1) {
            $this->print_error("指定された最大文字数が異常です。カスタムフィールドの最大文字数は1〜500の範囲で指定してください。");
            return FALSE;
        }
        
        if ($records_per_user > 100 || $records_per_user < 1) {
            $this->print_error("指定された最大件数が異常です。カスタムフィールドの最大件数は1〜100の範囲で指定してください。");
            return FALSE;
        }
        
        if (!is_bool($allow_nonunique_value)) {
            $this->print_error("カスタムフィールド値の重複可否の設定値が異常です。");
            return FALSE;
        }
        
        $this->custom_fields[$custom_field_name] = array(
            "is_numeric" => FALSE,
            "maximum_length" => $maximum_length,
            "records_per_user" => $records_per_user,
            "allow_nonunique_value" => $allow_nonunique_value
        );
        
        if ($save_now) {
            return $this->save_custom_fields();
        } else {
            return TRUE;
        }
    }
    
    
    function add_custom_numerical_field ($custom_field_name, $records_per_user = 1, $allow_nonunique_value = TRUE, $save_now = TRUE) {
        if (!self::check_id_string($custom_field_name)) {
            $this->print_error("指定されたカスタムフィールド名が異常です。");
            return FALSE;
        }
        
        if ($records_per_user > 100 || $records_per_user < 1) {
            $this->print_error("指定された最大件数が異常です。カスタムフィールドの最大件数は1〜100の範囲で指定してください。");
            return FALSE;
        }
        
        if (!is_bool($allow_nonunique_value)) {
            $this->print_error("カスタムフィールド値の重複可否の設定値が異常です。");
            return FALSE;
        }
        
        $this->custom_fields[$custom_field_name] = array(
            "is_numeric" => TRUE,
            "records_per_user" => $records_per_user,
            "allow_nonunique_value" => $allow_nonunique_value
        );
        
        if ($save_now) {
            return $this->save_custom_fields();
        } else {
            return TRUE;
        }
    }
    
    
    function delete_custom_field($custom_field_name, $save_now = TRUE) {
        if (!isset($this->custom_fields[$custom_field_name])) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        unset($this->custom_fields[$custom_field_name]);
        
        if ($save_now) {
            return $this->save_custom_fields();
        } else {
            return TRUE;
        }
    }
    
    
    protected function save_email_domain_blacklist () {
        $email_domain_blacklist = implode("\n", $this->email_domain_blacklist);
        
        if(@file_put_contents($this->base_path."/wakarana_email_domain_blacklist.conf", $email_domain_blacklist) !== FALSE){
            return TRUE;
        } else {
            $this->print_error("メールドメインブラックリストファイルへの書き込みに失敗しました。");
            return FALSE;
        }
    }
    
    
    function add_email_domain_to_blacklist ($damain_name) {
        if ($this->check_email_domain($damain_name)) {
            $this->email_domain_blacklist[] = mb_strtolower($damain_name);
            
            return $this->save_email_domain_blacklist();
        } else {
            return FALSE;
        }
    }
    
    
    function remove_email_domain_from_blacklist ($damain_name) {
        if (!$this->check_email_domain($damain_name)) {
            $this->email_domain_blacklist = array_values(array_diff($this->email_domain_blacklist, array(mb_strtolower($damain_name))));
            
            return $this->save_email_domain_blacklist();
        } else {
            return FALSE;
        }
    }
    
    
    function setup_db () {
        $this->connect_db();
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_users`(`user_id` TEXT COLLATE NOCASE NOT NULL PRIMARY KEY, `password` TEXT NOT NULL, `user_name` TEXT COLLATE NOCASE, `user_created` TEXT NOT NULL, `last_updated` TEXT NOT NULL, `last_access` TEXT NOT NULL, `status` INTEGER NOT NULL, `totp_key` TEXT)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_users"("user_id" varchar(60) NOT NULL PRIMARY KEY, "password" varchar(128) NOT NULL, "user_name" varchar(240), "user_created" timestamp NOT NULL, "last_updated" timestamp NOT NULL, "last_access" timestamp NOT NULL, "status" smallint NOT NULL, "totp_key" varchar(16))');
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
            
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u3" ON "wakarana_users"("user_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_users のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_email_addresses`(`user_id` TEXT COLLATE NOCASE NOT NULL, `email_address` TEXT, `is_primary` INTEGER NOT NULL, PRIMARY KEY(`user_id`, `email_address`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_email_addresses"("user_id" varchar(60) NOT NULL, "email_address" varchar(254), "is_primary" boolean NOT NULL, PRIMARY KEY("user_id", "email_address"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_email_addresses の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_e1" ON "wakarana_user_email_addresses"("user_id", "is_primary")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_e2" ON "wakarana_user_email_addresses"("email_address")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_email_addresses のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_custom_fields`(`user_id` TEXT COLLATE NOCASE NOT NULL, `custom_field_name` TEXT NOT NULL, `value_number` INTEGER NOT NULL, `custom_field_value` TEXT, PRIMARY KEY(`user_id`, `custom_field_name`, `value_number`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_custom_fields"("user_id" varchar(60) NOT NULL, "custom_field_name" varchar(60) NOT NULL, "value_number" smallint NOT NULL, "custom_field_value" text, PRIMARY KEY("user_id", "custom_field_name", "value_number"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_fields の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_c1" ON "wakarana_user_custom_fields"("user_id", "custom_field_name", "custom_field_value")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_c2" ON "wakarana_user_custom_fields"("custom_field_name", "custom_field_value")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_fields のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_custom_numerical_fields`(`user_id` TEXT COLLATE NOCASE NOT NULL, `custom_field_name` TEXT NOT NULL, `value_number` INTEGER NOT NULL, `custom_field_value` REAL, PRIMARY KEY(`user_id`, `custom_field_name`, `value_number`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_custom_numerical_fields"("user_id" varchar(60) NOT NULL, "custom_field_name" varchar(60) NOT NULL, "value_number" smallint NOT NULL, "custom_field_value" double precision, PRIMARY KEY("user_id", "custom_field_name", "value_number"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_numerical_fields の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_cn1" ON "wakarana_user_custom_numerical_fields"("user_id", "custom_field_name", "custom_field_value")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_cn2" ON "wakarana_user_custom_numerical_fields"("custom_field_name", "custom_field_value")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_numerical_fields のインデックス作成処理に失敗しました。".$err->getMessage());
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
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_l3" ON "wakarana_login_tokens"("user_id", "token")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_login_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_roles`(`role_id` TEXT NOT NULL PRIMARY KEY, `role_name` TEXT COLLATE NOCASE NOT NULL, `role_description` TEXT)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_roles"("role_id" varchar(60) NOT NULL PRIMARY KEY, "role_name" varchar(120) NOT NULL, "role_description" text)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_roles の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_r1" ON "wakarana_roles"("role_name", "role_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_roles のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_roles`(`user_id` TEXT COLLATE NOCASE NOT NULL, `role_id` TEXT NOT NULL, PRIMARY KEY(`user_id`, `role_id`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_roles"("user_id" varchar(60) NOT NULL, "role_id" varchar(60) NOT NULL, PRIMARY KEY("user_id", "role_id"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_roles の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ur1" ON "wakarana_user_roles"("role_id", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_roles のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permissions`(`resource_id` TEXT NOT NULL PRIMARY KEY, `permission_name` TEXT COLLATE NOCASE NOT NULL, `permission_description` TEXT)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permissions"("resource_id" varchar(120) NOT NULL PRIMARY KEY, "permission_name" varchar(120) NOT NULL, "permission_description" text)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permissions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_p1" ON "wakarana_permissions"("permission_name", "resource_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permissions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permission_actions`(`resource_id` TEXT NOT NULL, `action` TEXT NOT NULL, PRIMARY KEY(`resource_id`, `action`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permission_actions"("resource_id" varchar(120) NOT NULL, "action" varchar(60) NOT NULL, PRIMARY KEY("resource_id", "action"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permission_actions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_pa1" ON "wakarana_permission_actions"("action", "resource_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permission_actions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_role_permissions`(`role_id` TEXT NOT NULL, `resource_id` TEXT NOT NULL, `action` TEXT NOT NULL, PRIMARY KEY(`role_id`, `resource_id`, `action`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_role_permissions"("role_id" varchar(60) NOT NULL, "resource_id" varchar(120) NOT NULL, "action" varchar(60) NOT NULL, PRIMARY KEY("role_id", "resource_id", "action"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permissions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_rp1" ON "wakarana_role_permissions"("resource_id", "action", "role_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permissions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_permission_caches`(`user_id` TEXT COLLATE NOCASE NOT NULL, `resource_id` TEXT NOT NULL, `action` TEXT NOT NULL, PRIMARY KEY(`user_id`, `resource_id`, `action`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_permission_caches"("user_id" varchar(60) NOT NULL, "resource_id" varchar(120) NOT NULL, "action" varchar(60) NOT NULL, PRIMARY KEY("user_id", "resource_id", "action"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permission_caches の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_up1" ON "wakarana_user_permission_caches"("resource_id", "action", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permission_caches のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permitted_values`(`permitted_value_id` TEXT NOT NULL PRIMARY KEY, `permitted_value_name` TEXT NOT NULL, `permitted_value_description` TEXT)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permitted_values"("permitted_value_id" varchar(60) NOT NULL PRIMARY KEY, "permitted_value_name" varchar(120) NOT NULL, "permitted_value_description" text)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permitted_values の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_v1" ON "wakarana_permitted_values"("permitted_value_name", "permitted_value_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permitted_values のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_role_permitted_values`(`role_id` TEXT NOT NULL, `permitted_value_id` TEXT NOT NULL, `permitted_value` INTEGER NOT NULL, PRIMARY KEY(`role_id`, `permitted_value_id`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_role_permitted_values"("role_id" varchar(60) NOT NULL, "permitted_value_id" varchar(60) NOT NULL, "permitted_value" integer NOT NULL, PRIMARY KEY("role_id", "permitted_value_id"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permitted_values の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_rv1" ON "wakarana_role_permitted_values"("permitted_value_id", "permitted_value", "role_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permitted_values のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_permitted_value_caches`(`user_id` TEXT COLLATE NOCASE NOT NULL, `permitted_value_id` TEXT NOT NULL, `maximum_permitted_value` INTEGER NOT NULL, PRIMARY KEY(`user_id`, `permitted_value_id`))");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_permitted_value_caches"("user_id" varchar(60) NOT NULL, "permitted_value_id" varchar(60) NOT NULL, "maximum_permitted_value" integer NOT NULL, PRIMARY KEY("user_id", "permitted_value_id"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permitted_value_caches の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_uv1" ON "wakarana_user_permitted_value_caches"("permitted_value_id", "maximum_permitted_value", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permitted_value_caches のインデックス作成処理に失敗しました。".$err->getMessage());
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
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_o3" ON "wakarana_one_time_tokens"("user_id", "token")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_one_time_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_authenticate_logs`(`user_id` TEXT COLLATE NOCASE NOT NULL, `succeeded` INTEGER NOT NULL, `authenticate_datetime` TEXT NOT NULL, `ip_address` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_authenticate_logs"("user_id" varchar(60) NOT NULL, "succeeded" boolean NOT NULL, "authenticate_datetime" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_authenticate_logs の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a1" ON "wakarana_authenticate_logs"("user_id", "authenticate_datetime")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a2" ON "wakarana_authenticate_logs"("ip_address", "authenticate_datetime")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a3" ON "wakarana_authenticate_logs"("authenticate_datetime")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_authenticate_logs のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_email_address_verification_codes`(`user_id` TEXT COLLATE NOCASE UNIQUE, `email_address` TEXT NOT NULL, `verification_code` TEXT NOT NULL, `code_created` TEXT NOT NULL, `ip_address` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_email_address_verification_codes"("user_id" varchar(60) UNIQUE, "email_address" varchar(254) NOT NULL, "verification_code" varchar(8) NOT NULL, "code_created" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_email_address_verification_codes の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ev1" ON "wakarana_email_address_verification_codes"("email_address", "verification_code")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ev2" ON "wakarana_email_address_verification_codes"("code_created")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ev3" ON "wakarana_email_address_verification_codes"("ip_address", "code_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_email_address_verification_codes のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_invite_codes`(`invite_code` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE NOT NULL, `code_created` TEXT NOT NULL, `code_expire` TEXT, `remaining_number` INTEGER)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_invite_codes"("invite_code" varchar(16) NOT NULL PRIMARY KEY, "user_id" varchar(60) NOT NULL, "code_created" timestamp NOT NULL, "code_expire" timestamp, "remaining_number" integer)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_invite_codes の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i1" ON "wakarana_invite_codes"("user_id", "code_created")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i2" ON "wakarana_invite_codes"("code_expire")');
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i3" ON "wakarana_invite_codes"("code_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_invite_codes のインデックス作成処理に失敗しました。".$err->getMessage());
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
                $this->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_two_step_verification_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE UNIQUE NOT NULL, `token_created` TEXT NOT NULL)");
            } else {
                $this->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_two_step_verification_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) UNIQUE NOT NULL, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_two_step_verification_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_t1" ON "wakarana_two_step_verification_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_two_step_verification_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->db_obj->exec('INSERT INTO "wakarana_roles"("role_id", "role_name", "role_description") VALUES (\'__base__\', \'ベースロール\', \'\') ON CONFLICT ("role_id") DO NOTHING');
            $this->db_obj->exec('INSERT INTO "wakarana_roles"("role_id", "role_name", "role_description") VALUES (\'__admin__\', \'特権管理者ロール\', \'\') ON CONFLICT ("role_id") DO NOTHING');
        } catch (PDOException $err) {
            $this->print_error("初期ロールの追加処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->disconnect_db();
        
        return TRUE;
    }
}
