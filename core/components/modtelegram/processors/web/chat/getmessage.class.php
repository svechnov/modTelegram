<?php

require_once dirname(dirname(__FILE__)) . '/response.class.php';

class modChatInitializeProcessor extends modTelegramResponseProcessor
{
    function process()
    {
        $data = array();

        $limit = $this->getProperty('limit', 10);
        $timestamp = $this->getProperty('timestamp', (int)$_SERVER["HTTP_LAST_EVENT_ID"]);
        if (!$timestamp) {
            $limit = 0;
        }

        /** @var modTelegramUser $manager */
        /** @var modTelegramChat $chat */
        if (
            $user = $this->modx->getObject($this->classUser, array(
                'id' => session_id(),
            ))
            AND
            $chat = $this->modx->getObject($this->classChat, array(
                'uid' => session_id(),
            ))
        ) {

            $q = $this->modx->newQuery($this->classMessage);
            $q->where(array(
                'uid'         => $chat->getUser(),
                'mid'         => $chat->getManager(),
                'timestamp:>' => $timestamp
            ));

            $q->select($this->modx->getSelectColumns($this->classMessage, $this->classMessage));
            $q->sortby("{$this->classMessage}.timestamp", "ASC");
            $q->limit($limit);

            if ($q->prepare() AND $q->stmt->execute()) {
                while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data['messages'][] = $this->modtelegram->processChatMessage($row);
                    $data['timestamp'] = $row['timestamp'];
                }
            }

            if (!empty($data['messages'])) {
                $data['user'] = $this->modtelegram->getUserData($chat->getUser());
                $data['manager'] = $this->modtelegram->getManagerData($chat->getManager());
            }
        }

        return $this->sendRequest($data);
    }
}

return 'modChatInitializeProcessor';