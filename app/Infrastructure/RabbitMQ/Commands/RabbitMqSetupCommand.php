<?php

declare(strict_types=1);

namespace App\Infrastructure\RabbitMQ\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;

class RabbitMqSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rabbitmq-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Делает сетап в Раббите для сервиса';

    /**
     * Настраивает RabbitMQ exchange, queue и bindings для входящих событий dan-vehicles.
     *
     * Steps:
     * 1. Получает нативное соединение и канал.
     * 2. Указывает exchange, принимающую очередь и routing masks.
     * 3. Объявляет durable topic exchange.
     * 4. Объявляет durable queue.
     * 5. Связывает queue с exchange по каждой routing mask.
     */
    public function handle(): void
    {
        $connection = \Queue::connection('rabbitmq_inbox');
        /** @var AMQPChannel $channel */
        $channel = $connection->getChannel();

        $exchangeName = 'application.events';
        $queueName = 'vehicles.inbox';
        $routingKeyMasks = [
            'vehicles.inbox',
            // TODO: добавить routing-маски событий, которые слушает dan-vehicles.
            // Пример — события, публикуемые dan-center:
            //   'crm.stores.upserted',
            //   'crm.mp_card.relink',
        ];

        $channel->exchange_declare($exchangeName, 'topic', false, true, false);
        $this->info("Exchange [{$exchangeName}] проверен/создан.");

        $channel->queue_declare($queueName, false, true, false, false);
        $this->info("Очередь [{$queueName}] проверена/создана.");

        foreach ($routingKeyMasks as $routingKeyMask) {
            $channel->queue_bind($queueName, $exchangeName, $routingKeyMask);
        }
        $this->info("Связь [{$exchangeName}] -> [{$queueName}] установлена.");
    }
}
