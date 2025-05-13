<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class DB
{
   private $sql;
   private $table;
   private $method;
   private $wheres;

   function __construct()
   {
      $this->sql = [];
      $this->table = '';
      $this->wheres = '';
   }

   public static function conn()
   {
      try {
         $conn = new PDO('mysql:host=' . mysql_host . ';dbname=' . mysql_db . ';charset=utf8', mysql_user, mysql_pass);
         $conn->setAttribute(PDO::ATTR_ERRMODE, $conn::ERRMODE_WARNING);
         return $conn;
      } catch (PDOException $e) {
         return $e->getMessage();
      }
   }

   public function select($colunas = [])
   {
      $sql_query = "SELECT %s FROM `{{table}}` ";

      $this->wheres = '';
      if (!is_array($colunas)) {
         $sql_query = sprintf(
            $sql_query,
            "*"
         );
      } else {
         if (empty($colunas)) {
            $sql_query = sprintf(
               $sql_query,
               '*'
            );
         } else {
            $sql_query = sprintf(
               $sql_query,
               implode(', ', $colunas)
            );
         }
      }

      $this->method = 'select';
      $this->sql = $sql_query;
      return $this;
   }

   function insert($post = [])
   {

      $sql_query = "INSERT INTO `{{table}}` ( %s ) VALUES(%s) ";
      $this->wheres = '';
      $values = [];
      $coluns = [];

      if (isset($_SESSION['admin'])) {
         $post['admin'] = $_SESSION['admin'];
      }

      foreach ($post as $key => $c) {
         $coluns[] = "`$key`";
         $values[] = DB::conn()->quote($c);
      }

      $sql_query = sprintf(
         $sql_query,
         implode(', ', $coluns),
         implode(', ', $values)
      );

      $this->sql = $sql_query;
      $this->method = 'insert';
      return $this;
   }

   /**
    * update
    */
   function update($post = null)
   {
      try {

         if (!$post) throw new Error("Sem dados!");

         $sql_query = "UPDATE `{{table}}` SET %s";
         $this->wheres = '';
         $setStr = [];
         $whereQuery = "";

         foreach ($post as $key => $c) {
            if ($c !== "") {
               $val = DB::conn()->quote($c);
               $setStr[] = "`$key`=$val";
            }
         }

         $sql_query = sprintf(
            $sql_query,
            implode(', ', $setStr),
            $whereQuery
         );

         $this->sql = $sql_query;
         $this->method = 'update';

         return $this;
      } catch (Exception $e) {
         print_r($e);
      }
   }


   public function from($name)
   {
      $this->table = $name;
      $this->sql = str_replace("{{table}}", $this->table, $this->sql);
      return $this;
   }

   /**
    * Monta WHERE
    */
   function where(...$res)
   {
      $whereQuery = '';
      // mount query Where
      $where = $res[0]; // array
      if (!empty($where) && is_array($where)) {
         $whereArray = [];
         foreach ($where as $collumn => $value) :
            $whereArray[] = "`$collumn` = '$value'";
         endforeach;

         $whereQuery .= "AND " . implode(' AND ', $whereArray);

         $this->wheres .= "$whereQuery";
      } else {
         $lastPositionArray = strtoupper($res[(count($res) - 1)]);

         $andOr = $lastPositionArray === 'OR' ? 'OR' : 'AND';

         if ($andOr === 'OR') {
            unset($res[(count($res) - 1)]);
         }

         foreach ($res as $value) :
            if (preg_match("/\-|\s/", $value)) {
               $value = "'" . $value . "'";
            }
            $newRes[] = $value;
         endforeach;

         $whereArray[] = implode(" ", $newRes);

         $this->wheres .= "$andOr " . implode('', $whereArray) . " ";
      }

      return $this;
   }

   /**
    * o mesmo que LIMIT 0,10
    */
   function limit($inicio = 0, $rows = null)
   {
      if ($rows) {
         if ($this->wheres !== '') {
            $this->wheres .= " LIMIT $inicio, $rows";
         } else {
            $this->sql .= " LIMIT $inicio, $rows";
         }
      } else {
         if ($this->wheres !== '') {
            $this->wheres .= " LIMIT $inicio";
         } else {
            $this->sql .= " LIMIT $inicio";
         }
      }
      return $this;
   }

   public static function query($query)
   {
      $sql = DB::conn()->prepare($query);
      $sql->execute();
      $dados = $sql->fetchAll(PDO::FETCH_ASSOC);
      $dados["total"] = $sql->rowCount();
      return $dados;
   }

   /**
    * Executa a query e retorna o ID inserido
    */
   function execute($key = '')
   {
      $sql_str = $this->sql;
      if ($this->wheres !== '') {
         $re = '/^and|^or|^\s/mi';
         $this->wheres = preg_replace($re, ' ', $this->wheres);
         $sql_str = "$this->sql WHERE $this->wheres";
      }

      switch ($this->method):
         case "select":
            $sql = DB::conn()->prepare($sql_str);
            $sql->execute();
            if ($sql->rowCount() > 1) {
               return $sql->fetchAll(PDO::FETCH_ASSOC);
            } else {
               if ($key !== '') {
                  return $sql->fetch(PDO::FETCH_ASSOC)['habilitar_cartao'];
               }
               return $sql->fetch(PDO::FETCH_ASSOC);
            }
            break;
         case "insert":
            try {
               $sql = DB::conn();
               $sql->query($sql_str);
               if ($sql->lastInsertId() > 0) {
                  return $sql->lastInsertId();
               }

               throw new Exception("Não foi possível inserir na base de dados: " . $sql->errorCode());
            } catch (Exception $e) {
               return ["error" => $e->getMessage()];
            }

            break;
         case "update":
            $sql = DB::conn()->prepare($sql_str);
            return $sql->execute() ? true : ["error" => $sql->errorInfo()];
            break;
         default:
            $sql = DB::conn()->prepare($sql_str);
            if ($sql->execute()) {
               return $sql->rowCount();
            } else {
               return $sql->errorInfo();
            }
            break;

      endswitch;
   }

   /**
    * Executa a query e retorna o ID inserido
    */
    function fetch()
    {
       $sql_str = $this->sql;
       if ($this->wheres !== '') {
          $re = '/^and|^or|^\s/mi'; // remove and ou OR passando
          $this->wheres = preg_replace($re, ' ', $this->wheres);
          $sql_str = "$this->sql WHERE $this->wheres";
       }
 
       $sql = DB::conn()->prepare($sql_str);
       $sql->execute();
 
       if ($sql->rowCount() > 0) {
          return $sql->fetch(PDO::FETCH_ASSOC);
       } else {
          return false;
       }
    }

   /**
    * Executa a query e retorna o ID inserido
    */
   function get()
   {
      $sql_str = $this->sql;
      if ($this->wheres !== '') {
         $re = '/^and|^or|^\s/mi'; // remove and ou OR passando
         $this->wheres = preg_replace($re, ' ', $this->wheres);
         $sql_str = "$this->sql WHERE $this->wheres";
      }

      $sql = DB::conn()->prepare($sql_str);
      $sql->execute();

      if ($sql->rowCount() > 0) {
         return $sql->fetchAll(PDO::FETCH_ASSOC);
      } else {
         return false;
      }
   }


   function rowCount()
   {
      $sql_str = $this->sql;
      if ($this->wheres !== '') {
         $re = '/^and|^or|^\s/mi';
         $this->wheres = preg_replace($re, ' ', $this->wheres);
         $sql_str = "$this->sql WHERE $this->wheres";
      }

      $sql = DB::conn()->prepare($sql_str);
      $sql->execute();
      return $sql->rowCount();
   }

   public function getQuery()
   {
      if ($this->wheres !== '') {
         $re = '/^and|^or|^\s/mi';
         $this->wheres = preg_replace($re, '', $this->wheres);
         return "$this->sql WHERE $this->wheres";
      }

      return $this->sql;
   }
}
$conn = new DB();
