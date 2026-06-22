<?php
/*Wakarana wakarana_config.php*/

class wakarana_config {
    use wakarana_common;
    
    
    const ORIGINAL_CONFIG = array(
            "display_errors" => TRUE,
            
            "use_sqlite" => TRUE,
            "sqlite_db_file" => "wakarana.db",
            
            "pg_host" => "127.0.0.1",
            "pg_user" => "postgres",
            "pg_pass" => "",
            "pg_db" => "wakarana",
            "pg_port" => 5432,
            
            "allow_weak_password" => FALSE,
            
            "use_argon2_for_password_hashing" => TRUE,
            "argon2_memory_cost" => 19456,
            "argon2_time_cost" => 2,
            "argon2_parallelism" => 1,
            "dummy_password_hash" => NULL,
            
            "allow_nonunique_email_address" => FALSE,
            "email_addresses_per_user" => 5,
            "verification_email_expire" => 1800,
            "verification_email_sendable_interval" => 10,
            
            "session_token_cookie_name" => "wakarana_session_token",
            "cookie_domain" => "",
            "delete_session_on_ip_address_change" => FALSE,
            
            "sessions_per_user" => 4,
            "session_expire" => 2592000,
            "one_time_tokens_per_user" => 8,
            "one_time_token_expire" => 43200,
            
            "auth_initial_lockout_seconds" => 5,
            "auth_max_lockout_seconds" => 60,
            "auth_log_retention_seconds" => 1209600,
            "auth_failure_expiration_seconds" => 1800,
            
            "password_reset_token_expire" => 1800,
            
            "totp_pin_expire" => 1,
            "two_step_verification_token_expire" => 600,
            
            "proxy_count" => 0
        );
    
    
    function __construct ($base_dir = NULL) {
        if (empty($base_dir)) {
            $base_path = __DIR__;
        } else {
            $base_path = realpath($base_dir);
            
            if (!is_dir($base_path)) {
                throw new Exception("指定されたベースフォルダは存在しません。");
            }
        }
        
        if (!file_exists($base_path."/wakarana_config.ini")) {
            $this->initialize_config($base_path."/wakarana_config.ini");
        }
        
        if (!file_exists($base_path."/wakarana_custom_fields.json")) {
            file_put_contents($base_path."/wakarana_custom_fields.json", "{}");
        }
        
        if (!file_exists($base_path."/wakarana_email_domain_blacklist.conf")) {
            touch($base_path."/wakarana_email_domain_blacklist.conf");
        }
        
        $this->profile = wakarana_profile::of($base_path);
    }
    
    
    protected function save () {
        $file_h = @fopen($this->profile->get_base_path()."/wakarana_config.ini", "w");
        
        if (empty($file_h)) {
            $this->print_error("設定ファイルを書き込みモードで開くことができませんでした。");
            return FALSE;
        }
        
        fwrite($file_h, "display_errors = ".($this->profile->get_config("display_errors") ? "true" : "false")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "use_sqlite = ".($this->profile->get_config("use_sqlite") ? "true" : "false")."\n");
        fwrite($file_h, "sqlite_db_file = \"".$this->profile->get_config("sqlite_db_file")."\"\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "pg_host = \"".$this->profile->get_config("pg_host")."\"\n");
        fwrite($file_h, "pg_user = \"".$this->profile->get_config("pg_user")."\"\n");
        fwrite($file_h, "pg_pass = \"".$this->profile->get_config("pg_pass")."\"\n");
        fwrite($file_h, "pg_db = \"".$this->profile->get_config("pg_db")."\"\n");
        fwrite($file_h, "pg_port = ".$this->profile->get_config("pg_port")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "allow_weak_password = ".($this->profile->get_config("allow_weak_password") ? "true" : "false")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "use_argon2_for_password_hashing = ".($this->profile->get_config("use_argon2_for_password_hashing") ? "true" : "false")."\n");
        fwrite($file_h, "argon2_memory_cost = ".$this->profile->get_config("argon2_memory_cost")."\n");
        fwrite($file_h, "argon2_time_cost = ".$this->profile->get_config("argon2_time_cost")."\n");
        fwrite($file_h, "argon2_parallelism = ".$this->profile->get_config("argon2_parallelism")."\n");
        fwrite($file_h, "dummy_password_hash = ".(is_null($this->profile->get_config("dummy_password_hash")) ? "null" : "\"".$this->profile->get_config("dummy_password_hash")."\"")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "allow_nonunique_email_address = ".($this->profile->get_config("allow_nonunique_email_address") ? "true" : "false")."\n");
        fwrite($file_h, "email_addresses_per_user = ".$this->profile->get_config("email_addresses_per_user")."\n");
        fwrite($file_h, "verification_email_expire = ".$this->profile->get_config("verification_email_expire")."\n");
        fwrite($file_h, "verification_email_sendable_interval = ".$this->profile->get_config("verification_email_sendable_interval")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "session_token_cookie_name = \"".$this->profile->get_config("session_token_cookie_name")."\"\n");
        fwrite($file_h, "cookie_domain = \"".$this->profile->get_config("cookie_domain")."\"\n");
        fwrite($file_h, "delete_session_on_ip_address_change = ".($this->profile->get_config("delete_session_on_ip_address_change") ? "true" : "false")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "sessions_per_user = ".$this->profile->get_config("sessions_per_user")."\n");
        fwrite($file_h, "session_expire = ".$this->profile->get_config("session_expire")."\n");
        fwrite($file_h, "one_time_tokens_per_user = ".$this->profile->get_config("one_time_tokens_per_user")."\n");
        fwrite($file_h, "one_time_token_expire = ".$this->profile->get_config("one_time_token_expire")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "auth_initial_lockout_seconds = ".$this->profile->get_config("auth_initial_lockout_seconds")."\n");
        fwrite($file_h, "auth_max_lockout_seconds = ".$this->profile->get_config("auth_max_lockout_seconds")."\n");
        fwrite($file_h, "auth_log_retention_seconds = ".$this->profile->get_config("auth_log_retention_seconds")."\n");
        fwrite($file_h, "auth_failure_expiration_seconds = ".$this->profile->get_config("auth_failure_expiration_seconds")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "password_reset_token_expire = ".$this->profile->get_config("password_reset_token_expire")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "totp_pin_expire = ".$this->profile->get_config("totp_pin_expire")."\n");
        fwrite($file_h, "two_step_verification_token_expire = ".$this->profile->get_config("two_step_verification_token_expire")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "proxy_count = ".$this->profile->get_config("proxy_count")."\n");
        
        fclose($file_h);
        
        return TRUE;
    }
    
    
    function set_config_value ($key, $value, $save_now = TRUE) {
        if (!isset($value) || gettype(self::ORIGINAL_CONFIG[$key]) !== gettype($value)) {
            $this->print_error("設定ファイルの変数値を変更できません。変数型が不正です。");
            return FALSE;
        }
        
        $this->profile->set_config($key, $value);
        
        if ($save_now) {
            return $this->save();
        } else {
            return TRUE;
        }
    }
    
    
    protected function initialize_config ($config_file_path) {
        $file_h = @fopen($config_file_path, "w");
        
        if (empty($file_h)) {
            $this->print_error("設定ファイルを書き込みモードで開くことができませんでした。");
            return FALSE;
        }
        
        fwrite($file_h, "display_errors = ".(self::ORIGINAL_CONFIG["display_errors"] ? "true" : "false")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "use_sqlite = ".(self::ORIGINAL_CONFIG["use_sqlite"] ? "true" : "false")."\n");
        fwrite($file_h, "sqlite_db_file = \"".self::ORIGINAL_CONFIG["sqlite_db_file"]."\"\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "pg_host = \"".self::ORIGINAL_CONFIG["pg_host"]."\"\n");
        fwrite($file_h, "pg_user = \"".self::ORIGINAL_CONFIG["pg_user"]."\"\n");
        fwrite($file_h, "pg_pass = \"".self::ORIGINAL_CONFIG["pg_pass"]."\"\n");
        fwrite($file_h, "pg_db = \"".self::ORIGINAL_CONFIG["pg_db"]."\"\n");
        fwrite($file_h, "pg_port = ".self::ORIGINAL_CONFIG["pg_port"]."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "allow_weak_password = ".(self::ORIGINAL_CONFIG["allow_weak_password"] ? "true" : "false")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "use_argon2_for_password_hashing = ".(self::ORIGINAL_CONFIG["use_argon2_for_password_hashing"] ? "true" : "false")."\n");
        fwrite($file_h, "argon2_memory_cost = ".self::ORIGINAL_CONFIG["argon2_memory_cost"]."\n");
        fwrite($file_h, "argon2_time_cost = ".self::ORIGINAL_CONFIG["argon2_time_cost"]."\n");
        fwrite($file_h, "argon2_parallelism = ".self::ORIGINAL_CONFIG["argon2_parallelism"]."\n");
        fwrite($file_h, "dummy_password_hash = ".(is_null(self::ORIGINAL_CONFIG["dummy_password_hash"]) ? "null" : "\"".self::ORIGINAL_CONFIG["dummy_password_hash"]."\"")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "allow_nonunique_email_address = ".(self::ORIGINAL_CONFIG["allow_nonunique_email_address"] ? "true" : "false")."\n");
        fwrite($file_h, "email_addresses_per_user = ".self::ORIGINAL_CONFIG["email_addresses_per_user"]."\n");
        fwrite($file_h, "verification_email_expire = ".self::ORIGINAL_CONFIG["verification_email_expire"]."\n");
        fwrite($file_h, "verification_email_sendable_interval = ".self::ORIGINAL_CONFIG["verification_email_sendable_interval"]."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "session_token_cookie_name = \"".self::ORIGINAL_CONFIG["session_token_cookie_name"]."\"\n");
        fwrite($file_h, "cookie_domain = \"".self::ORIGINAL_CONFIG["cookie_domain"]."\"\n");
        fwrite($file_h, "delete_session_on_ip_address_change = ".(self::ORIGINAL_CONFIG["delete_session_on_ip_address_change"] ? "true" : "false")."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "sessions_per_user = ".self::ORIGINAL_CONFIG["sessions_per_user"]."\n");
        fwrite($file_h, "session_expire = ".self::ORIGINAL_CONFIG["session_expire"]."\n");
        fwrite($file_h, "one_time_tokens_per_user = ".self::ORIGINAL_CONFIG["one_time_tokens_per_user"]."\n");
        fwrite($file_h, "one_time_token_expire = ".self::ORIGINAL_CONFIG["one_time_token_expire"]."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "auth_initial_lockout_seconds = ".self::ORIGINAL_CONFIG["auth_initial_lockout_seconds"]."\n");
        fwrite($file_h, "auth_max_lockout_seconds = ".self::ORIGINAL_CONFIG["auth_max_lockout_seconds"]."\n");
        fwrite($file_h, "auth_log_retention_seconds = ".self::ORIGINAL_CONFIG["auth_log_retention_seconds"]."\n");
        fwrite($file_h, "auth_failure_expiration_seconds = ".self::ORIGINAL_CONFIG["auth_failure_expiration_seconds"]."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "password_reset_token_expire = ".self::ORIGINAL_CONFIG["password_reset_token_expire"]."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "totp_pin_expire = ".self::ORIGINAL_CONFIG["totp_pin_expire"]."\n");
        fwrite($file_h, "two_step_verification_token_expire = ".self::ORIGINAL_CONFIG["two_step_verification_token_expire"]."\n");
        fwrite($file_h, "\n");
        
        fwrite($file_h, "proxy_count = ".self::ORIGINAL_CONFIG["proxy_count"]."\n");
        
        fclose($file_h);
        
        return TRUE;
    }
    
    
    function reset_config () {
        $config_path = $this->profile->get_base_path()."/wakarana_config.ini";
        
        return $this->initialize_config($config_path) && $this->profile->load_config($config_path);
    }
    
    
    function generate_dummy_password_hash () {
        return $this->generate_password_hash(self::generate_random_password(), $this->profile->get_config("use_argon2_for_password_hashing") ? NULL : base64_encode(random_bytes(6)));
    }
    
    
    protected function save_custom_fields () {
        if (@file_put_contents($this->profile->get_base_path()."/wakarana_custom_fields.json", json_encode($this->profile->get_custom_field_definition())) !== FALSE) {
            return TRUE;
        } else {
            $this->print_error("カスタムフィールド設定ファイルへの書き込みに失敗しました。");
            return FALSE;
        }
    }
    
    
    function create_custom_field ($custom_field_name, $maximum_length = 500, $records_per_user = 1, $allow_nonunique_value = TRUE, $save_now = TRUE) {
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
        
        $this->profile->set_custom_field_definition($custom_field_name, array(
            "is_numeric" => FALSE,
            "maximum_length" => $maximum_length,
            "records_per_user" => $records_per_user,
            "allow_nonunique_value" => $allow_nonunique_value
        ));
        
        if ($save_now) {
            return $this->save_custom_fields();
        } else {
            return TRUE;
        }
    }
    
    
    function create_custom_numerical_field ($custom_field_name, $records_per_user = 1, $allow_nonunique_value = TRUE, $save_now = TRUE) {
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
        
        $this->profile->set_custom_field_definition($custom_field_name, array(
            "is_numeric" => TRUE,
            "records_per_user" => $records_per_user,
            "allow_nonunique_value" => $allow_nonunique_value
        ));
        
        if ($save_now) {
            return $this->save_custom_fields();
        } else {
            return TRUE;
        }
    }
    
    
    function delete_custom_field ($custom_field_name, $save_now = TRUE) {
        if (empty($this->profile->get_custom_field_definition($custom_field_name))) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        $this->profile->set_custom_field_definition($custom_field_name, NULL);
        
        if ($save_now) {
            return $this->save_custom_fields();
        } else {
            return TRUE;
        }
    }
    
    
    protected function save_email_domain_blacklist () {
        $email_domain_blacklist = implode("\n", $this->profile->get_email_domain_blacklist());
        
        if (@file_put_contents($this->profile->get_base_path()."/wakarana_email_domain_blacklist.conf", $email_domain_blacklist) !== FALSE) {
            return TRUE;
        } else {
            $this->print_error("メールドメインブラックリストファイルへの書き込みに失敗しました。");
            return FALSE;
        }
    }
    
    
    function add_email_domain_to_blacklist ($damain_name, $save_now = TRUE) {
        if (!preg_match("/\A[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)+\z/u", $damain_name)) {
            return FALSE;
        }
        
        if ($this->check_email_domain($damain_name)) {
            $this->profile->add_email_domain_to_blacklist($damain_name);
            
            return $save_now ? $this->save_email_domain_blacklist() : TRUE;
        } else {
            return NULL;
        }
    }
    
    
    function remove_email_domain_from_blacklist ($damain_name) {
        $list_index = array_search($damain_name, $this->profile->get_email_domain_blacklist());
        
        if ($list_index === FALSE) {
            return FALSE;
        }
        
        $this->profile->remove_email_domain_from_blacklist($list_index);
        
        return $this->save_email_domain_blacklist();
    }
    
    
    function merge_email_domain_blacklists ($damain_names) {
        $lines = preg_split("/\R/u", $damain_names);
        
        $added_count = 0;
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            if ($trimmed_line !== "") {
                if (!empty($this->add_email_domain_to_blacklist($trimmed_line, FALSE))) {
                    $added_count++;
                }
            }
        }
        
        if ($added_count === 0) {
            return 0;
        }
        
        return $this->save_email_domain_blacklist() ? $added_count : FALSE;
    }
    
    
    function clear_email_domain_blacklist ($save_now = TRUE) {
        $this->profile->set_email_domain_blacklist(array());
        
        return $save_now ? $this->save_email_domain_blacklist() : TRUE;
    }
    
    
    function replace_email_domain_blacklist ($damain_names) {
        $old_blacklist = $this->profile->get_email_domain_blacklist();
        $this->clear_email_domain_blacklist(FALSE);
        
        $added_count = $this->merge_email_domain_blacklists($damain_names);
        
        if ($added_count === FALSE) {
            $this->profile->set_email_domain_blacklist($old_blacklist);
        }
        
        return $added_count;
    }
    
    
    function setup_db () {
        $this->profile->connect_db();
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_users`(`user_id` TEXT COLLATE NOCASE NOT NULL PRIMARY KEY, `password_hash` TEXT NOT NULL, `user_name` TEXT COLLATE NOCASE, `user_created` TEXT NOT NULL, `last_updated` TEXT NOT NULL, `last_access` TEXT NOT NULL, `status` INTEGER NOT NULL, `totp_key` TEXT, `used_invite_code` TEXT)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_users"("user_id" varchar(60) NOT NULL PRIMARY KEY, "password_hash" text NOT NULL, "user_name" varchar(240), "user_created" timestamp NOT NULL, "last_updated" timestamp NOT NULL, "last_access" timestamp NOT NULL, "status" smallint NOT NULL, "totp_key" varchar(16), "used_invite_code" varchar(16))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_users の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE INDEX IF NOT EXISTS `wakarana_idx_u1` ON `wakarana_users`(`user_name`)");
            } else {
                $this->profile->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_u0" ON "wakarana_users"((LOWER("user_id")))');
                $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u1" ON "wakarana_users"(LOWER("user_name"))');
            }
            
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u3" ON "wakarana_users"("user_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_u4" ON "wakarana_users"("used_invite_code", "user_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_users のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_email_addresses`(`user_id` TEXT COLLATE NOCASE NOT NULL, `email_address` TEXT, `is_primary` INTEGER NOT NULL, PRIMARY KEY(`user_id`, `email_address`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_email_addresses"("user_id" varchar(60) NOT NULL, "email_address" varchar(254), "is_primary" boolean NOT NULL, PRIMARY KEY("user_id", "email_address"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_email_addresses の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_e1" ON "wakarana_user_email_addresses"("user_id", "is_primary")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_e2" ON "wakarana_user_email_addresses"("email_address")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_email_addresses のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_custom_fields`(`user_id` TEXT COLLATE NOCASE NOT NULL, `custom_field_name` TEXT NOT NULL, `value_number` INTEGER NOT NULL, `custom_field_value` TEXT, PRIMARY KEY(`user_id`, `custom_field_name`, `value_number`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_custom_fields"("user_id" varchar(60) NOT NULL, "custom_field_name" varchar(60) NOT NULL, "value_number" smallint NOT NULL, "custom_field_value" text, PRIMARY KEY("user_id", "custom_field_name", "value_number"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_fields の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_c1" ON "wakarana_user_custom_fields"("user_id", "custom_field_name", "custom_field_value")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_c2" ON "wakarana_user_custom_fields"("custom_field_name", "custom_field_value")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_fields のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_custom_numerical_fields`(`user_id` TEXT COLLATE NOCASE NOT NULL, `custom_field_name` TEXT NOT NULL, `value_number` INTEGER NOT NULL, `custom_field_value` REAL, PRIMARY KEY(`user_id`, `custom_field_name`, `value_number`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_custom_numerical_fields"("user_id" varchar(60) NOT NULL, "custom_field_name" varchar(60) NOT NULL, "value_number" smallint NOT NULL, "custom_field_value" double precision, PRIMARY KEY("user_id", "custom_field_name", "value_number"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_numerical_fields の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE UNIQUE INDEX IF NOT EXISTS "wakarana_idx_cn1" ON "wakarana_user_custom_numerical_fields"("user_id", "custom_field_name", "custom_field_value")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_cn2" ON "wakarana_user_custom_numerical_fields"("custom_field_name", "custom_field_value")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_custom_numerical_fields のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_recovery_codes`(`user_id` TEXT COLLATE NOCASE NOT NULL, `recovery_code` TEXT NOT NULL, PRIMARY KEY(`user_id`, `recovery_code`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_recovery_codes"("user_id" varchar(60) NOT NULL, "recovery_code" varchar(24) NOT NULL, PRIMARY KEY("user_id", "recovery_code"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_recovery_codes の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_sessions`(`session_id` TEXT NOT NULL PRIMARY KEY, `token` TEXT NOT NULL UNIQUE, `user_id` TEXT COLLATE NOCASE NOT NULL, `token_created` TEXT NOT NULL, `ip_address` TEXT NOT NULL, `operating_system` TEXT, `browser_name` TEXT, `last_access` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_sessions"("session_id" varchar(16) NOT NULL PRIMARY KEY, "token" varchar(43) NOT NULL UNIQUE, "user_id" varchar(60) NOT NULL, "token_created" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL, "operating_system" varchar(30), "browser_name" varchar(30), "last_access" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_sessions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_s1" ON "wakarana_sessions"("token_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_s2" ON "wakarana_sessions"("user_id", "token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_sessions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_roles`(`role_id` TEXT NOT NULL PRIMARY KEY, `role_name` TEXT COLLATE NOCASE NOT NULL, `role_description` TEXT)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_roles"("role_id" varchar(60) NOT NULL PRIMARY KEY, "role_name" varchar(120) NOT NULL, "role_description" text)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_roles の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_r1" ON "wakarana_roles"("role_name", "role_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_roles のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_roles`(`user_id` TEXT COLLATE NOCASE NOT NULL, `role_id` TEXT NOT NULL, PRIMARY KEY(`user_id`, `role_id`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_roles"("user_id" varchar(60) NOT NULL, "role_id" varchar(60) NOT NULL, PRIMARY KEY("user_id", "role_id"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_roles の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ur1" ON "wakarana_user_roles"("role_id", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_roles のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permissions`(`resource_id` TEXT NOT NULL PRIMARY KEY, `permission_name` TEXT COLLATE NOCASE NOT NULL, `permission_description` TEXT)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permissions"("resource_id" varchar(120) NOT NULL PRIMARY KEY, "permission_name" varchar(120) NOT NULL, "permission_description" text)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permissions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_p1" ON "wakarana_permissions"("permission_name", "resource_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permissions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permission_actions`(`resource_id` TEXT NOT NULL, `action` TEXT NOT NULL, PRIMARY KEY(`resource_id`, `action`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permission_actions"("resource_id" varchar(120) NOT NULL, "action" varchar(60) NOT NULL, PRIMARY KEY("resource_id", "action"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permission_actions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_pa1" ON "wakarana_permission_actions"("action", "resource_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permission_actions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_role_permissions`(`role_id` TEXT NOT NULL, `resource_id` TEXT NOT NULL, `action` TEXT NOT NULL, PRIMARY KEY(`role_id`, `resource_id`, `action`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_role_permissions"("role_id" varchar(60) NOT NULL, "resource_id" varchar(120) NOT NULL, "action" varchar(60) NOT NULL, PRIMARY KEY("role_id", "resource_id", "action"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permissions の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_rp1" ON "wakarana_role_permissions"("resource_id", "action", "role_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permissions のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_permission_caches`(`user_id` TEXT COLLATE NOCASE NOT NULL, `resource_id` TEXT NOT NULL, `action` TEXT NOT NULL, PRIMARY KEY(`user_id`, `resource_id`, `action`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_permission_caches"("user_id" varchar(60) NOT NULL, "resource_id" varchar(120) NOT NULL, "action" varchar(60) NOT NULL, PRIMARY KEY("user_id", "resource_id", "action"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permission_caches の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_up1" ON "wakarana_user_permission_caches"("resource_id", "action", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permission_caches のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_permitted_values`(`permitted_value_id` TEXT NOT NULL PRIMARY KEY, `permitted_value_name` TEXT NOT NULL, `permitted_value_description` TEXT)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_permitted_values"("permitted_value_id" varchar(60) NOT NULL PRIMARY KEY, "permitted_value_name" varchar(120) NOT NULL, "permitted_value_description" text)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permitted_values の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_v1" ON "wakarana_permitted_values"("permitted_value_name", "permitted_value_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_permitted_values のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_role_permitted_values`(`role_id` TEXT NOT NULL, `permitted_value_id` TEXT NOT NULL, `permitted_value` INTEGER NOT NULL, PRIMARY KEY(`role_id`, `permitted_value_id`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_role_permitted_values"("role_id" varchar(60) NOT NULL, "permitted_value_id" varchar(60) NOT NULL, "permitted_value" integer NOT NULL, PRIMARY KEY("role_id", "permitted_value_id"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permitted_values の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_rv1" ON "wakarana_role_permitted_values"("permitted_value_id", "permitted_value", "role_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_role_permitted_values のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_user_permitted_value_caches`(`user_id` TEXT COLLATE NOCASE NOT NULL, `permitted_value_id` TEXT NOT NULL, `maximum_permitted_value` INTEGER NOT NULL, PRIMARY KEY(`user_id`, `permitted_value_id`))");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_user_permitted_value_caches"("user_id" varchar(60) NOT NULL, "permitted_value_id" varchar(60) NOT NULL, "maximum_permitted_value" integer NOT NULL, PRIMARY KEY("user_id", "permitted_value_id"))');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permitted_value_caches の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_uv1" ON "wakarana_user_permitted_value_caches"("permitted_value_id", "maximum_permitted_value", "user_id")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_user_permitted_value_caches のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_one_time_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE NOT NULL, `token_created` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_one_time_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) NOT NULL, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_one_time_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_o1" ON "wakarana_one_time_tokens"("user_id", "token_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_o2" ON "wakarana_one_time_tokens"("token_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_o3" ON "wakarana_one_time_tokens"("user_id", "token")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_one_time_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_authentication_logs`(`ip_address` TEXT NOT NULL, `authentication_id` TEXT COLLATE NOCASE, `authentication_type` TEXT NOT NULL, `succeeded` INTEGER, `failure_reason` TEXT, `authentication_datetime` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_authentication_logs"("ip_address" varchar(39) NOT NULL, "authentication_id" varchar(254), "authentication_type" varchar(60) NOT NULL, "succeeded" boolean, "failure_reason" text, "authentication_datetime" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_authentication_logs の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a1" ON "wakarana_authentication_logs"("authentication_id", "authentication_datetime")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_a2" ON "wakarana_authentication_logs"("authentication_datetime")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_authentication_logs のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_failed_authentication_per_ip_address`(`ip_address` TEXT NOT NULL PRIMARY KEY, `failure_count` INTEGER NOT NULL, `last_authentication_datetime` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_failed_authentication_per_ip_address"("ip_address" varchar(39) NOT NULL PRIMARY KEY, "failure_count" integer NOT NULL, "last_authentication_datetime" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_failed_authentication_per_ip_address の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_fa1" ON "wakarana_failed_authentication_per_ip_address"("last_authentication_datetime")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_failed_authentication_per_ip_address のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_email_address_verification_codes`(`user_id` TEXT COLLATE NOCASE UNIQUE, `email_address` TEXT NOT NULL, `verification_code` TEXT NOT NULL, `code_created` TEXT NOT NULL, `ip_address` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_email_address_verification_codes"("user_id" varchar(60) UNIQUE, "email_address" varchar(254) NOT NULL, "verification_code" varchar(8) NOT NULL, "code_created" timestamp NOT NULL, "ip_address" varchar(39) NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_email_address_verification_codes の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ev1" ON "wakarana_email_address_verification_codes"("email_address", "verification_code")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ev2" ON "wakarana_email_address_verification_codes"("code_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_ev3" ON "wakarana_email_address_verification_codes"("ip_address", "code_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_email_address_verification_codes のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_invite_codes`(`invite_code` TEXT NOT NULL PRIMARY KEY, `is_active` INTEGER NOT NULL, `user_id` TEXT COLLATE NOCASE, `code_created` TEXT NOT NULL, `code_expire` TEXT, `remaining_number` INTEGER, `usage_count` INTEGER NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_invite_codes"("invite_code" varchar(16) NOT NULL PRIMARY KEY, "is_active" boolean NOT NULL, "user_id" varchar(60), "code_created" timestamp NOT NULL, "code_expire" timestamp, "remaining_number" integer, "usage_count" integer NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_invite_codes の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i1" ON "wakarana_invite_codes"("is_active", "code_expire")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i2" ON "wakarana_invite_codes"("code_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i3" ON "wakarana_invite_codes"("is_active", "code_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i4" ON "wakarana_invite_codes"("user_id", "code_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i5" ON "wakarana_invite_codes"("user_id", "is_active", "code_created")');
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_i6" ON "wakarana_invite_codes"("usage_count", "is_active")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_invite_codes のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_password_reset_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE NOT NULL UNIQUE, `token_created` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_password_reset_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) NOT NULL UNIQUE, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_password_reset_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_pr1" ON "wakarana_password_reset_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_password_reset_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $this->profile->db_obj->exec("CREATE TABLE IF NOT EXISTS `wakarana_two_step_verification_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT COLLATE NOCASE NOT NULL UNIQUE, `token_created` TEXT NOT NULL)");
            } else {
                $this->profile->db_obj->exec('CREATE TABLE IF NOT EXISTS "wakarana_two_step_verification_tokens"("token" varchar(43) NOT NULL PRIMARY KEY, "user_id" varchar(60) NOT NULL UNIQUE, "token_created" timestamp NOT NULL)');
            }
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_two_step_verification_tokens の作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('CREATE INDEX IF NOT EXISTS "wakarana_idx_t1" ON "wakarana_two_step_verification_tokens"("token_created")');
        } catch (PDOException $err) {
            $this->print_error("テーブル wakarana_two_step_verification_tokens のインデックス作成処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_roles"("role_id", "role_name", "role_description") VALUES (\'__base__\', \'ベースロール\', \'\') ON CONFLICT ("role_id") DO NOTHING');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_roles"("role_id", "role_name", "role_description") VALUES (\'__admin__\', \'特権管理者ロール\', \'\') ON CONFLICT ("role_id") DO NOTHING');
        } catch (PDOException $err) {
            $this->print_error("初期ロールの追加処理に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->profile->disconnect_db();
        
        return TRUE;
    }
    
    
    function add_custom_field ($custom_field_name, $maximum_length = 500, $records_per_user = 1, $allow_nonunique_value = TRUE, $save_now = TRUE) { //2027年6月以降のバージョンで削除
        return $this->create_custom_field($custom_field_name, $maximum_length, $records_per_user, $allow_nonunique_value, $save_now);
    }
    
    function add_custom_numerical_field ($custom_field_name, $records_per_user = 1, $allow_nonunique_value = TRUE, $save_now = TRUE) { //2027年6月以降のバージョンで削除
        return $this->create_custom_numerical_field($custom_field_name, $records_per_user, $allow_nonunique_value, $save_now);
    }
}
