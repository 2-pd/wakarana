<?php
/*Wakarana wakarana_permission.php*/

require_once(__DIR__."/wakarana_data_item.php");


class wakarana_permission extends wakarana_data_item {
    protected $permission_info;
    
    
    function __construct ($wakarana, $permission_info) {
        parent::__construct($wakarana);
        
        $this->permission_info = $permission_info;
    }
    
    
    function get_resource_id () {
        return $this->permission_info["resource_id"];
    }
    
    
    function get_name () {
        return $this->permission_info["permission_name"];
    }
    
    
    function get_description () {
        return $this->permission_info["permission_description"];
    }
    
    
    function set_info ($permission_name = NULL, $permission_description = NULL) {
        if (!is_null($permission_name)) {
            $set_q = '"permission_name" = :permission_name';
            
            if (!is_null($permission_description)) {
                $set_q .= ', "permission_description" = :permission_description';
            }
        } elseif (!is_null($permission_description)) {
            $set_q = '"permission_description" = :permission_description';
        } else {
            return TRUE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_permissions" SET '.$set_q.' WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\'');
            
            if (!is_null($permission_name)) {
                $stmt->bindValue(":permission_name", mb_substr($permission_name, 0, 120), PDO::PARAM_STR);
            }
            if (!is_null($permission_description)) {
                $stmt->bindValue(":permission_description", $permission_description, PDO::PARAM_STR);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("権限情報の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->permission_info["permission_name"] = $permission_name;
        $this->permission_info["permission_description"] = $permission_description;
        
        return TRUE;
    }
    
    
    function get_actions () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "action" FROM "wakarana_permission_actions" WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\' ORDER BY "action" ASC');
        } catch (PDOException $err) {
            $this->print_error("動作一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    
    function add_action ($action) {
        if (!wakarana::check_id_string($action)) {
            $this->print_error("動作識別名に使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $action = strtolower($action);
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_permission_actions"("resource_id", "action") SELECT "resource_id", \''.$action.'\' FROM "wakarana_permissions" WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\' ON CONFLICT ("resource_id", "action") DO NOTHING');
        } catch (PDOException $err) {
            $this->print_error("動作の追加に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $admin_role = $this->wakarana->get_role(WAKARANA_ADMIN_ROLE);
        if ($admin_role->add_permission($this->permission_info["resource_id"], $action)) {
            $this->profile->commit_transaction();
            
            return TRUE;
        } else {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
    }
    
    
    function delete_action ($action = NULL) {
        $parent_permission = $this->get_parent_permission();
        if (!empty($parent_permission)) {
            $actions = $parent_permission->get_actions();
        } else {
            $actions = array();
        }
        
        if (empty($action)) {
            $action_q = '"action" != \'any\'';
            
            if (count($actions) >= 2) {
                $this->print_error("親権限から \"any\" 以外の動作を継承しているため、動作識別名を省略することはできません。");
                return FALSE;
            }
        } else {
            if (!wakarana::check_id_string($action)) {
                $this->print_error("動作識別名に使用できない文字列が指定されました。");
                return FALSE;
            }
            
            $action = strtolower($action);
            
            if ($action === "any") {
                $this->print_error("初期動作 \"any\" を削除することはできません。");
                return FALSE;
            }
            
            if (in_array($action, $actions)) {
                $this->print_error("親権限から継承した動作を削除することはできません。");
                return FALSE;
            }
            
            $action_q = '"action" = \''.$action.'\'';
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_permission_actions" WHERE ("resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\') AND '.$action_q);
            $this->profile->db_obj->exec('DELETE FROM "wakarana_role_permissions" WHERE ("resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\') AND '.$action_q);
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permission_caches" WHERE ("resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\') AND '.$action_q);
        } catch (PDOException $err) {
            $this->print_error("動作の削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function get_parent_permission () {
        $resource_id = wakarana::get_parent_resource_id($this->permission_info["resource_id"]);
        
        if (!empty($resource_id)) {
            return $this->wakarana->get_permission($resource_id);
        } else {
            return NULL;
        }
    }
    
    
    function get_descendant_permissions () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_permissions" WHERE "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\' ORDER BY "resource_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permissions_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $permissions = array();
        foreach ($permissions_info as $permission_info) {
            $permissions[] = $this->wakarana->new_wakarana_permission($permission_info);
        }
        
        return $permissions;
    }
    
    
    function get_roles ($action = "any") {
        if (!wakarana::check_id_string($action)) {
            $this->print_error("動作識別名に使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $action = strtolower($action);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "wakarana_roles".* FROM "wakarana_roles", "wakarana_role_permissions" WHERE "wakarana_role_permissions"."resource_id" = \''.$this->permission_info["resource_id"].'\' AND "wakarana_role_permissions"."action" = \''.$action.'\' AND "wakarana_role_permissions"."role_id" = "wakarana_roles"."role_id" ORDER BY "wakarana_role_permissions"."role_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限を持つロールの一覧取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $roles_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $roles = array();
        foreach ($roles_info as $role_info) {
            $roles[] = $this->wakarana->new_wakarana_role($role_info);
        }
        
        return $roles;
    }
    
    
    function get_users ($action = "any") {
        if (!wakarana::check_id_string($action)) {
            $this->print_error("動作識別名に使用できない文字列が指定されました。");
            return FALSE;
        }
        
        $action = strtolower($action);
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "u"."user_id", "u"."password", "u"."user_name", "u"."user_created", "u"."last_updated", "u"."last_access", "u"."status", "u"."totp_key" FROM "wakarana_users" AS "u", "wakarana_user_permission_caches" WHERE "wakarana_user_permission_caches"."resource_id" = \''.$this->permission_info["resource_id"].'\' AND "wakarana_user_permission_caches"."action" = \''.$action.'\' AND "u"."user_id" = "wakarana_user_permission_caches"."user_id" ORDER BY "wakarana_user_permission_caches"."user_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("権限を持つユーザーの一覧取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $users[] = wakarana_user::of($this->wakarana, $user_info);
        }
        
        return $users;
    }
    
    
    function delete_permission () {
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_permissions" WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_permission_actions" WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_role_permissions" WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permission_caches" WHERE "resource_id" = \''.$this->permission_info["resource_id"].'\' OR "resource_id" LIKE \''.$this->permission_info["resource_id"].'/%\'');
        } catch (PDOException $err) {
            $this->print_error("権限の削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        unset($this->wakarana->resource_ids[$this->permission_info["resource_id"]]);
        
        unset($this->wakarana);
        unset($this->permission_info);
        
        return TRUE;
    }
}