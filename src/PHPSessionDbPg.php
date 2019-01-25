<?php
namespace \aNullValue\PHPSessionDbPg;

class PHPSessionDbPg implements \SessionHandlerInterface
{

    private $dbconn;
    private $tbl;
    private $lifetime;

    public function __construct($pghandle, $tblname = 'session_data', $maxlifetime = 0)
    {
        $this->dbconn = null;
        if (pg_connection_status($pghandle) == PGSQL_CONNECTION_OK) {
            $this->dbconn = $pghandle;
            $this->tbl = $tblname;
        } else {
            die('Database handle is not an OK PostgreSQL connection. ');
        }
        if ($maxlifetime > 0) {
            $this->lifetime = $maxlifetime;
        } else {
            $this->lifetime = ini_get('session.gc_maxlifetime');
        }

    }

    public function open($savePath = null, $sessionName = null)
    {
        // do nothing, because the constructor already took care of it
        return true;
    }

    public function close()
    {
        // do nothing, because we share a database handle, and must not close it simply because we are done with it
        return true;
    }

    public function read($session_id)
    {
        $query = 'SELECT data, touch_epoch FROM ' . $this->tbl . ' WHERE id = $1';
        $params = array(
            1 => $session_id,
        );
        $result = @pg_query_params($this->dbconn, $query, $params);
        if ($result && pg_num_rows($result) == 1) {
            $row = pg_fetch_assoc($result);
            if ($row['touch_epoch'] < (time() - $this->lifetime)) {
                return '';
            } else {
                return $row['data'];
            }
        } else if ($result === false) {
            return false;
        } else {
            return '';
        }
        return '';
    }

    public function write($session_id, $session_data)
    {

        $query = '
        INSERT INTO ' . $this->tbl . '
        (id, data, touch_epoch)
        VALUES ($1, $2, $3)
        ON CONFLICT (id) DO UPDATE
        SET data = $2
        , touch_epoch = $3
        WHERE ' . $this->tbl . '.id = $1;
        ';
        $params = array(
            1 => $session_id
            , 2 => $session_data
            , 3 => time(),
        );
        $result = @pg_query_params($this->dbconn, $query, $params);
        if ($result && pg_affected_rows($result) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function destroy($session_id)
    {
        $query = '
        DELETE FROM ' . $this->tbl . '
        WHERE id = $1;
        ';
        $params = array(
            1 => $session_id,
        );
        $result = @pg_query_params($this->dbconn, $query, $params);
        $result_error = pg_result_error($result);
        if ($result_error == false or (strlen($result_error) > 0)) {
            return false;
        } else {
            return true;
        }

    }

    public function gc($maxlifetime = 0)
    {
        if ($maxlifetime == 0) {
            $uselifetime = $this->lifetime;
        } else {
            $uselifetime = $maxlifetime;
        }
        $query = 'DELETE FROM ' . $this->tbl . ' WHERE touch_epoch < $1';
        $params = array(
            1 => (time() - $uselifetime),
        );
        $result = @pg_query_params($this->dbconn, $query, $params);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    public function get_all_sessions()
    {
        $query = 'SELECT * FROM ' . $this->tbl . ' ORDER BY touch_epoch';
        $result = @pg_query($this->dbconn, $query);
        if ($result && pg_num_rows($result) > 0) {
            $outarr = array();
            while ($row = pg_fetch_assoc($result)) {
                $outarr[($row['id'])] = $row;
            }
            return $outarr;
        } else if ($result === false) {
            return false;
        } else {
            return array();
        }
    }

    public function get_session_maxlifetime()
    {
        return $this->lifetime;
    }

}
