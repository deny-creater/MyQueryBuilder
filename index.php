<?php

class MyQueryBuilder
{
	public $sql, $execute_params, $limit_params, $start_where, $start_having;
   
	public function __construct($config) 
    {	
        $dsn = "$config[type]:host=$config[host];dbname=$config[dbname]";
        $this->pdo = new PDO($dsn, $config[user], $config[pass]);
    }


    /* * * * * * * * * * * * *  * * * * * * * * * * * * ** * * * * *  
    * CREATE TABLE Table1 (id int not null, name varchar(30))      *
    *                                                              *
    * $db->createTable('Table1', array(                            *
    *   'id'   => 'int not null',                                  *
    *   'name' => 'varchar(30)'                                    *
    * ));                                                          *
    *                                                              */
    public function create($table, $columns, $options=null)
    {
        $this->sql .= "CREATE TABLE $table ( ";

        foreach($columns as $key => $value)
        {
            $this->sql .= "$key $value, ";
        }

        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= ") $options ";

        return $this;
    }



    /* * * * * * * *  * * * * * * * *  * * * * **  * * * * ** *  * * ** * * ** **
    *  UPDATE Table1                                                            *   
    *  SET x = 1                                                                *
    *  WHERE (id = 1) or (id = 2)                                               *
    *                                                                           *
    *  $db->update('Table1', array('x'=>1), 'id = ? or id = ?', array(1,2));    *
    *                                                                           */    
    public function update($table, $columns, $conditions = '', $params = array())
    {
        $this->sql .= "UPDATE $table SET ";

        foreach($columns as $key => $value)
        {
            $this->sql .= "$key = ?, ";
            $this->execute_params[] = $value;
        }
        
        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= " WHERE ($conditions)";

        foreach($params as $v)
        {
            $this->execute_params[] = $v;
        }
        return $this;
    }   



    /* * * * * * * *  * * * * * * * *  * * * * **  * * * * ** *  * * ** *
    *  INSERT INTO Table1 (id,name) VALUES (5, 'Ульяновск');            *
    *                                                                   *
    *  $db->insert('Table1', array('id'=>'5','name'=>'Ульяновск'));     *
    *                                                                   */                                                          
    public function insert($table, $columns)
    {
        $this->sql .= "INSERT INTO $table (";

        foreach($columns as $key => $value)
        {
            $this->sql .= "$key, ";
            $this->execute_params[] = $value;
        }

        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= ') VALUES (';
        $this->sql .= str_repeat('?, ',count($this->execute_params));    
        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= ') ';
        return $this;
    }



    /* * * * * * *  * * * * * * * *  * * * * **  * * * * ** *  * * ** *
    *   DELETE FROM Table1 WHERE (id = 1) OR (id = 2)                 *
    *                                                                 *
    *   $db->delete('Table1', 'id = ? or id = ?', array(1,2));        *   
    *                                                                 */
    public function delete($table, $conditions = '', $params = array())
    {
        $this->sql .= "DELETE FROM $table WHERE ($conditions) ";

        foreach($params as $v)
        {
            $this->execute_params[] = $v;
        }
        return $this;
    }



    /** * * * * *  * * * * * * *  * * * * * *
    *   SELECT *                            *
    *   SELECT x1,x2                        *
    *                                       *
    *   select()                            *
    *   select('x1,x2')                     *
    *   select(array('x1','x2'))            *
    *                                       */
    public function select($columns = '*')
    {
        if(is_array($columns))
        {
            $this->sql .= 'SELECT '.implode(',',$columns).' '; 
        }
        else
        {   
            $this->sql .= "SELECT $columns ";
        } 
        return $this;
    }



    /* * * * * *  * * * * * * *  * * * * * *
    *   FROM table1,tabel2                 *  
    *                                      *
    *   from('table1,table2')              *
    *   from(array('table1','table2'))     *
    *                                      */     
    public function from($tables)
    {
        if(is_array($tables))
        {
           $this->sql .= 'FROM '.implode(',',$tables).' '; 
        }
        else
        {   
            $this->sql .= "FROM $tables ";
        }   
    	return $this;
    }



