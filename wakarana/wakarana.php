<?php
/*Wakarana wakarana.php*/

class wakarana {
    use wakarana_common;
    
    
    const STATUS_DISABLE = 0;
    const STATUS_NORMAL = 1;
    const STATUS_UNAPPROVED = -1;
    
    const ORDER_USER_ID = "user_id";
    const ORDER_USER_NAME = "user_name";
    const ORDER_USER_CREATED = "user_created";
    
    const BASE_ROLE = "__base__";
    const ADMIN_ROLE = "__admin__";
    
    protected const BASE32_TABLE = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "2", "3", "4", "5", "6", "7");
    
    
    protected $rejection_reason = NULL;
    
    
    function __construct ($base_dir = NULL) {
        $this->profile = wakarana_profile::of($base_dir);
        $this->profile->connect_db();
    }
    
    
    function __debugInfo () {
        return array("base_path" => $this->profile->get_base_path());
    }
    
    
    function get_rejection_reason () {
        return $this->rejection_reason;
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
    
    
    protected static function base32_decode ($base32_str) {
        $length = strlen($base32_str);
        
        $bin = "";
        $bin_buf = 0;
        $buf_head = 0;
        for ($cnt = 0; $cnt < $length; $cnt++) {
            $index = array_search(substr($base32_str, $cnt, 1), self::BASE32_TABLE);
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
    
    
    static function generate_unique_id () {
        $ts_bytes = substr(pack("J", intval(microtime(TRUE) * 1000)), 2);
        $rand_bytes = random_bytes(4);
        
        $unique_id_bin = $ts_bytes.$rand_bytes;
        
        $unique_id = "";
        for ($cnt = 0; $cnt < 16; $cnt++) {
            $unique_id .= self::BASE32_TABLE[self::bin_to_int($unique_id_bin, $cnt * 5, 5)];
        }
        
        return $unique_id;
    }
    
    
    static function verify_password ($hash, $password, $salt = NULL) {
        if (str_starts_with($hash, "$")) {
            return password_verify($password, $hash);
        } else {
            return $hash === hash("sha512", $password.hash("sha512", $salt));
        }
    }
    
    
    function get_user ($user_id) {
        if (!self::check_id_string($user_id)) {
            return FALSE;
        }
        
        try {
            if ($this->profile->get_config("use_sqlite")) {
                $stmt = $this->profile->db_obj->query("SELECT `user_id`, `password_hash`, `user_name`, `user_created`, `last_updated`, `last_access`, `status`, `totp_key` FROM `wakarana_users` WHERE `user_id` = '".$user_id."'");
            } else {
                $stmt = $this->profile->db_obj->query('SELECT "user_id", "password_hash", "user_name", "user_created", "last_updated", "last_access", "status", "totp_key" FROM "wakarana_users" WHERE LOWER("user_id") = \''.strtolower($user_id).'\'');
            }
        } catch (PDOException $err) {
            $this->print_error("ユーザー情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($user_info)) {
            return wakarana_user::of($this->profile, $this, $user_info);
        } else {
            return FALSE;
        }
    }
    
    
    function count_users () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT COUNT(*) FROM "wakarana_users"');
        } catch (PDOException $err) {
            $this->print_error("ユーザー数の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchColumn();
    }
    
    
    function get_all_users ($start = 0, $limit = 100, $order_by = self::ORDER_USER_CREATED, $asc = TRUE) {
        $start = intval($start);
        $limit = intval($limit);
        
        switch ($order_by) {
            case self::ORDER_USER_ID:
                if ($this->profile->get_config("use_sqlite")) {
                    $order_by_q = "`user_id`";
                } else {
                    $order_by_q = 'LOWER("user_id")';
                }
                break;
                
            case self::ORDER_USER_NAME:
                if ($this->profile->get_config("use_sqlite")) {
                    $order_by_q = "`user_name`";
                } else {
                    $order_by_q = 'LOWER("user_name")';
                }
                break;
                
            case self::ORDER_USER_CREATED:
                $order_by_q = '"user_created"';
                break;
                
            default:
                $this->print_error("対応していない並び替え基準です。");
                return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "user_id", "password_hash", "user_name", "user_created", "last_updated", "last_access", "status", "totp_key" FROM "wakarana_users" ORDER BY '.$order_by_q.' '.($asc ? 'ASC' : 'DESC').' LIMIT '.$limit.' OFFSET '.$start);
        } catch (PDOException $err) {
            $this->print_error("ユーザー一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = wakarana_user::of($this->profile, $this, $user_info);
        }
        
        return $users;
    }
    
    
    function create_user ($user_id, $password, $user_name = "", $status = self::STATUS_NORMAL, $used_invite_code = NULL) {
        $this->rejection_reason = NULL;
        
        if (!self::check_id_string($user_id)) {
            $this->rejection_reason = "invalid_user_id";
            return FALSE;
        }
        
        if (!$this->profile->get_config("allow_weak_password") && !self::check_password_strength($password)) {
            $this->rejection_reason = "weak_password";
            return FALSE;
        }
        
        $password_hash = $this->generate_password_hash($password, $user_id);
        $date_time = date("Y-m-d H:i:s");
        
        $this->profile->begin_transaction();
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_users" WHERE "user_id" = \''.$user_id.'\' LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("ユーザー作成の可否を確認できませんでした。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            $this->rejection_reason = "user_already_exists";
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_users"("user_id", "password_hash", "user_name", "user_created", "last_updated", "last_access", "status", "totp_key", "used_invite_code") VALUES (\''.$user_id.'\', \''.$password_hash.'\', :user_name, \''.$date_time.'\', \''.$date_time.'\', \''.$date_time.'\', '.intval($status).', NULL, :used_invite_code)');
            
            if (!empty($user_name)) {
                $stmt->bindValue(":user_name", mb_substr($user_name, 0, 240), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":user_name", NULL, PDO::PARAM_NULL);
            }
            
            if (!empty($used_invite_code)) {
                $stmt->bindValue(":used_invite_code", $used_invite_code, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":used_invite_code", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ユーザーの作成に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        $user = $this->get_user($user_id);
        
        return $user;
    }
    
    
    function create_user_with_invite_code ($invite_code, $user_id, $password, $user_name = "", $status = self::STATUS_NORMAL) {
        $this->rejection_reason = NULL;
        
        $ip_address = $this->get_client_ip_address();
        
        if (!$this->check_auth_allowed($ip_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        $this->disable_expired_invite_codes();
        
        $invite_code = strtoupper($invite_code);
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "remaining_number" FROM "wakarana_invite_codes" WHERE "invite_code" = :invite_code AND "is_active" = 1');
            
            $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コードの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $remaining_number = $stmt->fetchColumn();
        
        if ($remaining_number === FALSE) {
            $this->rejection_reason = "invalid_invite_code";
            
            $this->add_auth_log($ip_address, NULL, "create_user_with_invite_code", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        $user = $this->create_user($user_id, $password, $user_name, $status, $invite_code);
        
        if (!is_object($user)) {
            $this->profile->rollback_transaction();
            
            $this->add_auth_log($ip_address, NULL, "create_user_with_invite_code", NULL, $this->rejection_reason);
            
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_invite_codes" SET '.($remaining_number === 1 ? '"is_active" = 0, ' : '').(!is_null($remaining_number) ?'"remaining_number" = "remaining_number" - 1, ' : '').'"usage_count" = "usage_count" + 1 WHERE "invite_code" = :invite_code');
            
            $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コードの使用に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->add_auth_log($ip_address, $user->get_id(), "create_user_with_invite_code", TRUE);
        
        $this->profile->commit_transaction();
        
        return $user;
    }
    
    
    function get_role ($role_id) {
        if (!self::check_id_string($role_id)) {
            return FALSE;
        }
        
        $role_id = strtolower($role_id);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_roles" WHERE "role_id" = \''.$role_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("ロール情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $role_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($role_info)) {
            return wakarana_role::of($this->profile, $this, $role_info);
        } else {
            return FALSE;
        }
    }
    
    
    function get_all_roles () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_roles" ORDER BY "role_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロール一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $roles_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $roles = array();
        foreach ($roles_info as $role_info) {
            $roles[] = wakarana_role::of($this->profile, $this, $role_info);
        }
        
        return $roles;
    }
    
    
    function create_role ($role_id, $role_name, $role_description = "") {
        $this->rejection_reason = NULL;
        
        if (!self::check_id_string($role_id)) {
            $this->rejection_reason = "invalid_role_id";
            return FALSE;
        }
        
        $role_id = strtolower($role_id);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_roles" WHERE "role_id" = \''.$role_id.'\' LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("ロール作成の可否を確認できませんでした。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            $this->rejection_reason = "role_already_exists";
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_roles"("role_id", "role_name", "role_description") VALUES (\''.$role_id.'\', :role_name, :role_description)');
            
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
    
    
    static function get_parent_resource_id ($resource_id) {
        $slash_pos = strrpos($resource_id, "/");
        
        if ($slash_pos === FALSE) {
            return NULL;
        }
        
        return substr($resource_id, 0, $slash_pos);
    }
    
    
    function get_permission ($resource_id) {
        if (!self::check_resource_id_string($resource_id)) {
            return FALSE;
        }
        
        $resource_id = strtolower($resource_id);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_permissions" WHERE "resource_id" = \''.$resource_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("権限情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permission_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($permission_info)) {
            return wakarana_permission::of($this->profile, $this, $permission_info);
        } else {
            return FALSE;
        }
    }
    
    
    function get_all_permissions () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_permissions" ORDER BY "resource_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permissions_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $permissions = array();
        foreach ($permissions_info as $permission_info) {
            $permissions[] = wakarana_permission::of($this->profile, $this, $permission_info);
        }
        
        return $permissions;
    }
    
    
    function create_permission ($resource_id, $permission_name, $permission_description = "") {
        $this->rejection_reason = NULL;
        
        if (!self::check_resource_id_string($resource_id)) {
            $this->rejection_reason = "invalid_resource_id";
            return FALSE;
        }
        
        $resource_id = strtolower($resource_id);
        
        $parent_resource_id = self::get_parent_resource_id($resource_id);
        
        if (!empty($parent_resource_id)) {
            if (!is_object($this->get_permission($parent_resource_id))) {
                $this->rejection_reason = "parent_resource_not_exists";
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_permissions" WHERE "resource_id" = \''.$resource_id.'\' LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("権限作成の可否を確認できませんでした。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            $this->rejection_reason = "resource_already_exists";
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_permissions"("resource_id", "permission_name", "permission_description") VALUES (\''.$resource_id.'\', :permission_name, :permission_description)');
            
            $stmt->bindValue(":permission_name", mb_substr($permission_name, 0, 120), PDO::PARAM_STR);
            $stmt->bindValue(":permission_description", $permission_description, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("権限の作成に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        if (!empty($parent_resource_id)) {
            try {
                $this->profile->db_obj->exec('INSERT INTO "wakarana_permission_actions"("resource_id", "action") SELECT \''.$resource_id.'\', "action" FROM "wakarana_permission_actions" WHERE "resource_id" = \''.$parent_resource_id.'\'');
                $this->profile->db_obj->exec('INSERT INTO "wakarana_role_permissions"("role_id", "resource_id", "action") SELECT "role_id", \''.$resource_id.'\', "action" FROM "wakarana_role_permissions" WHERE "resource_id" = \''.$parent_resource_id.'\'');
                $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permission_caches"("user_id", "resource_id", "action") SELECT "user_id", \''.$resource_id.'\', "action" FROM "wakarana_user_permission_caches" WHERE "resource_id" = \''.$parent_resource_id.'\'');
            } catch (PDOException $err) {
                $this->print_error("親権限から子権限への設定継承に失敗しました。".$err->getMessage());
                
                $this->profile->rollback_transaction();
                
                return FALSE;
            }
        }
        
        $permission = $this->get_permission($resource_id);
        
        if (empty($parent_resource_id)) {
            if (!$permission->add_action("any")) {
                $this->profile->rollback_transaction();
                
                return FALSE;
            }
        }
        
        $this->profile->commit_transaction();
        
        return $permission;
    }
    
    
    function get_permitted_value ($permitted_value_id) {
        if (!self::check_id_string($permitted_value_id)) {
            return FALSE;
        }
        
        $permitted_value_id = strtolower($permitted_value_id);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_permitted_values" WHERE "permitted_value_id" = \''.$permitted_value_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("権限値情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permitted_value_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($permitted_value_info)) {
            return wakarana_permitted_value::of($this->profile, $this, $permitted_value_info);
        } else {
            return FALSE;
        }
    }
    
    
    function get_all_permitted_values () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_permitted_values" ORDER BY "permitted_value_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限値情報一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permitted_values_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $permitted_values = array();
        foreach ($permitted_values_info as $permitted_value_info) {
            $permitted_values[] = wakarana_permitted_value::of($this->profile, $this, $permitted_value_info);
        }
        
        return $permitted_values;
    }
    
    
    function create_permitted_value ($permitted_value_id, $permitted_value_name, $permitted_value_description = "") {
        $this->rejection_reason = NULL;
        
        if (!self::check_id_string($permitted_value_id)) {
            $this->rejection_reason = "invalid_permitted_value_id";
            return FALSE;
        }
        
        $permitted_value_id = strtolower($permitted_value_id);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_permitted_values" WHERE "permitted_value_id" = \''.$permitted_value_id.'\' LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("権限値作成の可否を確認できませんでした。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            $this->rejection_reason = "permitted_value_already_exists";
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_permitted_values"("permitted_value_id", "permitted_value_name", "permitted_value_description") VALUES (\''.$permitted_value_id.'\', :permitted_value_name, :permitted_value_description)');
            
            $stmt->bindValue(":permitted_value_name", mb_substr($permitted_value_name, 0, 120), PDO::PARAM_STR);
            $stmt->bindValue(":permitted_value_description", $permitted_value_description, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("権限値変数の作成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $this->get_permitted_value($permitted_value_id);
    }
    
    
    static function create_token () {
        return rtrim(strtr(base64_encode(random_bytes(32)), "+/", "-_"), "=");
    }
    
    
    function delete_all_tokens () {
        $this->profile->begin_transaction();
        
        if ($this->delete_session_tokens(0) && $this->delete_one_time_tokens(0) && $this->delete_email_address_verification_codes(0) && $this->disable_invite_code() && $this->delete_password_reset_tokens(0) && $this->delete_2sv_tokens(0)) {
            $this->profile->commit_transaction();
            
            return TRUE;
        } else {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
    }
    
    
    function get_client_ip_address () {
        if ($this->profile->get_config("proxy_count") >= 1) {
            if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $x_forwarded_for = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
                $proxy_cnt = count($x_forwarded_for);
            } else {
                $proxy_cnt = 0;
            }
            
            if ($proxy_cnt >= $this->profile->get_config("proxy_count")) {
                $remote_addr = trim($x_forwarded_for[$proxy_cnt - $this->profile->get_config("proxy_count")]);
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
    
    
    static function get_client_environment ($ua = NULL) {
        $os_names = array("Android", "iPhone", "iPad", "Windows", "Macintosh", "CrOS", "Linux", "BSD", "Nintendo", "PlayStation", "Xbox");
        $browser_names = array("Firefox", "Edg", "OPR", "Sleipnir", "Chrome", "Safari", "Trident");
        
        $environment = array("operating_system" => NULL, "browser_name" => NULL);
        
        if (empty($ua)) {
            if (!isset($_SERVER["HTTP_USER_AGENT"])) {
                return $environment;
            }
            
            $ua = $_SERVER["HTTP_USER_AGENT"];
        }
        
        foreach ($os_names as $os_name) {
            if (str_contains($ua, $os_name) !== FALSE) {
                $environment["operating_system"] = $os_name;
                break;
            }
        }
        
        foreach ($browser_names as $browser_name) {
            if (str_contains($ua, $browser_name) !== FALSE) {
                $environment["browser_name"] = $browser_name;
                break;
            }
        }
        
        return $environment;
    }
    
    
    function check_auth_allowed ($ip_address, $user_id_or_email_address = NULL) {
        $this->profile->begin_transaction();
        
        $this->delete_auth_logs();
        $this->delete_expired_ip_address_auth_info();
        
        $this->profile->commit_transaction();
        
        if (!is_null($user_id_or_email_address)) {
            try {
                $stmt = $this->profile->db_obj->prepare('SELECT 1 FROM "wakarana_authentication_logs" WHERE "authentication_id" = :authentication_id AND "authentication_datetime" > \''.(new DateTime("-".$this->profile->get_config("auth_initial_lockout_seconds")." seconds")->format("Y-m-d H:i:s.u")).'\' LIMIT 1');
                
                $stmt->bindValue(":authentication_id", $user_id_or_email_address, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("ユーザーIDのロックアウト状態の確認に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            if (!empty($stmt->fetchColumn())) {
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT * FROM "wakarana_failed_authentication_per_ip_address" WHERE "ip_address" = :ip_address');
            
            $stmt->bindValue(":ip_address", $ip_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("IPアドレスのロックアウト状態の確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $auth_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($auth_info)) {
            $lockout_seconds = min(2 ** ($auth_info["failure_count"] - 1) * $this->profile->get_config("auth_initial_lockout_seconds"), $this->profile->get_config("auth_max_lockout_seconds"));
            
            if ($auth_info["last_authentication_datetime"] > new DateTime("-".$lockout_seconds." seconds")->format("Y-m-d H:i:s.u")) {
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function add_auth_log ($ip_address, $user_id_or_email_address, $authentication_type, $succeeded, $failure_reason = NULL) {
        $dt = new DateTime();
        $now_datetime = $dt->format("Y-m-d H:i:s.u");
        $dt->modify("-".$this->profile->get_config("auth_failure_expiration_seconds")." seconds");
        $threshold_datetime = $dt->format("Y-m-d H:i:s.u");
        
        $this->profile->begin_transaction();
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_authentication_logs"("ip_address", "authentication_id", "authentication_type", "succeeded", "failure_reason", "authentication_datetime") VALUES (:ip_address, :authentication_id, :authentication_type, :succeeded, :failure_reason, \''.$now_datetime.'\')');
            
            $stmt->bindValue(":ip_address", $ip_address, PDO::PARAM_STR);
            if (is_null($user_id_or_email_address)) {
                $stmt->bindValue(":authentication_id", NULL, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":authentication_id", $user_id_or_email_address, PDO::PARAM_STR);
            }
            $stmt->bindValue(":authentication_type", $authentication_type, PDO::PARAM_STR);
            if (is_null($succeeded)) {
                $stmt->bindValue(":succeeded", NULL, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":succeeded", $succeeded, PDO::PARAM_INT);
            }
            if (is_null($failure_reason)) {
                $stmt->bindValue(":failure_reason", NULL, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":failure_reason", $failure_reason, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            
            if (!$succeeded && !is_null($succeeded)) {
                $stmt = $this->profile->db_obj->prepare('
                    INSERT INTO "wakarana_failed_authentication_per_ip_address" ("ip_address", "failure_count", "last_authentication_datetime")
                    VALUES (:ip_address, 1, \''.$now_datetime.'\')
                    ON CONFLICT ("ip_address") DO UPDATE SET
                        "failure_count" = CASE
                            WHEN "wakarana_failed_authentication_per_ip_address"."last_authentication_datetime" >= \''.$threshold_datetime.'\'
                            THEN "wakarana_failed_authentication_per_ip_address"."failure_count" + 1
                            ELSE 1
                        END,
                        "last_authentication_datetime" = \''.$now_datetime.'\'
                ');
                
                $stmt->bindValue(":ip_address", $ip_address, PDO::PARAM_STR);
                
                $stmt->execute();
            }
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの登録に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function delete_auth_logs ($retention_seconds_or_datetime = -1) {
        if (is_string($retention_seconds_or_datetime)) {
            if (!preg_match("/\A[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]) ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]\z/u", $retention_seconds_or_datetime)) {
                $this->print_error("日時の指定が異常です。");
                return FALSE;
            }
            
            $authentication_datetime = $retention_seconds_or_datetime.".999999";
        } else {
            if ($retention_seconds_or_datetime === -1) {
                $retention_seconds_or_datetime = $this->profile->get_config("auth_log_retention_seconds");
                
                if (empty($retention_seconds_or_datetime)) {
                    return NULL;
                }
            }
            
            $authentication_datetime = new DateTime("-".$retention_seconds_or_datetime." seconds")->format("Y-m-d H:i:s.u");
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_authentication_logs" WHERE "authentication_datetime" <= \''.$authentication_datetime.'\'');
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function export_auth_logs ($file_path, $date_str, $compress = TRUE, $delete_exported_logs = FALSE) {
        set_time_limit(0);
        
        $this->profile->db_obj->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->profile->db_obj->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        $fp = $compress ? gzopen($file_path, "wb9") : @fopen($file_path, "w");
        if (empty($fp)) {
            $this->print_error("ファイルの作成に失敗しました。");
            
            return FALSE;
        }
        
        $datetime_start = $date_str." 00:00:00.000000";
        $datetime_end = $date_str." 23:59:59.999999";
        
        if ($this->profile->get_config("use_sqlite")) {
            try {
                $stmt = $this->profile->db_obj->prepare('SELECT * FROM "wakarana_authentication_logs" WHERE "authentication_datetime" >= :datetime_start AND "authentication_datetime" <= :datetime_end ORDER BY "authentication_datetime" ASC');
                $stmt->bindValue(":datetime_start", $datetime_start, PDO::PARAM_STR);
                $stmt->bindValue(":datetime_end", $datetime_end, PDO::PARAM_STR);
                $stmt->execute();
                
                while ($row = $stmt->fetch()) {
                    if ($compress) {
                        gzwrite($fp, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                    } else {
                        fwrite($fp, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                    }
                }
            } catch (PDOException $err) {
                $this->print_error("認証試行ログの抽出に失敗しました。".$err->getMessage());
                return FALSE;
            }
        } else {
            $this->profile->begin_transaction();
            
            try {
                $stmt = $this->profile->db_obj->prepare('DECLARE "auth_log_cursor" CURSOR FOR SELECT * FROM "wakarana_authentication_logs" WHERE "authentication_datetime" >= :datetime_start AND "authentication_datetime" <= :datetime_end ORDER BY "authentication_datetime" ASC');
                $stmt->bindValue(":datetime_start", $datetime_start, PDO::PARAM_STR);
                $stmt->bindValue(":datetime_end", $datetime_end, PDO::PARAM_STR);
                $stmt->execute();
                
                $fetch_stmt = $this->profile->db_obj->prepare('FETCH 10000 FROM "auth_log_cursor"');
                
                while (TRUE) {
                    $fetch_stmt->execute();
                    $rows = $fetch_stmt->fetchAll();
                    
                    if (empty($rows)) {
                        break;
                    }
                    
                    foreach ($rows as $row) {
                        if ($compress) {
                            gzwrite($fp, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                        } else {
                            fwrite($fp, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                        }
                    }
                }
            } catch (PDOException $err) {
                $this->print_error("認証試行ログの抽出に失敗しました。".$err->getMessage());
                
                $this->profile->rollback_transaction();
                
                return FALSE;
            }
            
            $this->profile->commit_transaction();
        }
        
        if ($compress) {
            gzclose($fp);
        } else {
            fclose($fp);
        }
        
        if ($delete_exported_logs) {
            try {
                $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_authentication_logs" WHERE "authentication_datetime" >= :datetime_start AND "authentication_datetime" <= :datetime_end');
                $stmt->bindValue(":datetime_start", $datetime_start, PDO::PARAM_STR);
                $stmt->bindValue(":datetime_end", $datetime_end, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($this->profile->get_config("use_sqlite")) {
                    exec('VACUUM');
                } else {
                    exec('VACUUM FULL "wakarana_authentication_logs"');
                }
            } catch (PDOException $err) {
                $this->print_error("認証試行ログの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function delete_expired_ip_address_auth_info () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_failed_authentication_per_ip_address" WHERE "last_authentication_datetime" < \''.(new DateTime("-".$this->profile->get_config("auth_failure_expiration_seconds")." seconds")->format("Y-m-d H:i:s.u")).'\'');
        } catch (PDOException $err) {
            $this->print_error("認証失敗情報の削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function authenticate ($user_id, $password, $ip_address = NULL) {
        $this->rejection_reason = NULL;
        
        if (is_null($ip_address)) {
            $ip_address = $this->get_client_ip_address();
        }
        
        if (!$this->check_auth_allowed($ip_address, $user_id)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        $user = $this->get_user($user_id);
        
        if (empty($user)) {
            $this->rejection_reason = "parameters_not_matched";
            
            if (self::check_id_string($user_id)) {
                if (!empty($this->profile->get_config("dummy_password_hash"))) {
                    self::verify_password($this->profile->get_config("dummy_password_hash"), $password, $user_id);
                }
            } else {
                $user_id = NULL;
            }
            
            $this->add_auth_log($ip_address, $user_id, "authenticate", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
        
        $result = $user->authenticate($password, $ip_address, FALSE);
        
        if ($result === TRUE) {
            return $user;
        } else {
            if (empty($result)) {
                $this->rejection_reason = $user->get_rejection_reason();
            }
            
            return $result;
        }
    }
    
    
    function login ($user_id, $password) {
        $user = $this->authenticate($user_id, $password);
        
        if (is_object($user)) {
            $user->set_session_token();
        }
        
        return $user;
    }
    
    
    function authenticate_with_email_address ($email_address, $password, $ip_address = NULL) {
        $this->rejection_reason = NULL;
        
        if (is_null($ip_address)) {
            $ip_address = $this->get_client_ip_address();
        }
        
        if (!$this->check_auth_allowed($ip_address, $email_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        if ($this->profile->get_config("allow_nonunique_email_address")) {
            $this->print_error("同一メールアドレスの複数アカウントへの登録を容認する設定では、メールアドレスでのログインは利用できません。");
            return FALSE;
        }
        
        $users = $this->search_users_with_email_address($email_address);
        
        if (empty($users)) {
            if ($this->check_email_address($email_address, FALSE)) {
                if (!empty($this->profile->get_config("dummy_password_hash"))) {
                    self::verify_password($this->profile->get_config("dummy_password_hash"), $password, "");
                }
            } else {
                $email_address = NULL;
            }
            
            $this->rejection_reason = "parameters_not_matched";
            
            $this->add_auth_log($ip_address, $email_address, "authenticate_with_email_address", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
        
        $result = $users[0]->authenticate($password, $ip_address, FALSE);
        
        if ($result === TRUE) {
            return $users[0];
        } else {
            if (empty($result)) {
                $this->rejection_reason = $users[0]->get_rejection_reason();
            }
            
            return $result;
        }
    }
    
    
    function login_with_email_address ($email_address, $password) {
        $user = $this->authenticate_with_email_address($email_address, $password);
        
        if (is_object($user)) {
            $user->set_session_token();
        }
        
        return $user;
    }
    
    
    function delete_session_tokens ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->profile->get_config("session_expire");
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_sessions" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("セッショントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function search_users_with_email_address ($email_address) {
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "u"."user_id", "u"."password_hash", "u"."user_name", "u"."user_created", "u"."last_updated", "u"."last_access", "u"."status", "u"."totp_key" FROM "wakarana_users" AS "u", "wakarana_user_email_addresses" WHERE "wakarana_user_email_addresses"."email_address" = :email_address AND "u"."user_id" = "wakarana_user_email_addresses"."user_id"');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ユーザーの検索に失敗しました。".$err->getMessage());
            return -1;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = wakarana_user::of($this->profile, $this, $user_info);
        }
        
        return $users;
    }
    
    
    function check_email_address ($email_address, $check_blacklist = TRUE) {
        $this->rejection_reason = NULL;
        
        if (preg_match("/\A[A-Za-z0-9!#$%&'\*+\/=?^_`\{\|\}~\.\-]+@[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)+\z/u", $email_address)) {
            if (!$check_blacklist || $this->check_email_domain(substr($email_address, strpos($email_address, "@") + 1))) {
                return TRUE;
            }
            
            $this->rejection_reason = "blacklisted_email_domain";
        } else {
            $this->rejection_reason = "invalid_email_address";
        }
        
        return FALSE;
    }
    
    
    function check_email_sending_interval ($email_address) {
        $sendable_datetime_max = date("Y-m-d H:i:s", time() - $this->profile->get_config("verification_email_sendable_interval"));
        $ip_address = $this->get_client_ip_address();
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "code_created" FROM "wakarana_email_address_verification_codes" WHERE "ip_address" = \''.$ip_address.'\' ORDER BY "code_created" DESC LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの前回送信時間確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if ($stmt->fetchColumn() > $sendable_datetime_max) {
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "code_created" FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address ORDER BY "code_created" DESC LIMIT 1');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの前回送信時間確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if ($stmt->fetchColumn() > $sendable_datetime_max) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_email_address_verification_code ($email_address) {
        if (!$this->check_email_address($email_address)) {
            return FALSE;
        }
        
        if (!$this->profile->get_config("allow_nonunique_email_address") && !empty($this->search_users_with_email_address($email_address))) {
            $this->rejection_reason = "email_address_already_exists";
            return FALSE;
        }
        
        $this->delete_email_address_verification_codes();
        
        if (!$this->check_email_sending_interval($email_address)) {
            $this->rejection_reason = "currently_locked_out";
            return FALSE;
        }
        
        $verification_code = self::create_random_code(8);
        
        $code_created = date("Y-m-d H:i:s");
        $ip_address = $this->get_client_ip_address();
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_email_address_verification_codes"("user_id", "email_address", "verification_code", "code_created", "ip_address") VALUES (NULL, :email_address, \''.$verification_code.'\', \''.$code_created.'\', \''.$ip_address.'\')');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $verification_code;
    }
    
    
    function email_address_verify ($email_address, $verification_code) {
        $this->rejection_reason = NULL;
        
        $ip_address = $this->get_client_ip_address();
        
        if (!$this->check_auth_allowed($ip_address, $email_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        if (!$this->check_email_address($email_address)) {
            $this->add_auth_log($ip_address, NULL, "email_address_verify", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
        
        if (!$this->profile->get_config("allow_nonunique_email_address") && !empty($this->search_users_with_email_address($email_address))) {
            $this->rejection_reason = "email_address_already_exists";
            
            $this->add_auth_log($ip_address, $email_address, "email_address_verify", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
        
        $this->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT 1 FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" IS NULL LIMIT 1');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            try {
                $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code');
                
                $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
                $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みのメールアドレス確認コードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            $this->add_auth_log($ip_address, $email_address, "email_address_verify", TRUE);
            
            return TRUE;
        } else {
            $this->rejection_reason = "parameters_not_matched";
            
            $this->add_auth_log($ip_address, $email_address, "email_address_verify", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
    }
    
    
    function get_email_address_verification_code_expire ($email_address, $verification_code) {
        $this->rejection_reason = NULL;
        
        $ip_address = $this->get_client_ip_address();
        
        if (!$this->check_auth_allowed($ip_address, $email_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        $this->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "code_created" FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" IS NULL');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの情報取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if ($data !== FALSE) {
            $this->add_auth_log($ip_address, $email_address, "get_email_address_verification_code_expire", NULL);
            
            return date("Y-m-d H:i:s", strtotime($data) + $this->profile->get_config("verification_email_expire"));
        } else {
            if (!$this->check_email_address($email_address, FALSE)) {
                $email_address = NULL;
            }
            
            $this->rejection_reason = "parameters_not_matched";
            
            $this->add_auth_log($ip_address, $email_address, "get_email_address_verification_code_expire", FALSE, $this->rejection_reason);
            
            return FALSE;
        }
    }
    
    
    function delete_email_address_verification_codes ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->profile->get_config("verification_email_expire");
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_email_address_verification_codes" WHERE "code_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function get_invite_code_expire ($invite_code) {
        $this->rejection_reason = NULL;
        
        $ip_address = $this->get_client_ip_address();
        
        if (!$this->check_auth_allowed($ip_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        $invite_code = strtoupper($invite_code);
        $ts = time();
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "code_expire" FROM "wakarana_invite_codes" WHERE "invite_code" = :invite_code AND "is_active" = 1 AND ("code_expire" IS NULL OR "code_expire" >= \''.date("Y-m-d H:i:s", $ts).'\')');
            
            $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コードの有効期限確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $code_expire = $stmt->fetchColumn();
        
        if ($code_expire === FALSE) {
            $this->rejection_reason = "invalid_invite_code";
            
            $this->add_auth_log($ip_address, NULL, "get_invite_code_expire", FALSE, $this->rejection_reason);
            
            return $code_expire;
        }
        
        $this->add_auth_log($ip_address, NULL, "get_invite_code_expire", NULL);
        
        if (is_null($code_expire)) {
            return $code_expire;
        } else {
            return strtotime($code_expire) - $ts;
        }
    }
    
    
    function get_invite_code_info ($invite_code) {
        $this->disable_expired_invite_codes();
        
        $invite_code = strtoupper($invite_code);
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT * FROM "wakarana_invite_codes" WHERE "invite_code" = :invite_code');
            
            $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コード情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    
    function count_invite_codes ($is_active = NULL) {
        if (is_null($is_active)) {
            $is_active_q = '';
        } else {
            $this->disable_expired_invite_codes();
            
            $is_active_q = ' WHERE "is_active" = '.($is_active ? '1' : '0');
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT COUNT(*) FROM "wakarana_invite_codes"'.$is_active_q);
        } catch (PDOException $err) {
            $this->print_error("招待コード数の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchColumn();
    }
    
    
    function get_invite_codes ($is_active = NULL, $start = 0, $limit = 100, $asc = TRUE) {
        $this->disable_expired_invite_codes();
        
        if (is_null($is_active)) {
            $is_active_q = '';
        } else {
            $is_active_q = ' WHERE "is_active" = '.($is_active ? '1' : '0');
        }
        
        $start = intval($start);
        $limit = intval($limit);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_invite_codes"'.$is_active_q.' ORDER BY "code_created" '.($asc ? 'ASC' : 'DESC').' LIMIT '.$limit.' OFFSET '.$start);
        } catch (PDOException $err) {
            $this->print_error("招待コード一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    function create_invite_code ($code_expire = NULL, $remaining_number = NULL, $user_id = NULL) {
        if (is_null($user_id)) {
            $user_id_q = "NULL";
        } elseif (self::check_id_string($user_id)) {
            $user_id_q = "'".$user_id."'";
        } else {
            $this->print_error("ユーザーIDが異常です。");
            return FALSE;
        }
        
        $code_created = date("Y-m-d H:i:s");
        
        if (is_null($code_expire)) {
            $code_expire_q = "NULL";
        } else {
            if (!preg_match("/\A[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]) ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]\z/u", $code_expire)) {
                $this->print_error("異常な有効期限が指定されました。");
                return FALSE;
            }
            
            if ($code_expire <= $code_created) {
                $this->print_error("有効期限として現在以前の日時を指定することはできません。");
                return FALSE;
            }
            
            $code_expire_q = "'".$code_expire."'";
        }
        
        if (empty($remaining_number)) {
            $remaining_number_q = "NULL";
        } else {
            $remaining_number_q = intval($remaining_number);
            
            if ($remaining_number_q <= 0) {
                $this->print_error("自然数でない数値をコードの使用可能回数として指定することはできません。");
                return FALSE;
            }
        }
        
        $invite_code = self::create_random_code();
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_invite_codes"("invite_code", "is_active", "user_id", "code_created", "code_expire", "remaining_number", "usage_count") VALUES (\''.$invite_code.'\', 1, '.$user_id_q.', \''.$code_created.'\', '.$code_expire_q.', '.$remaining_number_q.', 0)');
        } catch (PDOException $err) {
            $this->print_error("招待コードの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $invite_code;
    }
    
    
    function disable_invite_code ($invite_code = NULL) {
        if (is_null($invite_code)) {
            try {
                $this->profile->db_obj->exec('UPDATE "wakarana_invite_codes" SET "is_active" = 0 WHERE "is_active" = 1');
            } catch (PDOException $err) {
                $this->print_error("招待コードの無効化に失敗しました。".$err->getMessage());
                return FALSE;
            }
        } else {
            $invite_code = strtoupper($invite_code);
            
            try {
                $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_invite_codes" SET "is_active" = 0 WHERE "invite_code" = :invite_code AND "is_active" = 1');
                
                $stmt->bindValue(":invite_code", $invite_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("招待コードの無効化に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            if ($stmt->rowCount() === 0) {
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function disable_expired_invite_codes () {
        try {
            $this->profile->db_obj->exec('UPDATE "wakarana_invite_codes" SET "is_active" = 0 WHERE "is_active" = 1 AND "code_expire" <= \''.date("Y-m-d H:i:s").'\'');
        } catch (PDOException $err) {
            $this->print_error("有効期限切れ招待コードの無効化に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_disabled_invite_codes ($keep_used_invite_codes = TRUE) {
        $this->disable_expired_invite_codes();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_invite_codes" WHERE "is_active" = 0'.($keep_used_invite_codes ? ' AND "usage_count" = 0' : ''));
        } catch (PDOException $err) {
            $this->print_error("無効な招待コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function get_invited_users ($invite_code) {
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "user_id", "password_hash", "user_name", "user_created", "last_updated", "last_access", "status", "totp_key" FROM "wakarana_users" WHERE "used_invite_code" = :used_invite_code ORDER BY "user_created" ASC');
            
            $stmt->bindValue(":used_invite_code", $invite_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("招待コードを使用したユーザーの取得に失敗しました。".$err->getMessage());
            return -1;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = wakarana_user::of($this->profile, $this, $user_info);
        }
        
        return $users;
    }
    
    
    function reset_password ($token, $new_password) {
        $this->rejection_reason = NULL;
        
        $this->delete_password_reset_tokens();
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "user_id" FROM "wakarana_password_reset_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("パスワード再設定用トークンの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user_id = $stmt->fetchColumn();
        
        if ($user_id === FALSE) {
            $this->rejection_reason = "invalid_token";
            return FALSE;
        }
        
        $user = $this->get_user($user_id);
        
        if (empty($user)) {
            $this->print_error("ユーザー情報の取得に失敗しました。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        if ($user->set_password($new_password)) {
            try {
                $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_password_reset_tokens" WHERE "token" = :token');
                
                $stmt->bindValue(":token", $token, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みのパスワード再設定用トークンの削除に失敗しました。".$err->getMessage());
                
                $this->profile->rollback_transaction();
                
                return FALSE;
            }
            
            $this->profile->commit_transaction();
            
            return $user;
        } else {
            $this->rejection_reason = $user->get_rejection_reason();
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
    }
    
    
    function get_password_reset_token_expire ($token) {
        $this->delete_password_reset_tokens();
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "token_created" FROM "wakarana_password_reset_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの情報取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if ($data !== FALSE) {
            return date("Y-m-d H:i:s", strtotime($data) + $this->profile->get_config("password_reset_token_expire"));
        } else {
            return FALSE;
        }
    }
    
    
    function delete_password_reset_tokens ($expire=-1) {
        if ($expire === -1) {
            $expire = $this->profile->get_config("password_reset_token_expire");
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_password_reset_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
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
            $stmt = $this->profile->db_obj->prepare('SELECT "u"."user_id", "u"."password_hash", "u"."user_name", "u"."user_created", "u"."last_updated", "u"."last_access", "u"."status", "u"."totp_key" FROM "wakarana_users" AS "u", "'.$table_name.'" WHERE "'.$table_name.'"."custom_field_name" = \''.$custom_field_name.'\' AND "'.$table_name.'"."custom_field_value" = :custom_field_value AND "u"."user_id" = "'.$table_name.'"."user_id"');
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ユーザーの検索に失敗しました。".$err->getMessage());
            return -1;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = wakarana_user::of($this->profile, $this, $user_info);
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
            $this->profile->db_obj->exec('DELETE FROM "'.$table_name.'" WHERE "custom_field_name" = \''.$custom_field_name.'\'');
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function get_2sv_token_holder ($tmp_token) {
        $this->delete_2sv_tokens();
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "user_id" FROM "wakarana_two_step_verification_tokens" WHERE "token" = :token');
            
            $stmt->bindValue(":token", $tmp_token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("2段階認証用仮トークンの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $user_id = $stmt->fetchColumn();
        
        if ($user_id === FALSE) {
            $this->rejection_reason = "invalid_token";
            return FALSE;
        }
        
        $user = $this->get_user($user_id);
        
        if (empty($user)) {
            $this->print_error("ユーザー情報の取得に失敗しました。");
            return FALSE;
        }
        
        if ($user->get_status() !== self::STATUS_NORMAL) {
            $this->rejection_reason = "unavailable_user";
            return FALSE;
        }
        
        return $user;
    }
    
    
    function delete_2sv_tokens ($expire=-1) {
        if ($expire === -1) {
            $expire = $this->profile->get_config("two_step_verification_token_expire");
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_two_step_verification_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("2段階認証用仮トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function totp_authenticate ($tmp_token, $totp_pin, $ip_address = NULL) {
        $this->rejection_reason = NULL;
        
        if (is_null($ip_address)) {
            $ip_address = $this->get_client_ip_address();
        }
        
        if (!$this->check_auth_allowed($ip_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        $user = $this->get_2sv_token_holder($tmp_token);
        
        if (is_object($user)) {
            if ($user->totp_check($totp_pin)) {
                $user->delete_2sv_token();
                
                $this->add_auth_log($ip_address, $user->get_id(), "totp_authenticate", TRUE);
                
                return $user;
            } else {
                $this->rejection_reason = "pin_not_matched";
                
                $this->add_auth_log($ip_address, $user->get_id(), "totp_authenticate", FALSE, $this->rejection_reason);
            }
        } else {
            $this->rejection_reason = "invalid_token";
            
            $this->add_auth_log($ip_address, NULL, "totp_authenticate", FALSE, $this->rejection_reason);
        }
        
        return FALSE;
    }
    
    
    function totp_login ($tmp_token, $totp_pin) {
        $user = $this->totp_authenticate($tmp_token, $totp_pin);
        
        if (is_object($user)) {
            $user->set_session_token();
        }
        
        return $user;
    }
    
    
    function authenticate_with_recovery_code ($tmp_token, $recovery_code, $ip_address = NULL) {
        $this->rejection_reason = NULL;
        
        if (is_null($ip_address)) {
            $ip_address = $this->get_client_ip_address();
        }
        
        if (!$this->check_auth_allowed($ip_address)) {
            $this->rejection_reason = "currently_locked_out";
            
            return FALSE;
        }
        
        $user = $this->get_2sv_token_holder($tmp_token);
        
        if (is_object($user)) {
            if ($user->check_recovery_code($recovery_code)) {
                $user->delete_2sv_token();
                
                $this->add_auth_log($ip_address, $user->get_id(), "authenticate_with_recovery_code", TRUE);
                
                return $user;
            } else {
                $this->rejection_reason = "code_not_matched";
            }
            
            $this->add_auth_log($ip_address, $user->get_id(), "authenticate_with_recovery_code", FALSE, $this->rejection_reason);
        } else {
            $this->rejection_reason = "invalid_token";
            
            $this->add_auth_log($ip_address, NULL, "authenticate_with_recovery_code", FALSE, $this->rejection_reason);
        }
        
        return FALSE;
    }
    
    
    function login_with_recovery_code ($tmp_token, $recovery_code) {
        $user = $this->authenticate_with_recovery_code($tmp_token, $recovery_code);
        
        if (is_object($user)) {
            $user->set_session_token();
        }
        
        return $user;
    }
    
    
    function check ($token = NULL, $update_last_access = TRUE, $ip_address = NULL) {
        if (empty($token)) {
            if (isset($_COOKIE[$this->profile->get_config("session_token_cookie_name")])) {
                $token = $_COOKIE[$this->profile->get_config("session_token_cookie_name")];
            } else {
                return FALSE;
            }
        }
        
        if (is_null($ip_address)) {
            $ip_address = $this->get_client_ip_address();
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "user_id", "session_id", "ip_address" FROM "wakarana_sessions" WHERE "token" = :token AND "token_created" > \''.date("Y-m-d H:i:s", time() - $this->profile->get_config("session_expire")).'\'');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("セッショントークンの確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $session_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($session_info)) {
            return FALSE;
        }
        
        if ($this->profile->get_config("delete_session_on_ip_address_change") && $ip_address !== $session_info["ip_address"]) {
            $this->delete_session_token($session_info["session_id"]);
            
            return FALSE;
        }
        
        $user = $this->get_user($session_info["user_id"]);
        
        if ($user !== FALSE) {
            if ($update_last_access) {
                $user->update_last_access($session_info["session_id"], $ip_address);
            }
            
            return $user;
        } else {
            return FALSE;
        }
    }
    
    
    function get_session_info ($session_id_or_token = NULL) {
        if (empty($session_id_or_token)) {
            if (isset($_COOKIE[$this->profile->get_config("session_token_cookie_name")])) {
                $session_id_or_token = $_COOKIE[$this->profile->get_config("session_token_cookie_name")];
            } else {
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "session_id", "user_id", "token_created", "ip_address", "operating_system", "browser_name", "last_access" FROM "wakarana_sessions" WHERE "'.(strlen($session_id_or_token) === 16 ? "session_id" : "token").'" = :session_id_or_token');
            
            $stmt->bindValue(":session_id_or_token", $session_id_or_token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("セッション情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    
    function delete_session_token ($session_id_or_token) {
        try {
            $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_sessions" WHERE "'.(strlen($session_id_or_token) === 16 ? "session_id" : "token").'" = :session_id_or_token');
            
            $stmt->bindValue(":session_id_or_token", $session_id_or_token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("セッショントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if ($stmt->rowCount() === 0) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function logout () {
        if (isset($_COOKIE[$this->profile->get_config("session_token_cookie_name")])) {
            $token = $_COOKIE[$this->profile->get_config("session_token_cookie_name")];
        } else {
            return NULL;
        }
        
        if (setcookie($this->profile->get_config("session_token_cookie_name"), "", time() - 1800, "/", $this->profile->get_config("cookie_domain"))) {
            return $this->delete_session_token($token);
        } else {
            $this->print_error("セッショントークンの削除に失敗しました。");
            return FALSE;
        }
    }
    
    
    function delete_one_time_tokens ($expire = -1) {
        if ($expire === -1) {
            $expire = $this->profile->get_config("one_time_token_expire");
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_one_time_tokens" WHERE "token_created" <= \''.date("Y-m-d H:i:s", time() - $expire).'\'');
        } catch (PDOException $err) {
            $this->print_error("ワンタイムトークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function totp_compare ($totp_key, $totp_pin) {
        $pin_expire = $this->profile->get_config("totp_pin_expire") * 2;
        
        for ($cnt = 0; $cnt <= $pin_expire; $cnt++) {
            if ($totp_pin === self::get_totp_pin($totp_key, $cnt)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    static function create_random_code ($code_length = 16) {
        $key_bin = random_bytes(ceil($code_length * 5 / 8));
        
        $random_code = "";
        for ($cnt = 0; $cnt < $code_length; $cnt++) {
            $random_code .= self::BASE32_TABLE[self::bin_to_int($key_bin, $cnt * 5, 5)];
        }
        
        return $random_code;
    }
    
    
    protected static function get_totp_pin ($key_base32, $past_30s = 0) {
        $mac = hash_hmac("sha1", pack("J", floor(time() / 30) - $past_30s), self::base32_decode($key_base32), TRUE);
        
        $bin_code = unpack("N", $mac, self::bin_to_int($mac, 156, 4));
        
        return str_pad((strval($bin_code[1] & 0x7FFFFFFF) % 1000000), 6, "0", STR_PAD_LEFT);
    }
    
    
    function add_user ($user_id, $password, $user_name = "", $status = self::STATUS_NORMAL) { //2027年5月以降のバージョンで削除
        return $this->create_user($user_id, $password, $user_name, $status);
    }
    
    function add_role ($role_id, $role_name, $role_description = "") { //2027年5月以降のバージョンで削除
        return $this->create_role($role_id, $role_name, $role_description);
    }
    
    function add_permission ($resource_id, $permission_name, $permission_description = "") { //2027年5月以降のバージョンで削除
        return $this->create_permission($resource_id, $permission_name, $permission_description);
    }
    
    function add_permitted_value ($permitted_value_id, $permitted_value_name, $permitted_value_description = "") { //2027年5月以降のバージョンで削除
        return $this->create_permitted_value($permitted_value_id, $permitted_value_name, $permitted_value_description);
    }
    
    function count_user () { //2027年6月以降のバージョンで削除
        return $this->count_users();
    }
    
    static function create_random_password ($length = 14) { //2027年6月以降のバージョンで削除
        return self::generate_random_password($length);
    }
    
    function delete_login_tokens ($expire = -1) { //2027年6月以降のバージョンで削除
        return $this->delete_session_tokens($expire);
    }
    
    function delete_login_token ($token) { //2027年6月以降のバージョンで削除
        return $this->delete_session_token($token);
    }
}
