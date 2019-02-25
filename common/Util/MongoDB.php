<?php

namespace src\Util;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;

class MongoDB
{
    public $manager;
    public $bulk;
    protected $db_host;
    protected $db_name;

    protected $m_db;
    protected $m_namespace;
    protected $m_query;
    protected $m_command;

    public function __construct()
    {
        $this->Init();

        $this->manager = new Manager("mongodb://{$this->db_host}");
        $this->bulk = new BulkWrite();
    }

    public function Init()
    {
        // Setting host, user, password, name
    }

    public function Query($namespace, $query_filter, $query_options = [])
    {
        $this->m_namespace = $namespace;
        $this->m_query = new Query($query_filter, $query_options);

        return $this->manager->executeQuery($this->m_namespace, $this->m_query);
    }

    public function Command($db, $command_document)
    {
        $this->m_db = $db;
        $this->m_command = new Command($command_document);

        return $this->manager->executeCommand($this->m_db, $this->m_command);
    }

    public function BulkWrite($namespace, $bulk = null)
    {
        if ($bulk === null) {
            $this->manager->executeBulkWrite($namespace, $this->bulk);
            $this->bulk = new BulkWrite();
        } else {
            $this->manager->executeBulkWrite($namespace, $bulk);
        }
    }
}
