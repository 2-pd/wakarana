<?php
/*Wakarana-21.11-1 common.php*/

define("WAKARANA_DBMS_SQLITE", "sqlite");
define("WAKARANA_DBMS_POSTGRES", "pgsql");
define("WAKARANA_DBMS_MYSQL", "mysql");

class wakarana_common {
    protected $config;
    protected $db_obj;
    
    private $last_error_text;
    
    function __construct () {
        $this->config = parse_ini_file(dirname(__FILE__)."/config.ini");
    }
    
    function connect_db ($select_db = TRUE) {
        if ($this->config["use_sqlite"]) {
            if (!extension_loaded("sqlite3")) {
                $this->print_error("このPHP実行環境にはSQLite3モジュールがインストールされていない、または、SQLite3モジュールが有効化されていません。");
                return FALSE;
            }
            
            $this->db_obj = new SQLite3(dirname(__FILE__)."/".$this->config["sqlite_db_file"]);
            
            $this->db_obj->busyTimeout(5000);
        } else {
            $this->db_obj = new mysqli($this->config["mysql_server"], $this->config["mysql_user"], $this->config["mysql_pass"]);
            if ($this->db_obj->connect_errno) {
                $this->print_error("データベース接続に失敗しました。".$this->db_obj->connect_error);
                return FALSE;
            }
            
            $this->db_obj->set_charset("utf8");
            
            if ($select_db) {
                $this->db_obj->select_db($this->config["mysql_db"]);
            }
        }
    }
    
    function disconnect_db () {
        $this->db_obj->close();
    }
    
    function print_error ($error_text) {
        $this->last_error_text = $error_text;
        
        if ($this->config["display_errors"]) {
            print "An error occurred in Wakarana : ".$error_text;
        }
    }
    
    function get_last_error_text () {
        return $this->last_error_text;
    }
}