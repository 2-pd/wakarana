<?php
/*Wakarana main.php*/
require_once(dirname(__FILE__)."/common.php");

define("WAKARANA_STATUS_DISABLE", 0);
define("WAKARANA_STATUS_NORMAL", 1);
define("WAKARANA_STATUS_UNAPPROVED", -1);

define("WAKARANA_ORDER_USER_ID", "user_id");
define("WAKARANA_ORDER_USER_NAME", "user_name");
define("WAKARANA_ORDER_USER_CREATED", "user_created");

define("WAKARANA_BASE_ROLE", "__BASE__");
define("WAKARANA_ADMIN_ROLE", "__ADMIN__");

define("WAKARANA_BASE32_TABLE", array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "2", "3", "4", "5", "6", "7"));


class wakarana extends wakarana_common {
    public $user_ids = array();
    public $role_ids = array();
    public $permitted_value_ids = array();
    
    
    function __construct ($base_dir = NULL) {
        parent::__construct($base_dir);
        $this->connect_db();
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
    
    
    protected function new_wakarana_user ($user_info) {
        if (!isset($this->user_ids[$user_info["user_id"]])) {
            $this->user_ids[$user_info["user_id"]] = new wakarana_user($this, $user_info);
        }
        
        return $this->user_ids[$user_info["user_id"]];
    }
    
    
    function get_user ($user_id) {
        if (!self::check_id_string($user_id)) {
            return FALSE;
        }
        
        try {
            if ($this->config["use_sqlite"]) {
                $stmt = $this->db_obj->query("SELECT * FROM `wakarana_users` WHERE `user_id` = '".$user_id."'");
            } else {
                $stmt = $this->db_obj->query('SELECT * FROM "wakarana_users" WHERE LOWER("user_id") = \''.strtolower($user_id).'\'');
            }
        } catch (PDOException $err) {
            $this->print_error("ユーザー情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($user_info)) {
            return $this->new_wakarana_user($user_info);
        } else {
            return FALSE;
        }
    }
    
    
    function count_user() {
        try {
            $stmt = $this->db_obj->query('SELECT COUNT(*) FROM "wakarana_users"');
        } catch (PDOException $err) {
            $this->print_error("ユーザー数の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchColumn();
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
            $users[] = $this->new_wakarana_user($user_info);
        }
        
        return $users;
    }
    
    
    function add_user ($user_id, $password, $user_name = "", $status = WAKARANA_STATUS_NORMAL) {
        if (!self::check_id_string($user_id)) {
            $this->print_error("ユーザーIDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        if (!$this->config["allow_weak_password"] && !self::check_password_strength($password)) {
            $this->print_error("パスワードの強度が不十分です。現在の設定では弱いパスワードの使用は許可されていません。");
            return FALSE;
        }
        
        $password_hash = self::hash_password($user_id, $password);
        $date_time = date("Y-m-d H:i:s");
        
        try {
            $stmt = $this->db_obj->prepare('INSERT INTO "wakarana_users"("user_id", "password", "user_name", "user_created", "last_updated", "last_access", "status", "totp_key") VALUES (\''.$user_id.'\', \''.$password_hash.'\', :user_name, \''.$date_time.'\', \''.$date_time.'\', \''.$date_time.'\', '.intval($status).', NULL)');
            
            if (!empty($user_name)) {
                $stmt->bindValue(":user_name", mb_substr($user_name, 0, 240), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":user_name", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ユーザーの作成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $this->get_user($user_id);
    }
    
    
    protected function new_wakarana_role ($role_info) {
        if (!isset($this->role_ids[$role_info["role_id"]])) {
            $this->role_ids[$role_info["role_id"]] = new wakarana_role($this, $role_info);
        }
        
        return $this->role_ids[$role_info["role_id"]];
    }
    
    
    function get_role ($role_id) {
        if (!self::check_id_string($role_id)) {
            return FALSE;
        }
        
        $role_id = strtolower($role_id);
        
        try {
            $stmt = $this->db_obj->query('SELECT * FROM "wakarana_roles" WHERE "role_id" = \''.$role_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("ロール情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $role_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($role_info)) {
            return $this->new_wakarana_role($role_info);
        } else {
            return FALSE;
        }
    }
    
    
    function get_all_roles () {
        try {
            $stmt = $this->db_obj->query('SELECT * FROM "wakarana_roles" ORDER BY "role_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロール一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $roles_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $roles = array();
        foreach ($roles_info as $role_info) {
            $roles[] = $this->new_wakarana_role($role_info);
        }
        
        return $roles;
    }
    
    
    function add_role ($role_id, $role_name, $role_description = "") {
        if (!self::check_id_string($role_id)) {
            $this->print_error("ロールIDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $role_id = strtolower($role_id);
        
        try {
            $stmt = $this->db_obj->prepare('INSERT INTO "wakarana_roles"("role_id", "role_name", "role_description") VALUES (\''.$role_id.'\', :role_name, :role_description)');
            
            $stmt->bindValue(":role_name", mb_substr($role_name, 0, 120), PDO::PARAM_STR);
            $stmt->bindValue(":role_description", $role_description, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ロールの作成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $this->get_role($role_id);
    }
    
    
    static function check_resource_id_string ($resource_id) {
        if (gettype($resource_id) === "string" && strlen($resource_id) <= 120 && preg_match("/\A[0-9A-Za-z_]+(\/[0-9A-Za-z_]+)*\z/u", $resource_id)) {
            return TRUE;
        } else {
            return FALSE;
        }
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
    
    
    protected function new_wakarana_permitted_value ($permitted_value_info) {
        if (!isset($this->permitted_value_ids[$permitted_value_info["permitted_value_id"]])) {
            $this->permitted_value_ids[$permitted_value_info["permitted_value_id"]] = new wakarana_permitted_value($this, $permitted_value_info);
        }
        
        return $this->permitted_value_ids[$permitted_value_info["permitted_value_id"]];
    }
    
    
    function get_permitted_value ($permitted_value_id) {
        if (!self::check_id_string($permitted_value_id)) {
            return FALSE;
        }
        
        $permitted_value_id = strtolower($permitted_value_id);
        
        try {
            $stmt = $this->db_obj->query('SELECT * FROM "wakarana_permitted_values" WHERE "permitted_value_id" = \''.$permitted_value_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("権限値情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permitted_value_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($permitted_value_info)) {
            return $this->new_wakarana_permitted_value($permitted_value_info);
        } else {
            return FALSE;
        }
    }
    
    
    function get_all_permitted_values () {
        try {
            $stmt = $this->db_obj->query('SELECT * FROM "wakarana_permitted_values" ORDER BY "permitted_value_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限値情報一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permitted_values_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $permitted_values = array();
        foreach ($permitted_values_info as $permitted_value_info) {
            $permitted_values[] = $this->new_wakarana_permitted_value($permitted_value_info);
        }
        
        return $permitted_values;
    }
    
    
    function add_permitted_value ($permitted_value_id, $permitted_value_name, $permitted_value_description = "") {
        if (!self::check_id_string($permitted_value_id)) {
            $this->print_error("権限値変数IDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $permitted_value_id = strtolower($permitted_value_id);
        
        try {
            $stmt = $this->db_obj->prepare('INSERT INTO "wakarana_permitted_values"("permitted_value_id", "permitted_value_name", "permitted_value_description") VALUES (\''.$permitted_value_id.'\', :permitted_value_name, :permitted_value_description)');
            
            $stmt->bindValue(":permitted_value_name", mb_substr($permitted_value_name, 0, 120), PDO::PARAM_STR);
            $stmt->bindValue(":permitted_value_description", $permitted_value_description, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("権限値変数の作成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $this->get_permitted_value($permitted_value_id);
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
        if($this->delete_login_tokens(0) && $this->delete_one_time_tokens(0) && $this->delete_email_address_verification_codes(0) && $this->delete_invite_code() && $this->delete_password_reset_tokens(0) && $this->delete_2sv_tokens(0)){
            return TRUE;
        } else {
            return FALSE;
        }
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
        
        if (filter_var($remote_addr, FILTER_VALIDATE_IP) !== FALSE) {
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
    
    
    function get_client_auth_logs ($ip_address) {
        try {
            $stmt = $this->db_obj->query('SELECT "user_id", "succeeded", "authenticate_datetime" FROM "wakarana_authenticate_logs" WHERE "ip_address" = \''.$ip_address.'\' ORDER BY "authenticate_datetime" DESC');
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function check_client_auth_interval ($ip_address, $unsucceeded_only = FALSE) {
        if ($unsucceeded_only) {
            $succeeded_q = ' AND "succeeded" = FALSE';
        } else {
            $succeeded_q = '';
        }
        
        try {
            $stmt = $this->db_obj->query('SELECT COUNT("ip_address") FROM "wakarana_authenticate_logs" WHERE "ip_address" = \''.$ip_address.'\' AND "authenticate_datetime" >= \''.date("Y-m-d H:i:s", time() - $this->config["minimum_authenticate_interval"]).'\''.$succeeded_q);
            
            if ($stmt->fetchColumn() >= 1) {
                return FALSE;
            } else {
                return TRUE;
            }
        } catch (PDOException $err) {
            $this->print_error("認証試行間隔の確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function delete_auth_logs ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->config["minimum_authenticate_interval"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_authenticate_logs" WHERE "authenticate_datetime" <= \''.(new DateTime())->modify("-".$expire." second")->format("Y-m-d H:i:s.u").'\'');
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
        
        $result = $user->authenticate($password, $totp_pin);
        
        if ($result === TRUE) {
            return $user;
        } else {
            return $result;
        }
    }
    
    
    function login ($user_id, $password, $totp_pin = NULL) {
        $user = $this->authenticate($user_id, $password, $totp_pin);
        
        if (is_object($user)) {
            $user->set_login_token();
        }
        
        return $user;
    }
    
    
    function authenticate_with_email_address ($email_address, $password, $totp_pin = NULL) {
        if ($this->config["allow_nonunique_email_address"]) {
            $this->print_error("同一メールアドレスの複数アカウントへの登録を容認する設定では、メールアドレスでのログインは利用できません。");
            return FALSE;
        }
        
        $users = $this->search_users_with_email_address($email_address);
        
        if (empty($users)) {
            return FALSE;
        }
        
        $result = $users[0]->authenticate($password, $totp_pin);
        
        if ($result === TRUE) {
            return $users[0];
        } else {
            return $result;
        }
    }
    
    
    function login_with_email_address ($email_address, $password, $totp_pin = NULL) {
        $user = $this->authenticate_with_email_address($email_address, $password, $totp_pin);
        
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
            $stmt = $this->db_obj->prepare('SELECT "wakarana_users".* FROM "wakarana_users", "wakarana_user_email_addresses" WHERE "wakarana_user_email_addresses"."email_address" = :email_address AND "wakarana_users"."user_id" = "wakarana_user_email_addresses"."user_id"');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレスの確認に失敗しました。".$err->getMessage());
            return -1;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = $this->new_wakarana_user($user_info);
        }
        
        return $users;
    }
    
    
    function check_email_address ($email_address) {
        if (preg_match("/\A[A-Za-z0-9!#$%&'\*+\/=?^_`\{\|\}~\.\-]+@[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)+\z/u", $email_address)) {
            return $this->check_email_domain(substr($email_address, strpos($email_address, "@") + 1));
        } else {
            return FALSE;
        }
    }
    
    
    function create_email_address_verification_code ($email_address) {
        if (!$this->check_email_address($email_address)) {
            $this->print_error("使用できないメールアドレスです。");
            return FALSE;
        }
        
        if (!$this->config["allow_nonunique_email_address"] && !empty($this->search_users_with_email_address($email_address))) {
            $this->print_error("現在の設定では同一メールアドレスでの復数アカウント作成は許可されていません。");
            return NULL;
        }
        
        $this->delete_email_address_verification_codes();
        
        $verification_code = self::create_random_code(8);
        
        $code_created = date("Y-m-d H:i:s");
        
        try {
            $stmt = $this->db_obj->prepare('INSERT INTO "wakarana_email_address_verification_codes"("user_id", "email_address", "verification_code", "code_created") VALUES (NULL, :email_address, \''.$verification_code.'\', \''.$code_created.'\')');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $verification_code;
    }
    
    
    function email_address_verify ($email_address, $verification_code) {
        if (!$this->check_email_address($email_address)) {
            $this->print_error("使用できないメールアドレスです。");
            return FALSE;
        }
        
        $this->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->db_obj->prepare('SELECT COUNT(*) FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" IS NULL');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if (!empty($data)) {
            try {
                $stmt = $this->db_obj->prepare('DELETE FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code');
                
                $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
                $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みのメールアドレス確認コードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function get_email_address_verification_code_expire ($email_address, $verification_code) {
        $this->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->db_obj->prepare('SELECT "code_created" FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" IS NULL');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの情報取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if ($data !== FALSE) {
            return date("Y-m-d H:i:s", strtotime($data) + $this->config["verification_email_expire"]);
        } else {
            return FALSE;
        }
    }
    
    
    function delete_email_address_verification_codes ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->config["verification_email_expire"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_email_address_verification_codes" WHERE "code_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function check_invite_code ($invite_code) {
        $this->delete_expired_invite_codes();
        
        $invite_code = strtoupper($invite_code);
        
        try {
            $stmt = $this->db_obj->prepare('SELECT COUNT(*) FROM "wakarana_invite_codes" WHERE "invite_code" = :invite_code');
            
            $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コードの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if (!empty($data)) {
            $this->delete_invite_code($invite_code);
            
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function get_invite_codes () {
        $this->delete_expired_invite_codes();
        
        try {
            $stmt = $this->db_obj->query('SELECT * FROM "wakarana_invite_codes" ORDER BY "code_created" ASC');
        } catch (PDOException $err) {
            $this->print_error("招待コード一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    function get_invite_code_info ($invite_code) {
        $this->delete_expired_invite_codes();
        
        $invite_code = strtoupper($invite_code);
        
        try {
            $stmt = $this->db_obj->prepare('SELECT * FROM "wakarana_invite_codes" WHERE "invite_code" = :invite_code');
            
            $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コード情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    
    function delete_invite_code ($invite_code = NULL) {
        if (is_null($invite_code)) {
            try {
                $this->db_obj->exec('DELETE FROM "wakarana_invite_codes"');
            } catch (PDOException $err) {
                $this->print_error("招待コードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
        } else {
            $invite_code = strtoupper($invite_code);
            
            try {
                $stmt = $this->db_obj->prepare('DELETE FROM "wakarana_invite_codes" WHERE "invite_code" = :invite_code');
                
                $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("招待コードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function delete_expired_invite_codes () {
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_invite_codes" WHERE "code_expire" <= \''.date("Y-m-d H:i:s").'\'');
        } catch (PDOException $err) {
            $this->print_error("有効期限切れ招待コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function reset_password ($token, $new_password) {
        $this->delete_password_reset_tokens();
        
        try {
            $stmt = $this->db_obj->prepare('SELECT "user_id" FROM "wakarana_password_reset_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("パスワード再設定用トークンの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user = $this->get_user($stmt->fetchColumn());
        
        if ($user !== FALSE && $user->set_password($new_password)) {
            try {
                $stmt = $this->db_obj->prepare('DELETE FROM "wakarana_password_reset_tokens" WHERE "token" = :token');
                
                $stmt->bindValue(":token", $token, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みのパスワード再設定用トークンの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            return $user;
        } else {
            return FALSE;
        }
    }
    
    
    function get_password_reset_token_expire ($token) {
        $this->delete_password_reset_tokens();
        
        try {
            $stmt = $this->db_obj->prepare('SELECT "token_created" FROM "wakarana_password_reset_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの情報取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if ($data !== FALSE) {
            return date("Y-m-d H:i:s", strtotime($data) + $this->config["password_reset_token_expire"]);
        } else {
            return FALSE;
        }
    }
    
    
    function delete_password_reset_tokens ($expire=-1) {
        if ($expire === -1) {
            $expire = $this->config["password_reset_token_expire"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_password_reset_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("パスワード再設定用トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function search_users_with_custom_field ($custom_field_name, $custom_field_value) {
        if (!self::check_id_string($custom_field_name) || !isset($this->custom_fields[$custom_field_name])) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        try {
            $stmt = $this->db_obj->prepare('SELECT "wakarana_users".* FROM "wakarana_users", "'.$table_name.'" WHERE "'.$table_name.'"."custom_field_name" = \''.$custom_field_name.'\' AND "'.$table_name.'"."custom_field_value" = :custom_field_value AND "wakarana_users"."user_id" = "'.$table_name.'"."user_id"');
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の確認に失敗しました。".$err->getMessage());
            return -1;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = $this->new_wakarana_user($user_info);
        }
        
        return $users;
    }
    
    
    function delete_all_users_values ($custom_field_name) {
        if (!self::check_id_string($custom_field_name) || !isset($this->custom_fields[$custom_field_name])) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "'.$table_name.'" WHERE "custom_field_name" = \''.$custom_field_name.'\'');
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_2sv_tokens ($expire=-1) {
        if ($expire === -1) {
            $expire = $this->config["two_step_verification_token_expire"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_two_step_verification_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("2段階認証用一時トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function totp_authenticate ($tmp_token, $totp_pin) {
        $this->delete_2sv_tokens();
        
        try {
            $stmt = $this->db_obj->prepare('SELECT "user_id" FROM "wakarana_two_step_verification_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $tmp_token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("2段階認証用一時トークンの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user = $this->get_user($stmt->fetchColumn());
        
        if ($user !== FALSE) {
            if ($user->totp_check($totp_pin) && $this->check_client_auth_interval($this->get_client_ip_address(), TRUE) && $user->check_auth_interval(TRUE)) {
                $user->add_auth_log(TRUE);
                
                $status = $user->get_status();
                if ($status !== WAKARANA_STATUS_NORMAL) {
                    return $status;
                }
                
                return $user;
            } else {
                $user->add_auth_log(FALSE);
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    
    
    function totp_login ($tmp_token, $totp_pin) {
        $user = $this->totp_authenticate($tmp_token, $totp_pin);
        
        if (is_object($user)) {
            $user->set_login_token();
        }
        
        return $user;
    }
    
    
    function check ($token = NULL, $update_last_access = TRUE) {
        if (empty($token)) {
            if (isset($_COOKIE[$this->config["login_token_cookie_name"]])) {
                $token = $_COOKIE[$this->config["login_token_cookie_name"]];
            } else {
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->db_obj->prepare('SELECT "user_id" FROM "wakarana_login_tokens" WHERE "token" = :token AND "token_created" > \''.date("Y-m-d H:i:s", time() - $this->config["login_token_expire"]).'\'');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ログイントークンの確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user = $this->get_user($stmt->fetchColumn());
        
        if ($user !== FALSE) {
            if ($update_last_access) {
                $user->update_last_access($token);
            }
            
            return $user;
        } else {
            return FALSE;
        }
    }
    
    
    function delete_login_token ($token) {
        try {
            $stmt = $this->db_obj->prepare('DELETE FROM "wakarana_login_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ログイントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function logout () {
        if (isset($_COOKIE[$this->config["login_token_cookie_name"]])) {
            $token = $_COOKIE[$this->config["login_token_cookie_name"]];
        } else {
            return NULL;
        }
        
        if (setcookie($this->config["login_token_cookie_name"], "", time() - 1800, "/", $this->config["cookie_domain"])) {
            return $this->delete_login_token($token);
        } else {
            $this->print_error("ログイントークンの削除に失敗しました。");
            return FALSE;
        }
    }
    
    
    function delete_one_time_tokens ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->config["one_time_token_expire"];
        }
        
        try {
            $this->db_obj->exec('DELETE FROM "wakarana_one_time_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("ワンタイムトークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
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
    
    
    static function create_random_code ($code_length = 16) {
        $key_bin = random_bytes($code_length * 5 / 8);
        
        $random_code = "";
        for ($cnt = 0; $cnt < $code_length; $cnt++) {
            $random_code .= WAKARANA_BASE32_TABLE[self::bin_to_int($key_bin, $cnt * 5, 5)];
        }
        
        return $random_code;
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
    
    
    static function free (&$wakarana_user) {
        unset($wakarana_user->wakarana->user_ids[$wakarana_user->user_info["user_id"]]);
        unset($wakarana_user);
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
    
    
    function get_primary_email_address () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "email_address" FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "is_primary" = TRUE');
        } catch (PDOException $err) {
            $this->wakarana->print_error("プライマリメールアドレスの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $primary_email_address = $stmt->fetchColumn();
        
        if (!empty($primary_email_address)) {
            return $primary_email_address;
        } else {
            return NULL;
        }
    }
    
    
    function get_email_addresses () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "email_address" FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "email_address" ASC');
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレスの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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
    
    
    function get_value ($custom_field_name) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["records_per_user"] !== 1) {
            $this->wakarana->print_error("指定されたカスタムフィールドは単一値ではありません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "custom_field_value" FROM "'.$table_name.'" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $custom_field_value = $stmt->fetchColumn();
        if ($custom_field_value !== FALSE) {
            return $custom_field_value;
        } else {
            return NULL;
        }
    }
    
    
    function get_values ($custom_field_name) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "custom_field_value" FROM "'.$table_name.'" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' ORDER BY "value_number" ASC');
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    
    function set_password ($password) {
        if (!$this->wakarana->config["allow_weak_password"] && !wakarana::check_password_strength($password)) {
            $this->wakarana->print_error("パスワードの強度が不十分です。現在の設定では弱いパスワードの使用は許可されていません。");
            return FALSE;
        }
        
        $password_hash = wakarana::hash_password($this->user_info["user_id"], $password);
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "password" = \''.$password_hash.'\', "last_updated" = \''.date("Y-m-d H:i:s").'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("パスワードの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["password"] = $password_hash;
        
        return TRUE;
    }
    
    
    function set_name ($user_name) {
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_users" SET "user_name" = :user_name, "last_updated" = \''.date("Y-m-d H:i:s").'\' WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
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
    
    
    function add_email_address ($email_address) {
        $email_addresses_count = count($this->get_email_addresses());
        
        if ($email_addresses_count >= $this->wakarana->config["email_addresses_per_user"]) {
            $this->wakarana->print_error("現在の設定ではこのアカウントにはこれ以上メールアドレスを追加できません。");
            return FALSE;
        }
        
        if (!$this->wakarana->check_email_address($email_address)) {
            $this->wakarana->print_error("使用できないメールアドレスです。");
            return FALSE;
        }
        
        if (!$this->wakarana->config["allow_nonunique_email_address"] && !empty($this->wakarana->search_users_with_email_address($email_address))) {
            $this->wakarana->print_error("現在の設定では同一メールアドレスの復数アカウントでの使用は許可されていません。");
            return FALSE;
        }
        
        if ($email_addresses_count === 0) {
            $is_primary_q = "TRUE";
        } else {
            $is_primary_q = "FALSE";
        }
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('INSERT INTO "wakarana_user_email_addresses"("user_id", "email_address", "is_primary") VALUES (\''.$this->user_info["user_id"].'\', :email_address, '.$is_primary_q.')');
            
            $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレスの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function set_primary_email_address ($email_address) {
        if (array_search($email_address, $this->get_email_addresses()) === FALSE) {
            $this->wakarana->print_error("未登録のメールアドレスをプライマリメールアドレスに設定することはできません。");
            return FALSE;
        }
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_user_email_addresses" SET "is_primary" = FALSE  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_user_email_addresses" SET "is_primary" = TRUE WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "email_address" = :email_address');
            
            $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("プライマリメールアドレスの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_email_address ($email_address) {
        if ($this->get_primary_email_address() === $email_address) {
            $this->wakarana->print_error("この関数ではプライマリメールアドレスを削除することはできません。");
            return FALSE;
        }
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('DELETE FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "email_address" = :email_address');
            
            $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレスの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_all_email_addresses () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーの全メールアドレスの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function set_status ($status) {
        $status = intval($status);
        
        if ($status !== WAKARANA_STATUS_NORMAL) {
            $this->delete_login_tokens();
        }
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "status" = \''.$status.'\', "last_updated" = \''.date("Y-m-d H:i:s").'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーアカウントの状態の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["status"] = $status;
        
        return TRUE;
    }
    
    
    function enable_2_factor_auth ($totp_key = NULL) {
        if (empty($totp_key)) {
            $totp_key = wakarana::create_random_code();
        } elseif (preg_match("/\A[A-Z2-7]{16}\z/", $totp_key) !== 1) {
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
        
        $this->user_info["totp_key"] = $totp_key;
        
        return $totp_key;
    }
    
    
    function disable_2_factor_auth () {
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "totp_key" = NULL WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("2要素認証の無効化に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["totp_key"] = NULL;
        
        return TRUE;
    }
    
    
    function set_value ($custom_field_name, $custom_field_value) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["records_per_user"] !== 1) {
            $this->wakarana->print_error("指定されたカスタムフィールドは単一値ではありません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
            
            if (!is_numeric($custom_field_value)) {
                $this->wakarana->print_error("数値型のカスタムフィールドに格納できない値が指定されました。");
                return FALSE;
            }
        } else {
            $table_name = "wakarana_user_custom_fields";
            
            $custom_field_value = mb_substr($custom_field_value, 0, $this->wakarana->custom_fields[$custom_field_name]["maximum_length"]);
        }
        
        if (!$this->wakarana->custom_fields[$custom_field_name]["allow_nonunique_value"]) {
            $other_users = $this->wakarana->search_users_with_custom_field($custom_field_name, $custom_field_value);
            
            if (!empty($other_users) && $other_users[0]->get_id() !== $this->get_id()) {
                $this->wakarana->print_error("他のユーザーに割り当て済みの値です。指定されたカスタムフィールドでは複数のアカウントに同じ値を設定することは許可されていません。");
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('INSERT INTO "'.$table_name.'"("user_id", "custom_field_name", "value_number", "custom_field_value") VALUES (\''.$this->user_info["user_id"].'\', \''.$custom_field_name.'\', 1, :custom_field_value) ON CONFLICT("user_id", "custom_field_name", "value_number") DO UPDATE SET "custom_field_value" = :custom_field_value_2');
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            $stmt->bindValue(":custom_field_value_2", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の設定に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function add_value ($custom_field_name, $custom_field_value, $value_number = -1) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        $value_count = count($this->get_values($custom_field_name));
        
        if ($value_count >= $this->wakarana->custom_fields[$custom_field_name]["records_per_user"]) {
            $this->wakarana->print_error("指定されたカスタムフィールドにはこれ以上項目を追加できません。");
            return FALSE;
        }
        
        if ($value_number === -1) {
            $value_number = $value_count + 1;
        } elseif($value_number <= $value_count + 1) {
            $value_number = intval($value_number);
        } else {
            $this->wakarana->print_error("並び順番号として使用可能な数値は既存の項目数に1を加えた値以下です。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
            
            if (!is_numeric($custom_field_value)) {
                $this->wakarana->print_error("数値型のカスタムフィールドに格納できない値が指定されました。");
                return FALSE;
            }
        } else {
            $table_name = "wakarana_user_custom_fields";
            
            $custom_field_value = mb_substr($custom_field_value, 0, $this->wakarana->custom_fields[$custom_field_name]["maximum_length"]);
        }
        
        if (!$this->wakarana->custom_fields[$custom_field_name]["allow_nonunique_value"] && !empty($this->wakarana->search_users_with_custom_field($custom_field_name, $custom_field_value))) {
            $this->wakarana->print_error("使用できない値です。指定されたカスタムフィールドでは複数のアカウントに同じ値を設定することは許可されていません。");
            return FALSE;
        }
        
        try {
            if ($value_number <= $value_count) {
                $this->wakarana->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" + '.$this->wakarana->custom_fields[$custom_field_name]["records_per_user"].' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" >= '.$value_number);
                $this->wakarana->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" - '.($this->wakarana->custom_fields[$custom_field_name]["records_per_user"] - 1).' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" >= '.$this->wakarana->custom_fields[$custom_field_name]["records_per_user"]);
            }
            
            $stmt = $this->wakarana->db_obj->prepare('INSERT INTO "'.$table_name.'"("user_id", "custom_field_name", "value_number", "custom_field_value") VALUES (\''.$this->user_info["user_id"].'\', \''.$custom_field_name.'\', '.$value_number.', :custom_field_value)');
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の追加に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function update_value ($custom_field_name, $value_number, $custom_field_value) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
            
            $custom_field_value = mb_substr($custom_field_value, 0, $this->wakarana->custom_fields[$custom_field_name]["maximum_length"]);
        }
        
        if (!$this->wakarana->custom_fields[$custom_field_name]["allow_nonunique_value"]) {
            $other_users = $this->wakarana->search_users_with_custom_field($custom_field_name, $custom_field_value);
            
            if (!empty($other_users) && $other_users[0]->get_id() !== $this->get_id()) {
                $this->wakarana->print_error("他のユーザーに割り当て済みの値です。指定されたカスタムフィールドでは複数のアカウントに同じ値を設定することは許可されていません。");
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "'.$table_name.'" SET "custom_field_value" = :custom_field_value WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" = '.intval($value_number));
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_value ($custom_field_name, $value_number = NULL) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($this->wakarana->custom_fields[$custom_field_name]["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        if (is_null($value_number)) {
            $value_number_q = '';
        } else {
            $value_number = intval($value_number);
            
            $value_number_q = ' AND "value_number" = '.$value_number;
        }
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "'.$table_name.'" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\''.$value_number_q);
            
            if (!is_null($value_number)) {
                $this->wakarana->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" + '.$this->wakarana->custom_fields[$custom_field_name]["records_per_user"].' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" > '.$value_number);
                $this->wakarana->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" - '.($this->wakarana->custom_fields[$custom_field_name]["records_per_user"] + 1).' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" >= '.$this->wakarana->custom_fields[$custom_field_name]["records_per_user"]);
            }
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_value ($custom_field_name, $custom_field_value) {
        if (!wakarana::check_id_string($custom_field_name) || !isset($this->wakarana->custom_fields[$custom_field_name])) {
            $this->wakarana->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        $index = array_search($custom_field_value, $this->get_values($custom_field_name));
        
        if ($index === FALSE) {
            $this->wakarana->print_error("指定されたカスタムフィールド値は存在しません。");
            return FALSE;
        }
        
        $value_number = $index + 1;
        
        return $this->delete_value($custom_field_name, $value_number);
    }
    
    
    function delete_all_values () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_user_custom_fields" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_user_custom_numerical_fields" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("カスタムフィールド値の削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function get_roles () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "role_name" FROM "wakarana_user_roles" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "role_name" ASC');
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
            $stmt_1 = $this->wakarana->db_obj->query('SELECT MAX("wakarana_permission_values"."permission_value") FROM "wakarana_user_roles", "wakarana_permission_values" WHERE (("wakarana_user_roles"."user_id" = \''.$this->user_info["user_id"].'\' AND "wakarana_user_roles"."role_name" = "wakarana_permission_values"."role_name") OR "wakarana_permission_values"."role_name" = \''.WAKARANA_BASE_ROLE.'\') AND "permission_name" = \''.strtolower($permission_name).'\'');
            $permission_value = $stmt_1->fetchColumn();
            
            if (!empty($permission_value)) {
                return $permission_value;
            } else {
                $stmt_2 = $this->wakarana->db_obj->query('SELECT COUNT("role_name") FROM "wakarana_user_roles" WHERE "user_id"=\''.$this->user_info["user_id"].'\' AND "role_name" = \''.WAKARANA_ADMIN_ROLE.'\'');
                if ($stmt_2->fetchColumn()) {
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
        if($this->delete_login_tokens() && $this->delete_one_time_tokens() && $this->delete_email_address_verification_code() && $this->delete_invite_codes() && $this->delete_password_reset_token() && $this->delete_2sv_token()){
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function get_auth_logs () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT "succeeded", "authenticate_datetime", "ip_address" FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "authenticate_datetime" DESC');
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行ログの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }


    function check_auth_interval($unsucceeded_only = FALSE) {
        if ($unsucceeded_only) {
            $succeeded_q = ' AND "succeeded" = FALSE';
        } else {
            $succeeded_q = '';
        }
        
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT COUNT("user_id") FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "authenticate_datetime" >= \''.date("Y-m-d H:i:s", time() - $this->wakarana->config["minimum_authenticate_interval"]).'\''.$succeeded_q);
            
            if ($stmt->fetchColumn() >= 1) {
                return FALSE;
            } else {
                return TRUE;
            }
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行間隔の確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function add_auth_log ($succeeded) {
        if ($succeeded) {
            $succeeded_q = "TRUE";
        } else {
            $succeeded_q = "FALSE";
        }
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "authenticate_datetime" NOT IN (SELECT "authenticate_datetime" FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "authenticate_datetime" DESC LIMIT '.($this->wakarana->config["authenticate_logs_per_user"] - 1).')');
            
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_authenticate_logs"("user_id", "succeeded", "authenticate_datetime", "ip_address") VALUES (\''.$this->user_info["user_id"].'\', '.$succeeded_q.', \''.(new DateTime())->format("Y-m-d H:i:s.u").'\', \''.$this->wakarana->get_client_ip_address().'\')');
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行ログの追加に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_auth_logs () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("認証試行ログの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function update_last_access ($token = NULL) {
        $last_access = date("Y-m-d H:i:s");
        
        try {
            $this->wakarana->db_obj->exec('UPDATE "wakarana_users" SET "last_access" = \''.$last_access.'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
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
    
    
    function get_login_tokens () {
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT SUBSTR("token", 1, 6) AS "token", "token_created", "ip_address", "operating_system", "browser_name", "last_access" FROM "wakarana_login_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "last_access" DESC');
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $err) {
            $this->wakarana->print_error("ログイントークン情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
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
    
    
    function delete_login_token ($abbreviated_token) {
        try {
            $stmt = $this->wakarana->db_obj->prepare('DELETE FROM "wakarana_login_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "token" LIKE :token');
            
            $stmt->bindValue(":token", $abbreviated_token."%", PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("指定されたログイントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_login_tokens () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_login_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーのログイントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function authenticate ($password, $totp_pin = NULL) {
        if ($this->check_password($password) && $this->wakarana->check_client_auth_interval($this->wakarana->get_client_ip_address()) && $this->check_auth_interval()) {
            $status = $this->get_status();
            if ($status !== WAKARANA_STATUS_NORMAL) {
                $this->add_auth_log(TRUE);
                return $status;
            }
            
            if ($this->get_totp_enabled()) {
                if (!is_null($totp_pin)) {
                    if ($this->totp_check($totp_pin)) {
                        $this->add_auth_log(TRUE);
                        return TRUE;
                    }
                } else {
                    $this->add_auth_log(TRUE);
                    return $this->create_2sv_token();
                }
            } else {
                $this->add_auth_log(TRUE);
                return TRUE;
            }
        }
        
        $this->add_auth_log(FALSE);
        return FALSE;
    }
    
    
    function create_email_address_verification_code ($email_address) {
        if (!$this->wakarana->check_email_address($email_address)) {
            $this->wakarana->print_error("使用できないメールアドレスです。");
            return FALSE;
        }
        
        if (!$this->wakarana->config["allow_nonunique_email_address"] && !empty($this->wakarana->search_users_with_email_address($email_address))) {
            $this->wakarana->print_error("現在の設定では同一メールアドレスの復数アカウントでの使用は許可されていません。");
            return NULL;
        }
        
        $this->wakarana->delete_email_address_verification_codes();
        
        $verification_code = wakarana::create_random_code(8);
        
        $code_created = date("Y-m-d H:i:s");
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('INSERT INTO "wakarana_email_address_verification_codes"("user_id", "email_address", "verification_code", "code_created") VALUES (\''.$this->user_info["user_id"].'\', :email_address, \''.$verification_code.'\', \''.$code_created.'\') ON CONFLICT("user_id") DO UPDATE SET "email_address" = :email_address_2, "verification_code" = \''.$verification_code.'\', "code_created" = \''.$code_created.'\'');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":email_address_2", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレス確認コードの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $verification_code;
    }
    
    
    function email_address_verify ($email_address, $verification_code, $verification_only = FALSE) {
        if (!$this->wakarana->check_email_address($email_address)) {
            $this->wakarana->print_error("使用できないメールアドレスです。");
            return FALSE;
        }
        
        $this->wakarana->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('SELECT COUNT(*) FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレス確認コードの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if (!empty($data)) {
            try {
                $stmt = $this->wakarana->db_obj->prepare('DELETE FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code');
                
                $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
                $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->wakarana->print_error("使用済みのメールアドレス確認コードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            if (!$verification_only) {
                return $this->add_email_address($email_address);
            } else {
                return TRUE;
            }
        } else {
            return FALSE;
        }
    }
    
    
    function get_email_address_verification_code_expire ($email_address, $verification_code) {
        $this->wakarana->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('SELECT "code_created" FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("メールアドレス確認コードの情報取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if ($data !== FALSE) {
            return date("Y-m-d H:i:s", strtotime($data) + $this->wakarana->config["verification_email_expire"]);
        } else {
            return FALSE;
        }
    }
    
    
    function delete_email_address_verification_code () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_email_address_verification_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーのメールアドレス確認コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_invite_code ($code_expire = NULL, $remaining_number = NULL) {
        $this->wakarana->delete_expired_invite_codes();
        
        if (is_null($code_expire)) {
            $code_expire_q = "NULL";
        } else {
            if (!preg_match("/\A[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]) ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]\z/u", $code_expire)) {
                $this->wakarana->print_error("異常な有効期限が指定されました。");
                return FALSE;
            }
            
            $code_expire_q = "'".$code_expire."'";
        }
        
        if (empty($remaining_number)) {
            $remaining_number_q = "NULL";
        } else {
            $remaining_number_q = intval($remaining_number);
        }
        
        $invite_code = wakarana::create_random_code();
        
        $code_created = date("Y-m-d H:i:s");
        
        try {
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_invite_codes"("invite_code", "user_id", "code_created", "code_expire", "remaining_number") VALUES (\''.$invite_code.'\', \''.$this->user_info["user_id"].'\', \''.$code_created.'\', '.$code_expire_q.', '.$remaining_number_q.')');
        } catch (PDOException $err) {
            $this->wakarana->print_error("招待コードの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $invite_code;
    }
    
    
    function get_invite_codes () {
        $this->wakarana->delete_expired_invite_codes();
        
        try {
            $stmt = $this->wakarana->db_obj->query('SELECT * FROM "wakarana_invite_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "code_created" ASC');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーの招待コード一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    function delete_invite_codes () {
        $this->wakarana->delete_expired_invite_codes();
        
        try {
            $stmt = $this->wakarana->db_obj->query('DELETE FROM "wakarana_invite_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーが発行した招待コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_password_reset_token () {
        $this->wakarana->delete_password_reset_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        try {
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_password_reset_tokens"("token", "user_id", "token_created") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.$token_created.'\') ON CONFLICT("user_id") DO UPDATE SET "token" = \''.$token.'\', "token_created"=\''.$token_created.'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("パスワード再設定用トークンの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $token;
    }
    
    
    function delete_password_reset_token () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_password_reset_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーのパスワード再設定用トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_2sv_token () {
        $this->wakarana->delete_2sv_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        try {
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_two_step_verification_tokens"("token", "user_id", "token_created") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.$token_created.'\') ON CONFLICT("user_id") DO UPDATE SET "token" = \''.$token.'\', "token_created"=\''.$token_created.'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("2段階認証用一時トークンの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $token;
    }
    
    
    function delete_2sv_token () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_two_step_verification_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーの2段階認証用一時トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_one_time_token () {
        $this->wakarana->delete_one_time_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_one_time_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "token" NOT IN (SELECT "token" FROM "wakarana_one_time_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "token_created" DESC LIMIT '.($this->wakarana->config["one_time_tokens_per_user"] - 1).')');
            
            $this->wakarana->db_obj->exec('INSERT INTO "wakarana_one_time_tokens"("token", "user_id", "token_created") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.date("Y-m-d H:i:s").'\')');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ワンタイムトークンの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $token;
    }
    
    
    function check_one_time_token ($token) {
        $this->wakarana->delete_one_time_tokens();
        
        try {
            $stmt = $this->wakarana->db_obj->prepare('SELECT COUNT(*) FROM "wakarana_one_time_tokens" WHERE "token" = :token AND "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("ワンタイムトークンの確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (intval($stmt->fetchColumn()) === 1) {
            try {
                $stmt = $this->wakarana->db_obj->prepare('DELETE FROM "wakarana_one_time_tokens" WHERE "token" = :token');
                
                $stmt->bindValue(":token", $token, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->wakarana->print_error("使用済みワンタイムトークンの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function delete_one_time_tokens () {
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_one_time_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーのワンタイムトークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function totp_check ($totp_pin) {
        if ($this->get_totp_enabled()){
            return $this->wakarana->totp_compare($this->user_info["totp_key"], $totp_pin);
        } else {
            return FALSE;
        }
    }
    
    
    function delete_user () {
        if (!$this->delete_all_tokens() || !$this->remove_role() || !$this->remove_all_email_addresses() || !$this->delete_all_values() || !$this->delete_auth_logs()){
            return FALSE;
        }
        
        try {
            $this->wakarana->db_obj->exec('DELETE FROM "wakarana_users" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->wakarana->print_error("ユーザーの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        unset($this->wakarana->user_ids[$this->user_info["user_id"]]);
        
        unset($this->wakarana);
        unset($this->user_info);
        
        return TRUE;
    }
}


class wakarana_role {
    protected $wakarana;
    protected $role_info;
    
    
    function __construct ($wakarana, $role_info) {
        $this->wakarana = $wakarana;
        $this->role_info = $role_info;
    }
    
    
    function get_id () {
        return $this->role_info["role_id"];
    }
    
    
    function get_name () {
        return $this->role_info["role_name"];
    }
    
    
    function get_description () {
        return $this->role_info["role_description"];
    }
    
    
    function set_info ($role_name, $role_description = "") {
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_roles" SET "role_name" = :role_name, "role_description" = :role_description WHERE "role_id" = \''.$this->role_info["role_id"].'\'');
            
            $stmt->bindValue(":role_name", mb_substr($role_name, 0, 120), PDO::PARAM_STR);
            $stmt->bindValue(":role_description", $role_description, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("ロール情報の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->role_info["role_name"] = $role_name;
        $this->role_info["role_description"] = $role_description;
        
        return TRUE;
    }
}


class wakarana_permitted_value {
    protected $wakarana;
    protected $permitted_value_info;
    
    
    function __construct ($wakarana, $permitted_value_info) {
        $this->wakarana = $wakarana;
        $this->permitted_value_info = $permitted_value_info;
    }
    
    
    function get_id () {
        return $this->permitted_value_info["permitted_value_id"];
    }
    
    
    function get_name () {
        return $this->permitted_value_info["permitted_value_name"];
    }
    
    
    function get_description () {
        return $this->permitted_value_info["permitted_value_description"];
    }
    
    
    function set_info ($permitted_value_name, $permitted_value_description = "") {
        try {
            $stmt = $this->wakarana->db_obj->prepare('UPDATE "wakarana_permitted_values" SET "permitted_value_name" = :permitted_value_name, "permitted_value_description" = :permitted_value_description WHERE "permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\'');
            
            $stmt->bindValue(":permitted_value_name", mb_substr($permitted_value_name, 0, 120), PDO::PARAM_STR);
            $stmt->bindValue(":permitted_value_description", $permitted_value_description, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->wakarana->print_error("権限情報の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->permitted_value_info["permitted_value_name"] = $permitted_value_name;
        $this->permitted_value_info["permitted_value_description"] = $permitted_value_description;
        
        return TRUE;
    }
}
