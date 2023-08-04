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
    
    
    static function check_id_string ($id, $length = 60) {
        if (gettype($id) === "string" && strlen($id) >= 1 && strlen($id) <= $length && preg_match("/^[0-9A-Za-z_]+$/u", $id)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    static function hash_password ($user_id, $password) {
        return hash("sha512", $password.hash("sha512", $user_id));
    }
    
    
    static function check_password_strength ($password, $min_length = 10) {
        if (strlen($password) >= $min_length && preg_match("/[A-Z]/u", $password) && preg_match("/[a-z]/u", $password) && preg_match("/[0-9]/u", $password)) {
            return TRUE;
        } else {
            return FALSE;
        }
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
        
        if (!$this->config["allow_weak_password"] && !self::check_password_strength($password)) {
            $this->print_error("パスワードの強度が不十分です。現在の設定では弱いパスワードの使用は許可されていません。");
            return FALSE;
        }
        
        if (!$this->config["allow_duplicate_email_address"] && (empty($email_address) || !empty($this->search_users_with_email_address($email_address)))) {
            $this->print_error("使用できないメールアドレスです。現在の設定では同一メールアドレスでの復数アカウント作成は許可されていません。");
            return FALSE;
        }
        
        $password_hash = self::hash_password($user_id, $password);
        $date_time = date("Y-m-d H:i:s");
        
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
            $stmt = $this->db_obj->query('SELECT DISTINCT "role_name" FROM "wakarana_permission_values" WHERE "role_name" != \''.WAKARANA_BASE_ROLE.'\' ORDER BY "role_name" ASC');
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
        
        if ($role_name !== WAKARANA_ADMIN_ROLE) {
            $role_name = strtolower($role_name);
        }
        
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
        
        if ($role_name !== WAKARANA_ADMIN_ROLE) {
            $role_name = strtolower($role_name);
        }
        
        try {
            $stmt = $this->db_obj->query('SELECT "permission_name", "permission_value" FROM "wakarana_permission_values" WHERE "role_name" = \''.$role_name.'\' ORDER BY "permission_name" ASC');
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
        
        if ($role_name !== WAKARANA_ADMIN_ROLE && $role_name !== WAKARANA_BASE_ROLE) {
            $role_name = strtolower($role_name);
        }
        
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
            
            if ($role_name !== WAKARANA_ADMIN_ROLE && $role_name !== WAKARANA_BASE_ROLE) {
                $role_name = strtolower($role_name);
            }
            
            $role_name_q = '"role_name" = \''.$role_name.'\'';
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
    
    
    static function create_random_password ($length = 14) {
        $password = substr(strtr(base64_encode(random_bytes(ceil($length * 0.75))), "+/", "-."), 0, $length);
        
        if ($length >= 3 && !self::check_password_strength($password, $length)) {
            $random_array = range(0, $length - 1);
            shuffle($random_array);
            
            $alphabets = range("A","Z");
            
            $password = substr($password, 0, $random_array[0]).$alphabets[mt_rand(0, 25)].substr($password, $random_array[0] + 1);
            $password = substr($password, 0, $random_array[1]).strtolower($alphabets[mt_rand(0, 25)]).substr($password, $random_array[1] + 1);
            $password = substr($password, 0, $random_array[2]).mt_rand(0, 9).substr($password, $random_array[2] + 1);
        }
        
        return $password;
    }
    
    
    static function create_token () {
        return rtrim(strtr(base64_encode(random_bytes(32)), "+/", "-_"), "=");
    }
    
    
    function delete_all_tokens () {
        //あとで実装
    }
    
    
    function get_client_ip_address () {
        if ($this->config["proxy_count"] >= 1) {
            if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $x_forwarded_for = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
                $proxy_cnt = count($x_forwarded_for);
            } else {
                $proxy_cnt = 0;
            }
            
            if ($proxy_cnt >= $this->config["proxy_count"]) {
                $remote_addr = trim($x_forwarded_for[$proxy_cnt - $this->config["proxy_count"]]);
            } else {
                $this->print_error("設定ファイルで指定されたプロキシ数が検出されたプロキシ数未満です。");
                return "0.0.0.0";
            }
        } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $remote_addr = $_SERVER["REMOTE_ADDR"];
        } else {
            $this->print_error("クライアント端末のIPアドレスが取得できません。");
            return "0.0.0.0";
        }
        
        if (preg_match("/^(((1[0-9]{2}|2([0-4][0-9]|5[0-5])|[1-9]?[0-9])\.){3}(1[0-9]{2}|2([0-4][0-9]|5[0-5])|[1-9]?[0-9])|([0-9a-f]{0,4}:){2,7}[0-9a-f]{0,4})$/u", $remote_addr) && !preg_match("/(::.*::|:::)/u", $remote_addr)) {
            return $remote_addr;
        } else {
            $this->print_error("クライアント端末のIPアドレスが異常です。");
            return "0.0.0.0";
        }
    }
    
    
    static function get_client_environment () {
        $os_names = array("Android", "iPhone", "iPad", "Windows", "Macintosh", "CrOS", "Linux", "BSD", "Nintendo", "PlayStation", "Xbox");
        $browser_names = array("Firefox", "Edg", "OPR", "Sleipnir", "Chrome", "Safari", "Trident");
        
        $environment = array("operating_system" => NULL, "browser_name" => NULL);
        
        foreach ($os_names as $os_name) {
            if (strpos($_SERVER["HTTP_USER_AGENT"], $os_name) !== FALSE){
                $environment["operating_system"] = $os_name;
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
    
    
    function get_client_attempt_logs ($ip_address) {
        try {
            $stmt = $this->db_obj->query('SELECT "user_id", "succeeded", "attempt_datetime" FROM "wakarana_attempt_logs" WHERE "ip_address" = \''.$ip_address.'\' ORDER BY "attempt_datetime" DESC');
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function check_client_attempt_interval ($ip_address) {
        try {
            $stmt = $this->db_obj->query('SELECT COUNT("ip_address") FROM "wakarana_attempt_logs" WHERE "ip_address" = \''.$ip_address.'\' AND "attempt_datetime" >= \''.date("Y-m-d H:i:s", time() - $this->config["min_attempt_interval"]).'\'');
            
            if ($stmt->fetch(PDO::FETCH_COLUMN) >= 1) {
                return FALSE;
            } else {
                return TRUE;
            }
        } catch (PDOException $err) {
            $this->print_error("認証試行間隔の確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function delete_attempt_logs ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->config["min_attempt_interval"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_attempt_logs" WHERE "attempt_datetime" <= \''.(new DateTime())->modify("-".$expire." second")->format("Y-m-d H:i:s.u").'\'');
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function authenticate ($user_id, $password, $totp_pin = NULL) {
        $user = $this->get_user($user_id);
        
        if (empty($user)) {
            return FALSE;
        }
        
        if ($user->get_status() === WAKARANA_STATUS_NORMAL && $user->check_password($password) && $this->check_client_attempt_interval($this->get_client_ip_address()) && $user->check_attempt_interval()) {
            if ($user->get_totp_enabled()) {
                if (!is_null($totp_pin)) {
                    if ($user->totp_check($totp_pin)) {
                        $user->add_attempt_log(TRUE);
                        return $user;
                    }
                } else {
                    $user->add_attempt_log(TRUE);
                    return $user->create_totp_temporary_token();
                }
            } else {
                $user->add_attempt_log(TRUE);
                return $user;
            }
        }
        
        $user->add_attempt_log(FALSE);
        return FALSE;
    }
    
    
    function login ($user_id, $password, $totp_pin = NULL) {
        $user = $this->authenticate($user_id, $password, $totp_pin);
        
        if (is_object($user)) {
            $user->set_login_token();
        }
        
        return $user;
    }
    
    
    function delete_login_tokens ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->config["login_token_expire"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_login_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("ログイントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function search_users_with_email_address ($email_address) {
        try {
            $stmt = $this->db_obj->prepare('SELECT * FROM "wakarana_users" WHERE "email_address"=:email_address');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレスの確認に失敗しました。".$err->getMessage());
            return -1;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = new wakarana_user($this, $user_info);
        }
        
        return $users;
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
        $length = strlen($base32_str);
        
        $bin = "";
        $bin_buf = 0;
        $buf_head = 0;
        for ($cnt = 0; $cnt < $length; $cnt++) {
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
    
    
    function set_password ($password) {
        if (!$this->wakarana->config["allow_weak_password"] && !wakarana::check_password_strength($password)) {
            $this->wakarana->print_error("パスワードの強度が不十分です。現在の設定では弱いパスワードの使用は許可されていません。");
            return FALSE;
        }
        
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
        if (!$this->wakarana->config["allow_duplicate_email_address"] && $email_address !== $this->user_info["email_address"] && (empty($email_address) || !empty($this->wakarana->search_users_with_email_address($email_address)))) {
            $this->wakarana->print_error("使用できないメールアドレスです。現在の設定では同一メールアドレスでの復数アカウント作成は許可されていません。");
            return FALSE;
        }
        
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
        
        if ($status !== WAKARANA_STATUS_NORMAL) {
            $this->delete_login_tokens();
        }
        
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
        
        if ($role_name !== WAKARANA_ADMIN_ROLE) {
            $role_name = strtolower($role_name);
        }
        
        try {
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_user_roles"("user_id", "role_name") VALUES (\''.$this->user_info["user_id"].'\', \''.$role_name.'\')');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ロールの付与に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_role ($role_name = NULL) {
        if (!empty($role_name)) {
            if ($role_name === WAKARANA_BASE_ROLE) {
                $this->wakarana->print_error("ベースロールを剥奪することはできません。");
                return FALSE;
            }
        
            if (!wakarana::check_id_string($role_name)) {
                return FALSE;
            }
        
            if ($role_name !== WAKARANA_ADMIN_ROLE) {
                $role_name = strtolower($role_name);
            }
            
            $role_name_q = ' AND "role_name" = \''.$role_name.'\'';
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
    
    
    function check_permission ($permission_name) {
        if (!wakarana::check_id_string($permission_name)) {
            return FALSE;
        }
        
        try {
            $stmt_1 = $this->wakarana->db_obj->query('SELECT MAX("wakarana_permission_values"."permission_value") FROM "wakarana_user_roles", "wakarana_permission_values" WHERE (("wakarana_user_roles"."user_id"=\''.$this->user_info["user_id"].'\' AND "wakarana_user_roles"."role_name" = "wakarana_permission_values"."role_name") OR "wakarana_permission_values"."role_name" = \''.WAKARANA_BASE_ROLE.'\') AND "permission_name" = \''.strtolower($permission_name).'\'');
            $permission_value = $stmt_1->fetch(PDO::FETCH_COLUMN);
            
            if (!empty($permission_value)) {
                return $permission_value;
            } else {
                $stmt_2 = $this->wakarana->db_obj->query('SELECT COUNT("role_name") FROM "wakarana_user_roles" WHERE "user_id"=\''.$this->user_info["user_id"].'\' AND "role_name" = \''.WAKARANA_ADMIN_ROLE.'\'');
                if ($stmt_2->fetch(PDO::FETCH_COLUMN)) {
                    return -1;
                } else {
                    return 0;
                }
            }
        } catch (PDOException $err) {
            $this->wakarana->print_error("権限値の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function delete_all_tokens () {
        //あとで実装
    }
    
    
    function get_attempt_logs () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "succeeded", "attempt_datetime", "ip_address" FROM "wakarana_attempt_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "attempt_datetime" DESC');
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行ログの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }


    function check_attempt_interval() {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT COUNT("user_id") FROM "wakarana_attempt_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "attempt_datetime" >= \''.date("Y-m-d H:i:s", time() - $this->wakarana->config["min_attempt_interval"]).'\'');
            
            if ($stmt->fetch(PDO::FETCH_COLUMN) >= 1) {
                return FALSE;
            } else {
                return TRUE;
            }
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行間隔の確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function add_attempt_log ($succeeded) {
        if ($succeeded) {
            $succeeded_q = "TRUE";
        } else {
            $succeeded_q = "FALSE";
        }
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_attempt_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "attempt_datetime" NOT IN (SELECT "attempt_datetime" FROM "wakarana_attempt_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "attempt_datetime" DESC LIMIT '.($this->wakarana->config["attempt_logs_per_user"] - 1).')');
            
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_attempt_logs"("user_id", "succeeded", "attempt_datetime", "ip_address") VALUES (\''.$this->user_info["user_id"].'\', '.$succeeded_q.', \''.(new DateTime())->format("Y-m-d H:i:s.u").'\', \''.$this->wakarana->get_client_ip_address().'\')');
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行ログの追加に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_attempt_logs () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_attempt_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行ログの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function update_last_access ($token = NULL) {
        $last_access = date("Y-m-d H:i:s");
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "last_access"=\''.$last_access.'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーの最終アクセス日時の更新に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["last_access"] = $last_access;
        
        if (!empty($token)) {
            try {
                $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_login_tokens" SET "last_access"=\''.$last_access.'\'  WHERE "token" = :token');
                
                $stmt->bindValue(":token", $token, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->wakarana->print_error("ログイントークンの最終アクセス日時の更新に失敗しました。".$err->getMessage());
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function create_login_token () {
        $this->wakarana->delete_login_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        $client_env = wakarana::get_client_environment();
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_login_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "token" NOT IN (SELECT "token" FROM "wakarana_login_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "token_created" DESC LIMIT '.($this->wakarana->config["login_tokens_per_user"] - 1).')');
            
            $stmt = $this->wakarana->db_obj->prepare('INSERT INTO "wakarana_login_tokens"("token", "user_id", "token_created", "ip_address", "operating_system", "browser_name", "last_access") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.$token_created.'\', \''.$this->wakarana->get_client_ip_address().'\', :operating_system, :browser_name, \''.$token_created.'\')');
            
            if (!empty($client_env["operating_system"])) {
                $stmt->bindValue(":operating_system", $client_env["operating_system"], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":operating_system", NULL, PDO::PARAM_NULL);
            }
            
            if (!empty($client_env["browser_name"])) {
                $stmt->bindValue(":browser_name", $client_env["browser_name"], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":browser_name", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("ログイントークンの保存に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->update_last_access();
        
        return $token;
    }
    
    
    function set_login_token () {
        $token = $this->create_login_token();
        
        if (!empty($token) && setcookie($this->wakarana->config["login_token_cookie_name"], $token, time() + $this->wakarana->config["login_token_expire"], "/", $this->wakarana->config["cookie_domain"], FALSE, TRUE)) {
            return TRUE;
        } else {
            $this->wakarana->print_error("ログイントークンの送信に失敗しました。");
            return FALSE;
        }
    }
    
    
    function delete_login_tokens () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_login_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーのログイントークン削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
}
