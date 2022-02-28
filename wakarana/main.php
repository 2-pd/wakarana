<?php
/*Wakarana-21.11-1 main.php*/
require_once(dirname(__FILE__)."/common.php");

define("WAKARANA_STATUS_DISABLE", 0);
define("WAKARANA_STATUS_NORMAL", 1);
define("WAKARANA_STATUS_MAIL_UNCONFIRMED", 3);

define("WAKARANA_MAIL_PURPOSE_NEW_USER", 0);
define("WAKARANA_MAIL_PURPOSE_EXISTING_USER", 1);

define("WAKARANA_BASE32_TABLE", array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "2", "3", "4", "5", "6", "7"));


class wakarana extends wakarana_common {
    function __construct () {
        parent::__construct();
        $this->connect_db();
    }
    
    
    function escape_id ($id, $len = 60) {
        return substr(preg_replace("/[^0-9a-z_]/", "", strtolower($id)), 0, $len);
    }
    
    
    function get_user_info ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT * FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT * FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if ($q_kekka) {
            return $q_kekka;
        } else {
            return NULL;
        }
    }
    
    
    function mail_exists ($mail_address) {
        if (strlen($mail_address) == 0) {
            return NULL;
        }
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `user_id` FROM `wakarana_users` WHERE `mail_address` = '".$this->db_obj->escapeString($mail_address)."'");
            if($sql_r1 === FALSE){
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return NULL;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `user_id` FROM `wakarana_users` WHERE `mail_address` = '".$this->db_obj->real_escape_string($mail_address)."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return NULL;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if (!empty($q_kekka)) {
            return $q_kekka["user_id"];
        } else {
            return FALSE;
        }
    }
    
    
    function add_user ($user_id, $password, $user_name = "匿名", $mail_address = NULL, $is_master = FALSE, $status = WAKARANA_STATUS_NORMAL){
        if (!$this->config["allow_mail_duplication"] && !empty($this->mail_exists($mail_address))) {
            $this->print_error("既に使用されているメールアドレスです。同一メールアドレスでの復数アカウント作成を許可しない設定になっています。");
            return FALSE;
        }
        
        $user_id = $this->escape_id($user_id);
        $password_hash = hash("sha512", $password.hash("sha512", $user_id));
        if (!empty($mail_address)) {
            if ($this->config["use_sqlite"]) {
                $mail_address_q = "'".$this->db_obj->escapeString($mail_address)."'";
            } else {
                $mail_address_q = "'".$this->db_obj->real_escape_string($mail_address)."'";
            }
        } else {
            $mail_address_q = "NULL";
        }
        if ($is_master) {
            $is_master_num = 1;
        } else {
            $is_master_num = 0;
        }
        $status = intval($status);
        
        $ima = date("Y-m-d H:i:s");
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_users`(`user_id`,`password`,`user_name`,`mail_address`,`user_created`,`last_updated`,`last_access`,`status`,`is_master`,`totp_key`) VALUES ('".$user_id."','".$password_hash."','".$this->db_obj->escapeString($user_name)."',".$mail_address_q.",'".$ima."','".$ima."','".$ima."',".$status.",".$is_master_num.",NULL)");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_users`(`user_id`,`password`,`user_name`,`mail_address`,`user_created`,`last_updated`,`last_access`,`status`,`is_master`,`totp_key`) VALUES ('".$user_id."','".$password_hash."','".$this->db_obj->real_escape_string($user_name)."',".$mail_address_q.",'".$ima."','".$ima."','".$ima."',".$status.",".$is_master_num.",NULL)");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return $user_id;
    }
    
    
    function change_user_data ($user_id, $password = NULL, $user_name = NULL, $mail_address = NULL, $is_master = NULL, $status = NULL) {
        $user_id = $this->escape_id($user_id);
        
        $data = $this->get_user_info($user_id, FALSE);
        
        if ($password === NULL) {
            $password_hash = $data["password"];
        } else {
            $password_hash = hash("sha512", $password.hash("sha512", $user_id));
        }
        
        if ($user_name === NULL) {
            $user_name = $data["user_name"];
        }
        
        if ($mail_address === NULL) {
            $mail_address_q = $data["mail_address"];
        } elseif ($mail_address === "") {
            $mail_address_q = NULL;
        } else {
            $mail_address_q = $mail_address;
            
            if ((!$this->config["allow_mail_duplication"]) && $mail_address !== $data["mail_address"]) {
                if (!empty($this->mail_exists($mail_address))) {
                    $this->print_error("既に使用されているメールアドレスです。同一メールアドレスでの復数アカウント作成を許可しない設定になっています。");
                    return FALSE;
                }
            }
        }
        
        if ($mail_address_q === NULL) {
            $mail_address_q = "NULL";
        } else {
            if ($this->config["use_sqlite"]) {
                $mail_address_q = "'".$this->db_obj->escapeString($mail_address_q)."'";
            }else{
                $mail_address_q = "'".$this->db_obj->real_escape_string($mail_address_q)."'";
            }
        }
        
        if ($is_master === NULL) {
            $is_master_num = $data["is_master"];
        } else {
            if ($is_master) {
                $is_master_num = 1;
            } else {
                $is_master_num = 0;
            }
        }
        
        if ($status === NULL) {
            $status = $data["status"];
        } else {
            $status = intval($status);
        }
        
        $ima = date("Y-m-d H:i:s");
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `password` = '".$password_hash."',`user_name` = '".$this->db_obj->escapeString($user_name)."', `mail_address` = ".$mail_address_q.", `last_updated` = '".$ima."', `status` = ".$status.", `is_master` = ".$is_master_num." WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `password` = '".$password_hash."',`user_name` = '".$this->db_obj->real_escape_string($user_name)."', `mail_address` = ".$mail_address_q.", `last_updated` = '".$ima."', `status` = ".$status.", `is_master` = ".$is_master_num." WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        if ($status == WAKARANA_STATUS_DISABLE) {
            $this->delete_all_tokens($user_id);
        }
        
        return TRUE;
    }
    
    
    function enable_2_factor_auth ($user_id, $totp_key = NULL) {
        $user_id = $this->escape_id($user_id);
        
        if (empty($totp_key)) {
            $totp_key = $this->create_totp_key();
        }
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `totp_key` = '".$this->db_obj->escapeString($totp_key)."' WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `totp_key` = '".$this->db_obj->real_escape_string($totp_key)."' WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return $totp_key;
    }
    
    
    function disable_2_factor_auth ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `totp_key` = NULL WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `totp_key` = NULL WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function delete_user ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_users` WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return NULL;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_users` WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return NULL;
            }
        }
        
        $this->delete_all_tokens($user_id);
        $this->delete_attempt_logs($user_id);
        $this->delete_privilege($user_id);
        
        return TRUE;
    }
    
    
    function get_all_users ($start = 0, $limit = 100) {
        $start = intval($start);
        $limit = intval($limit);
        
        $kekka_list = array();
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT * FROM `wakarana_users` order by `user_created` limit ".$start.",".$limit);
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            for ($cnt = 0; $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC); $cnt++) {
                $kekka_list[$cnt] = $q_kekka;
            }
        } else {
            $sql_r1 = $this->db_obj->query("SELECT * FROM `wakarana_users` order by `user_created` limit ".$start.",".$limit);
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            for ($cnt = 0; $q_kekka = $sql_r1->fetch_assoc(); $cnt++) {
                $kekka_list[$cnt] = $q_kekka;
            }
        }
        
        return $kekka_list;
    }
            
    
    function add_privilege ($user_id, $permission_id) {
        $user_id = $this->escape_id($user_id);
        $permission_id = $this->escape_id($permission_id, 120);
        
        if ($permission_id === "master") {
            $this->print_error("「master」は利用できません。");
            return FALSE;
        }
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_privileges`(`user_id`,`permission_id`) VALUES ('".$user_id."','".$permission_id."')");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_privileges`(`user_id`,`permission_id`) VALUES ('".$user_id."','".$permission_id."')");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function check_privilege ($user_id, $permission_id, $always_allow_master = TRUE) {
        $user_id = $this->escape_id($user_id);
        $permission_id = $this->escape_id($permission_id,120);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `user_id` FROM `wakarana_privileges` WHERE `user_id`='".$user_id."' AND `permission_id`='".$permission_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return NULL;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `user_id` FROM `wakarana_privileges` WHERE `user_id`='".$user_id."' AND `permission_id`='".$permission_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return NULL;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if ($q_kekka) {
            return TRUE;
        } elseif ($always_allow_master) {
            if ($this->config["use_sqlite"]) {
                $sql_r1 = $this->db_obj->query("SELECT `is_master` FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $q_kekka_2 = $sql_r1->fetchArray(SQLITE3_ASSOC);
            } else {
                $sql_r1 = $this->db_obj->query("SELECT `is_master` FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            
                $q_kekka_2 = $sql_r1->fetch_assoc();
            }
            
            if ($q_kekka_2["is_master"] == 1) {//MySQLは文字列型で出力される仕様のため型比較なし
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    
    
    function delete_privilege ($user_id = NULL, $permission_id = NULL) {
        if ($user_id != NULL) {
            $user_id = $this->escape_id($user_id);
                        
            if ($permission_id == NULL) {
                if($this->config["use_sqlite"]){
                    $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_privileges` WHERE `user_id`='".$user_id."'");
                    if ($sql_r1 === FALSE) {
                        $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                        return FALSE;
                    }
                } else {
                    $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_privileges` WHERE `user_id`='".$user_id."'");
                    if ($sql_r1 === FALSE) {
                        $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                        return FALSE;
                    }
                }
            } else {
                $permission_id = $this->escape_id($permission_id,120);
                
                if ($this->config["use_sqlite"]) {
                    $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_privileges` WHERE `user_id`='".$user_id."' AND `permission_id`='".$permission_id."'");
                    if ($sql_r1 === FALSE) {
                        $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                        return FALSE;
                    }
                } else {
                    $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_privileges` WHERE `user_id`='".$user_id."' AND `permission_id`='".$permission_id."'");
                    if ($sql_r1 === FALSE) {
                        $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                        return FALSE;
                    }
                }
            }
        } else {
            if ($permission_id == NULL) {
                return FALSE;
            }
            
            $permission_id = $this->escape_id($permission_id,120);
            
            if ($this->config["use_sqlite"]) {
                $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_privileges` WHERE `permission_id`='".$permission_id."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            } else {
                $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_privileges` WHERE `permission_id`='".$permission_id."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
        }
        
        return TRUE;
    }
    
    
    function create_token () {
        return rtrim(strtr(base64_encode(random_bytes(32)), "+/", "-_"), "=");
    }
    
    
    function delete_old_login_tokens ($expire = -1) {
        if ($expire < 0) {
            $where_q = "`expire` < '".date("Y-m-d H:i:s")."'";
        } else {
            $where_q = "`token_created` < '".date("Y-m-d H:i:s", time() - $expire)."'";
        }
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE ".$where_q);
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE ".$where_q);
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function delete_all_tokens ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `user_id` = '".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `user_id` = '".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function delete_old_attempt_logs ($expire = -1) {
        if ($expire < 0) {
            $expire = $this->config["one_time_token_expire"];
        }
        
        $kako = date("Y-m-d H:i:s", time() - $expire);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_attempt_logs` WHERE `attempt_datetime` < '".$kako."'");
            if($sql_r1 === FALSE){
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_attempt_logs` WHERE `attempt_datetime` < '".$kako."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function add_attempt_logs ($user_id, $succeeded) {
        $user_id = $this->escape_id($user_id);
        if ($succeeded) {
            $succeeded_num = 1;
        } else {
            $succeeded_num = 0;
        }
        
        $this->delete_old_attempt_logs();
        
        if ($this->config["use_sqlite"]) {//SQLiteとMySQLで最古ログを削除するSQLは異なる
            
            
            $sql_r2 = $this->db_obj->query("SELECT COUNT(*) AS `cnt` FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."'");
            if($sql_r2 === FALSE){
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka_2 = $sql_r2->fetchArray(SQLITE3_ASSOC);
            if($q_kekka_2["cnt"] >= $this->config["attempt_logs_per_user"]){
                $sql_r3 = $this->db_obj->query("DELETE FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."' AND not exists(SELECT * FROM `wakarana_attempt_logs` AS `tbl` WHERE `tbl`.`user_id`='".$user_id."' AND `wakarana_attempt_logs`.`attempt_datetime` > `tbl`.`attempt_datetime`)");
                if($sql_r3 === FALSE){
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            $sql_r4 = $this->db_obj->query("INSERT INTO `wakarana_attempt_logs`(`user_id`,`succeeded`,`attempt_datetime`,`ip_address`) VALUES ('".$user_id."',".$succeeded_num.",'".date("Y-m-d H:i:s")."','".$this->db_obj->escapeString($_SERVER["REMOTE_ADDR"])."')");
            if($sql_r4 === FALSE){
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            
            
            $sql_r2 = $this->db_obj->query("SELECT COUNT(*) AS `cnt` FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka_2 = $sql_r2->fetch_assoc();
            if ($q_kekka_2["cnt"] >= $this->config["attempt_logs_per_user"]) {
                $sql_r3 = $this->db_obj->query("DELETE FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."' order by `attempt_datetime` limit 1");
                if ($sql_r3 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $sql_r4 = $this->db_obj->query("INSERT INTO `wakarana_attempt_logs`(`user_id`,`succeeded`,`attempt_datetime`,`ip_address`) VALUES ('".$user_id."',".$succeeded_num.",'".date("Y-m-d H:i:s")."','".$this->db_obj->real_escape_string($_SERVER["REMOTE_ADDR"])."')");
            if ($sql_r4 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function check_attempt_interval ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        $ima = time();
        $datetime_min = date("Y-m-d H:i:s", $ima - $this->config["attempt_interval"]);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `attempt_datetime` FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."' AND `attempt_datetime`>='".$datetime_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        }else{
            $sql_r1 = $this->db_obj->query("SELECT `attempt_datetime` FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."' AND `attempt_datetime`>='".$datetime_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if (empty($q_kekka)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function get_attempt_logs ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        $kekka_list = array();
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT * FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."' order by `attempt_datetime` DESC");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            for ($cnt = 0; $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC); $cnt++) {
                $kekka_list[$cnt] = $q_kekka;
            }
        }else{
            $sql_r1 = $this->db_obj->query("SELECT * FROM `wakarana_attempt_logs` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            for ($cnt = 0; $q_kekka = $sql_r1->fetch_assoc(); $cnt++) {
                $kekka_list[$cnt] = $q_kekka;
            }
        }
        
        return $kekka_list;
    }
    
    
    function delete_attempt_logs ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_attempt_logs` WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_attempt_logs` WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
    }
    
    
    function update_last_access ($user_id) {
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `last_access` = '".date("Y-m-d H:i:s")."' WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("UPDATE `wakarana_users` SET `last_access` = '".date("Y-m-d H:i:s")."' WHERE `user_id` = '".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
    }
    
    
    function check_password ($user_id, $password) {
        $user_id = $this->escape_id($user_id);
        $password_hash = hash("sha512", $password.hash("sha512", $user_id));
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `password` FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `password` FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if ($q_kekka) {
            if ($q_kekka["password"] === $password_hash) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    function set_login_token ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        $user_info = $this->get_user_environment();
        
        $ima = date("Y-m-d H:i:s");
        $expire_ts = time() + $this->config["login_token_expire"];
        $expire = date("Y-m-d H:i:s", $expire_ts);
        
        $token = $this->create_token();
        
        $this->delete_old_login_tokens();
        
        if ($this->config["use_sqlite"]) {//SQLiteとMySQLで最古トークンを削除するSQLは異なる
            $sql_r2 = $this->db_obj->query("SELECT COUNT(*) AS `cnt` FROM `wakarana_login_tokens` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka_2 = $sql_r2->fetchArray(SQLITE3_ASSOC);
            if ($q_kekka_2["cnt"] >= $this->config["login_tokens_per_user"]) {
                $sql_r3 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE `user_id`='".$user_id."' AND not exists(SELECT * FROM `wakarana_login_tokens` AS `tbl` WHERE `tbl`.`user_id`='".$user_id."' AND `wakarana_login_tokens`.`token_created` > `tbl`.`token_created`)");
                if ($sql_r3 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            $sql_r4 = $this->db_obj->query("INSERT INTO `wakarana_login_tokens`(`token`,`user_id`,`token_created`,`expire`,`ip_address`,`operating_system`,`browser_name`,`last_access`) VALUES ('".$token."','".$user_id."','".$ima."','".$expire."','".$this->db_obj->escapeString($_SERVER["REMOTE_ADDR"])."','".$user_info["os_name"]."','".$user_info["browser_name"]."','".$ima."')");
            if ($sql_r4 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r2 = $this->db_obj->query("SELECT COUNT(*) AS `cnt` FROM `wakarana_login_tokens` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka_2 = $sql_r2->fetch_assoc();
            if ($q_kekka_2["cnt"] >= $this->config["login_tokens_per_user"]) {
                $sql_r3 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE `user_id`='".$user_id."' order by `token_created` limit 1");
                if ($sql_r3 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $sql_r4 = $this->db_obj->query("INSERT INTO `wakarana_login_tokens`(`token`,`user_id`,`token_created`,`expire`,`ip_address`,`operating_system`,`browser_name`,`last_access`) VALUES ('".$token."','".$user_id."','".$ima."','".$expire."','".$this->db_obj->real_escape_string($_SERVER["REMOTE_ADDR"])."','".$user_info["os_name"]."','".$user_info["browser_name"]."','".$ima."')");
            if ($sql_r4 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        $kekka = setcookie("wakarana_login_token", $token, $expire_ts, "/", "", FALSE, TRUE);
        
        if ($kekka) {
            $this->add_attempt_logs($user_id, TRUE);
            $this->update_last_access($user_id);
            
            return TRUE;
        } else {
            $this->print_error("Cookieの送信に失敗しました。この処理を実行する前に文字が出力されていないか確認してください。");
            return FALSE;
        }
    }
    
    
    function login ($user_id, $password, $totp_pin = NULL) {
        if (!$this->check_attempt_interval($user_id)) {
            return FALSE;
        }
        
        $user_id = $this->escape_id($user_id);
        $password_hash = hash("sha512", $password.hash("sha512", $user_id));
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `password`,`status`,`totp_key` FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `password`,`status`,`totp_key` FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if ($q_kekka) {
            if ($q_kekka["password"] === $password_hash && $q_kekka["status"] == WAKARANA_STATUS_NORMAL) {//MySQLは文字列型で出力される仕様のためstatusの型比較なし
                if (!empty($q_kekka["totp_key"])) {
                    if ($totp_pin === NULL) {
                        return $this->create_totp_temporary_token($user_id);
                    } else {
                        if ($this->totp_check($user_id, $totp_pin)) {
                            return $this->set_login_token($user_id);
                        }
                    }
                } else {
                    return $this->set_login_token($user_id);
                }
            }
            
            $this->add_attempt_logs($user_id, FALSE);
        }
        
        return FALSE;
    }
    
    
    function delete_old_mail_confirmation_tokens ($expire = -1) {
        if ($expire < 0) {
            $expire = $this->config["confirmation_mail_expire"];
        }
        
        $kako = date("Y-m-d H:i:s", time() - $expire);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_mail_confirmation` WHERE `token_created` < '".$kako."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_mail_confirmation` WHERE `token_created` < '".$kako."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function create_mail_confirmation_token ($mail_address, $user_id = NULL) {
        if (!$this->config["allow_mail_duplication"] && !empty($this->mail_exists($mail_address))) {
            $this->print_error("既に使用されているメールアドレスです。同一メールアドレスでの復数アカウント作成を許可しない設定になっています。");
            return FALSE;
        }
        
        if (!empty($user_id)) {
            $purpose = WAKARANA_MAIL_PURPOSE_NEW_USER;
            $user_id_q = "NULL";
        } else {
            $purpose = WAKARANA_MAIL_PURPOSE_EXISTING_USER;
            $user_id_q = "'".$this->escape_id($user_id)."'";
        }
        
        $this->delete_old_mail_confirmation_tokens();
        
        $token = $this->create_token();
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_mail_confirmation`(`token`,`user_id`,`mail_address`,`purpose`,`token_created`) VALUES ('".$token."',".$user_id_q.",'".$this->db_obj->escapeString($mail_address)."',".$purpose.",'".date("Y-m-d H:i:s")."')");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_mail_confirmation`(`token`,`user_id`,`mail_address`,`purpose`,`token_created`) VALUES ('".$token."',".$user_id_q.",'".$this->db_obj->real_escape_string($mail_address)."',".$purpose.",'".date("Y-m-d H:i:s")."')");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return $token;
    }
    
    
    function mail_confirm ($token, $delete_token = TRUE) {
        $created_min = date("Y-m-d H:i:s", time() - $this->config["confirmation_mail_expire"]);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `user_id`,`mail_address`,`purpose` FROM `wakarana_mail_confirmation` WHERE `token`='".$this->db_obj->escapeString($token)."' AND `token_created`>'".$created_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
            
            if ($delete_token) {
                $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_mail_confirmation` WHERE `token`='".$this->db_obj->escapeString($token)."'");
                if ($sql_r2 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `user_id`,`mail_address`,`purpose` FROM `wakarana_mail_confirmation` WHERE `token`='".$this->db_obj->real_escape_string($token)."' AND `token_created`>'".$created_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
            
            if ($delete_token) {
                $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_mail_confirmation` WHERE `token`='".$this->db_obj->real_escape_string($token)."'");
                if ($sql_r2 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
        }
        
        if (!empty($q_kekka)) {
            return $q_kekka;
        } else {
            return FALSE;
        }
    }
    
    
    function save_user_mail_address($token) {
        $kekka = $this->mail_confirm($token);
        
        if (!empty($kekka) && !empty($kekka["user_id"])) {
            return $this->change_user_data($kekka["user_id"], NULL, NULL, $kekka["mail_address"]);
        } else {
            return FALSE;
        }
    }
    
    
    function create_totp_temporary_token ($user_id) {
        $user_id = $this->escape_id($user_id);
        $token = $this->create_token();
        
        $this->delete_old_totp_tokens();
        $this->delete_totp_tokens($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_totp_temporary_tokens`(`token`,`user_id`,`token_created`) VALUES ('".$token."','".$user_id."','".date("Y-m-d H:i:s")."')");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("INSERT INTO `wakarana_totp_temporary_tokens`(`token`,`user_id`,`token_created`) VALUES ('".$token."','".$user_id."','".date("Y-m-d H:i:s")."')");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        $this->add_attempt_logs($user_id, TRUE);
        
        return $token;
    }
    
    
    function delete_totp_tokens ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        if ($this->config["use_sqlite"]) {
            $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_totp_temporary_tokens` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_totp_temporary_tokens` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function delete_old_totp_tokens ($expire = -1) {
        if ($expire < 0) {
            $expire = $this->config["totp_temporary_token_expire"];
        }
        
        $kako = date("Y-m-d H:i:s", time() - $expire);
        
        if ($this->config["use_sqlite"]) {
            $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_totp_temporary_tokens` WHERE `token_created`<'".$kako."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r2 = $this->db_obj->query("DELETE FROM `wakarana_totp_temporary_tokens` WHERE `token_created`<'".$kako."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function totp_login ($tmp_token, $totp_pin) {
        $ima = time();
        $created_min = date("Y-m-d H:i:s", $ima - $this->config["totp_temporary_token_expire"]);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `user_id` FROM `wakarana_totp_temporary_tokens` WHERE `token`='".$this->db_obj->escapeString($tmp_token)."' AND `token_created`>'".$created_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `user_id` FROM `wakarana_totp_temporary_tokens` WHERE `token`='".$this->db_obj->real_escape_string($tmp_token)."' AND `token_created`>'".$created_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        if (!empty($q_kekka)) {
            if (!$this->check_attempt_interval($q_kekka["user_id"])) {
                return FALSE;
            }
            
            if ($this->totp_check($q_kekka["user_id"], $totp_pin)) {
                $this->delete_totp_tokens($q_kekka["user_id"]);
                
                return $this->set_login_token($q_kekka["user_id"]);
            }
            
            $this->add_attempt_logs($q_kekka["user_id"], FALSE);
        }
        
        return FALSE;
    }
    
    
    function check ($permission_id = NULL) {
        if (isset($_COOKIE["wakarana_login_token"])) {
            if ($this->config["use_sqlite"]) {
                $sql_r1 = $this->db_obj->query("SELECT `user_id`,strftime('%s',`expire`) AS `expire` FROM `wakarana_login_tokens` WHERE `token`='".$this->db_obj->escapeString($_COOKIE["wakarana_login_token"])."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
                
                $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
            } else {
                $sql_r1 = $this->db_obj->query("SELECT `user_id`,unix_timestamp(`expire`) AS `expire` FROM `wakarana_login_tokens` WHERE `token`='".$this->db_obj->real_escape_string($_COOKIE["wakarana_login_token"])."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
                
                $q_kekka = $sql_r1->fetch_assoc();
            }
            
            if ($q_kekka) {
                if ($q_kekka["expire"] > time()) {
                    if ($permission_id === NULL) {
                        return $q_kekka["user_id"];
                    } else {
                        if ($this->check_privilege($q_kekka["user_id"],$permission_id)) {
                            return $q_kekka["user_id"];
                        }
                    }
                }
            }
        }
        
        return FALSE;
    }
    
    
    function logout () {
        $user_id = $this->check();
        
        if ($user_id !== FALSE) {
            if ($this->config["use_sqlite"]) {
                $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE `token`='".$this->db_obj->escapeString($_COOKIE["wakarana_login_token"])."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            } else {
                $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_login_tokens` WHERE `token`='".$this->db_obj->real_escape_string($_COOKIE["wakarana_login_token"])."'");
                if ($sql_r1 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $kako = time() - 3600;
            setcookie("wakarana_login_token", "", $kako, "/");
            
            $this->update_last_access($user_id);
            
            return TRUE;
        } else {
            $this->print_error("ログインが有効な状態ではないためログアウトできませんでした。");
            return FALSE;
        }
    }
    
    
    function delete_old_one_time_tokens ($expire = -1) {
        if ($expire < 0) {
            $expire = $this->config["one_time_token_expire"];
        }
        
        $kako = date("Y-m-d H:i:s", time() - $expire);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `token_created` < '".$kako."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `token_created` < '".$kako."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function create_one_time_token ($user_id) {
        $user_id = $this->escape_id($user_id);
        
        $token = $this->create_token();
        
        $this->delete_old_one_time_tokens();
        
        if ($this->config["use_sqlite"]) {//SQLiteとMySQLで最古トークンを削除するSQLは異なる
            $sql_r2 = $this->db_obj->query("SELECT COUNT(*) AS `cnt` FROM `wakarana_one_time_tokens` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka_2 = $sql_r2->fetchArray(SQLITE3_ASSOC);
            if ($q_kekka_2["cnt"] >= $this->config["one_time_tokens_per_user"]) {
                $sql_r3 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `user_id`='".$user_id."' AND not exists(SELECT * FROM `wakarana_one_time_tokens` AS `tbl` WHERE `tbl`.`user_id`='".$user_id."' AND `wakarana_one_time_tokens`.`token_created` > `tbl`.`token_created`)");
                if ($sql_r3 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                    return FALSE;
                }
            }
            
            $sql_r4 = $this->db_obj->query("INSERT INTO `wakarana_one_time_tokens`(`token`,`user_id`,`token_created`) VALUES ('".$token."','".$user_id."','".date("Y-m-d H:i:s")."')");
            if ($sql_r4 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
        } else {
            $sql_r2 = $this->db_obj->query("SELECT COUNT(*) AS `cnt` FROM `wakarana_one_time_tokens` WHERE `user_id`='".$user_id."'");
            if ($sql_r2 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka_2 = $sql_r2->fetch_assoc();
            if ($q_kekka_2["cnt"] >= $this->config["one_time_tokens_per_user"]) {
                $sql_r3 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `user_id`='".$user_id."' order by `token_created` limit 1");
                if ($sql_r3 === FALSE) {
                    $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                    return FALSE;
                }
            }
            
            $sql_r4 = $this->db_obj->query("INSERT INTO `wakarana_one_time_tokens`(`token`,`user_id`,`token_created`) VALUES ('".$token."','".$user_id."','".date("Y-m-d H:i:s")."')");
            if ($sql_r4 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
        }
        
        return $token;
    }
    
    
    function check_one_time_token ($user_id, $token) {
        $user_id = $this->escape_id($user_id);
        
        $ima = time();
        $created_min = date("Y-m-d H:i:s", $ima - $this->config["one_time_token_expire"]);
        
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `token`='".$this->db_obj->escapeString($token)."' AND `user_id` = '".$user_id."' AND `token_created`>'".$created_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            if ($this->db_obj->changes() >= 1) {
                return TRUE;
            }
        } else {
            $sql_r1 = $this->db_obj->query("DELETE FROM `wakarana_one_time_tokens` WHERE `token`='".$this->db_obj->real_escape_string($token)."' AND `user_id` = '".$user_id."' AND `token_created`>'".$created_min."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            if ($this->db_obj->affected_rows >= 1) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    function get_user_environment () {
        $os_names = array("Android", "iPhone", "iPad", "Windows", "Macintosh", "CrOS", "Linux", "BSD", "Nintendo", "PlayStation", "Xbox");
        $browser_names = array("Firefox", "Edg", "OPR", "Sleipnir", "Chrome", "Safari", "Trident");
        
        $environment = array("os_name" => NULL, "browser_name" => NULL);
        
        foreach ($os_names as $os_name) {
            if (strpos($_SERVER["HTTP_USER_AGENT"], $os_name) !== FALSE){
                $environment["os_name"] = $os_name;
                break;
            }
        }
        
        foreach ($browser_names as $browser_name) {
            if (strpos($_SERVER["HTTP_USER_AGENT"], $browser_name) !== FALSE){
                $environment["browser_name"] = $browser_name;
                break;
            }
        }
        
        return $environment;
    }
    
    
    function totp_compare ($totp_key, $totp_pin) {
        $pin_expire = $this->config["totp_pin_expire"] * 2;
        
        for ($cnt = 0; $cnt <= $pin_expire; $cnt++) {
            if ($totp_pin === $this->get_totp_pin($totp_key, $cnt)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    function totp_check ($user_id, $totp_pin) {
        if ($this->config["use_sqlite"]) {
            $sql_r1 = $this->db_obj->query("SELECT `totp_key` FROM `wakarana_users` WHERE `user_id`='".$this->escape_id($user_id)."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->lastErrorMsg());
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetchArray(SQLITE3_ASSOC);
        } else {
            $sql_r1 = $this->db_obj->query("SELECT `totp_key` FROM `wakarana_users` WHERE `user_id`='".$this->escape_id($user_id)."'");
            if ($sql_r1 === FALSE) {
                $this->print_error("SQL文の実行に失敗しました。".$this->db_obj->error);
                return FALSE;
            }
            
            $q_kekka = $sql_r1->fetch_assoc();
        }
        
        return $this->totp_compare($q_kekka["totp_key"], $totp_pin);
    }
    
    
    function bin_to_int ($bin, $start, $length) {
        if ($length > PHP_INT_SIZE * 8 - 1) {
            return FALSE;
        }
        
        if (PHP_INT_SIZE >= 8) {
            $format = "J";
        } else {
            $format = "N";
        }
        
        $end = $start + $length;
        
        $byte_start = floor($start / 8);
        
        $bin_int = unpack($format, str_pad(substr($bin, $byte_start, ceil($end / 8) - $byte_start), PHP_INT_SIZE, "\0", STR_PAD_LEFT));
        
        if ($end % 8 !== 0) {
            return $bin_int[1] >> (8 - $end % 8) & (2**$length - 1);
        } else {
            return $bin_int[1] & (2**$length - 1);
        }
    }
    
    
    function int_to_bin ($int, $digits_start) {
        if ($digits_start < 8) {
            $int = $int << (8 - $digits_start);
            $digits_start = 8;
        } elseif ($digits_start > 8) {
            $int = $int >> ($digits_start - 8);
        }
        
        return chr($int & 0xFF);
    }
    
    
    function create_totp_key () {
        $key_bin = random_bytes(10);
        
        $totp_key = "";
        for ($cnt = 0; $cnt < 16; $cnt++) {
            $totp_key .= WAKARANA_BASE32_TABLE[$this->bin_to_int($key_bin, $cnt * 5, 5)];
        }
        
        return $totp_key;
    }
    
    
    function base32_decode ($base32_str) {
        $len = strlen($base32_str);
        
        $bin = "";
        $bin_buf = 0;
        $buf_head = 0;
        for ($cnt = 0; $cnt < $len; $cnt++) {
            $index = array_search(substr($base32_str, $cnt, 1), WAKARANA_BASE32_TABLE);
            if ($index === FALSE) {
                break;
            }
            
            $bin_buf = $bin_buf << 5 | $index;
            $buf_head += 5;
            
            if ($buf_head >= 8) {
                $bin .= $this->int_to_bin($bin_buf, $buf_head);
                $buf_head -= 8;
            }
        }
        
        if ($buf_head >= 1) {
            $bin .= $this->int_to_bin($bin_buf, $buf_head);
        }
        
        return $bin;
    }
    
    
    function get_totp_pin ($key_base32, $past_30s = 0) {
        $mac = hash_hmac("sha1", pack("J", floor(time() / 30) - $past_30s), $this->base32_decode($key_base32), TRUE);
        
        $bin_code = unpack("N", $mac, $this->bin_to_int($mac, 156, 4));
        
        return str_pad((strval($bin_code[1] & 0x7FFFFFFF) % 1000000), 6, "0", STR_PAD_LEFT);
    }
    
    
    function generate_random_password () {
        return strtolower($this->create_totp_key());
    }
}
