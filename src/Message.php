<?php
namespace aqie\mailerqueue;

use Yii;

class Message extends \yii\swiftmailer\Message
{
    public function queue()
    {
        // Yii::$app->mailer->compose()->setFrom()->setTo()->send()
        //Yii::$app->redis->set("aqiename","aqie"); die;
         // echo (Yii::$app->redis->get("aqiename"));die;
        $redis = Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found in config.');
        }
        // 0 - 15  select 0 select 1
        // db => 1
        $mailer = Yii::$app->mailer;
        if (empty($mailer) || !$redis->select($mailer->db)) {
            throw new \yii\base\InvalidConfigException('db not defined.');
        }
        $message = [];
        // var_dump($this->from);die;
        $message['from'] = array_keys($this->from);
        // var_dump($message['from']);die;
        $message['to'] = array_keys($this->getTo());
        // var_dump($message['to']);die;
        //var_dump($this->getCc());die;   // null
        $message['cc'] = empty($this->getCc()) ? "" :array_keys($this->getCc());

        //var_dump($message['cc']);die;
        //var_dump();
        // var_dump($this->getBcc());die;  // null
        $message['bcc'] = empty($this->getBcc()) ? "" :array_keys($this->getBcc());
        // var_dump($this->getSubject());die;
        $message['reply_to'] = empty($this->getReplyTo()) ? "" :$this->getReplyTo();      // null
        $message['charset'] = empty($this->getCharset()) ? "" :$this->getCharset();          // null
        $message['subject'] = $this->getSubject();      // 有值
        // var_dump($message['subject']);die;      后面三个是字符串
        $parts = $this->getSwiftMessage()->getChildren();
        if (!is_array($parts) || !sizeof($parts)) {
            $parts = [$this->getSwiftMessage()];
        }
        foreach ($parts as $part) {
            if (!$part instanceof \Swift_Mime_Attachment) {
                switch($part->getContentType()) {
                    case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body'] = $part->getBody();
                        break;
                }
                if (!$message['charset']) {
                    $message['charset'] = $part->getCharset();
                }
            }
        }
        return $redis->rpush($mailer->key, json_encode($message));
    }
}
