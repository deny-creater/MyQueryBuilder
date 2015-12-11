# MyQueryBuilder

**public function create($table, $columns, $options=null)**
```php
$db->createTable('Table1', array(
  'id'   => 'int not null',                                  
  'name' => 'varchar(30)'                                    
))->execute();
```
```sql
CREATE TABLE Table1 
(
  id int not null, 
  name varchar(30)
)
```
**public function update($table, $columns, $conditions = '', $params = array())**
```php
$db->update('Table1', array('x'=>1), 'id = ? or id = ?', array(1,2))->execute();
));
```
```sql
UPDATE Table1                           
SET x = 1                                                                
WHERE (id = 1) or (id = 2)
```
**public function insert($table, $columns)**
```php
$db->insert('Table1', array('id'=>'5','name'=>'Ульяновск'))->execute();
```
```sql
INSERT INTO Table1 (id,name) VALUES (5, 'Ульяновск');
```
