<?php
/*Wakarana-21.11-1 config.php*/
require_once(dirname(__FILE__)."/common.php");

define("WAKARANA_CONFIG_ORIGINAL",
    array(
            "display_errors" => TRUE,
            
            "use_sqlite" => TRUE,
            "sqlite_db_file" => "wakarana.db",
            
            "mysql_server" => "localhost",
            "mysql_user" => "root",
            "mysql_pass" => "",
            "mysql_db" => "wakarana",
            
            "allow_mail_duplication" => FALSE,
            "confirmation_mail_expire" => 1800,
            
            "login_tokens_per_user" => 4,
            "login_token_expire" => 2592000,
            "one_time_tokens_per_user" => 8,
            "one_time_token_expire" => 43200,
            
            "attempt_interval" => 5,
            "attempt_logs_per_user" => 20,
            "attempt_log_retention_time" => 1209600,
            
            "totp_pin_expire" => 1,
            "totp_temporary_token_expire" => 600
        )
    );


class wakarana_config extends wakarana_common {
    function save () {
        $file_h = fopen(dirname(__FILE__)."/config.ini","w");
        
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
        
        fwrite($file_h,"mysql_server=\"".$this->config["mysql_server"]."\"\n");
        fwrite($file_h,"mysql_user=\"".$this->config["mysql_user"]."\"\n");
        fwrite($file_h,"mysql_pass=\"".$this->config["mysql_pass"]."\"\n");
        fwrite($file_h,"mysql_db=\"".$this->config["mysql_db"]."\"\n");
        fwrite($file_h,"\n");
        
        if ($this->config["allow_mail_duplication"]) {
            fwrite($file_h,"allow_mail_duplication=true\n");
        } else {
            fwrite($file_h,"allow_mail_duplication=false\n");
        }
        fwrite($file_h,"confirmation_mail_expire=".$this->config["confirmation_mail_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"login_tokens_per_user=".$this->config["login_tokens_per_user"]."\n");
        fwrite($file_h,"login_token_expire=".$this->config["login_token_expire"]."\n");
        fwrite($file_h,"one_time_tokens_per_user=".$this->config["one_time_tokens_per_user"]."\n");
        fwrite($file_h,"one_time_token_expire=".$this->config["one_time_token_expire"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"attempt_interval=".$this->config["attempt_interval"]."\n");
        fwrite($file_h,"attempt_logs_per_user=".$this->config["attempt_logs_per_user"]."\n");
        fwrite($file_h,"attempt_log_retention_time=".$this->config["attempt_log_retention_time"]."\n");
        fwrite($file_h,"\n");
        
        fwrite($file_h,"totp_pin_expire=".$this->config["totp_pin_expire"]."\n");
        fwrite($file_h,"totp_temporary_token_expire=".$this->config["totp_temporary_token_expire"]."\n");
        
        fclose($file_h);
    }
    
    
    function set_config_value($key, $value, $save_now = TRUE) {
        if (!isset($value) || gettype(WAKARANA_CONFIG_ORIGINAL[$key]) !== gettype($value)) {
            $this->print_error("入力値が誤っています。");
            return FALSE;
        }
        
        $this->config[$key] = $value;
        
        if ($save_now) {
            $this->save();
        }
        
        return TRUE;
    }
    
    
    function reset_config() {
        $this->config = WAKARANA_CONFIG_ORIGINAL;
        
        $this->save();
    }
    
    
    function setup_db () {
        $this->connect_db();
        
        if ($this->config["use_sqlite"]) {
            $sqlite_r1 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_users'");
            if($sqlite_r1 == FALSE){
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r1->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r1_2 = $this->db_obj->query("CREATE TABLE `wakarana_users`(`user_id` TEXT NOT NULL PRIMARY KEY, `password` TEXT NOT NULL, `user_name` TEXT, `mail_address` TEXT, `user_created` TEXT NOT NULL, `last_updated` TEXT NOT NULL, `last_access` TEXT NOT NULL, `status` INTEGER NOT NULL, `is_master` INTEGER NOT NULL, `totp_key` TEXT)");
                if ($sqlite_r1_2 == FALSE) {
                    $this->print_error("テーブル wakarana_users の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r1_3 = $this->db_obj->query("CREATE INDEX `idx_1` ON `wakarana_users`(`mail_address`)");
                if ($sqlite_r1_3 == FALSE) {
                    $this->print_error("テーブル wakarana_users のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r1_4 = $this->db_obj->query("CREATE INDEX `idx_2` ON `wakarana_users`(`user_name`)");
                if ($sqlite_r1_4 == FALSE) {
                    $this->print_error("テーブル wakarana_users のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r1_5 = $this->db_obj->query("CREATE INDEX `idx_3` ON `wakarana_users`(`user_created`)");
                if ($sqlite_r1_5 == FALSE) {
                    $this->print_error("テーブル wakarana_users のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r1_6 = $this->db_obj->query("CREATE INDEX `idx_4` ON `wakarana_users`(`last_updated`)");
                if ($sqlite_r1_6 == FALSE) {
                    $this->print_error("テーブル wakarana_users のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            
            $sqlite_r2 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_login_tokens'");
            if ($sqlite_r2 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r2->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r2_2 = $this->db_obj->query("CREATE TABLE `wakarana_login_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT NOT NULL, `token_created` TEXT NOT NULL, `expire` TEXT NOT NULL, `ip_address` TEXT NOT NULL, `operating_system` TEXT, `browser_name` TEXT, `last_access` TEXT NOT NULL)");
                if ($sqlite_r2_2 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r2_3 = $this->db_obj->query("CREATE INDEX `idx_5` ON `wakarana_login_tokens`(`user_id`,`token_created`)");
                if ($sqlite_r2_3 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r2_4 = $this->db_obj->query("CREATE INDEX `idx_6` ON `wakarana_login_tokens`(`token_created`)");
                if ($sqlite_r2_4 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r2_5 = $this->db_obj->query("CREATE INDEX `idx_7` ON `wakarana_login_tokens`(`expire`)");
                if ($sqlite_r2_5 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            
            $sqlite_r3 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_privileges'");
            if ($sqlite_r3 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r3->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r3_2 = $this->db_obj->query("CREATE TABLE `wakarana_privileges`(`user_id` TEXT NOT NULL, `permission_id` TEXT NOT NULL, PRIMARY KEY(`user_id`,`permission_id`))");
                if ($sqlite_r3_2 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r3_3 = $this->db_obj->query("CREATE INDEX `idx_8` ON `wakarana_privileges`(`permission_id`,`user_id`)");
                if ($sqlite_r3_3 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            
            $sqlite_r4 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_one_time_tokens'");
            if ($sqlite_r4 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r4->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r4_2 = $this->db_obj->query("CREATE TABLE `wakarana_one_time_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT NOT NULL, `token_created` TEXT NOT NULL)");
                if ($sqlite_r4_2 == FALSE) {
                    $this->print_error("テーブル wakarana_one_time_tokens の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r4_3 = $this->db_obj->query("CREATE INDEX `idx_9` ON `wakarana_one_time_tokens`(`user_id`,`token_created`)");
                if ($sqlite_r4_3 == FALSE) {
                    $this->print_error("テーブル wakarana_one_time_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r4_4 = $this->db_obj->query("CREATE INDEX `idx_10` ON `wakarana_one_time_tokens`(`token_created`)");
                if ($sqlite_r4_4 == FALSE) {
                    $this->print_error("テーブル wakarana_one_time_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            
            $sqlite_r5 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_attempt_logs'");
            if ($sqlite_r5 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r5->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r5_2 = $this->db_obj->query("CREATE TABLE `wakarana_attempt_logs`(`user_id` TEXT NOT NULL, `succeeded` INTEGER NOT NULL, `attempt_datetime` TEXT NOT NULL, `ip_address` TEXT NOT NULL)");
                if ($sqlite_r5_2 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r5_3 = $this->db_obj->query("CREATE INDEX `idx_11` ON `wakarana_attempt_logs`(`user_id`,`attempt_datetime`)");
                if ($sqlite_r5_3 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r5_4 = $this->db_obj->query("CREATE INDEX `idx_12` ON `wakarana_attempt_logs`(`ip_address`,`attempt_datetime`)");
                if ($sqlite_r5_4 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            
            $sqlite_r6 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_mail_confirmation'");
            if ($sqlite_r6 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r6->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r6_2 = $this->db_obj->query("CREATE TABLE `wakarana_mail_confirmation`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT UNIQUE, `mail_address` TEXT NOT NULL, `purpose` INTEGER NOT NULL, `token_created` TEXT NOT NULL)");
                if ($sqlite_r6_2 == FALSE) {
                    $this->print_error("テーブル wakarana_mail_confirmation の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r6_3 = $this->db_obj->query("CREATE INDEX `idx_13` ON `wakarana_mail_confirmation`(`mail_address`,`user_id`)");
                if ($sqlite_r6_3 == FALSE) {
                    $this->print_error("テーブル wakarana_mail_confirmation のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r6_4 = $this->db_obj->query("CREATE INDEX `idx_14` ON `wakarana_mail_confirmation`(`token_created`)");
                if ($sqlite_r6_4 == FALSE) {
                    $this->print_error("テーブル wakarana_mail_confirmation のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            $sqlite_r7 = $this->db_obj->query("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'wakarana_totp_temporary_tokens'");
            if ($sqlite_r7 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sqlite_r7->fetchArray(SQLITE3_ASSOC);
            if (!$q_kekka) {
                $sqlite_r7_2 = $this->db_obj->query("CREATE TABLE `wakarana_totp_temporary_tokens`(`token` TEXT NOT NULL PRIMARY KEY, `user_id` TEXT NOT NULL, `token_created` TEXT NOT NULL)");
                if ($sqlite_r7_2 == FALSE) {
                    $this->print_error("テーブル wakarana_totp_temporary_tokens の作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r7_3 = $this->db_obj->query("CREATE INDEX `idx_15` ON `wakarana_totp_temporary_tokens`(`user_id`,`token_created`)");
                if ($sqlite_r7_3 == FALSE) {
                    $this->print_error("テーブル wakarana_totp_temporary_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $sqlite_r7_4 = $this->db_obj->query("CREATE INDEX `idx_16` ON `wakarana_totp_temporary_tokens`(`token_created`)");
                if ($sqlite_r7_4 == FALSE) {
                    $this->print_error("テーブル wakarana_totp_temporary_tokens のインデックスの作成に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            $this->disconnect_db();
            
            return TRUE;
        } else {
            $mysql_r1 = $this->db_obj->query("SHOW DATABASES LIKE '".$this->config["mysql_db"]."'");
            if ($mysql_r1 == FALSE) {
                $this->print_error("データベースの確認に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $mysql_r1->fetch_assoc();
            if (!$q_kekka) {
                $mysql_r1_2 = $this->db_obj->query("CREATE DATABASE `".$this->config["mysql_db"]."` default character set utf8");
                if ($mysql_r1_2 == FALSE) {
                    $this->print_error("データベースの作成に失敗しました。".$this->db_obj->error."データベース作成権限をご確認ください。");
                    return FALSE;
                }
            }
            
            $this->db_obj->select_db($this->config["mysql_db"]);
            
            $mysql_r2 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_users'");
            if ($mysql_r2 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        
            if ($mysql_r2->fetch_assoc() == NULL) {
                $mysql_r2_2 = $this->db_obj->query("CREATE TABLE `wakarana_users`(`user_id` char(63) NOT NULL PRIMARY KEY, `password` varchar(128) NOT NULL, `user_name` char(63), `mail_address` varchar(255), `user_created` datetime NOT NULL, `last_updated` datetime NOT NULL, `last_access` datetime NOT NULL, `status` tinyint NOT NULL, `is_master` tinyint NOT NULL, `totp_key` varchar(16), INDEX idx_1(`mail_address`), INDEX idx_2(`user_name`), INDEX idx_3(`user_created`), INDEX idx_4(`last_updated`))");
                if ($mysql_r2_2 == FALSE) {
                    $this->print_error("テーブル wakarana_users の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $mysql_r3 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_login_tokens'");
            if ($mysql_r3 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            if ($mysql_r3->fetch_assoc() == NULL) {
                $mysql_r3_2 = $this->db_obj->query("CREATE TABLE `wakarana_login_tokens`(`token` char(43) NOT NULL PRIMARY KEY, `user_id` char(63) NOT NULL, `token_created` datetime NOT NULL, `expire` datetime NOT NULL, `ip_address` char(15) NOT NULL, `operating_system` varchar(31), `browser_name` varchar(31) ,`last_access` datetime NOT NULL, INDEX idx_5(`user_id`,`token_created`), INDEX idx_6(`token_created`), INDEX idx_7(`expire`))");
                if ($mysql_r3_2 == FALSE) {
                    $this->print_error("テーブル wakarana_login_tokens の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $mysql_r4 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_privileges'");
            if ($mysql_r4 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
            }
            
            if ($mysql_r4->fetch_assoc() == NULL) {
                $mysql_r4_2 = $this->db_obj->query("CREATE TABLE `wakarana_privileges`(`user_id` char(63) NOT NULL, `permission_id` char(127) NOT NULL, PRIMARY KEY(`user_id`,`permission_id`), INDEX idx_8(`permission_id`,`user_id`))");
                if ($mysql_r4_2 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $mysql_r5 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_one_time_tokens'");
            if ($mysql_r5 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
            }
            
            if ($mysql_r5->fetch_assoc() == NULL) {
                $mysql_r5_2 = $this->db_obj->query("CREATE TABLE `wakarana_one_time_tokens`(`token` char(43) NOT NULL PRIMARY KEY, `user_id` char(63) NOT NULL, `token_created` datetime NOT NULL, INDEX idx_9(`user_id`,`token_created`), INDEX idx_10(`token_created`))");
                if ($mysql_r5_2 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $mysql_r6 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_attempt_logs'");
            if ($mysql_r6 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
            }
            
            if ($mysql_r6->fetch_assoc() == NULL) {
                $mysql_r6_2 = $this->db_obj->query("CREATE TABLE `wakarana_attempt_logs`(`user_id` char(63) NOT NULL, `succeeded` tinyint NOT NULL, `attempt_datetime` datetime NOT NULL, ip_address char(15), INDEX idx_11(`user_id`,`attempt_datetime`), INDEX idx_12(`ip_address`,`attempt_datetime`))");
                if ($mysql_r6_2 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $mysql_r7 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_mail_confirmation'");
            if ($mysql_r7 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
            }
            
            if ($mysql_r7->fetch_assoc() == NULL) {
                $mysql_r7_2 = $this->db_obj->query("CREATE TABLE `wakarana_mail_confirmation`(`token` char(43) NOT NULL PRIMARY KEY, `user_id` char(63) UNIQUE, `mail_address` varchar(255) NOT NULL, `purpose` tinyint NOT NULL, `token_created` datetime NOT NULL, INDEX idx_13(`mail_address`,`user_id`), INDEX idx_14(`token_created`))");
                if ($mysql_r7_2 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $mysql_r8 = $this->db_obj->query("SHOW TABLES FROM `".$this->config["mysql_db"]."` LIKE 'wakarana_totp_temporary_tokens'");
            if ($mysql_r8 == FALSE) {
                $this->print_error("テーブルの確認に失敗しました。".$this->db_obj->error);
            }
            
            if ($mysql_r8->fetch_assoc() == NULL) {
                $mysql_r8_2 = $this->db_obj->query("CREATE TABLE `wakarana_totp_temporary_tokens`(`token` char(43) NOT NULL PRIMARY KEY, `user_id` char(63) NOT NULL, `token_created` datetime NOT NULL, INDEX idx_15(`user_id`,`token_created`), INDEX idx_16(`token_created`))");
                if ($mysql_r8_2 == FALSE) {
                    $this->print_error("テーブル wakarana_privileges の作成に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $this->disconnect_db();
            return TRUE;
        }
    }
}
