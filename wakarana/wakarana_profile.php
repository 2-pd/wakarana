<?php
/*Wakarana wakarana_profile.php*/

class wakarana_profile {
    private static $instances = array();
    
    private $base_path;
    
    private $config;
    public $db_obj;
    private $custom_fields;
    private $email_domain_blacklist;
    
    private $transaction_cnt;
    
    
    private function __construct ($base_path) {
        $this->base_path = $base_path;
        
        $this->load_config($base_path."/wakarana_config.ini");
        $this->load_custom_field_definitions($base_path."/wakarana_custom_fields.json");
        
        $this->email_domain_blacklist = NULL;
        $this->transaction_cnt = 0;
    }
    
    
    static function of ($base_dir = NULL) {
        if (empty($base_dir)) {
            $base_path = __DIR__;
        } else {
            $base_path = realpath($base_dir);
            
            if (!is_dir($base_path)) {
                throw new Exception("指定されたベースフォルダは存在しません。");
            }
        }
        
        if (!array_key_exists($base_path, self::$instances)) {
            self::$instances[$base_path] = new self($base_path);
        }
        
        return self::$instances[$base_path];
    }
    
    
    function get_base_path () {
        return $this->base_path;
    }
    
    
    function load_config ($config_path) {
        $this->config = @parse_ini_file($config_path, FALSE, INI_SCANNER_TYPED);
        
        if (empty($this->config)) {
            throw new Exception("設定ファイル ".$config_path." の読み込みに失敗しました。");
        }
        
        return TRUE;
    }
    
    
    function get_config ($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return NULL;
        }
    }
    
    
    function set_config ($key, $value) {
        if (isset($this->config[$key])) {
            $this->config[$key] = $value;
            
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    function connect_db () {
        try {
            if ($this->config["use_sqlite"]) {
                $this->db_obj = new PDO("sqlite:".$this->base_path."/".$this->config["sqlite_db_file"]);
                
                $this->db_obj->setAttribute(PDO::ATTR_TIMEOUT, 5);
            } else {
                $this->db_obj = new PDO("pgsql:dbname=".$this->config["pg_db"].";host=".$this->config["pg_host"]." options='--client_encoding=UTF8';port=".$this->config["pg_port"].";user=".$this->config["pg_user"].";password=".$this->config["pg_pass"]);
            }
        } catch (PDOException $err) {
            throw new Exception("データベース接続に失敗しました。".$err->getMessage());
        }
        
        return TRUE;
    }
    
    
    function begin_transaction () {
        try {
            if ($this->transaction_cnt === 0) {
                $this->db_obj->exec("BEGIN");
            } else {
                $this->db_obj->exec("SAVEPOINT sp_".($this->transaction_cnt + 1));
            }
        } catch (PDOException $err) {
            throw new Exception("トランザクションの開始に失敗しました。".$err->getMessage());
        }
        
        $this->transaction_cnt++;
        
        return TRUE;
    }
    
    
    function commit_transaction () {
        try {
            if ($this->transaction_cnt === 1) {
                $this->db_obj->exec("COMMIT");
            } else {
                $this->db_obj->exec("RELEASE SAVEPOINT sp_".$this->transaction_cnt);
            }
        } catch (PDOException $err) {
            throw new Exception("トランザクションの完了に失敗しました。".$err->getMessage());
        }
        
        $this->transaction_cnt--;
        
        return TRUE;
    }
    
    
    function rollback_transaction () {
        try {
            if ($this->transaction_cnt === 1) {
                $this->db_obj->exec("ROLLBACK");
            } else {
                $this->db_obj->exec("ROLLBACK TO SAVEPOINT sp_".$this->transaction_cnt);
            }
        } catch (PDOException $err) {
            throw new Exception("トランザクションの取り消しに失敗しました。".$err->getMessage());
        }
        
        $this->transaction_cnt--;
        
        return TRUE;
    }
    
    
    function disconnect_db () {
        $this->db_obj = NULL;
        
        return TRUE;
    }
    
    
    function load_custom_field_definitions ($custom_fields_file_path) {
        if (file_exists($custom_fields_file_path)) {
            $this->custom_fields = @json_decode(file_get_contents($custom_fields_file_path), TRUE);
            
            if (is_null($this->custom_fields)) {
                throw new Exception("カスタムフィールド設定ファイル ".$custom_fields_file_path." は破損しています。");
            }
        } else {
            throw new Exception("カスタムフィールド設定ファイル ".$custom_fields_file_path." が存在しません。");
        }
    }
    
    
    function get_custom_field_names () {
        return array_keys($this->custom_fields);
    }
    
    
    function get_custom_field_definition ($custom_field_name = NULL) {
        if (is_null($custom_field_name)) {
            return $this->custom_fields;
        } elseif (isset($this->custom_fields[$custom_field_name])) {
            return $this->custom_fields[$custom_field_name];
        } else {
            return NULL;
        }
    }
    
    
    function set_custom_field_definition ($custom_field_name, $custom_field_definition) {
        if (!is_null($custom_field_definition)) {
            $this->custom_fields[$custom_field_name] = $custom_field_definition;
        } elseif (isset($this->custom_fields[$custom_field_name])) {
            unset($this->custom_fields[$custom_field_name]);
        } else {
            return NULL;
        }
        
        return TRUE;
    }
    
    
    function get_email_domain_blacklist () {
        if (is_null($this->email_domain_blacklist)) {
            $this->email_domain_blacklist = @file($this->base_path."/wakarana_email_domain_blacklist.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        return $this->email_domain_blacklist;
    }
    
    
    function add_email_domain_to_blacklist ($damain_name) {
        $this->email_domain_blacklist[] = mb_strtolower($damain_name);
        
        return TRUE;
    }
    
    
    function remove_email_domain_from_blacklist ($list_index) {
        $before_processing_blacklist_count = count($this->email_domain_blacklist);
        
        if ($list_index >= $before_processing_blacklist_count) {
            return FALSE;
        }
        
        array_splice($this->email_domain_blacklist, $list_index, 1);
        
        if (count($this->email_domain_blacklist) === $before_processing_blacklist_count) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    function set_email_domain_blacklist ($email_domain_blacklist) {
        $this->email_domain_blacklist = $email_domain_blacklist;
        
        return TRUE;
    }
}
