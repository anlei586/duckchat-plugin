<?php
/**
 * Created by PhpStorm.
 * User: anguoyue
 * Date: 08/08/2018
 * Time: 8:00 PM
 */

class DuckChatRedPacketGrabberDao extends BaseDao
{
    private $table = "DuckChatRedPacketGrabbers";

    private $columns = [
        "id",
        "packetId",
        "userId",
        "amount",
        "number",
        "status", //0 未被抢 1 抢夺
        "grabTime",
    ];

    private $queryColumns;

    public function __init()
    {
        $this->queryColumns = implode(",", $this->columns);
    }

    public function insertGrabbers($data)
    {
        $data['grabTime'] = ZalyHelper::getCurrentTimeMillis();
        $data['status'] = 0;
        return $this->saveData($this->table, $data, $this->columns);
    }

    public function updateRedPacket($data, $where)
    {
        $tag = __CLASS__ . "->" . __FUNCTION__;
        return $this->updateData($this->table, $where, $data, $this->columns);
    }

    public function queryRedPacketGrabbers($packetId, $userId = false)
    {
        $tag = __CLASS__ . "->" . __FUNCTION__;
        $startTime = $this->getCurrentTimeMills();
        $sql = "select $this->queryColumns from $this->table where packetId=:packetId ";

        if ($userId) {
            $sql .= "and userId=:userId";
        }

        try {
            $prepare = $this->db->prepare($sql);
            $this->handlePrepareError($tag, $prepare);
            $prepare->bindValue(":packetId", $packetId);
            if ($userId) {
                $prepare->bindValue(":userId", $userId);
            }
            $flag = $prepare->execute();
            $result = $prepare->fetchAll(\PDO::FETCH_ASSOC);
            if ($flag && $result) {
                return $result;
            }
        } catch (Exception $e) {
            $this->logger->error($tag, $e);
        } finally {
            $this->logger->writeSqlLog($tag, $sql, $packetId, $startTime);
        }
        return false;
    }

}