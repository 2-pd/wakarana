<?php
/*Wakarana main.php*/
require_once(dirname(__FILE__)."/common.php");

define("WAKARANA_STATUS_DISABLE", 0);
define("WAKARANA_STATUS_NORMAL", 1);
define("WAKARANA_STATUS_EMAIL_ADDRESS_UNVERIFIED", 3);

define("WAKARANA_ORDER_USER_ID", "user_id");
define("WAKARANA_ORDER_USER_NAME", "user_name");
define("WAKARANA_ORDER_USER_CREATED", "user_created");

define("WAKARANA_BASE_ROLE", "__BASE__");
define("WAKARANA_ADMIN_ROLE", "__ADMIN__");

define("WAKARANA_BASE32_TABLE", array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "2", "3", "4", "5", "6", "7"));


class wakarana extends wakarana_common {
    function __construct ($base_dir = NULL) {
        parent::__construct($base_dir);
        $this->connect_db();
    }
    
    
    static function check_id_string ($id, $len = 60) {
        if (gettype($id) === "string" && strlen($id) <= $len && preg_match("/^[0-9A-Za-z_]+$/u", $id)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    static function hash_password ($user_id, $password) {
        return hash("sha512", $password.hash("sha512", $user_id));
    }
    
    
    function get_user ($user_id) {
        if (!self::check_id_string($user_id)) {
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $stmt = $this->db_obj->query("SELECT * FROM `wakarana_users` WHERE `user_id`='".$user_id."'");
            } else {
                $stmt = $this->db_obj->query('SELECT * FROM "wakarana_users" WHERE LOWER("user_id")=\''.strtolower($user_id).'\'');
            }
        } catch (PDOException $err) {
            $this->print_error("ユーザー情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($user_info)) {
            return new wakarana_user($this, $user_info);
        } else {
            return FALSE;
        }
    }
    
    
    function get_all_users ($start = 0, $limit = 100, $order_by = WAKARANA_ORDER_USER_CREATED, $asc = TRUE) {
        $start = intval($start);
        $limit = intval($limit);
        
        switch ($order_by) {
            case WAKARANA_ORDER_USER_ID:
                if ($this->config["use_sqlite"]) {
                    $order_by_q = "`user_id`";
                } else {
                    $order_by_q = 'LOWER("user_id")';
                }
                break;
                
            case WAKARANA_ORDER_USER_NAME:
                if ($this->config["use_sqlite"]) {
                    $order_by_q = "`user_name`";
                } else {
                    $order_by_q = 'LOWER("user_name")';
                }
                break;
                
            case WAKARANA_ORDER_USER_CREATED:
                $order_by_q = '"user_created"';
                break;
                
            default:
                $this->print_error("対応していない並び替え基準です。");
                return FALSE;
        }
        
        if ($asc) {
            $asc_q = "ASC";
        } else {
            $asc_q = "DESC";
        }
        
        try {
            $stmt = $this->db_obj->query('SELECT * FROM "wakarana_users" ORDER BY '.$order_by_q.' '.$asc_q.' LIMIT '.$limit.' OFFSET '.$start);
        } catch (PDOException $err) {
            $this->print_error("ユーザー一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = new wakarana_user($this, $user_info);
        }
        
        return $users;
    }
    
    
    function add_user ($user_id, $password, $user_name = "", $email_address = NULL, $status = WAKARANA_STATUS_NORMAL) {
        if (!self::check_id_string($user_id)) {
            $this->print_error("ユーザーIDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $password_hash = self::hash_password($user_id, $password);
        $date_time = date("Y-m-d H:i:s");
        
        if (empty($user_id)) {
            $this->print_error("無効なユーザーIDです。");
            return FALSE;
        }
        
        if (!$this->config["allow_duplicate_email_address"] && !empty($this->email_address_exists($email_address))) {
            $this->print_error("既に使用されているメールアドレスです。現在の設定では同一メールアドレスでの復数アカウント作成は許可されていません。");
            return FALSE;
        }
        
        try {
            $stmt = $this->db_obj->prepare('INSERT INTO "wakarana_users"("user_id", "password", "user_name", "email_address", "user_created", "last_updated", "last_access", "status", "totp_key") VALUES (\''.$user_id.'\', \''.$password_hash.'\', :user_name, :email_address, \''.$date_time.'\', \''.$date_time.'\', \''.$date_time.'\', '.intval($status).', NULL)');
            
            if (!empty($user_name)) {
                $stmt->bindValue(":user_name", mb_substr($user_name, 0, 240), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":user_name", NULL, PDO::PARAM_NULL);
            }
            
            if (!empty($email_address)) {
                $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":email_address", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ユーザーの作成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $this->get_user($user_id);
    }
    
    
    function get_roles () {
        try {
            $stmt = $this->db_obj->query('SELECT DISTINCT "role_name" FROM "wakarana_permission_values" ORDER BY "role_name" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロールの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    
    function delete_role ($role_name) {
        if ($role_name === WAKARANA_BASE_ROLE) {
            $this->print_error("ベースロールを削除することはできません。");
            return FALSE;
        }
        
        if (!self::check_id_string($role_name)) {
            return FALSE;
        }
        
        $role_name = strtolower($role_name);
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_user_roles" WHERE "role_name" = \''.$role_name.'\'');
        } catch (PDOException $err) {
            $this->print_error("ロールの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $this->remove_permission_value($role_name);
    }
    
    
    function get_permission_values ($role_name) {
        if (!self::check_id_string($role_name)) {
            return FALSE;
        }
        
        try {
            $stmt = $this->db_obj->query('SELECT "permission_name", "permission_value" FROM "wakarana_permission_values" WHERE "role_name" = \''.strtolower($role_name).'\' ORDER BY "permission_name" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限値の一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    
    function set_permission_value ($role_name, $permission_name, $permission_value = TRUE) {
        if (!self::check_id_string($role_name) || !self::check_id_string($permission_name, 120)) {
            $this->print_error("識別名にに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $role_name = strtolower($role_name);
        $permission_name = strtolower($permission_name);
        $permission_value = intval($permission_value);
        
        try {
            $this->db_obj->exec('INSERT INTO "wakarana_permission_values"("role_name", "permission_name", "permission_value") VALUES (\''.$role_name.'\', \''.$permission_name.'\','.$permission_value.') ON CONFLICT ("role_name", "permission_name") DO UPDATE SET "role_name" = \''.$role_name.'\', "permission_name" = \''.$permission_name.'\', "permission_value" = '.$permission_value.'');
        } catch (PDOException $err) {
            $this->print_error("権限値の設定に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_permission_value ($role_name = NULL, $permission_name = NULL) {
        if (!empty($role_name)) {
            if (!self::check_id_string($role_name)) {
                return FALSE;
            }
            
            $role_name_q = '"role_name" = \''.strtolower($role_name).'\'';
        } else {
            $role_name_q = '';
        }
        
        if (!empty($permission_name)) {
            if (!self::check_id_string($permission_name, 120)) {
                return FALSE;
            }
            
            $permission_name_q = '"permission_name" = \''.strtolower($permission_name, 120).'\'';
        } else {
            $permission_name_q = '';
        }
        
        if (!empty($role_name) && !empty($permission_name)) {
            $q = ' WHERE '.$role_name_q.' AND '.$permission_name_q;
        } elseif (empty($role_name) && empty($permission_name)) {
            $q = '';
        } else {
            $q = ' WHERE '.$role_name_q.$permission_name_q;
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_permission_values"'.$q);
        } catch (PDOException $err) {
            $this->print_error("権限の削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    static function create_token () {
        return rtrim(strtr(base64_encode(random_bytes(32)), "+/", "-_"), "=");
    }
    
    
    
    
    
    static function get_client_environment () {
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
            if ($totp_pin === self::get_totp_pin($totp_key, $cnt)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    protected static function bin_to_int ($bin, $start, $length) {
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
    
    
    protected static function int_to_bin ($int, $digits_start) {
        if ($digits_start < 8) {
            $int = $int << (8 - $digits_start);
        } elseif ($digits_start > 8) {
            $int = $int >> ($digits_start - 8);
        }
        
        return chr($int & 0xFF);
    }
    
    
    static function create_totp_key () {
        $key_bin = random_bytes(10);
        
        $totp_key = "";
        for ($cnt = 0; $cnt < 16; $cnt++) {
            $totp_key .= WAKARANA_BASE32_TABLE[self::bin_to_int($key_bin, $cnt * 5, 5)];
        }
        
        return $totp_key;
    }
    
    
    protected static function base32_decode ($base32_str) {
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
                $bin .= self::int_to_bin($bin_buf, $buf_head);
                $buf_head -= 8;
            }
        }
        
        if ($buf_head >= 1) {
            $bin .= self::int_to_bin($bin_buf, $buf_head);
        }
        
        return $bin;
    }
    
    
    protected static function get_totp_pin ($key_base32, $past_30s = 0) {
        $mac = hash_hmac("sha1", pack("J", floor(time() / 30) - $past_30s), self::base32_decode($key_base32), TRUE);
        
        $bin_code = unpack("N", $mac, self::bin_to_int($mac, 156, 4));
        
        return str_pad((strval($bin_code[1] & 0x7FFFFFFF) % 1000000), 6, "0", STR_PAD_LEFT);
    }
}


class wakarana_user {
    protected $wakarana;
    protected $user_info;
    
    
    function __construct ($wakarana, $user_info) {
        $this->wakarana = $wakarana;
        $this->user_info = $user_info;
    }
    
    
    function get_id () {
        return $this->user_info["user_id"];
    }
    
    
    function get_name () {
        return $this->user_info["user_name"];
    }
    
    
    function check_password ($password) {
        if (wakarana::hash_password($this->user_info["user_id"], $password) === $this->user_info["password"]) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function get_email_address () {
        return $this->user_info["email_address"];
    }
    
    
    function get_created () {
        return $this->user_info["user_created"];
    }
    
    
    function get_last_updated () {
        return $this->user_info["last_updated"];
    }
    
    
    function get_last_access () {
        return $this->user_info["last_access"];
    }
    
    
    function get_status () {
        return $this->user_info["status"];
    }
    
    
    function get_totp_enabled () {
        if (!empty($this->user_info["totp_key"])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function change_password ($password) {
        $password_hash = wakarana::hash_password($this->user_info["user_id"], $password);
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "password"=\''.$password_hash.'\', "last_updated"=\''.date("Y-m-d H:i:s").'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("パスワードの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["password"] = $password_hash;
        
        return TRUE;
    }
    
    
    function set_name ($user_name) {
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_users" SET "user_name"= :user_name, "last_updated"=\''.date("Y-m-d H:i:s").'\' WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            if (!empty($user_name)) {
                $stmt->bindValue(":user_name", mb_substr($user_name, 0, 240), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":user_name", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザー名の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["user_name"] = $user_name;
        
        return TRUE;
    }
    
    
    function set_email_address ($email_address) {
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_users" SET "email_address"= :email_address, "last_updated"=\''.date("Y-m-d H:i:s").'\' WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            if (!empty($email_address)) {
                $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":email_address", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレスの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["email_address"] = $email_address;
        
        return TRUE;
    }
    
    
    function set_status ($status) {
        $status = intval($status);
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "status"=\''.$status.'\', "last_updated"=\''.date("Y-m-d H:i:s").'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーアカウントの状態の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["status"] = $status;
        
        return TRUE;
    }
    
    
    function enable_2_factor_auth ($totp_key = NULL) {
        if (empty($totp_key)) {
            $totp_key = wakarana::create_totp_key();
        } elseif (preg_match("/^[A-Z2-7]{16}$/", $totp_key) !== 1) {
            $this->wakarana->print_error("TOTP生成鍵が不正です。");
            return FALSE;
        }
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_users" SET "totp_key" = :totp_key WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt->bindValue(":totp_key", $totp_key, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("2要素認証の有効化に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $totp_key;
    }
    
    
    function disable_2_factor_auth () {
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "totp_key" = NULL WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("2要素認証の無効化に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function get_roles () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "role_name" FROM "wakarana_user_roles" WHERE "user_id"=\''.$this->user_info["user_id"].'\' ORDER BY "role_name" ASC');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ロールの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    
    function add_role ($role_name) {
        if ($role_name === WAKARANA_BASE_ROLE) {
            $this->wakarana->print_error("ベースロールは追加する必要がありません。");
            return FALSE;
        }
        
        if (!wakarana::check_id_string($role_name)) {
            $this->wakarana->print_error("ロール名にに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        try {
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_user_roles"("user_id", "role_name") VALUES (\''.$this->user_info["user_id"].'\', \''.strtolower($role_name).'\')');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ロールの付与に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_role($role_name = NULL) {
        if (!empty($role_name)) {
            if ($role_name === WAKARANA_BASE_ROLE) {
                $this->wakarana->print_error("ベースロールを剥奪することはできません。");
                return FALSE;
            }
        
            if (!wakarana::check_id_string($role_name)) {
                return FALSE;
            }
            
            $role_name_q = ' AND "role_name" = \''.strtolower($role_name).'\'';
        } else {
            $role_name_q = '';
        }
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_user_roles" WHERE "user_id" = \''.$this->user_info["user_id"].'\''.$role_name_q);
        } catch (PDOException $err) {
            $this->wakarana->print_error("ロールの剥奪に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
}
