<?php
/*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  Wakarana
*/
    define("WAKARANA_VERSION", "26.04-1");
/*
 *_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  LICENSE
 *
 *   このソフトウェアは、無権利創作宣言に基づき著作権放棄されています。
 *   営利・非営利を問わず、自由にご利用いただくことが可能です。
 *
 *    https://www.2pd.jp/license/
 *
*/

class wakarana_common {
    private $last_error_text = NULL;
    
    
    function __construct ($base_dir = NULL) {
        $custom_fields_path = $this->base_path."/wakarana_custom_fields.json";
        if (file_exists($custom_fields_path)) {
            $this->custom_fields = json_decode(file_get_contents($custom_fields_path), TRUE);
            
            if (is_null($this->custom_fields)) {
                $this->print_error("カスタムフィールド設定ファイル ".$custom_fields_path." は破損しています。");
            }
        } else {
            $this->print_error("カスタムフィールド設定ファイル ".$custom_fields_path." が存在しません。");
        }
    }
    
    
    static function check_id_string ($id, $length = 60) {
        if (gettype($id) === "string" && preg_match("/\A[0-9A-Za-z_]{1,".$length."}\z/u", $id)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    protected function print_error ($error_text) {
        $this->last_error_text = $error_text;
        
        if (empty($this->config) || $this->config["display_errors"]) {
            print "An error occurred in Wakarana : ".$error_text;
        }
    }
    
    
    function get_last_error_text () {
        return $this->last_error_text;
    }
    
    
    function get_config_keys () {
        return array_keys($this->config);
    }
    
    
    function get_config_value ($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return NULL;
        }
    }
    
    
    function get_custom_field_names () {
        return array_keys($this->custom_fields);
    }
    
    
    function get_custom_field_is_numeric ($custom_field_name) {
        if (isset($this->custom_fields[$custom_field_name])) {
            return $this->custom_fields[$custom_field_name]["is_numeric"];
        } else {
            return NULL;
        }
    }
    
    
    function get_custom_field_maximum_length ($custom_field_name) {
        if ($this->get_custom_field_is_numeric($custom_field_name) === FALSE) {
            return $this->custom_fields[$custom_field_name]["maximum_length"];
        } else {
            return NULL;
        }
    }
    
    
    function get_custom_field_records_per_user ($custom_field_name) {
        if (isset($this->custom_fields[$custom_field_name])) {
            return $this->custom_fields[$custom_field_name]["records_per_user"];
        } else {
            return NULL;
        }
    }
    
    
    function get_custom_field_allow_nonunique_value ($custom_field_name) {
        if (isset($this->custom_fields[$custom_field_name])) {
            return $this->custom_fields[$custom_field_name]["allow_nonunique_value"];
        } else {
            return NULL;
        }
    }
    
    
    function check_email_domain ($domain_name) {
        $this->load_email_domain_blacklist();
        
        return !in_array(mb_strtolower(trim($domain_name)), $this->email_domain_blacklist);
    }
}
