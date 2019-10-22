<?php
namespace Core\Api;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
class RabbitmqConsumer {
    protected $connKey = [
        'host','port','user','password','vhost'
    ];
    protected $exchangeKey = [
        'exchange','queue','route_key'
    ];

    /**
     * 从rmq消费数据
     * @param $connInfo 队列连接信息
     * @param $exchangeInfo 队列交换机信息
     * @param $callback 回调方法
     * @param string $consumerTag
     * @param string $direct direct|fanout|topic
     * @return bool
     */
    public function consumer($connInfo,$exchangeInfo,$callback,$consumerTag= '',$direct=''){
        //回调、连接信息、交换机数据均不能为空,注：回调方法在调用此方法的代码中实现
        if(empty($callback)){
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
        if(empty($consumerTag)){
            $consumerTag = 'consumer';
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
            if(empty($direct)){
               $direct = AMQPExchangeType::DIRECT;
            }
            $channel->exchange_declare($exchange, $direct, false, true, false);
            $channel->queue_bind($queue, $exchange);
            /*
                queue: Queue from where to get the messages
                consumer_tag: Consumer identifier
                no_local: Don't receive messages published by this consumer.
                no_ack: If set to true, automatic acknowledgement mode will be used by this consumer. See https://www.rabbitmq.com/confirms.html for details.
                exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
                nowait:
                callback: A PHP Callback
            */

            $channel->basic_qos(null, 1, null);
            $channel->basic_consume($queue, $consumerTag, false, false, false, false, $callback);
            while (count($channel->callbacks)) {
                $channel->wait();
            }
        }catch (\Exception $e){
//            var_dump($e->getMessage());
            return false;
        }
    }

}
