# PHP7+ Session Handler using PostgreSQL

> * Many solutions/projects will not require the use of database-backed PHP session handling. In event yours does, and your project requires PostgreSQL 9.5+ anyway, I wrote this solution for you. *

## Requirements

* PostgreSQL 9.5+ (must support "ON CONFLICT DO UPDATE")
* PHP 7.0+ 
* PostgreSQL connection (via pg_connect, not PDO)
* Create "session_data" table as follows (table name (but not schema) may vary; this is just an example):

    CREATE TABLE public.session_data
    (
        id character varying NOT NULL,
        data character varying NOT NULL,
        touch_epoch integer NOT NULL,
        PRIMARY KEY (id)
    )
    WITH (
        OIDS = FALSE
    )
    TABLESPACE pg_default;

    ALTER TABLE public.session_data
        OWNER to postgres;

    CREATE INDEX session_data__id ON session_data(id);
    CREATE INDEX session_data__touch_epoch ON session_data(touch_epoch);

## Example code

    $dbconn = pg_connect("your connection string here");
    require_once 'PHPSessionDbPg.php'; // required only if you are not autoloading this library
    $sessions_handler = new \aNullValue\PHPSessionDbPg\PHPSessionDbPg($dbconn,'session_data', 86400);
    session_set_save_handler($sessions_handler, true);
    session_name('MySessionName'); //optional
    session_start();
    
    $_SESSION['something'] = 'foo';
    $_SESSION['something_else'] = 'bar';
    echo session_id(),'<br/>',$_SESSION['something'],'<br />',$_SESSION['something_else'] ;

## Misc

    Inspired by https://github.com/iKevinShah/PGSessions
