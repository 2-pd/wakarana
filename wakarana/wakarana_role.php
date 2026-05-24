<?php
/*Wakarana wakarana_role.php*/

require_once(__DIR__."/wakarana_data_item.php");


class wakarana_role extends wakarana_data_item {
    private static $instances = array();
    
    
    protected $role_info;
    
    
    protected function __construct ($wakarana_profile, $wakarana, $role_info) {
        parent::__construct($wakarana_profile, $wakarana);
        
        $this->role_info = $role_info;
    }
    
    
    function __debugInfo () {
        return array("base_path" => $this->profile->get_base_path(), "role_id" => $this->role_info["role_id"], "role_name" => $this->role_info["role_name"]);
    }
    
    
    static function of ($wakarana_profile, $wakarana, $role_info) {
        $base_path = $wakarana_profile->get_base_path();
        
        if (!isset(self::$instances[$base_path])) {
            self::$instances[$base_path] = array();
        }
        
        if (!isset(self::$instances[$base_path][$role_info["role_id"]])) {
            self::$instances[$base_path][$role_info["role_id"]] = new self($wakarana_profile, $wakarana, $role_info);
        }
        
        return self::$instances[$base_path][$role_info["role_id"]];
    }
    
    
    static function free (&$wakarana_role) {
        unset(self::$instances[$wakarana_role->profile->get_base_path()][$wakarana_role->role_info["role_id"]]);
        $wakarana_role = NULL;
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
    
    
    function set_info ($role_name = NULL, $role_description = NULL) {
        if (!is_null($role_name)) {
            $set_q = '"role_name" = :role_name';
            
            if (!is_null($role_description)) {
                $set_q .= ', "role_description" = :role_description';
            }
        } elseif (!is_null($role_description)) {
            $set_q = '"role_description" = :role_description';
        } else {
            return TRUE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_roles" SET '.$set_q.' WHERE "role_id" = \''.$this->role_info["role_id"].'\'');
            
            if (!is_null($role_name)) {
                $stmt->bindValue(":role_name", mb_substr($role_name, 0, 120), PDO::PARAM_STR);
            }
            if (!is_null($role_description)) {
                $stmt->bindValue(":role_description", $role_description, PDO::PARAM_STR);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ロール情報の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->role_info["role_name"] = $role_name;
        $this->role_info["role_description"] = $role_description;
        
        return TRUE;
    }
    
    
    function get_users () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "u"."user_id", "u"."password_hash", "u"."user_name", "u"."user_created", "u"."last_updated", "u"."last_access", "u"."status", "u"."totp_key" FROM "wakarana_users" AS "u", "wakarana_user_roles" WHERE "wakarana_user_roles"."role_id" = \''.$this->role_info["role_id"].'\' AND "u"."user_id" = "wakarana_user_roles"."user_id" ORDER BY "wakarana_user_roles"."user_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロールを持つユーザーの一覧取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = wakarana_user::of($this->profile, $this->wakarana, $user_info);
        }
        
        return $users;
    }
    
    
    function get_permissions ($get_descendant_permissions = TRUE) {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "resource_id", "action" FROM "wakarana_role_permissions" WHERE "role_id" = \''.$this->role_info["role_id"].'\' ORDER BY "resource_id", "action" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロールの権限一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if ($get_descendant_permissions) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
        } else {
            $permissions = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $resource_id_pieces = explode("/", $row["resource_id"]);
                array_pop($resource_id_pieces);
                
                $ancestral_resource_id = "";
                foreach ($resource_id_pieces as $resource_id_piece) {
                    if (!empty($ancestral_resource_id)) {
                        $ancestral_resource_id .= "/";
                    }
                    $ancestral_resource_id .= $resource_id_piece;
                    
                    if (array_key_exists($ancestral_resource_id, $permissions) && in_array($row["action"], $permissions[$ancestral_resource_id])) {
                        continue 2;
                    }
                }
                
                if (!array_key_exists($row["resource_id"], $permissions)) {
                    $permissions[$row["resource_id"]] = array();
                }
                
                $permissions[$row["resource_id"]][] = $row["action"];
            }
            
            return $permissions;
        }
    }
    
    
    function check_permission ($resource_id, $action = "any") {
        if (!wakarana::check_resource_id_string($resource_id) || !wakarana::check_id_string($action)) {
            $this->print_error("識別名として使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $resource_id = strtolower($resource_id);
        $action = strtolower($action);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_role_permissions" WHERE "role_id" = \''.$this->role_info["role_id"].'\' AND "resource_id" = \''.$resource_id.'\' AND "action" = \''.$action.'\' LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("ロールの権限確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function add_permission ($resource_id, $action = "any") {
        if (!wakarana::check_resource_id_string($resource_id) || !wakarana::check_id_string($action)) {
            $this->print_error("識別名として使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $resource_id = strtolower($resource_id);
        $action = strtolower($action);
        
        $permission = $this->wakarana->get_permission($resource_id);
        if (empty($permission) || !in_array($action, $permission->get_actions())) {
            $this->print_error("存在しない権限を割り当てることはできません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_role_permissions"("role_id", "resource_id", "action") SELECT \''.$this->role_info["role_id"].'\', "resource_id", \''.$action.'\' FROM "wakarana_permissions" WHERE "resource_id" = \''.$resource_id.'\' OR "resource_id" LIKE \''.$resource_id.'/%\' ON CONFLICT ("role_id", "resource_id", "action") DO NOTHING');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permission_caches"("user_id", "resource_id", "action") SELECT "wakarana_user_roles"."user_id", "wakarana_role_permissions"."resource_id", \''.$action.'\' FROM "wakarana_user_roles", "wakarana_role_permissions" WHERE "wakarana_user_roles"."role_id" = "wakarana_role_permissions"."role_id" AND ("wakarana_role_permissions"."resource_id" = \''.$resource_id.'\' OR "wakarana_role_permissions"."resource_id" LIKE \''.$resource_id.'/%\') AND "wakarana_role_permissions"."action" = \''.$action.'\' ON CONFLICT ("user_id", "resource_id", "action") DO NOTHING');
        } catch (PDOException $err) {
            $this->print_error("権限の追加に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function remove_permission ($resource_id, $action = "any") {
        if ($this->role_info["role_id"] === WAKARANA_ADMIN_ROLE) {
            $this->print_error("管理者ロールから権限を剥奪することはできません。");
            return FALSE;
        }
        
        if (!wakarana::check_resource_id_string($resource_id)) {
            $this->print_error("権限対象リソースIDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $resource_id = strtolower($resource_id);
        $parent_resource_id = wakarana::get_parent_resource_id($resource_id);
        
        if (empty($action)) {
            if (!empty($parent_resource_id)) {
                $permissions = array_keys($this->get_permissions());
                
                if (in_array($parent_resource_id, $permissions)) {
                    $this->print_error("このロールには親権限が割り当てられているため、子権限を削除できません。");
                    return FALSE;
                }
            }
            
            $action_q = '';
        } else {
            if (!wakarana::check_id_string($action)) {
                $this->print_error("動作識別名に使用できない文字列が指定されました。");
                return FALSE;
            }
            
            $action = strtolower($action);
            
            if (!empty($parent_resource_id) && $this->check_permission($parent_resource_id, $action)) {
                $this->print_error("このロールには親権限が割り当てられているため、子権限を削除できません。");
                return FALSE;
            }
            
            $action_q = ' AND "action" = \''.$action.'\'';
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_role_permissions" WHERE "role_id" = \''.$this->role_info["role_id"].'\' AND ("resource_id" = \''.$resource_id.'\' OR "resource_id" LIKE \''.$resource_id.'%\')'.$action_q);
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permission_caches" WHERE "user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND ("resource_id" = \''.$resource_id.'\' OR "resource_id" LIKE \''.$resource_id.'%\')'.$action_q);
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permission_caches"("user_id", "resource_id", "action") SELECT DISTINCT "wakarana_user_roles"."user_id", "wakarana_role_permissions"."resource_id", "wakarana_role_permissions"."action" FROM "wakarana_user_roles", "wakarana_role_permissions" WHERE "wakarana_user_roles"."user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND "wakarana_role_permissions"."role_id" = "wakarana_user_roles"."role_id" AND ("resource_id" = \''.$resource_id.'\' OR "resource_id" LIKE \''.$resource_id.'%\')'.$action_q);
        } catch (PDOException $err) {
            $this->print_error("ロールからの権限剥奪に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function remove_all_permissions () {
        if ($this->role_info["role_id"] === WAKARANA_ADMIN_ROLE) {
            $this->print_error("管理者ロールから権限を剥奪することはできません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_role_permissions" WHERE "role_id" = \''.$this->role_info["role_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permission_caches" WHERE "user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\')');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permission_caches"("user_id", "resource_id", "action") SELECT DISTINCT "wakarana_user_roles"."user_id", "wakarana_role_permissions"."resource_id", "wakarana_role_permissions"."action" FROM "wakarana_user_roles", "wakarana_role_permissions" WHERE "wakarana_user_roles"."user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND "wakarana_role_permissions"."role_id" = "wakarana_user_roles"."role_id"');
        } catch (PDOException $err) {
            $this->print_error("ロールからの全権限剥奪に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function get_permitted_values () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "permitted_value_id", "permitted_value" FROM "wakarana_role_permitted_values" WHERE "role_id" = \''.$this->role_info["role_id"].'\' ORDER BY "permitted_value_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロールの権限値一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    
    function get_permitted_value ($permitted_value_id) {
        if (!wakarana::check_id_string($permitted_value_id)) {
            $this->print_error("権限値変数IDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $permitted_value_id = strtolower($permitted_value_id);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "permitted_value" FROM "wakarana_role_permitted_values" WHERE "role_id" = \''.$this->role_info["role_id"].'\' AND "permitted_value_id" = \''.$permitted_value_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("ロールの権限値の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permitted_value = $stmt->fetchColumn();
        if ($permitted_value !== FALSE) {
            return $permitted_value;
        } else {
            return NULL;
        }
    }
    
    
    function set_permitted_value ($permitted_value_id, $permitted_value) {
        if (!wakarana::check_id_string($permitted_value_id)) {
            $this->print_error("権限値変数IDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $permitted_value_id = strtolower($permitted_value_id);
        $permitted_value = intval($permitted_value);
        
        $old_permitted_value = $this->get_permitted_value($permitted_value_id);
        
        if (is_null($old_permitted_value) && empty($this->wakarana->get_permitted_value($permitted_value_id))) {
            $this->print_error("存在しない権限値を設定することはできません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_role_permitted_values"("role_id", "permitted_value_id", "permitted_value") VALUES (\''.$this->role_info["role_id"].'\', \''.$permitted_value_id.'\', '.$permitted_value.') ON CONFLICT ("role_id", "permitted_value_id") DO UPDATE SET "permitted_value" = '.$permitted_value);
            
            if (!is_null($old_permitted_value) && $old_permitted_value > $permitted_value) {
                $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permitted_value_caches" WHERE "user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND "permitted_value_id" = \''.$permitted_value_id.'\'');
                $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permitted_value_caches"("user_id", "permitted_value_id", "maximum_permitted_value") SELECT "wakarana_user_roles"."user_id", \''.$permitted_value_id.'\', MAX("wakarana_role_permitted_values"."permitted_value") FROM "wakarana_user_roles", "wakarana_role_permitted_values" WHERE "wakarana_user_roles"."user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND "wakarana_role_permitted_values"."role_id" = "wakarana_user_roles"."role_id" AND "wakarana_role_permitted_values"."permitted_value_id" = \''.$permitted_value_id.'\' GROUP BY "wakarana_user_roles"."user_id"');
            } else {
                $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permitted_value_caches"("user_id", "permitted_value_id", "maximum_permitted_value") SELECT "user_id", \''.$permitted_value_id.'\', '.$permitted_value.' FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\' ON CONFLICT ("user_id", "permitted_value_id") DO UPDATE SET "maximum_permitted_value" = '.$permitted_value.' WHERE "maximum_permitted_value" < '.$permitted_value.'');
            }
        } catch (PDOException $err) {
            $this->print_error("ロールの権限値設定に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function remove_permitted_value ($permitted_value_id = NULL) {
        if (!empty($permitted_value_id)) {
            if (!wakarana::check_id_string($permitted_value_id)) {
                $this->print_error("権限値変数IDに使用できない文字列が指定されました。");
                return FALSE;
            }
            
            $permitted_value_id = strtolower($permitted_value_id);
            
            $permitted_value_id_q = ' AND "permitted_value_id" = \''.$permitted_value_id.'\'';
        } else {
            $permitted_value_id_q = '';
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_role_permitted_values" WHERE "role_id" = \''.$this->role_info["role_id"].'\''.$permitted_value_id_q);
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permitted_value_caches" WHERE "user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\')'.$permitted_value_id_q);
            
            if (!empty($permitted_value_id)) {
                $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permitted_value_caches"("user_id", "permitted_value_id", "maximum_permitted_value") SELECT "wakarana_user_roles"."user_id", \''.$permitted_value_id.'\', MAX("wakarana_role_permitted_values"."permitted_value") FROM "wakarana_user_roles", "wakarana_role_permitted_values" WHERE "wakarana_user_roles"."user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND "wakarana_role_permitted_values"."role_id" = "wakarana_user_roles"."role_id" AND "wakarana_role_permitted_values"."permitted_value_id" = \''.$permitted_value_id.'\' GROUP BY "wakarana_user_roles"."user_id"');
            } else {
                $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permitted_value_caches"("user_id", "permitted_value_id", "maximum_permitted_value") SELECT "wakarana_user_roles"."user_id", "wakarana_role_permitted_values"."permitted_value_id", MAX("wakarana_role_permitted_values"."permitted_value") FROM "wakarana_user_roles", "wakarana_role_permitted_values" WHERE "wakarana_user_roles"."user_id" IN (SELECT "user_id" FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\') AND "wakarana_role_permitted_values"."role_id" = "wakarana_user_roles"."role_id" GROUP BY "wakarana_user_roles"."user_id", "wakarana_role_permitted_values"."permitted_value_id"');
            }
        } catch (PDOException $err) {
            $this->print_error("ロールからの権限値削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function delete_role () {
        if ($this->role_info["role_id"] === WAKARANA_BASE_ROLE || $this->role_info["role_id"] === WAKARANA_ADMIN_ROLE) {
            $this->print_error("初期ロールを削除することはできません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        if (!$this->remove_all_permissions() || !$this->remove_permitted_value()) {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_roles" WHERE "role_id" = \''.$this->role_info["role_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ロールの削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        unset($this->wakarana->role_ids[$this->role_info["role_id"]]);
        
        unset($this->wakarana);
        unset($this->role_info);
        
        return TRUE;
    }
}
