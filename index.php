<?php

$config = array(
	'type'=>'mysql',
	'user'=>'root',
	'pass'=>'',
	'host'=>'localhost', 
	'dbname'=>'MyQueryBuilder',
	'charset'=>'charset=utf8'
);

class MyQueryBuilder extends PDO
{
	public $sql;
	private $where, $limit, $insert, $update_columns, $update_params;
    public $in = array('in','IN', 'not in', 'NOT IN');
    public $like = array('like','LIKE', 'or like', 'OR LIKE', 'NOT LIKE', 'not like', 'OR NOT LIKE','or not like');

	public function __construct($config) 
    {	
    	parent::__construct($config['type'].':host='.$config['host'].';dbname='.$config['dbname'].';'.$config['charset'], $config['user'], $config['pass']);
    }

    public function create($table, $columns, $options=null)
    {
        $this->sql .= "CREATE TABLE `$table` ( ";

        foreach($columns as $key => $value)
        {
            $this->sql .= "`$key` $value, ";
        }

        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= ") $options ";

        return $this;
    }

     public function update($table, $columns, $conditions = '', $params = array())
    {
        $this->sql .= "UPDATE `$table` SET ";

        foreach($columns as $key => $value)
        {
            $this->sql .= "`$key`= ?, ";
            $this->update_columns[] = $value;
        }
        
        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= " WHERE ($conditions)";

        foreach($params as $v)
        {
            $this->update_params[] = $v;
        }
        return $this;
    }

    public function insert($table, $columns)
    {
        $this->sql .= "INSERT INTO `$table` (";

        foreach($columns as $key => $value)
        {
            $this->sql .= "`$key`, ";
            $this->insert[] = $value; 
        }

        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= ') VALUES (';
        $this->sql .= str_repeat('?, ',count($this->insert));    
        $this->sql = substr($this->sql, 0, -2);
        $this->sql .= ') ';
        return $this;
    }

    public function delete($table, $conditions = '', $params = array())
    {
        $this->sql .= "DELETE FROM `$table` WHERE ($conditions) ";

        foreach($params as $v)
        {
            $this->delete_params[] = $v;
        }
        return $this;
    }

    public function select($columns = '*')
    {
        /* если на вход подан массив столбцов 
        */
    	if (is_array($columns))
        {
            /* экранируем и фильтруем названия столбцов в массиве         */
            $j = 0;
            while($j != (count($columns))) 
            {
                $columns[$j] = '`'.$this->normalize($columns[$j]).'`';
                $j++;
            }
            /* ---------------------------------------------------------- */

        	$this->sql = 'SELECT '.implode(',',$columns).' ';
        }

        /* если на вход подана простая строка столбцов 
        */
        elseif(is_string($columns))
        {   
        	$this->sql = 'SELECT '.$this->normalize($columns).' ';
        }	
    	return $this;
    }

    public function from($table)
    {
        $this->sql .= 'FROM `'.$this->normalize($table).'` ';
    	return $this;
    }