    /* * * * * * * * * * * * *  * * * * * * * * * * * *  * * * * * * * *  *  
    *   WHERE (x > 1)                                                     * 
    *   WHERE (x > 5) AND (y IN (5,3,2))                                  *
    *   WHERE (x LIKE 'Ульяновск') OR (x LIKE 'Самара')                   *
    *                                                                     *
    *   ->where('x','>',1);                                               *
    *   ->where('x','>','5)->and_where('y','in',array(5,3,2));            *
    *   ->where('x','like','Ульяновск')->or_where('x','like','Самара');   *
    *                                                                     */
    public function where($x, $condition, $y)
    {   
        if(empty($this->start_where))
        {
            $this->sql .= ' WHERE ';
            $this->start_where = 1;
        }

        switch(strtoupper($condition))
        {
            case 'IN':
                $this->sql .= "($x IN (".str_repeat('?,',count($y));                
                $this->sql = substr($this->sql, 0, -1).'))';
                $this->execute_params = array_merge((array)$this->execute_params,(array)$y); 
                break;

            case 'NOT IN':
                $this->sql .= "($x NOT IN (".str_repeat('?,',count($y));                
                $this->sql = substr($this->sql, 0, -1).'))';
                $this->execute_params = array_merge((array)$this->execute_params,(array)$y); 
                break;

            case 'LIKE':
                $this->sql .= "($x LIKE ?)";
                $this->execute_params[] = $y;
                break;

            case 'NOT LIKE':
                $this->sql .= "($x NOT LIKE ?)";
                $this->execute_params[] = $y;
                break;

            default:
                $this->sql .= "($x $condition ?)";
                $this->execute_params[] = $y;
                break;
        }

        return $this;
    }

    public function and_where($x, $condition, $y)
    {
        $this->sql .= ' AND ';
        $this->where($x,$condition,$y);
        return $this;   
    }

    public function or_where($x, $condition, $y)
    {
        $this->sql .= ' OR ';
        $this->where($x,$condition,$y);
        return $this;   
    }  



    /*  * * * *  * * * * * * * * * * * * *  ** * *
    *   ORDER BY x,z DESC                        *
    *                                            *
    *   orderby('x,z','DESC');                   *
    *   orderby(array('x','z'),'DESC');          *
    *                                            */
    public function orderby($params, $condition)
    {
        if(is_array($params)) 
        {
            $this->sql .= ' ORDER BY '.implode(',', $params).' ';
        }
        else 
        {
            $this->sql .= " ORDER BY $params ";
        }

        if(!empty($condition)) 
        {
            $this->sql .= " $condition ";
        }       
        return $this;
    }



    /* * *  * ** *  * * * * * * * * * * * * * * * 
    *   LIMIT 10,20                             *
    *                                           *
    *   limit(10,20)                            *
    *                                           */
    public function limit($x1, $x2 = null)
    {
        if(!empty($x2))
        {
            $this->sql .= 'LIMIT :limit_1, :limit_2 ';
            $this->limit_params[] = $x1;
            $this->limit_params[] = $x2;  
        }
        else
        {
            $this->sql .= 'LIMIT :limit_1 ';
            $this->limit_params[] = $x1;
        }
        return $this;
    }




    /* * * * * *  ** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *  ** * *  * 
    *   LEFT JOIN table2 ON table1.id = table2.id_student AND table1.x = 'something'                *
    *                                                                                               *
    *   leftJoin('table2', '(table1.id = table2.id_student AND table1.x = ?)', array('something')); *
    *                                                                                               */
    public function addjoin($table, $condition, $params = array())
    {
        $this->sql .= "JOIN $table ON $condition ";

        foreach($params as $v)
        {
            $this->execute_params[] = $v;
        }
    }

    public function innerjoin($table, $condition, $params = array())
    {
        $this->sql .= "INNER ";
        $this->addjoin($table, $condition, $params = array());
        return $this;
    }

