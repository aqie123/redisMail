<?php
namespace aqie\mailerqueue;
use Yii;
class Message2 extends \yii\swiftmailer\Message
{
    public function queue()
    {
        // Yii::$app->mailer->compose()->setFrom()->setTo()->
        $redis = Yii::$app->redis;
        if(empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found in config');
        }
        // 0 ->15 select 1
        // db =>1
        $mailer = Yii::$app->mailer;
        // 为空，或者数据库选择失败
        if(empty($mailer) || !$redis->select($mailer->db)){
            throw new \yii\base\InvalidConfigException('db not defined.');
        }

        // 收集发送邮件信息
        $message = [];
        $message['from'] = array_keys($this->from);
        $message['to'] = array_keys($this->getTo());
        $message['cc'] = array_keys($this->getCc());
        $message['bcc'] = array_keys($this->getBcc());
        $message['reply_to'] = array_keys($this->getReplyTo());
        $message['charset'] = array_keys($this->getCharset());
        $message['subject'] = array_keys($this->getSubject());

        // 邮件内容
        $parts = $this->getSwiftMessage()->getChildren();  // 拿到邮件信息子信息
        // 如果不是数组，或者为空
        if (!is_array($parts) || !sizeof($parts)) {
            $parts = [$this->getSwiftMessage()];
        }

        foreach ($parts as $part) {
            if (!$part instanceof \Swift_Mime_Attachment) {
                switch($part->getContentType()) {       // 判断类型
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
        // 在配置文件配置 'key' => 'mails',


    }
}