    public function where($condition, $params = array())
    {   
        /* Прописываем WHERE к строке SQL если функция вызывается впервые
        */
        if(empty($this->where))
        {
            $this->sql .= ' WHERE ';
            $this->where = 1;
        }

        /*  Если не указаны параметры, значит всё условие лежит в $condition в виде строки
        */
        if(!empty($condition) and empty($params))
        {
            $this->sql .= ' ('.$this->normalize($condition).') ';
        }

        /* Если условие и параметры не пустые, то начинаем обход
        */
        if(!empty($condition) and !empty($params))
        {       
            $i = 0;

            /* пока не прошли по всем параметрам 
            */
            while(!empty($params[$i]))
            {   
                /* если текущий параметр не является массивом
                */
                if(!is_array($params[$i]))
                {         
                    /* если параметр находится в условиях LIKE или IN 
                    */
                    if(in_array($condition, $this->like) or in_array($condition, $this->in))
                    {
                        /* экранируем текущий параметр 
                        */
                        $this->sql .= ' (`'.$params[$i].'`)';

                        /* запоминаем оператор, если он находится в условии LIKE 
                        *  чтобы повторять при AND или OR 
                        *  Пример: WHERE x LIKE 'abc' AND x LIKE 'efg'
                        */
                        if(in_array($condition, $this->like))
                        {
                            $this->znzn = ' (`'.$params[$i].'`)';
                        }              
                    }

                    /* иначе, если параметр находится в условии AND или OR
                    */
                    else
                    {
                        /* просто экранируем текущий параметр
                        */
                        $this->sql .= ' ('.$params[$i].')';
                    }
                }   

                /* если текущий параметр не является последним в массиве
                *  И если следующий параметр не является массивом,
                *  повторяем знак условия после текущего параметра (and, например)
                */
                if(!empty($params[$i+1]) and !is_array($params[$i+1]))
                {
                    $this->sql .= ' '.$condition;
                }

                /* [СПЕЦИАЛЬНАЯ ПРОВЕРКА ДЛЯ МАССИВА IN | LIKE]
                *  если текущий параметр не является последним в массиве
                *  если следующий параметр это массив с условием IN или LIKE
                *  аналогично повторяем знак условия после текущего параметра
                */
                if(!empty($params[$i+1]) and (in_array($params[$i+1][0], $this->in) or in_array($params[$i+1][0], $this->like) ))/////////////ТУТ  and !is_array($params[$i+1])
                {
                    $this->sql .= ' '.$condition;
                }

                /* Если текущий параметр - это массив, 
                *  Но массив обычный, без условий IN | LIKE
                *  То огорождаем его скобками, происходит рекурсия
                *  + если после массива следует элемент, прописываем знак условия (and например)
                */
                if(is_array($params[$i]) and (!in_array($condition, $this->in) and !in_array($condition, $this->like)) ) // если наткнулись на вложеный масив
                {
                    $this->sql .= ' (';
                    $this->where($params[$i][0],$params[$i][1]);
                    $this->sql .= ' )';

                    if(!empty($params[$i+1])) 
                    {
                        $this->sql .= ' '.$condition;
                    }                     
                }

                /* Если мы наткнулись на массив, который лежит в условии LIKE
                *  array('like', array())
                */
                if(is_array($params[$i]) and in_array($condition, $this->like))
                {
                    $this->sql .= ' ';
                    
                    /* Экранируем значения найденного массива           */
                    $j = 0;
                    while($j != (count($params[$i])))
                    {
                        $paramz[$i][$j] = '(`'.$params[$i][$j].'`)';
                        $j++;
                    }
                    /* ------------------------------------------------ */

                    /*  переводим массив в строку  */
                    $smb = implode(',', $paramz[$i]); 

                    /* Если это просто LIKE, а не OR NOT LIKE, NOT LIKE
                    *  то меняем запятые в строке параметров на 'AND $х LIKE'
                    */
                    if(!in_array($condition, array('OR NOT LIKE','or not like','NOT LIKE','not like')))
                    {
                        $this->sql .= ' '.$condition.' ';
                        $smb = str_replace(","," AND $this->znzn LIKE ", $smb); 
                        $this->sql .= $smb; 
                    }

                    /* Если это OR LIKE
                    *  то меняем запятые в строке параметров на 'OR $х LIKE'
                    */
                    elseif(in_array($condition, array('OR LIKE','or like')))
                    {
                        $this->sql .= ' '.'LIKE ';
                        $smb = str_replace(","," OR $this->znzn LIKE ", $smb);  
                        $this->sql .= $smb; 
                    }

                    /* Если это NOT LIKE
                    *  то меняем запятые в строке параметров на 'AND $х NOT LIKE'
                    */
                    elseif(in_array($condition, array('NOT LIKE','not like')))
                    {
                        $this->sql .= ' '.$condition.' '; //если это простой LIKE
                        $smb = str_replace(","," AND $this->znzn NOT LIKE ", $smb); 
                        $this->sql .= $smb;
                    }

                    /* Если это OR NOT LIKE
                    *  то меняем запятые в строке параметров на 'OR $х NOT LIKE'
                    */
                    elseif(in_array($condition, array('OR NOT LIKE','or not like')))
                    {
                        $this->sql .= ' '.'NOT LIKE ';
                        $smb = str_replace(","," OR $this->znzn NOT LIKE ", $smb);  
                        $this->sql .= $smb;
                    }              
                } // обработка like закончилась

                /* Если мы наткнулись на массив, который лежит в условии IN
                *  where('in',array('x',array(10,9)))
                */
                if(is_array($params[$i]) and in_array($condition, $this->in)) // для IN NOT IN
                {
                    $this->sql .= ' '.$condition;
                    $this->sql .= ' (';
                    $this->sql .= implode(',', $params[$i]);
                    $this->sql .= ')';                        
                }

                $i++;
            }
        }
        return $this;
    }   

