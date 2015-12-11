# MyQueryBuilder

#h3 Примеры использования

#h5 Метод create() 
Описание: создаёт таблицу с заданными параметрами.
Создание SQL строки "CREATE TABLE Table1 (id int not null, name varchar(30))" выглядит следующим образом:
```php
$db->createTable('Table1', array(
  'id'   => 'int not null',                                  
  'name' => 'varchar(30)'                                    
));
```
