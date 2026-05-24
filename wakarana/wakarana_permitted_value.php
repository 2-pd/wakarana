<?php
/*Wakarana wakarana_permitted_value.php*/

require_once(__DIR__."/wakarana_data_item.php");


class wakarana_permitted_value extends wakarana_data_item {
    private static $instances = array();
    
    
    protected $permitted_value_info;
    
    
    protected function __construct ($wakarana_profile, $wakarana, $permitted_value_info) {
        parent::__construct($wakarana_profile, $wakarana);
        
        $this->permitted_value_info = $permitted_value_info;
    }
    
    
    function __debugInfo () {
        return array("base_path" => $this->profile->get_base_path(), "permitted_value_id" => $this->permitted_value_info["permitted_value_id"], "permitted_value_name" => $this->permitted_value_info["permitted_value_name"]);
    }
    
    
    static function of ($wakarana_profile, $wakarana, $permitted_value_info) {
        $base_path = $wakarana_profile->get_base_path();
        
        if (!isset(self::$instances[$base_path])) {
            self::$instances[$base_path] = array();
        }
        
        if (!isset(self::$instances[$base_path][$permitted_value_info["permitted_value_id"]])) {
            self::$instances[$base_path][$permitted_value_info["permitted_value_id"]] = new self($wakarana_profile, $wakarana, $permitted_value_info);
        }
        
        return self::$instances[$base_path][$permitted_value_info["permitted_value_id"]];
    }
    
    
    static function free (&$wakarana_permitted_value) {
        unset(self::$instances[$wakarana_permitted_value->profile->get_base_path()][$wakarana_permitted_value->permitted_value_info["permitted_value_id"]]);
        $wakarana_permitted_value = NULL;
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
    
    
    function set_info ($permitted_value_name = NULL, $permitted_value_description = NULL) {
        if (!is_null($permitted_value_name)) {
            $set_q = '"permitted_value_name" = :permitted_value_name';
            
            if (!is_null($permitted_value_description)) {
                $set_q .= ', "permitted_value_description" = :permitted_value_description';
            }
        } elseif (!is_null($permitted_value_description)) {
            $set_q = '"permitted_value_description" = :permitted_value_description';
        } else {
            return TRUE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_permitted_values" SET '.$set_q.' WHERE "permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\'');
            
            if (!is_null($permitted_value_name)) {
                $stmt->bindValue(":permitted_value_name", mb_substr($permitted_value_name, 0, 120), PDO::PARAM_STR);
            }
            if (!is_null($permitted_value_description)) {
                $stmt->bindValue(":permitted_value_description", $permitted_value_description, PDO::PARAM_STR);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("権限値情報の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->permitted_value_info["permitted_value_name"] = $permitted_value_name;
        $this->permitted_value_info["permitted_value_description"] = $permitted_value_description;
        
        return TRUE;
    }
    
    
    function get_roles ($min = NULL, $max = NULL) {
        if (is_null($min)) {
            $min_q = "";
        } else {
            $min_q = 'AND "wakarana_role_permitted_values"."permitted_value" >= '.intval($min).' ';
        }
        
        if (is_null($max)) {
            $max_q = "";
        } else {
            $max_q = 'AND "wakarana_role_permitted_values"."permitted_value" <= '.intval($max).' ';
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "wakarana_roles".*, "wakarana_role_permitted_values"."permitted_value" FROM "wakarana_roles", "wakarana_role_permitted_values" WHERE "wakarana_role_permitted_values"."permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\' '.$min_q.$max_q.'AND "wakarana_role_permitted_values"."role_id" = "wakarana_roles"."role_id" ORDER BY "wakarana_role_permitted_values"."permitted_value" DESC');
        } catch (PDOException $err) {
            $this->print_error("権限値を持つロールの一覧取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $roles_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $roles = array();
        foreach ($roles_info as $role_info) {
            $role_data = array("permitted_value" => $role_info["permitted_value"]);
            
            unset($role_info["permitted_value"]);
            $role_data["role"] = wakarana_role::of($this->profile, $this->wakarana, $role_info);
            
            $roles[] = $role_data;
        }
        
        return $roles;
    }
    
    
    function get_users ($min = NULL, $max = NULL) {
        if (is_null($min)) {
            $min_q = "";
        } else {
            $min_q = 'AND "wakarana_user_permitted_value_caches"."maximum_permitted_value" >= '.intval($min).' ';
        }
        
        if (is_null($max)) {
            $max_q = "";
        } else {
            $max_q = 'AND "wakarana_user_permitted_value_caches"."maximum_permitted_value" <= '.intval($max).' ';
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "u"."user_id", "u"."password_hash", "u"."user_name", "u"."user_created", "u"."last_updated", "u"."last_access", "u"."status", "u"."totp_key", "wakarana_user_permitted_value_caches"."maximum_permitted_value" FROM "wakarana_users" AS "u", "wakarana_user_permitted_value_caches" WHERE "wakarana_user_permitted_value_caches"."permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\' '.$min_q.$max_q.'AND "wakarana_user_permitted_value_caches"."user_id" = "u"."user_id" ORDER BY "wakarana_user_permitted_value_caches"."maximum_permitted_value" DESC');
        } catch (PDOException $err) {
            $this->print_error("権限値を持つユーザーの一覧取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $users_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = array();
        foreach ($users_info as $user_info) {
            $user_data = array("permitted_value" => $user_info["maximum_permitted_value"]);
            
            unset($user_info["maximum_permitted_value"]);
            $user_data["user"] = wakarana_user::of($this->profile, $this->wakarana, $user_info);
            $users[] = $user_data;
        }
        
        return $users;
    }
    
    
    function delete_permitted_value () {
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_permitted_values" WHERE "permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_role_permitted_values" WHERE "permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permitted_value_caches" WHERE "permitted_value_id" = \''.$this->permitted_value_info["permitted_value_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("権限値の削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        unset($this->wakarana->permitted_value_ids[$this->permitted_value_info["permitted_value_id"]]);
        
        unset($this->wakarana);
        unset($this->permitted_value_info);
        
        return TRUE;
    }
}