    public function orderby($condition, $params=array())
    {
        /* если не указан порядок сортировки 
        */
        if(!empty($condition) and empty($params))
        {
            /* если подан массив параметров
            */
            if(is_array($condition))
            {
                /* экранирование и фильтрация значений массива */
                $j = 0;
                while($j != (count($condition))) 
                {
                    $condition[$j] = '`'.$this->normalize($condition[$j]).'`';
                    $j++;
                }
                /* --------------------------------------------------------- */

                $this->sql .= 'ORDER BY '.implode(',', $condition).' ';
            }

            /* если подана строка параметров
            */
            else
            {
                $this->sql .= 'ORDER BY '.$this->normalize($condition).' ';
            }
        }

        /* если есть условие сортировки и параметры 
        */
        elseif(!empty($condition) and !empty($params)) 
        {
            /* если подан массив параметров
            */
            if(is_array($params))
            {
                /* экранирование и фильтрация */
                $j = 0;
                while($j != (count($params))) 
                {
                    $params[$j] = '`'.$this->normalize($params[$j]).'`';
                    $j++;
                }
                /* ---------------------------------------------------- */

                /* приписываем условие сортировки */
                $this->sql .= 'ORDER BY '.implode(',', $params).' '.$this->normalize($condition).' ';
            }

            /* если подана строка параметров
            */
            else
            {
                $this->sql .= 'ORDER BY '.$this->normalize($params).' '.$this->normalize($condition).' ';
            }
        }       

        return $this;
    }

    public function limit($amount)
    {
        /* наращиваю запрос, а значение $amount запоминаю, 
        *  чтобы потом прибиндить 
        */
        $this->sql .= 'LIMIT :amount ';
        $this->limit = $amount;
        return $this;
    }

    public function dropTable($table)
    {
        $this->sql .= "DROP TABLE `$table`";
        return $this;
    }


    public function save()
    {    
    	$query = $this->prepare($this->sql);
  
        if(!empty($this->limit))
        {
            $query->bindValue(':amount', $this->limit, PDO::PARAM_INT);
        }

        if(!empty($this->insert))
        {
             $query->execute($this->insert);
             $this->sql = '';
        }

        if(!empty($this->update_columns))
        {
            $columns_params = array_merge((array)$this->update_columns, (array)$this->update_params);
            $query->execute($columns_params);
            $this->sql = '';
        }

        if(!empty($this->delete_params))
        {
            $query->execute($this->delete_params);
            $this->sql = '';
        }
        
        else
        {
            $query->execute();
            $this->sql = '';
        }
        
       
        echo 'SQL :  '.$this->sql.'<br>';

        /* просмотр для себя */
        foreach ($query as $row)
        {
            echo $row[id_city].' | '.$row[name_city].' | '.$row[amount_of_people].'<br>';
        }
        /* ------------------------------------------ */
    }

    public function normalize($line)
    {
        //здесь будет очистка мусора и экранирование
    	return $line;
    }
}    	

$db = new MyQueryBuilder($config);	
//$db->select(array('id_city','name_city'))->from('City')->where('and', array('id=1','id=2', array('or', array('x=5','x=7',array('and',array('v=1','v=2')),'z=3' )), 'y = 2')); 
//$db->select()->from('City')->where('OR',array('x=1','x=2',  array('in',array('x',array(10,20)))  ));






//$db->update('City', array('name_city'=>'Ульяноz', 'amount_of_people'=>5), 'id_city = ? or id_city = ?', array(1,2))->save();

//$db->delete('City', 'id_city = ? or id_city = ?', array(1,2))->save();

//$db->createTable('NEW2', array('id'=>'int not null primary key', 'name'=>'varchar(30) not null'))->save();
//$db->insert('City', array('id_city'=>'5','name_city'=>'ЯЯЯЯЯ','amount_of_people'=>'33'))->save();

//echo '<br><br>';

//$db->select()->from('City')->save();

//$db->dropTable('NEW1')->save();
