# MyQueryBuilder
## Примеры использования

##### public function create($table, $columns, $options=null) 
Вызов:
```php
$db->createTable('Table1', array(
  'id'   => 'int not null',                                  
  'name' => 'varchar(30)'                                    
));
```
Полученный SQL запрос: 

CREATE TABLE Table1 
(
  id int not null, 
  name varchar(30)
)

#### public function update($table, $columns, $conditions = '', $params = array())
Вызов:
```php
$db->update('Table1', array('x'=>1), 'id = ? or id = ?', array(1,2));
));
```
Полученный SQL запрос: 

UPDATE Table1                           
SET x = 1                                                                
WHERE (id = 1) or (id = 2)   
