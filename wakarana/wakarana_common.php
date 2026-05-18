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
        
        return $this->custom_field_definition["maximum_length"];
    }
    
    
    function get_custom_field_records_per_user ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            return NULL;
        }
        
        return $this->custom_fields[$custom_field_name]["records_per_user"];
    }
    
    
    function get_custom_field_allow_nonunique_value ($custom_field_name) {
        $custom_field_definition = $this->profile->get_custom_field_definition($custom_field_name);
        
        if (empty($custom_field_definition)) {
            return NULL;
        }
        
        return $this->custom_fields[$custom_field_name]["allow_nonunique_value"];
    }
    
    
    function check_email_domain ($domain_name) {
        return !in_array(mb_strtolower(trim($domain_name)), $this->profile->get_email_domain_blacklist());
    }
}
