<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateController extends Controller
{
    private $conn = "";  
    private $tables = array();    
    private $table_name = "";   
    private $tableInfo = array(); 
    private $tableIndex;

    public function index(){
      $this->conn = $this->getMSAccessConnection();
      $this->createTable();
      //$this->insertData();
    }
    
    public function getMSAccessConnection(){
      $db = realpath("D:/database/em/RProfile.mdb").";";
      $conn = odbc_connect("Driver={Microsoft Access Driver (*.mdb)};Dbq=$db", "", "");      
      return $conn ;
    }
    
    public function createTable() {
      echo "///////////////////<br>";
      echo "//create table//<br>";
      echo "//////////////////<br>";
      $result = odbc_tables($this->conn);
      
      
      while (odbc_fetch_row($result)){
        if (odbc_result($result, 4) == "TABLE"){
          array_push($this->tables, odbc_result($result, "TABLE_NAME") );
        }
      }

      //var_dump($this->tables); exit;
      //odbc_result_all($result); exit;

      $this->tableIndex=0;
      foreach($this->tables as $tb){

        $this->table_name = $this->tis620_to_utf8($tb);
        
        

        if(Schema::hasTable($this->table_name)){
          //Alter table
          echo $this->table_name." is exist.<br>";

        }else{
            Schema::create($this->table_name ,function ($table) {
              //Query get field name
              $table_name = $this->utf8_to_tis620($this->table_name);
              echo "Create table ".$this->table_name ."<br>";
              $query = "SELECT top 1 * FROM ".$table_name;
              $result = odbc_exec($this->conn, $query);               
              $table->increments('key');
              
              //Generate field name
              $fieldIndex=0;
              for($i=1; $i<=odbc_num_fields($result); $i++){ 
                $field_name = $this->tis620_to_utf8(odbc_field_name($result, $i));
                $field_type = odbc_field_type($result, $i);
                

                switch ($field_type) {
                case "COUNTER":
                    $table->integer($field_name);
                    break;
                case "INTEGER":
                    $table->integer($field_name);
                    break;
                case "VARCHAR":
                    $table->string($field_name);
                    break;
                case "LONGCHAR":
                    $table->text($field_name);
                    break;
                case "DATETIME":
                    $table->dateTime($field_name);
                    break;
                case "BIT":
                    $table->boolean($field_name);
                    break;
                default:
                    $table->text($field_name);
                }
                
                $this->tableInfo[$this->tableIndex]["field_name"][$fieldIndex++] = $field_name;
                //$this->tableInfo[$this->tableIndex]["field_type"][$fieldIndex++] = $field_type;
              }
              $table->timestamps();
            });

        }
        
        
        //var_dump($this->tableInfo);

        $this->tableIndex++;
      }//end loop table
    }
    
    public function insertAllData() {
      echo "<br>///////////////////<br>";
      echo "//insert data//<br>";
      echo "//////////////////<br>";
      
      //var_dump($this->tableInfo);
      
      foreach ($this->tableInfo as $key => $value){
        //Count field
        $query = "SELECT count(*) FROM ".$value["tables"];
        $result = odbc_exec($this->conn, $query); 
        $rows =odbc_result($result, 1);
        echo $value["tables"]." : ".$rows."<br>";
               
        //Year([SalesDate]) = Year(Date())

      }

    }
    
    public function insertData() {
      
      echo "<br>///////////////////<br>";
      echo "//insert data//<br>";
      echo "//////////////////<br>";  
      
        //Group year
        $dateField = $this->utf8_to_tis620("วันเดือนปีรับฟ้อง");
        $numberCaseField = $this->utf8_to_tis620("หมายเลขดำที่/พศ");
        $tableCaseOfice = $this->utf8_to_tis620("แผนกรับฟ้อง");
        $queryYears ="SELECT DISTINCT Year(".$dateField.") FROM ".$tableCaseOfice;
        $resultYears = odbc_exec($this->conn, $queryYears); 
        $years = array();
        $j=0;
        while(odbc_fetch_row($resultYears)){
            for($i=1;$i<=odbc_num_fields($resultYears);$i++){
                //echo "Result is ".odbc_result($result_year, $i)."<br>";
              //$years[$j++] = odbc_result($resultYears, $i);
              $temp = odbc_result($resultYears, $i);
              if(!is_null($temp)){
                $years[$j++] = $temp;
              }else{
                $years[$j++] = "0000";
              }
            }
        }

        //Count defendant by year
        $rows = 0;
        $sum = 0;
        
        
        /* //Test select by year
        foreach ($years as $year) {
          if($year == "0000"){
            $queryCountByYear = "SELECT count($numberCaseField) FROM ".$tableCaseOfice." WHERE $dateField is null";
          }else{
            $queryCountByYear = "SELECT count($dateField) FROM ".$tableCaseOfice." WHERE Year(".$dateField.") = ".$year;
          }
          $resultCountByYear = odbc_exec($this->conn, $queryCountByYear); 
          $rows =odbc_result($resultCountByYear, 1); 
          echo "Year ".($year+543)." : ".$rows."<br>";
          $sum = $sum+$rows;
        }
        echo "Summary : ".$sum."<br>";
        */
    }
    
    public function drop() {

      $tables = DB::select('SHOW TABLES');
     
      foreach($tables as $table){
        Schema::dropIfExists($table->Tables_in_coj_em);
        echo "Drop table : ".$table->Tables_in_coj_em."<br>";
      }
      echo "<br>";
      echo "########Drop tables was finished. ##########<br>";
    }
    
    public function utf8_to_tis620($str){
      return iconv("UTF-8", "tis-620", $str);
    }
    
    public function tis620_to_utf8($str){
      return iconv("tis-620", "UTF-8", $str);
    }

}
