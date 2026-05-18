<?php
/*Wakarana wakarana_data_item.php*/

class wakarana_data_item {
    protected $profile;
    
    private $last_error_text = NULL;
    
    
    protected function __construct ($wakarana_profile) {
        $this->profile = $wakarana_profile;
    }
    
    
    protected function print_error ($error_text) {
        $this->last_error_text = $error_text;
        
        if ($this->profile->get_config("display_errors")) {
            print "An error occurred in Wakarana : ".$error_text;
        }
    }
    
    
    function get_last_error_text () {
        return $this->last_error_text;
    }
}
