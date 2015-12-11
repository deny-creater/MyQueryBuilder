# MyQueryBuilder

### Примеры использования

##### public function create($table, $columns, $options=null) 

SQL запрос: "CREATE TABLE Table1 (id int not null, name varchar(30))"

Создание данного запроса:
```php
$db->createTable('Table1', array(
  'id'   => 'int not null',                                  
  'name' => 'varchar(30)'                                    
));
```

