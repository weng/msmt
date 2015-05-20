msmt---------mysql schema migration tool
----------------------------
This is a simple php CLI script.It was designed to be used as a database schema migration tool.

The idea was inspired by Ruby on rails schema migration function.

usage:
0. Open msmt.php with your lovely editor,configure the database connection information.

1. php -f msmt.php g|generate name

  Generate a migration.User should assign name as a meaningful word.For example:add_index_to_post,create_account_table,etc.
  
2. This script will auto generate a sql file in migrations dir.Fill native mysql statement in this sql.
  
3. php -f msmt.php migrate

  Will apply all the sql files which was never executed.
  
4. repeat 1 2 3 


warning:

This script support multi sql statement in a migration sql file.Be careful to do this because multi sql execution is not a atomic operation.
It means some query may success and some query may fail, and no database migration version will be logged.