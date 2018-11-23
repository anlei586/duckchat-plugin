<?php
/**
 * Author: SAM<an.guoyue254@gmail.com>
 * Date: 2018/11/21
 * Time: 7:53 PM
 */

class Api_RedPacket_GrabController extends MiniRedController
{

    /**
     * http get request
     */
    protected function doGet()
    {
        return true;
    }

    /**
     * http post request
     */
    protected function doPost()
    {
        $params = [
            "errCode" => "error",
        ];

        try {
            $packetId = trim($_POST['packetId']);
            $redPacketInfo = $this->getRedPacketInfo($packetId);
            if (!$redPacketInfo) {
                throw new Exception("红包已经失效");
            }
            $totalAmount = $redPacketInfo['totalAmount'];
            $grabbers = $this->getRedPacketGrabbers($packetId);
            $grabberCount = empty($grabbers) ? 0 : count($grabbers);
            if ($grabberCount < $totalAmount) {
                $quantity = $redPacketInfo['quantity'];
                $result = $this->grabRedPacket($packetId, $this->userId, $quantity);
                if ($result) {
                    $result['errCode'] = "success";
                }
            } else {
                //抢光了
                $result['errCode'] = "success";
            }
        } catch (Exception $e) {
            $result['errInfo'] = $e->getMessage();
            $this->logger->error($this->action, $e);
        }

        echo json_encode($params);
        return;
    }

    //grab red packet
    private function grabRedPacket($packetId, $userId, $quantity)
    {
        //遍历循环，获取没有被抢走的
        $grabbingPackets = $this->ctx->DuckChatRedPacketGrabberDao->queryRedPacketGrabbers($packetId,
            false, false, RedPacketStatus::grabbingStatus);

        if (empty($grabbingPackets)) {
            return true;
        }

        $count = count($grabbingPackets);

        for ($j = 0; $j < $count; $j++) {
            $randomKey = array_rand($grabbingPackets, 1);
            $randomRedPacket = $grabbingPackets[$randomKey];
            $currentTime = ZalyHelper::getCurrentTimeMillis();

            //get myAccount
            $id = $randomRedPacket["id"];
            error_log("JJ=" . $j . "=============Start grab red packet packetId=" . $packetId . " id=" . $id);
            $result = $this->calculateGrabbingRedPackets($packetId, $userId, $randomRedPacket, $currentTime);

            error_log("JJ=" . $j . "=============Finish grab red packet packetId=" . $packetId . " id=" . $id);
            if ($result) {
                //检测所有红包是不是抢完
                $this->checkAllRedPackets($packetId, $quantity, $currentTime);
                return true;
            }
            //remove it after used
            unset($grabbingPackets[$randomKey]);
        }

    }

    private function calculateGrabbingRedPackets($packetId, $userId, $randomRedPacket, $currentTime)
    {
        $tag = __CLASS__ . "->" . __FUNCTION__;

        $id = $randomRedPacket["id"];
        $grabbedAmount = $randomRedPacket["amount"];

        error_log("=============grab red packet packetId=" . $packetId . " id=" . $id);
        try {
            $this->ctx->db->beginTransaction();


            //add table row lock
            $status = $this->ctx->DuckChatRedPacketGrabberDao->lockRedPacket($id);

            if ($status > RedPacketStatus::grabbingStatus) {
                $this->ctx->db->commit();
                $this->logger->error("==============", "红包已经被抢1,LOCK失败packetId=" . $packetId . " id=" . $id);
                return false;
            }

//            $data = [
//                "status" => RedPacketStatus::grabbedStatus,
//            ];
//            $where = [
//                "packetId" => $packetId,
//            ];
//
//            $result = $this->ctx->DuckChatRedPacketGrabberDao->updateRedPacket($data, $where);
//
//            if (!$result) {
//                throw new Exception("红包已经被抢2 packetId=" . $packetId . " id=" . $id);
//            }

            $data2 = [
                "userId" => $userId,
                "status" => RedPacketStatus::grabbedStatus,
                'grabTime' => $currentTime,
            ];
            $where2 = [
                "id" => $id,
                "packetId" => $packetId,
            ];
            $result = $this->ctx->DuckChatRedPacketGrabberDao->updateRedPacket($data2, $where2);

            if (!$result) {
                throw new Exception("更新我的红包状态失败");
            }

            //inc my amount
            $userAccount = $this->getUserAccount($userId);
            if (!$userAccount) {
                //insert
                $data = [
                    "userId" => $userId,
                    "amount" => $grabbedAmount,
                ];
                $result = $this->ctx->DuckChatUserAccountDao->addUserAccount($data);
            } else {
                //update
                $data = [];
                $where = [];
                $result = $this->ctx->DuckChatUserAccountDao->updateUserAccount($data, $where);
            }

            if (!$result) {
                throw new Exception("更新我的我的账户失败");
            }

            $this->ctx->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->ctx->db->rollBack();
            $this->logger->error($tag, $e);
        }
        return false;
    }

    private function checkAllRedPackets($packetId, $quantity, $currentTime)
    {
        $grabbersCount = $this->getRedPacketGrabbersCount($packetId);

        if ($grabbersCount >= $quantity) {
            $this->ctx->DuckChatRedPacketDao->updateRedPacketFinish($packetId, $currentTime);
        }

    }

}