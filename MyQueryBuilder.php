<?php

class MyQueryBuilder
{
    /**
     * string for composing sql request
     *
     * @var string
     */
	private $sql;

    /**
     * array for saving sql parameters
     *
     * @var array
     */
    private $execute_params;

    /**
     * for saving limit parameter
     *
     * @var int
     */
    private $limit_value;

    /**
     * for saving offset parameter
     *
     * @var int
     */
    private $offset_value;

    /**
     * key of first starting where()
     *
     * @var int
     */
    private $start_where;

    /**
     * key of first starting having()
     *
     * @var int
     */
    private $start_having;
    
    /**
     * Constructor create a connection to a database
     * 
     * @param mixed[] $config Array 
     */
	public function __construct($config) 
    {	
        $dsn = "$config[type]:host=$config[host];dbname=$config[dbname]";
        $this->pdo = new PDO($dsn, $config[user], $config[pass]);
    }

    /**
    * CREATE TABLE
    *
    * @param string $table 
    * @param mixed[] $columns Array
    * @param null|string $options
    *
    * @return $this  
    */
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

    /**
    * UPDATE TABLE
    *
    * @param string $table 
    * @param mixed[] $columns Array
    * @param string|null $conditions
    * @param array $params
    *
    * @return $this  
    */    
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

    /**
    * INSERT INTO
    *
    * @param string $table 
    * @param mixed[] $columns Array
    *
    * @return $this
    */                                                             
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

    /**
    * DELETE FROM
    *
    * @param string $table 
    * @param string|null $conditions
    * @param array $params  
    */    
    public function delete($table, $conditions = '', $params = array())
    {
        $this->sql .= "DELETE FROM $table WHERE ($conditions) ";

        foreach($params as $v)
        {
            $this->execute_params[] = $v;
        }
        return $this;
    }

    /**
    * SELECT
    *
    * @param array|string|null $columns
    *
    * @return $this 
    */    
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

    /**
    * FROM
    *
    * @param array|string $tables
    *
    * @return $this  
    */       
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

    /**
    * WHERE
    *
    * @param string $x 
    * @param string $condition
    * @param string|array|int $y
    *
    * @return $this
    */    
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

    /**
    * ORDER BY
    *
    * @param string|array $params
    * @param string|null $condition
    *
    * @return $this
    */
    public function orderby($params, $condition=null)
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

    /**
    * LIMIT
    *
    * @param int $x
    *
    * @return $this
    */
    public function limit($x)
    {
        $this->sql .= 'LIMIT :limit';
        $this->limit_value = $x; 
        return $this;
    }

    /**
    * OFFSET
    *
    * @param int $y
    *
    * @return $this
    */
    public function offset($y)
    {
        $this->sql .= ' OFFSET :offset';
        $this->offset_value = $y;    
        return $this;
    }

    /**
    * INNER/LEFT/RIGHT JOIN
    *
    * @param string $table
    * @param string $condition
    * @param array|null $params
    *
    * @return $this
    */
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

    /**
    * CROSS JOIN
    *
    * @param string $table
    *
    * @return $this
    */
    public function crossjoin($table)
    {
        $this->sql .= "CROSS JOIN $table ";
        return $this;
    }

    /**
    * NATURAL JOIN
    *
    * @param string $table
    *
    * @return $this
    */
    public function naturaljoin($table)
    {
        $this->sql .= "NATURAL JOIN $table ";
        return $this;
    }

    /**
    * GROUP BY
    *
    * @param string|array $columns
    *
    * @return $this
    */
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

    /**
    * HAVING
    *
    * @param string $x 
    * @param string $condition
    * @param string|array|int $y
    *
    * @return $this
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

    /**
    * UNION
    *
    * @param string|null $sql
    *
    * @return $this 
    */ 
    public function union($sql = null)
    {
        if(!empty($sql))
        {
            $this->sql .= ' UNION ($sql)'; 
        }
        else
        {
            $this->start_where = null;
            $this->start_having = null;
            $this->sql .= ' UNION ';  
        }
        return $this;
    } 

    /**
    * Prepare and execute sql request
    *
    * @return $this 
    */ 
    public function execute()
    {    
    	$query = $this->pdo->prepare($this->sql);
               
        if(!empty($this->limit_value))
            $query->bindValue(':limit', $this->limit_value, PDO::PARAM_INT);

        if(!empty($this->offset_value))
            $query->bindValue(':offset', $this->offset_value, PDO::PARAM_INT);       

        $query->execute($this->execute_params);

        return $this;
    }

    /**
    * Reset all parameters and clear sql line
    */ 
    public function reset()
    {
        $this->sql = '';
        $this->limit_value = null;
        $this->offset_value = null;
        $this->execute_params = null;
        $this->start_where = null;
        $this->start_having = null;
        return $this;
    }
}