    public function leftjoin($table, $condition, $params = array())
    {
        $this->sql .= "LEFT ";
        $this->addjoin($table, $condition, $params = array());
        return $this;
    }

    public function rightjoin($table, $condition, $params = array())
    {
        $this->sql .= "RIGHT ";
        $this->addjoin($table, $condition, $params = array());
        return $this;
    }

    public function crossjoin($table)
    {
        $this->sql .= "CROSS JOIN $table ";
        return $this;
    }

    public function naturaljoin($table)
    {
        $this->sql .= "NATURAL JOIN $table ";
        return $this;
    }




    /*  * * * *  * * * * * * * * * * * * *  ** * *
    *   GROUP BY x,z                             *
    *                                            *
    *   groupby('x, z');                         *
    *   groupby(array('x','z'));                 *
    *                                            */
    public function groupby($columns)
    {
        if(is_array($columns))
        {
            $this->sql .= 'GROUP BY '.implode(',',$columns).' '; 
        }
        else
        {   
            $this->sql .= "GROUP BY $columns ";
        } 
        return $this; 
    }



    /* look to where()
    */
    public function having($x, $condition, $y)
    {
       if(empty($this->start_having))
        {
            $this->sql .= ' HAVING ';
            $this->start_having = 1;
        }

        switch(strtoupper($condition))
        {
            case 'IN':
                $this->sql .= "($x IN (".str_repeat('?,',count($y));                
                $this->sql = substr($this->sql, 0, -1).'))';
                $this->execute_params = array_merge((array)$this->execute_params,(array)$y); 
                break;

            case 'NOT IN':
                $this->sql .= "($x NOT IN (".str_repeat('?,',count($y));                
                $this->sql = substr($this->sql, 0, -1).'))';
                $this->execute_params = array_merge((array)$this->execute_params,(array)$y); 
                break;

            case 'LIKE':
                $this->sql .= "($x LIKE ?)";
                $this->execute_params[] = $y;
                break;

            case 'NOT LIKE':
                $this->sql .= "($x NOT LIKE ?)";
                $this->execute_params[] = $y;
                break;

            default:
                $this->sql .= "($x $condition ?)";
                $this->execute_params[] = $y;
                break;
        }
        return $this; 
    }

    public function and_having($x, $condition, $y)
    {
        $this->sql .= ' AND ';
        $this->having($x,$condition,$y);
        return $this;   
    }

    public function or_having($x, $condition, $y)
    {
        $this->sql .= ' OR ';
        $this->having($x,$condition,$y);
        return $this;   
    } 




    /* * ** * * * * * *  * * * * * * * * *
    * select.. UNION ..select            *
    *                                    *
    * .. ->union('SELECT x FROM table'); *
    *                                    *
    * .. ->union();                      *
    * $db->select(..)                    *
    *                                    */
    public function union($sql = null)
    {
        if(!empty($sql))
        {
            $this->sql .= ' UNION ($sql)'; 
        }
        else
        {
            $this->sql .= ' UNION ';  
        }
        return $this;
    } 



    public function save()
    {    
    	$query = $this->pdo->prepare($this->sql);
        
        /*  Когда PDO работает в режиме эмуляции, все данные, 
            которые были переданы напрямую в execute(), форматируются как строки. 
            То есть, эскейпятся и обрамляются кавычками. 
            Поэтому LIMIT ?,? превращается в LIMIT '10', '10' 
            и очевидным образом вызывает ошибку синтаксиса.
            Поэтому использую bindValue, принудительно выставляя параметрам тип PDO::PARAM_INT.
        */   
        if(!empty($this->limit_params))
        {
            $query->bindValue(':limit_1', $this->limit_params[0], PDO::PARAM_INT);
            if(!empty($this->limit_params[1]))
            {
                $query->bindValue(':limit_2', $this->limit_params[1], PDO::PARAM_INT);
            }      
        }

        $query->execute($this->execute_params);
    }


}   



$config = array(
    'type'=>'mysql',
    'user'=>'root',
    'pass'=>'',
    'host'=>'localhost', 
    'dbname'=>'MyQueryBuilder'
);
