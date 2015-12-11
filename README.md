# MyQueryBuilder

*Описание работы с оновными методами класса*

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
**public function delete($table, $conditions = '', $params = array())**
```php
$db->delete('Table1', 'id = ? or id = ?', array(1,2))->execute();
```
```sql
DELETE FROM Table1 WHERE (id = 1) OR (id = 2) 
```
**public function select($columns = '*')**
```php
$db->select()
```
```sql
SELECT *
```
```php
$db->select('x1,x2') =  $db->select(array('x1','x2'))
```
```sql
SELECT x1,x2
```
**public function from($tables)**
```php
from('table1,table2')

from(array('table1','table2'))
```
```sql
FROM table1,tabel2
```
**public function where($x, $condition, $y)**
```php
where('x','>',1) 

where('x','>','5)->and_where('y','in',array(5,3,2)) 

where('x','like','Ульяновск')->or_where('x','like','Самара')
```
```sql
WHERE (x > 1)                                                     

WHERE (x > 5) AND (y IN (5,3,2))                                  

WHERE (x LIKE 'Ульяновск') OR (x LIKE 'Самара')
```
**public function orderby($params, $condition=null)**
```php
orderby('x,z','DESC')                   
orderby(array('x','z'),'DESC')
```
```sql
ORDER BY x,z DESC 
```
**public function limit($x)**
```php
limit(10)
```
```sql
LIMIT 10
```
**public function offset($y)**
```php
offset(2)
```
```sql
OFFSET 2
```
**public function leftjoin($table, $condition, $params = array())**
```php
leftJoin('table2', '(table1.id = table2.id_student AND table1.x = ?)', array('something'))
```
```sql
LEFT JOIN table2 ON table1.id = table2.id_student AND table1.x = 'something'
```
**public function groupby($columns)**
```php
groupby('x, z')

groupby(array('x','z'))
```
```sql
GROUP BY x,z
```
public function union($sql = null)
```php
union('SELECT x FROM table')
```
```sql
.. UNION (SELECT x FROM table)
```


