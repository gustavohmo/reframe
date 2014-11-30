<?php

/* config stuff */
error_reporting(E_ALL);
define('TM_TIMEZONE','America/Sao_Paulo');

define('TM_MYSQL_HOST','localhost');
define('TM_MYSQL_DBNAME','');
define('TM_MYSQL_USER','');
define('TM_MYSQL_PASSWORD','');


Class Event {
    public $connection;
    public $name;
    public $id;

    public function __construct($con) {
        $this->connection = $con;
    }

    public function getEvent($id) {
        $query = "SELECT event_id, event_name, event_date, event_desc FROM events WHERE event_id=?";
        $consulta = $this->connection->prepare($query);
        $consulta->bindParam(1,$id);
        $consulta->execute();

        $event_data = $consulta->fetch();
        $this->id = $event_data[0];
        $this->name = $event_data[1];
        return ($event_data);
    }

    public function getEventChildren($id) {
        # Finding children
        //$query = "SELECT e.event_id, e.event_name, e.event_date, e.event_desc, r.report_link FROM events e, report r WHERE e.event_id=events_event_id=?";
        $query = "SELECT e.event_id, e.event_name, e.event_date, e.event_desc, r.report_link, r.report_news_source, re.quote FROM events e
 JOIN reports_has_events re ON (e.event_id = re.events_event_id)
 JOIN reports r ON (re.reports_report_id = r.report_id)
 WHERE e.events_event_id=? ORDER BY e.event_id ASC";
        $consulta = $this->connection->prepare($query);
        $consulta->bindParam(1,$id);
        $consulta->execute();
        return($consulta->fetchAll(\PDO::FETCH_OBJ));
    }

    public function getEventChildrenJSON($con,$max_degree,$child) {
        $name = $this->name;
        $id = $this->id;
        $event_children = $this->getEventChildren($id);

        $degree = 0;
        $json_output = '';

        if (isset($name)) {


            # montando o metadata
            foreach ($event_children as $ec) {
                if ($ec->quote == '') { $ec->quote = "(no quote yet)"; }
                $metadata[$ec->event_id] = "'$ec->quote' - <a href='$ec->report_link'>$ec->report_news_source</a>";
                if (isset($metadata_insert[$ec->event_id])) {
                    $metadata_insert[$ec->event_id] = $metadata_insert[$ec->event_id] . "</br>" . $metadata[$ec->event_id];
                }
                else {
                    $metadata_insert[$ec->event_id] = $metadata[$ec->event_id];
                }
            }

            $run = 0;
            $ids_done = array();

            $printed_once = 0;
            foreach ($event_children as $ecc) {
                if ($printed_once == 0 && $child == 0) {
                    $json_output = "{
\"name\": \"$name\",
\"quote\": \"\",
\"children\": [";
                    $printed_once = 1;
                }
                //print_r($ecc);
                if (!in_array($ecc->event_id,$ids_done)) {
                    if ($ecc->quote == '') { $ecc->quote = "(no quote yet)"; }

                    if ($run > 0) {
                        $json_output .= ",";
                    }

                    $mt = $metadata_insert[$ecc->event_id];
                    $json_output .= "{
                \"name\": \"$ecc->event_name\",
                \"url\": \"node.php?id=$ecc->event_id\",
                \"news_url\": \"$ecc->report_link\",
                \"quote\": \"$ecc->quote\",
                \"metadata\": \"$mt\",
                \"children\": [";

                    # Running sub-children
                    $children_output = '';
                    if ($degree < $max_degree) {
                        $event2 = new Event($con);
                        $event2_data = $event2->getEvent($ecc->event_id);
                        # Passando o filho tb: se for filho, nao ha necessidade de printar de novo
                        $children_output = $event2->getEventChildrenJSON($con,$max_degree--,1);
                        //print "AAA: $ecc->event_id\n $children_output\n\n\n";
                    }
                    $json_output .= $children_output;

                    # Continuando com o parent
                    $json_output .= "]
                    }";

                    $run++;
                    $ids_done[] = $ecc->event_id;
                }
            }
            if ($printed_once == 1) {
                $json_output .= "]
            }";
            }
        }
        else {
            $json_output = '';
        }
        return $json_output;

    }
}

Class Events {
    public $connection;

    public function __construct($con) {
        $this->connection = $con;
    }

    public function getEvents() {
        $query = "SELECT event_name, event_datetime FROM events";
        $consulta = $this->connection->prepare($query);
        $consulta->execute();
        return($consulta->fetchAll(\PDO::FETCH_OBJ));
    }
}



Class SafePDO extends PDO {
    public static function exception_handler($exception) {
        //Output the exception details
        die('Uncaught exception: '. $exception->getMessage());
    }
    public function __construct($dsn, $username='', $password='', $driver_options=array()) {
        // Temporarily change the PHP exception handler while we . . .
        set_exception_handler(array(__CLASS__, 'exception_handler'));

        // . . . create a PDO object
        parent::__construct($dsn, $username, $password, $driver_options);

        // Change the exception handler back to whatever it was before
        restore_exception_handler();
    }
}

Class Connection extends \SafePDO {
    public function __construct() {
        parent::__construct('mysql:host='.TM_MYSQL_HOST.';dbname='.TM_MYSQL_DBNAME,
            TM_MYSQL_USER,
            TM_MYSQL_PASSWORD,
            array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => true,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8mb4'
            )
        );
    }
}

?>