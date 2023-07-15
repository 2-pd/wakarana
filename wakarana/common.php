<?php
/*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  Wakarana
*/
    define("WAKARANA_VERSION", "23.07-1");
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
    
    private $last_error_text;
    
    
    function __construct ($base_dir = NULL) {
        if (empty($base_dir)) {
            $this->base_path = __DIR__;
        } else {
            $this->base_path = realpath($base_dir);
        }
        
        $config_path = $this->base_path."/wakarana_config.ini";
        $this->config = @parse_ini_file($config_path);
        
        if (empty($this->config)) {
            $this->print_error("設定ファイル ".$config_path." の読み込みに失敗しました。");
        }
    }
    
    
    function __get ($name) {
        switch ($name) {
            case "base_path":
                return $this->base_path;
            case "config":
                return $this->config;
            case "db_obj":
                return $this->db_obj;
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
    
    
    protected function print_error ($error_text) {
        $this->last_error_text = $error_text;
        
        if (empty($this->config) || $this->config["display_errors"]) {
            print "An error occurred in Wakarana : ".$error_text;
        }
    }
    
    
    function get_last_error_text () {
        return $this->last_error_text;
    }
}