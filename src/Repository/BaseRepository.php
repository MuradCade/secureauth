<?php

namespace SecureAuth\Repository;

class BaseRepository
{
    protected $connection;
    protected $statement;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function query($query, $type, ...$value)
    {
        $this->statement = $this->connection->prepare($query);

        if (empty($type) || empty($value)) {
            $this->statement->execute();
        } else {
            $this->statement->bind_param($type, ...$value);
            $this->statement->execute();
        }

        return $this;
    }


    public function getuserid()
    {
        return $this->statement->insert_id;
    }
    public function fetchAll()
    {

        return $this->statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function fetchOne()
    {
        return $this->statement->get_result()->fetch_assoc();
    }
}
