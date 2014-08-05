<?php
/**
 * User: lancio
 * Date: 01/08/14
 * Time: 23:18
 */

namespace Rn2014;

use Doctrine\DBAL\Connection;

class HumenRepository {

    protected $typeConditions = [
        "extra" => 'h.idgruppo in ("SER0", "SER4") ',
        'lab' => 'h.idgruppo in ("SER1") OR h.ruolo = 11 ',
        'oneteam' => 'h.idgruppo in ("SER2") OR h.ruolo = 8 ',
        'rs' => 'h.ruolo = 7 ',
        'rscapi' => 'h.idgruppo not in ("SER0, SER1, SER2, SER3, SER4") and h.ruolo in (0,1,2,3,4,5,6) ',
    ];

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getUsers($type)
    {
        $sql = 'SELECT h.cu, h.nome, h.cognome, hp.datanascita FROM humen h LEFT JOIN humen_personali hp USING (cu) WHERE ';
        $sql .= $this->typeConditions[$type];
//        $sql .= " LIMIT 1";

        $stmt = $this->conn->query($sql);

        return $stmt;
    }

    public function getWrongUsers()
    {
        $sql = 'SELECT cu, nome, cognome, ruolo, idgruppo FROM humen WHERE idgruppo in ("SER2") and id < 28566';
        $sql .= " LIMIT 1";

        $stmt = $this->conn->query($sql);

        return $stmt;
    }

    public function countWrongUsers()
    {
        $sql = 'SELECT count(*) FROM humen WHERE idgruppo in ("SER2") and id < 28566';

        return $this->conn->fetchColumn($sql);

        return $stmt;
    }

    public function countUsers($type)
    {
        $sql = 'SELECT count(h.cu) FROM humen h WHERE ';
        $sql .= $this->typeConditions[$type];

        return $this->conn->fetchColumn($sql);
    }
}
