<?php
/*Wakarana wakarana_user.php*/

require_once(dirname(__FILE__)."/wakarana_data_item.php");


class wakarana_user extends wakarana_data_item {
    protected $user_info;
    protected $rejection_reason = NULL;
    
    
    function __construct ($wakarana, $user_info) {
        parent::__construct($wakarana);
        
        $this->user_info = $user_info;
    }
    
    
    function __debugInfo () {
        return array("base_path" => $this->profile->base_path, "user_info" => $this->user_info);
    }
    
    
    static function free (&$wakarana_user) {
        unset($wakarana_user->wakarana->user_ids[$wakarana_user->user_info["user_id"]]);
        unset($wakarana_user);
    }
    
    
    function get_rejection_reason () {
        return $this->rejection_reason;
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
            $stmt = $this->profile->db_obj->query('SELECT "email_address" FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "is_primary" = TRUE');
        } catch (PDOException $err) {
            $this->print_error("プライマリメールアドレスの取得に失敗しました。".$err->getMessage());
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
            $stmt = $this->profile->db_obj->query('SELECT "email_address" FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "email_address" ASC');
        } catch (PDOException $err) {
            $this->print_error("メールアドレスの取得に失敗しました。".$err->getMessage());
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
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($custom_field_definition["records_per_user"] !== 1) {
            $this->print_error("指定されたカスタムフィールドは単一値ではありません。");
            return FALSE;
        }
        
        if ($custom_field_definition["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "custom_field_value" FROM "'.$table_name.'" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\'');
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の取得に失敗しました。".$err->getMessage());
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
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($custom_field_definition["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "custom_field_value" FROM "'.$table_name.'" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' ORDER BY "value_number" ASC');
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    
    function set_password ($password) {
        $this->rejection_reason = NULL;
        
        if (!$this->profile->get_config("allow_weak_password") && !wakarana::check_password_strength($password)) {
            $this->rejection_reason = "weak_password";
            return FALSE;
        }
        
        $password_hash = wakarana::hash_password($this->user_info["user_id"], $password);
        
        try {
            $this->profile->db_obj->exec('UPDATE "wakarana_users" SET "password" = \''.$password_hash.'\', "last_updated" = \''.date("Y-m-d H:i:s").'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("パスワードの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["password"] = $password_hash;
        
        return TRUE;
    }
    
    
    function set_name ($user_name) {
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_users" SET "user_name" = :user_name, "last_updated" = \''.date("Y-m-d H:i:s").'\' WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            if (!empty($user_name)) {
                $stmt->bindValue(":user_name", mb_substr($user_name, 0, 240), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":user_name", NULL, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ユーザー名の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["user_name"] = $user_name;
        
        return TRUE;
    }
    
    
    function add_email_address ($email_address) {
        $this->rejection_reason = NULL;
        
        $email_addresses_count = count($this->get_email_addresses());
        
        if ($email_addresses_count >= $this->profile->get_config("email_addresses_per_user")) {
            $this->rejection_reason = "registration_limit_over";
            return FALSE;
        }
        
        if (!$this->wakarana->check_email_address($email_address)) {
            $this->rejection_reason = $this->wakarana->get_rejection_reason();
            return FALSE;
        }
        
        if (!$this->profile->get_config("allow_nonunique_email_address") && !empty($this->wakarana->search_users_with_email_address($email_address))) {
            $this->rejection_reason = "email_address_already_exists";
            return FALSE;
        }
        
        if ($email_addresses_count === 0) {
            $is_primary_q = "TRUE";
        } else {
            $is_primary_q = "FALSE";
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_user_email_addresses"("user_id", "email_address", "is_primary") VALUES (\''.$this->user_info["user_id"].'\', :email_address, '.$is_primary_q.')');
            
            $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレスの変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function set_primary_email_address ($email_address) {
        if (!in_array($email_address, $this->get_email_addresses())) {
            $this->print_error("未登録のメールアドレスをプライマリメールアドレスに設定することはできません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('UPDATE "wakarana_user_email_addresses" SET "is_primary" = FALSE  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_user_email_addresses" SET "is_primary" = TRUE WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "email_address" = :email_address');
            
            $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("プライマリメールアドレスの変更に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function replace_primary_email_address ($email_address) {
        if ($this->profile->get_config("email_addresses_per_user") !== 1) {
            $this->print_error("各ユーザーが複数のメールアドレスを登録可能な設定ではこの関数を使用できません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        if ($this->remove_all_email_addresses() && $this->add_email_address($email_address)) {
            $this->profile->commit_transaction();
            
            return TRUE;
        } else {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
    }
    
    
    function remove_email_address ($email_address) {
        if ($this->get_primary_email_address() === $email_address) {
            $this->print_error("この関数ではプライマリメールアドレスを削除することはできません。");
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "email_address" = :email_address');
            
            $stmt->bindValue(":email_address", mb_substr($email_address, 0, 254), PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレスの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function remove_all_email_addresses () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_email_addresses" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの全メールアドレスの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function set_status ($status) {
        $status = intval($status);
        
        $this->profile->begin_transaction();
        
        if ($status !== WAKARANA_STATUS_NORMAL) {
            $this->delete_session_tokens();
        }
        
        try {
            $this->profile->db_obj->exec('UPDATE "wakarana_users" SET "status" = \''.$status.'\', "last_updated" = \''.date("Y-m-d H:i:s").'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーアカウントの状態の変更に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        $this->user_info["status"] = $status;
        
        return TRUE;
    }
    
    
    function enable_2_factor_auth ($totp_key = NULL) {
        if (empty($totp_key)) {
            $totp_key = wakarana::create_random_code();
        } elseif (preg_match("/\A[A-Z2-7]{16}\z/", $totp_key) !== 1) {
            $this->print_error("TOTP生成鍵が不正です。");
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_users" SET "totp_key" = :totp_key WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt->bindValue(":totp_key", $totp_key, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("2要素認証の有効化に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["totp_key"] = $totp_key;
        
        return $totp_key;
    }
    
    
    function disable_2_factor_auth () {
        try {
            $this->profile->db_obj->exec('UPDATE "wakarana_users" SET "totp_key" = NULL WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("2要素認証の無効化に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["totp_key"] = NULL;
        
        return TRUE;
    }
    
    
    function generate_recovery_codes ($code_count = 10) {
        if (!is_numeric($code_count) || $code_count < 1) {
            $this->print_error("リカバリコードの生成個数が自然数ではありません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        $this->delete_recovery_codes();
        
        $recovery_codes = array();
        try {
            for ($cnt = 0; $cnt < $code_count; $cnt++) {
                $recovery_codes[] = wakarana::create_random_code(24);
                
                $this->profile->db_obj->exec('INSERT INTO "wakarana_recovery_codes"("user_id", "recovery_code") VALUES (\''.$this->user_info["user_id"].'\', \''.$recovery_codes[$cnt].'\')');
            }
        } catch (PDOException $err) {
            $this->print_error("新しいリカバリコードの生成に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return $recovery_codes;
    }
    
    
    function delete_recovery_codes () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_recovery_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("リカバリコードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function set_value ($custom_field_name, $custom_field_value) {
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($custom_field_definition["records_per_user"] !== 1) {
            $this->print_error("指定されたカスタムフィールドは単一値ではありません。");
            return FALSE;
        }
        
        if ($custom_field_definition["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
            
            if (!is_numeric($custom_field_value)) {
                $this->print_error("数値型のカスタムフィールドに格納できない値が指定されました。");
                return FALSE;
            }
        } else {
            $table_name = "wakarana_user_custom_fields";
            
            $custom_field_value = mb_substr($custom_field_value, 0, $custom_field_definition["maximum_length"]);
        }
        
        if (!$custom_field_definition["allow_nonunique_value"]) {
            $other_users = $this->wakarana->search_users_with_custom_field($custom_field_name, $custom_field_value);
            
            if (!empty($other_users) && $other_users[0]->get_id() !== $this->get_id()) {
                $this->print_error("他のユーザーに割り当て済みの値です。指定されたカスタムフィールドでは複数のアカウントに同じ値を設定することは許可されていません。");
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "'.$table_name.'"("user_id", "custom_field_name", "value_number", "custom_field_value") VALUES (\''.$this->user_info["user_id"].'\', \''.$custom_field_name.'\', 1, :custom_field_value) ON CONFLICT("user_id", "custom_field_name", "value_number") DO UPDATE SET "custom_field_value" = :custom_field_value_2');
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            $stmt->bindValue(":custom_field_value_2", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の設定に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function add_value ($custom_field_name, $custom_field_value, $value_number = -1) {
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        $value_count = count($this->get_values($custom_field_name));
        
        if ($value_count >= $custom_field_definition["records_per_user"]) {
            $this->print_error("指定されたカスタムフィールドにはこれ以上項目を追加できません。");
            return FALSE;
        }
        
        if ($value_number === -1) {
            $value_number = $value_count + 1;
        } elseif ($value_number <= $value_count + 1) {
            $value_number = intval($value_number);
        } else {
            $this->print_error("並び順番号として使用可能な数値は既存の項目数に1を加えた値以下です。");
            return FALSE;
        }
        
        if ($custom_field_definition["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
            
            if (!is_numeric($custom_field_value)) {
                $this->print_error("数値型のカスタムフィールドに格納できない値が指定されました。");
                return FALSE;
            }
        } else {
            $table_name = "wakarana_user_custom_fields";
            
            $custom_field_value = mb_substr($custom_field_value, 0, $custom_field_definition["maximum_length"]);
        }
        
        if (!$custom_field_definition["allow_nonunique_value"] && !empty($this->wakarana->search_users_with_custom_field($custom_field_name, $custom_field_value))) {
            $this->print_error("使用できない値です。指定されたカスタムフィールドでは複数のアカウントに同じ値を設定することは許可されていません。");
            return FALSE;
        }
        
        $this->profile->begin_transaction();
        
        try {
            if ($value_number <= $value_count) {
                $this->profile->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" + '.$custom_field_definition["records_per_user"].' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" >= '.$value_number);
                $this->profile->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" - '.($custom_field_definition["records_per_user"] - 1).' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" >= '.$custom_field_definition["records_per_user"]);
            }
            
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "'.$table_name.'"("user_id", "custom_field_name", "value_number", "custom_field_value") VALUES (\''.$this->user_info["user_id"].'\', \''.$custom_field_name.'\', '.$value_number.', :custom_field_value)');
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の追加に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function update_value ($custom_field_name, $value_number, $custom_field_value) {
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($custom_field_definition["is_numeric"]) {
            $table_name = "wakarana_user_custom_numerical_fields";
        } else {
            $table_name = "wakarana_user_custom_fields";
            
            $custom_field_value = mb_substr($custom_field_value, 0, $custom_field_definition["maximum_length"]);
        }
        
        if (!$custom_field_definition["allow_nonunique_value"]) {
            $other_users = $this->wakarana->search_users_with_custom_field($custom_field_name, $custom_field_value);
            
            if (!empty($other_users) && $other_users[0]->get_id() !== $this->get_id()) {
                $this->print_error("他のユーザーに割り当て済みの値です。指定されたカスタムフィールドでは複数のアカウントに同じ値を設定することは許可されていません。");
                return FALSE;
            }
        }
        
        try {
            $stmt = $this->profile->db_obj->prepare('UPDATE "'.$table_name.'" SET "custom_field_value" = :custom_field_value WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" = '.intval($value_number));
            
            $stmt->bindValue(":custom_field_value", $custom_field_value);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function increment_value ($custom_field_name, $increments = 1) {
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if (!$custom_field_definition["is_numeric"]) {
            $this->print_error("指定されたカスタムフィールドは数値型ではありません。");
            return FALSE;
        }
        
        if ($custom_field_definition["records_per_user"] !== 1) {
            $this->print_error("指定されたカスタムフィールドは単一値ではありません。");
            return FALSE;
        }
        
        if (!$custom_field_definition["allow_nonunique_value"]) {
            $this->print_error("複数のアカウントに同一の値を割り当てできないカスタムフィールドが指定されました。");
            return FALSE;
        }
        
        $increments = intval($increments);
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_custom_numerical_fields"("user_id", "custom_field_name", "value_number", "custom_field_value") VALUES (\''.$this->user_info["user_id"].'\', \''.$custom_field_name.'\', 1, \''.$increments.'\') ON CONFLICT("user_id", "custom_field_name", "value_number") DO UPDATE SET "custom_field_value" = "custom_field_value" + '.$increments.'');
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の変更に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }
    
    
    function delete_value ($custom_field_name, $value_number = NULL) {
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        if ($custom_field_definition["is_numeric"]) {
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
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "'.$table_name.'" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\''.$value_number_q);
            
            if (!is_null($value_number)) {
                $this->profile->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" + '.$custom_field_definition["records_per_user"].' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" > '.$value_number);
                $this->profile->db_obj->exec('UPDATE "'.$table_name.'" SET "value_number" = "value_number" - '.($custom_field_definition["records_per_user"] + 1).' WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "custom_field_name" = \''.$custom_field_name.'\' AND "value_number" >= '.$custom_field_definition["records_per_user"]);
            }
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function remove_value ($custom_field_name, $custom_field_value) {
        if (!wakarana::check_id_string($custom_field_name)) {
            $this->print_error("カスタムフィールド名が不正です。");
            return FALSE;
        }
        
        if (empty($this->profile->get_custom_field_definition($custom_field_name))) {
            $this->print_error("指定されたカスタムフィールドは存在しません。");
            return FALSE;
        }
        
        $index = array_search($custom_field_value, $this->get_values($custom_field_name));
        
        if ($index === FALSE) {
            $this->print_error("指定されたカスタムフィールド値は存在しません。");
            return FALSE;
        }
        
        $value_number = $index + 1;
        
        return $this->delete_value($custom_field_name, $value_number);
    }
    
    
    function delete_all_values () {
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_custom_fields" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_custom_numerical_fields" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("カスタムフィールド値の削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function get_roles () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "wakarana_roles".* FROM "wakarana_roles", "wakarana_user_roles" WHERE "wakarana_user_roles"."user_id" = \''.$this->user_info["user_id"].'\' AND "wakarana_roles"."role_id" = "wakarana_user_roles"."role_id" ORDER BY "wakarana_user_roles"."role_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("ロールの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $roles_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $roles = array();
        foreach ($roles_info as $role_info) {
            $roles[] = $this->wakarana->new_wakarana_role($role_info);
        }
        
        return $roles;
    }
    
    
    function add_role ($role_id) {
        $role = $this->wakarana->get_role($role_id);
        
        if (!is_object($role)) {
            $this->print_error("正しいロールIDではありません。");
            return FALSE;
        }
        
        $role_id = $role->get_id();
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_roles"("user_id", "role_id") VALUES (\''.$this->user_info["user_id"].'\', \''.$role_id.'\') ON CONFLICT ("user_id", "role_id") DO NOTHING');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permission_caches"("user_id", "resource_id", "action") SELECT \''.$this->user_info["user_id"].'\', "resource_id", "action" FROM "wakarana_role_permissions" WHERE "role_id" = \''.$role_id.'\' ON CONFLICT ("user_id", "resource_id", "action") DO NOTHING');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permitted_value_caches"("user_id", "permitted_value_id", "maximum_permitted_value") SELECT \''.$this->user_info["user_id"].'\', "permitted_value_id", "permitted_value" FROM "wakarana_role_permitted_values" WHERE "role_id" = \''.$role_id.'\' ON CONFLICT("user_id", "permitted_value_id") DO UPDATE SET "maximum_permitted_value" = (SELECT MAX("wakarana_role_permitted_values"."permitted_value") FROM "wakarana_user_roles", "wakarana_role_permitted_values" WHERE "wakarana_user_roles"."user_id" = \''.$this->user_info["user_id"].'\' AND "wakarana_role_permitted_values"."role_id" = "wakarana_user_roles"."role_id" AND "wakarana_role_permitted_values"."permitted_value_id" = EXCLUDED."permitted_value_id" GROUP BY "wakarana_role_permitted_values"."permitted_value_id")');
        } catch (PDOException $err) {
            $this->print_error("ロールの付与に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function remove_role ($role_id = NULL) {
        if (!empty($role_id)) {
            if (!wakarana::check_id_string($role_id)) {
                $this->print_error("ロールIDに使用できない文字列が指定されました。");
                return FALSE;
            }
            
            $role_id = strtolower($role_id);
            
            if ($role_id === WAKARANA_BASE_ROLE) {
                $this->print_error("ベースロールを剥奪することはできません。");
                return FALSE;
            }
            
            $role_id_q = '"role_id" = \''.$role_id.'\'';
        } else {
            $role_id_q = '"role_id" != \''.WAKARANA_BASE_ROLE.'\'';
        }
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_roles" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND '.$role_id_q);
            
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permission_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permission_caches"("user_id", "resource_id", "action") SELECT DISTINCT \''.$this->user_info["user_id"].'\', "wakarana_role_permissions"."resource_id", "wakarana_role_permissions"."action" FROM "wakarana_user_roles", "wakarana_role_permissions" WHERE "wakarana_user_roles"."user_id" = \''.$this->user_info["user_id"].'\' AND "wakarana_role_permissions"."role_id" = "wakarana_user_roles"."role_id"');
            
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permitted_value_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->profile->db_obj->exec('INSERT INTO "wakarana_user_permitted_value_caches"("user_id", "permitted_value_id", "maximum_permitted_value") SELECT \''.$this->user_info["user_id"].'\', "wakarana_role_permitted_values"."permitted_value_id", MAX("wakarana_role_permitted_values"."permitted_value") FROM "wakarana_user_roles", "wakarana_role_permitted_values" WHERE "wakarana_user_roles"."user_id" = \''.$this->user_info["user_id"].'\' AND  "wakarana_role_permitted_values"."role_id" = "wakarana_user_roles"."role_id" GROUP BY "wakarana_role_permitted_values"."permitted_value_id"');
        } catch (PDOException $err) {
            $this->print_error("ロールの剥奪に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return TRUE;
    }
    
    
    function check_permission ($resource_id, $action = "any") {
        if (!wakarana::check_resource_id_string($resource_id) || !wakarana::check_id_string($action)) {
            $this->print_error("識別名として使用できない文字列が指定されました。");
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_user_permission_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "resource_id" = \''.strtolower($resource_id).'\' AND "action" = \''.strtolower($action).'\' LIMIT 1');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの権限確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function get_permissions ($get_descendant_permissions = TRUE) {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "resource_id", "action" FROM "wakarana_user_permission_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "resource_id", "action" ASC');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの権限一覧の取得に失敗しました。".$err->getMessage());
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
    
    
    function get_permitted_value ($permitted_value_id) {
        if (!wakarana::check_id_string($permitted_value_id)) {
            $this->print_error("権限値変数IDに使用できない文字列が指定されました。");
            return FALSE;
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT "maximum_permitted_value" FROM "wakarana_user_permitted_value_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "permitted_value_id" = \''.$permitted_value_id.'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの権限値取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $permitted_value = $stmt->fetchColumn();
        
        if ($permitted_value !== FALSE) {
            return $permitted_value;
        } else {
            return NULL;
        }
    }
    
    
    function get_permitted_values () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "permitted_value_id", "maximum_permitted_value" FROM "wakarana_user_permitted_value_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "permitted_value_id" ASC');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの権限値一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    
    function delete_all_tokens () {
        $this->profile->begin_transaction();
        
        if ($this->delete_session_tokens() && $this->delete_one_time_tokens() && $this->delete_email_address_verification_code() && $this->disable_invite_code() && $this->delete_password_reset_token() && $this->delete_2sv_token()) {
            $this->profile->commit_transaction();
            
            return TRUE;
        } else {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
    }
    
    
    function get_auth_logs () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "succeeded", "authenticate_datetime", "ip_address" FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "authenticate_datetime" DESC');
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
    }


    function check_auth_interval ($unsucceeded_only = FALSE) {
        if ($unsucceeded_only) {
            $succeeded_q = ' AND "succeeded" = FALSE';
        } else {
            $succeeded_q = '';
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT 1 FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "authenticate_datetime" >= \''.date("Y-m-d H:i:s", time() - $this->profile->get_config("minimum_authenticate_interval")).'\''.$succeeded_q." LIMIT 1");
            
            if (empty($stmt->fetchColumn())) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (PDOException $err) {
            $this->print_error("認証試行間隔の確認に失敗しました。".$err->getMessage());
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
            $this->profile->db_obj->exec('DELETE FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "authenticate_datetime" NOT IN (SELECT "authenticate_datetime" FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "authenticate_datetime" DESC LIMIT '.($this->profile->get_config("authenticate_logs_per_user") - 1).')');
            
            $this->profile->db_obj->exec('INSERT INTO "wakarana_authenticate_logs"("user_id", "succeeded", "authenticate_datetime", "ip_address") VALUES (\''.$this->user_info["user_id"].'\', '.$succeeded_q.', \''.(new DateTime())->format("Y-m-d H:i:s.u").'\', \''.$this->wakarana->get_client_ip_address().'\')');
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの追加に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_auth_logs () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_authenticate_logs" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("認証試行ログの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function update_last_access ($session_id = NULL, $ip_address = NULL) {
        $last_access = date("Y-m-d H:i:s");
        
        try {
            $this->profile->db_obj->exec('UPDATE "wakarana_users" SET "last_access" = \''.$last_access.'\'  WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの最終アクセス情報更新に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $this->user_info["last_access"] = $last_access;
        
        if (!empty($session_id)) {
            try {
                if (empty($ip_address)) {
                    $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_sessions" SET "last_access" = \''.$last_access.'\' WHERE "session_id" = :session_id');
                } else {
                    $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_sessions" SET "last_access" = \''.$last_access.'\', "ip_address" = :ip_address WHERE "session_id" = :session_id');
                    
                    $stmt->bindValue(":ip_address", $ip_address, PDO::PARAM_STR);
                }
                
                $stmt->bindValue(":session_id", $session_id, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("セッショントークンの最終アクセス情報更新に失敗しました。".$err->getMessage());
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    function get_sessions () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "session_id", "token_created", "ip_address", "operating_system", "browser_name", "last_access" FROM "wakarana_sessions" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "last_access" DESC');
        } catch (PDOException $err) {
            $this->print_error("セッション情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    function create_session_token ($ip_address = NULL, $ua = NULL) {
        $this->wakarana->delete_session_tokens();
        
        $session_id = wakarana::generate_unique_id();
        $token = wakarana::create_token();
        $token_created = date("Y-m-d H:i:s");
        
        if (is_null($ip_address)) {
            $ip_address = $this->wakarana->get_client_ip_address();
        }
        
        $client_env = wakarana::get_client_environment($ua);
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_sessions" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "token" NOT IN (SELECT "token" FROM "wakarana_sessions" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "token_created" DESC LIMIT '.($this->profile->get_config("sessions_per_user") - 1).')');
            
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_sessions"("session_id", "token", "user_id", "token_created", "ip_address", "operating_system", "browser_name", "last_access") VALUES (\''.$session_id.'\', \''.$token.'\', \''.$this->user_info["user_id"].'\', \''.$token_created.'\', \''.$ip_address.'\', :operating_system, :browser_name, \''.$token_created.'\')');
            
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
            $this->print_error("セッショントークンの保存に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        if ($this->update_last_access()) {
            $this->profile->commit_transaction();
            
            return $token;
        } else {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
    }
    
    
    function set_session_token () {
        $token = $this->create_session_token();
        
        if (!empty($token) && setcookie($this->profile->get_config("session_token_cookie_name"), $token, time() + $this->profile->get_config("session_expire"), "/", $this->profile->get_config("cookie_domain"), FALSE, TRUE)) {
            return TRUE;
        } else {
            $this->print_error("セッショントークンの送信に失敗しました。");
            return FALSE;
        }
    }
    
    
    function delete_session_token ($session_id) {
        try {
            $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_sessions" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "session_id" = :session_id');
            
            $stmt->bindValue(":session_id", $session_id, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("指定されたセッショントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function delete_session_tokens () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_sessions" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーのセッショントークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function authenticate ($password, $ip_address = NULL) {
        $this->rejection_reason = NULL;
        
        if (is_null($ip_address)) {
            $ip_address = $this->wakarana->get_client_ip_address();
        }
        
        if (!($this->wakarana->check_client_auth_interval($ip_address) && $this->check_auth_interval())) {
            $this->rejection_reason = "currently_locked_out";
            $this->add_auth_log(FALSE);
            return FALSE;
        }
        
        if ($this->check_password($password)) {
            if ($this->get_status() !== WAKARANA_STATUS_NORMAL) {
                $this->rejection_reason = "unavailable_user";
                $this->add_auth_log(FALSE);
                return FALSE;
            }
            
            if ($this->get_totp_enabled()) {
                $this->add_auth_log(TRUE);
                return $this->create_2sv_token();
            } else {
                $this->add_auth_log(TRUE);
                return TRUE;
            }
        }
        
        $this->rejection_reason = "parameters_not_matched";
        $this->add_auth_log(FALSE);
        return FALSE;
    }
    
    
    function create_email_address_verification_code ($email_address, $check_registration_limit = TRUE) {
        $this->rejection_reason = NULL;
        
        if ($check_registration_limit && count($this->get_email_addresses()) >= $this->profile->get_config("email_addresses_per_user")) {
            $this->rejection_reason = "registration_limit_over";
            return FALSE;
        }
        
        if (!$this->wakarana->check_email_address($email_address)) {
            $this->rejection_reason = $this->wakarana->get_rejection_reason();
            return FALSE;
        }
        
        if (!$this->profile->get_config("allow_nonunique_email_address") && !empty($this->wakarana->search_users_with_email_address($email_address))) {
            $this->rejection_reason = "email_address_already_exists";
            return FALSE;
        }
        
        $this->wakarana->delete_email_address_verification_codes();
        
        if (!$this->wakarana->check_email_sending_interval($email_address)) {
            $this->rejection_reason = "currently_locked_out";
            return FALSE;
        }
        
        $verification_code = wakarana::create_random_code(8);
        
        $code_created = date("Y-m-d H:i:s");
        $ip_address = $this->wakarana->get_client_ip_address();
        
        try {
            $stmt = $this->profile->db_obj->prepare('INSERT INTO "wakarana_email_address_verification_codes"("user_id", "email_address", "verification_code", "code_created", "ip_address") VALUES (\''.$this->user_info["user_id"].'\', :email_address, \''.$verification_code.'\', \''.$code_created.'\', \''.$ip_address.'\') ON CONFLICT("user_id") DO UPDATE SET "email_address" = :email_address_2, "verification_code" = \''.$verification_code.'\', "code_created" = \''.$code_created.'\', "ip_address" = \''.$ip_address.'\'');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":email_address_2", $email_address, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $verification_code;
    }
    
    
    function email_address_verify ($email_address, $verification_code, $verification_only = FALSE) {
        $this->rejection_reason = NULL;
        
        if (!$this->wakarana->check_email_address($email_address)) {
            $this->rejection_reason = $this->wakarana->get_rejection_reason();
            return FALSE;
        }
        
        if (!$this->profile->get_config("allow_nonunique_email_address") && !empty($this->wakarana->search_users_with_email_address($email_address))) {
            $this->rejection_reason = "email_address_already_exists";
            return FALSE;
        }
        
        $this->wakarana->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT 1 FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" = \''.$this->user_info["user_id"].'\' LIMIT 1');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの認証に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            try {
                $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" = \''.$this->user_info["user_id"].'\'');
                
                $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
                $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みのメールアドレス確認コードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            if (!$verification_only) {
                return $this->add_email_address($email_address);
            } else {
                return TRUE;
            }
        } else {
            $this->rejection_reason = "parameters_not_matched";
            return FALSE;
        }
    }
    
    
    function verify_and_replace_primary_email_address ($email_address, $verification_code) {
        if ($this->profile->get_config("email_addresses_per_user") !== 1) {
            $this->print_error("各ユーザーが複数のメールアドレスを登録可能な設定ではこの関数を使用できません。");
            return FALSE;
        }
        
        if ($this->email_address_verify($email_address, $verification_code, TRUE)) {
            return $this->replace_primary_email_address($email_address);
        } else {
            return FALSE;
        }
    }
    
    
    function get_email_address_verification_code_expire ($email_address, $verification_code) {
        $this->wakarana->delete_email_address_verification_codes();
        
        $verification_code = strtoupper($verification_code);
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT "code_created" FROM "wakarana_email_address_verification_codes" WHERE "email_address" = :email_address AND "verification_code" = :verification_code AND "user_id" = \''.$this->user_info["user_id"].'\'');
            
            $stmt->bindValue(":email_address", $email_address, PDO::PARAM_STR);
            $stmt->bindValue(":verification_code", $verification_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("メールアドレス確認コードの情報取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $data = $stmt->fetchColumn();
        
        if ($data !== FALSE) {
            return date("Y-m-d H:i:s", strtotime($data) + $this->profile->get_config("verification_email_expire"));
        } else {
            return FALSE;
        }
    }
    
    
    function delete_email_address_verification_code () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_email_address_verification_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーのメールアドレス確認コードの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function get_used_invite_code () {
        try {
            $stmt = $this->profile->db_obj->query('SELECT "wakarana_invite_codes".* FROM "wakarana_users", "wakarana_invite_codes" WHERE "wakarana_users"."user_id" = \''.$this->user_info["user_id"].'\' AND "wakarana_invite_codes"."invite_code" = "wakarana_users"."used_invite_code"');
        } catch (PDOException $err) {
            $this->print_error("ユーザーアカウント作成時に使用された招待コードの情報の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        $invite_code_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return empty($invite_code_info) ? NULL : $invite_code_info;
    }
    
    
    function create_invite_code ($code_expire = NULL, $remaining_number = NULL) {
        return $this->wakarana->create_invite_code($code_expire, $remaining_number, $this->user_info["user_id"]);
    }
    
    
    function get_invite_codes ($is_active = NULL) {
        $this->wakarana->disable_expired_invite_codes();
        
        if (is_null($is_active)) {
            $is_active_q = '';
        } else {
            $is_active_q = ' AND "is_active" = '.($is_active ? '1' : '0');
        }
        
        try {
            $stmt = $this->profile->db_obj->query('SELECT * FROM "wakarana_invite_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\''.$is_active_q.' ORDER BY "code_created" ASC');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの招待コード一覧の取得に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    function disable_invite_code ($invite_code = NULL, $delete_user_id = FALSE) {
        $delete_user_id_q = $delete_user_id ? ', "user_id" = NULL' : '';
        
        if (is_null($invite_code)) {
            try {
                $this->profile->db_obj->exec('UPDATE "wakarana_invite_codes" SET "is_active" = 0'.$delete_user_id_q.' WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            } catch (PDOException $err) {
                $this->print_error("招待コードの無効化に失敗しました。".$err->getMessage());
                return FALSE;
            }
        } else {
            $invite_code = strtoupper($invite_code);
            
            try {
                $stmt = $this->profile->db_obj->prepare('UPDATE "wakarana_invite_codes" SET "is_active" = 0'.$delete_user_id_q.' WHERE "invite_code" = :invite_code AND "user_id" = \''.$this->user_info["user_id"].'\' AND "is_active" = 1');
                
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
    
    
    function create_password_reset_token () {
        $this->wakarana->delete_password_reset_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_password_reset_tokens"("token", "user_id", "token_created") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.$token_created.'\') ON CONFLICT("user_id") DO UPDATE SET "token" = \''.$token.'\', "token_created"=\''.$token_created.'\'');
        } catch (PDOException $err) {
            $this->print_error("パスワード再設定用トークンの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $token;
    }
    
    
    function delete_password_reset_token () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_password_reset_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーのパスワード再設定用トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_2sv_token () {
        $this->wakarana->delete_2sv_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        try {
            $this->profile->db_obj->exec('INSERT INTO "wakarana_two_step_verification_tokens"("token", "user_id", "token_created") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.$token_created.'\') ON CONFLICT("user_id") DO UPDATE SET "token" = \''.$token.'\', "token_created"=\''.$token_created.'\'');
        } catch (PDOException $err) {
            $this->print_error("2段階認証用仮トークンの生成に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return $token;
    }
    
    
    function delete_2sv_token () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_two_step_verification_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの2段階認証用仮トークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function create_one_time_token () {
        $this->wakarana->delete_one_time_tokens();
        
        $token = wakarana::create_token();
        
        $token_created = date("Y-m-d H:i:s");
        
        $this->profile->begin_transaction();
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_one_time_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "token" NOT IN (SELECT "token" FROM "wakarana_one_time_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\' ORDER BY "token_created" DESC LIMIT '.($this->profile->get_config("one_time_tokens_per_user") - 1).')');
            
            $this->profile->db_obj->exec('INSERT INTO "wakarana_one_time_tokens"("token", "user_id", "token_created") VALUES (\''.$token.'\', \''.$this->user_info["user_id"].'\', \''.date("Y-m-d H:i:s").'\')');
        } catch (PDOException $err) {
            $this->print_error("ワンタイムトークンの生成に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        return $token;
    }
    
    
    function check_one_time_token ($token) {
        $this->wakarana->delete_one_time_tokens();
        
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT 1 FROM "wakarana_one_time_tokens" WHERE "token" = :token AND "user_id" = \''.$this->user_info["user_id"].'\' LIMIT 1');
            
            $stmt->bindValue(":token", $token, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("ワンタイムトークンの確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            try {
                $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_one_time_tokens" WHERE "token" = :token');
                
                $stmt->bindValue(":token", $token, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みワンタイムトークンの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function delete_one_time_tokens () {
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_one_time_tokens" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーのワンタイムトークンの削除に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function totp_check ($totp_pin) {
        if ($this->get_totp_enabled()) {
            return $this->wakarana->totp_compare($this->user_info["totp_key"], $totp_pin);
        } else {
            return FALSE;
        }
    }
    
    
    function check_recovery_code ($recovery_code) {
        try {
            $stmt = $this->profile->db_obj->prepare('SELECT 1 FROM "wakarana_recovery_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "recovery_code" = :recovery_code LIMIT 1');
            
            $stmt->bindValue(":recovery_code", $recovery_code, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $err) {
            $this->print_error("リカバリコードの確認に失敗しました。".$err->getMessage());
            return FALSE;
        }
        
        if (!empty($stmt->fetchColumn())) {
            try {
                $stmt = $this->profile->db_obj->prepare('DELETE FROM "wakarana_recovery_codes" WHERE "user_id" = \''.$this->user_info["user_id"].'\' AND "recovery_code" = :recovery_code');
                
                $stmt->bindValue(":recovery_code", $recovery_code, PDO::PARAM_STR);
                
                $stmt->execute();
            } catch (PDOException $err) {
                $this->print_error("使用済みリカバリコードの削除に失敗しました。".$err->getMessage());
                return FALSE;
            }
            
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function delete_user () {
        $this->profile->begin_transaction();
        
        if (!$this->delete_all_tokens() || !$this->remove_all_email_addresses() || !$this->delete_all_values() || !$this->delete_auth_logs() || !$this->delete_recovery_codes()) {
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        try {
            $this->profile->db_obj->exec('DELETE FROM "wakarana_users" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_roles" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permission_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
            $this->profile->db_obj->exec('DELETE FROM "wakarana_user_permitted_value_caches" WHERE "user_id" = \''.$this->user_info["user_id"].'\'');
        } catch (PDOException $err) {
            $this->print_error("ユーザーの削除に失敗しました。".$err->getMessage());
            
            $this->profile->rollback_transaction();
            
            return FALSE;
        }
        
        $this->profile->commit_transaction();
        
        unset($this->wakarana->user_ids[$this->user_info["user_id"]]);
        
        unset($this->wakarana);
        unset($this->user_info);
        
        return TRUE;
    }
    
    
    function create_login_token () { //2027年6月以降のバージョンで削除
        return $this->create_session_token();
    }
    
    function set_login_token () { //2027年6月以降のバージョンで削除
        return $this->set_session_token();
    }
    
    function delete_login_tokens () { //2027年6月以降のバージョンで削除
        return $this->delete_session_tokens();
    }
}