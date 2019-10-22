<?php
namespace Core\Api;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
class RabbitmqPublisher {
    protected $connKey = [
        'host','port','user','password','vhost'
    ];
    protected $exchangeKey = [
        'exchange','queue','route_key'
    ];

    public function __construct() {
    }

    /**
     * 向rmq生产数据
     * @param $connInfo 队列连接信息
     * @param $exchangeInfo 交换机信息
     * @param $message 要放入队列的数据
     * @param array $delayInfo 延时队列的信息
     * @param int $delaySeconds 延时的秒数
     * @return bool
     */
    public function publish($connInfo,$exchangeInfo,$message,$delayInfo=[],$delaySeconds=0){
        //消息、连接信息、交换机数据均不能为空
        if(empty($message)){
            return false;
        }
        foreach ($this->connKey as $ck){
            if(empty($connInfo[$ck])){
                return false;
            }
        }
        foreach ($this->exchangeKey as $ek){
            if(empty($exchangeInfo[$ek])){
                return false;
            }
        }

        try{
            /*
                The following code is the same both in the consumer and the producer.
                In this way we are sure we always have a queue to consume from and an
                    exchange where to publish messages.
            */
            $connection = new AMQPStreamConnection($connInfo['host'],$connInfo['port'],$connInfo['user'],$connInfo['password'],$connInfo['vhost']);
            $channel = $connection->channel();
            $exchange = $exchangeInfo['exchange'];
            $queue = $exchangeInfo['queue'];
            $routeKey = $exchangeInfo['route_key'];
            /*
                name: $queue
                passive: false
                durable: true // the queue will survive server restarts
                exclusive: false // the queue can be accessed in other channels
                auto_delete: false //the queue won't be deleted once the channel is closed.
            */
            $channel->queue_declare($queue, false, true, false, false);
            /*
                name: $exchange
                type: direct
                passive: false
                durable: true // the exchange will survive server restarts
                auto_delete: false //the exchange won't be deleted once the channel is closed.
            */
            $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
            $channel->queue_bind($queue, $exchange);
            $delay = true;
            if(!empty($delayInfo) && !empty($delaySeconds)){
                foreach ($this->exchangeKey as $ek){
                    if(empty($delayInfo[$ek])){
                        $delay = false;
                    }
                }
            }else{
                $delay = false;
            }
            if($delay){
                // now create the delayed queue and the exchange
                $channel->queue_declare(
                    $delayInfo['queue'],
                    false,
                    false,
                    false,
                    true,
                    true,
                    array(
                        'x-message-ttl' => $delaySeconds*1000,   // delay in seconds to milliseconds
                        "x-expires" => $delaySeconds*1000+1000,
                        'x-dead-letter-exchange' => $exchangeInfo['exchange'] // after message expiration in delay queue, move message to the right.now.queue
                    )
                );
                $channel->exchange_declare($delayInfo['exchange'], AMQPExchangeType::DIRECT, false, true, false);
                $channel->queue_bind($delayInfo['queue'], $delayInfo['exchange']);
                $publishMessage = new AMQPMessage($message, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
                $channel->basic_publish($publishMessage, $delayInfo['exchange']);
//              $channel->basic_publish($publishMessage, $delayInfo['exchange'],$delayInfo['route_key']);
            }else{
//                $publishMessage = new AMQPMessage($message);
                $publishMessage = new AMQPMessage($message, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
                $channel->basic_publish($publishMessage, $exchange);
//              $channel->basic_publish($publishMessage, $exchange,$routeKey);
            }
            $channel->close();
            $connection->close();
            return  true;
        }catch (\Exception $e){
//            var_dump($e->getMessage());
            return false;
        }

    }
}
