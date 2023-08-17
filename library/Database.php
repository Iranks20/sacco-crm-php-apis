<?php

class Database extends PDO {

    public function __construct() {
		 parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
	
public function startTransaction(){
	
	return $this->beginTransaction();
}

function InsertLog($query,$table_name,$op_type,$op_status){
$ip = $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP']);
$data_collection = array(
'query_executed'  => $query,
'table_name' => $table_name,
'log_time' => date('Y-m-d H:i:s'),
'user_id' => $_SESSION['user_id'],
'office_id' => $_SESSION['office'],
'op_type' => $op_type,
'op_status' => $op_status,
'ip_address'=>$ip
);
$this->InsertData('tr_system_log', $data_collection);
}


public function InsertData($table,$data,$return_id = false) {
        ksort($data);

        $fieldNames = implode(', ', array_keys($data));
        $fieldInputs = ':' . implode(', :', array_keys($data));
        $sql_statement = "INSERT INTO $table 
                    ($fieldNames)
            VALUES  ($fieldInputs)";
        $sth = $this->prepare($sql_statement);

        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }

        $sth->execute();
        $res = $this->lastInsertId($return_id);
        
 if($table!="tr_system_log"){
     $this->InsertLog($sql_statement,$table,"INSERT",$res);
 }

return $res;
    }

    
public function InsertDataFromFile($table, $filename) {
        $lineseparator = "\n";
        $fieldseparator = ",";
        $fieldenclosing = '"';

        $statement = 'LOAD DATA LOCAL INFILE ' . $this->quote($filename) . ' INTO TABLE ' . $table . '
          FIELDS TERMINATED BY ' . $this->quote($fieldseparator) . ' ENCLOSED BY ' . $this->quote($fieldenclosing) . ' LINES TERMINATED BY ' . $this->quote($lineseparator);
        try {
            $affected_rows = $this->exec($statement);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        return $affected_rows;
    }

public function UpdateData($table, $data, $where) {
        ksort($data);

        $fieldDetails = NULL;
        foreach ($data as $key => $value) {
            $fieldDetails .= "$key = :$key,";
        }

        $fieldDetails = rtrim($fieldDetails, ',');

        $sth = $this->prepare("UPDATE $table SET $fieldDetails WHERE $where");

        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        
        $res = $this->lastInsertId();

        $this->InsertLog(serialize($data).$where,$table,"UPDATE",$res);
        return $sth->execute();
    }

    public function SelectData($sql, $data= array(), $fetchMode = PDO::FETCH_ASSOC) {
        
        $sth = $this->prepare($sql);
        foreach ($data as $key => $value) {
                    $sth->bindValue(":$key", $value);
        }
    
        $sth->execute();
        return $sth->fetchAll();
    }
    
    public function DeleteData($table, $where ){
        $res = $this->lastInsertId();
        $sql = "DELETE FROM $table WHERE $where  ";
        $this->InsertLog($sql,$table,"DELETE",$res);

        return $this->exec($sql);
    }

}

?>
