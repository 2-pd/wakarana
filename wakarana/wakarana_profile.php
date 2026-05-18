<?php
/*Wakarana wakarana_profile.php*/

class wakarana_profile {
    protected $base_path;
    
    private $config;
    protected $db_obj;
    private $custom_fields;
    private $email_domain_blacklist;
    
    private $transaction_cnt;
    
    
    function __construct ($base_dir = NULL) {
        if (empty($base_dir)) {
            $this->base_path = __DIR__;
        } else {
            $this->base_path = realpath($base_dir);
            
            if (!is_dir($this->base_path)) {
                throw new Exception("指定されたベースフォルダは存在しません。");
            }
        }
        
        $this->email_domain_blacklist = NULL;
        $this->transaction_cnt = 0;
    }
    
    
    protected function connect_db () {
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
    
    
    protected function disconnect_db () {
        $this->db_obj = NULL;
    }
    
    
    function get_email_domain_blacklist () {
        if (is_null($this->email_domain_blacklist)) {
            $this->email_domain_blacklist = @file($this->base_path."/wakarana_email_domain_blacklist.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        return $this->email_domain_blacklist;
    }
}