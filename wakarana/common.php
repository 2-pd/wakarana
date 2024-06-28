<?php
/*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  Wakarana
*/
    define("WAKARANA_VERSION", "24.06-2");
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
    protected $base_path;
    
    protected $config;
    protected $db_obj;
    protected $custom_fields;
    protected $email_domain_blacklist;
    
    private $last_error_text;
    
    
    function __construct ($base_dir = NULL) {
        if (empty($this->base_path)) {
            $this->update_base_path($base_dir);
        }
        
        $config_path = $this->base_path."/wakarana_config.ini";
        $this->config = @parse_ini_file($config_path);
        
        if (empty($this->config)) {
            $this->print_error("設定ファイル ".$config_path." の読み込みに失敗しました。");
        }
        
        $custom_fields_path = $this->base_path."/wakarana_custom_fields.json";
        if (file_exists($custom_fields_path)) {
            $this->custom_fields = json_decode(file_get_contents($custom_fields_path), TRUE);
            
            if (is_null($this->custom_fields)) {
                $this->print_error("カスタムフィールド設定ファイル ".$custom_fields_path." は破損しています。");
            }
        } else {
            $this->print_error("カスタムフィールド設定ファイル ".$custom_fields_path." が存在しません。");
        }
        
        $this->email_domain_blacklist = NULL;
    }
    
    
    function __get ($name) {
        switch ($name) {
            case "base_path":
                return $this->base_path;
            case "config":
                return $this->config;
            case "db_obj":
                return $this->db_obj;
            case "custom_fields":
                return $this->custom_fields;
        }
    }
    
    
    static function check_id_string ($id, $length = 60) {
        if (gettype($id) === "string" && preg_match("/\A[0-9A-Za-z_]{1,".$length."}\z/u", $id)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    protected function update_base_path ($base_dir) {
        if (empty($base_dir)) {
            $this->base_path = __DIR__;
        } else {
            $this->base_path = realpath($base_dir);
            
            if (!is_dir($this->base_path)) {
                $this->print_error("指定されたベースフォルダは存在しません。");
            }
        }
    }
    
    
    protected function connect_db () {
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj = new PDO("sqlite:".$this->base_path."/".$this->config["sqlite_db_file"]);
                
                $this->db_obj->setAttribute(PDO::ATTR_TIMEOUT, 5);
            } else {
                $this->db_obj = new PDO("pgsql:dbname=".$this->config["pg_db"].";host=".$this->config["pg_host"]." options='--client_encoding=UTF8';port=".$this->config["pg_port"].";user=".$this->config["pg_user"].";password=".$this->config["pg_pass"]);
            }
            
            return TRUE;
        } catch (PDOException $err) {
            $this->print_error("データベース接続に失敗しました。".$err->getMessage());
            
            return FALSE;
        }
    }
    
    
    protected function disconnect_db () {
        $this->db_obj = NULL;
    }
    
    
    function print_error ($error_text) {
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
    
    
    protected function load_email_domain_blacklist () {
        if (is_null($this->email_domain_blacklist)) {
            $this->email_domain_blacklist = file($this->base_path."/wakarana_email_domain_blacklist.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
    }
    
    
    function check_email_domain ($domain_name) {
        $this->load_email_domain_blacklist();
        
        return !in_array(mb_strtolower(trim($domain_name)), $this->email_domain_blacklist);
    }
    
    
    function get_email_domain_blacklist () {
        $this->load_email_domain_blacklist();
        
        return $this->email_domain_blacklist;
    }
}
