<?php
/*Wakarana wakarana_common.php*/

trait wakarana_common {
    protected $profile;
    
    private $last_error_text = NULL;
    
    
    protected function print_error ($error_text) {
        $this->last_error_text = $error_text;
        
        if (empty($this->profile) || $this->profile->get_config("display_errors")) {
            print "An error occurred in Wakarana : ".$error_text;
        }
    }
    
    
    function get_last_error_text () {
        return $this->last_error_text;
    }
    
    
    static function check_id_string ($id, $length = 60) {
        if (gettype($id) === "string" && preg_match("/\A[0-9A-Za-z_]{1,".$length."}\z/u", $id)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    static function check_password_strength ($password, $min_length = 10) {
        if (strlen($password) >= $min_length && preg_match("/[A-Z]/u", $password) && preg_match("/[a-z]/u", $password) && preg_match("/[0-9]/u", $password)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    static function generate_random_password ($length = 14) {
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
    
    
    function generate_password_hash ($password, $salt = NULL) {
        if ($this->profile->get_config("use_argon2_for_password_hashing")) {
            return password_hash($password, PASSWORD_ARGON2ID, array("memory_cost" => $this->profile->get_config("argon2_memory_cost"), "time_cost" => $this->profile->get_config("argon2_time_cost"), "threads" => $this->profile->get_config("argon2_parallelism")));
        } elseif (!empty($salt)) {
            return hash("sha512", $password.hash("sha512", $salt));
        } else {
            $this->print_error("現在の設定ではハッシュ値の算出にソルト値の指定が必要です。");
            return FALSE;
        }
    }
    
    
    function get_config_value ($key) {
        return $this->profile->get_config($key);
    }
    
    
    function get_custom_field_names () {
        return $this->profile->get_custom_field_names();
    }
    
    
    function get_custom_field_is_numeric ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            return NULL;
        }
        
        return $custom_field_definition["is_numeric"];
    }
    
    
    function get_custom_field_maximum_length ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition) || $custom_field_definition["is_numeric"]) {
            return NULL;
        }
        
        return $custom_field_definition["maximum_length"];
    }
    
    
    function get_custom_field_precision ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition) || !$custom_field_definition["is_numeric"]) {
            return NULL;
        }
        
        return $custom_field_definition["precision"];
    }
    
    
    function get_custom_field_records_per_user ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            return NULL;
        }
        
        return $custom_field_definition["records_per_user"];
    }
    
    
    function get_custom_field_allow_nonunique_value ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            return NULL;
        }
        
        return $custom_field_definition["allow_nonunique_value"];
    }
    
    
    function get_custom_field_trigger_user_last_updated ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            return NULL;
        }
        
        return $custom_field_definition["trigger_user_last_updated"];
    }
    
    
    function check_email_domain ($domain_name) {
        return !in_array(mb_strtolower(trim($domain_name)), $this->profile->get_email_domain_blacklist());
    }
    
    
    function get_email_domain_blacklist () {
        return $this->profile->get_email_domain_blacklist();
    }
}
