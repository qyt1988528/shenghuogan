<?php
namespace MDK\Event;
use Phalcon\Di\Injectable;

class Database extends Injectable
{
    public function beforeQuery($event, $connection) {
        $sqlStatement = $connection->getSQLStatement();
        $this->profiler->start($sqlStatement, 'database');
        return true;
    }
    public function afterQuery($event, $connection) {
        $sqlStatement = $connection->getSQLStatement();
        $this->profiler->stop($sqlStatement, 'database');
        return true;
    }
}