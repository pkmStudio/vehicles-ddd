<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Templates;

use App\Modules\Templates\Application\WiperSpecificationService;
use Psr\Log\AbstractLogger;
use Stringable;
use Tests\TestCase;

final class WiperSpecificationServiceTest extends TestCase
{
    private WiperSpecificationService $service;

    /**
     * Creates the default service instance without a logger for baseline tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WiperSpecificationService;
    }

    /**
     * Проверяет detectSide(): по какому ключу верхнего уровня (front/back) определяется
     * сторона деталей, и что при неоднозначности (обе стороны сразу или ни одной) — null.
     *
     * Шаги:
     * 1. Проверяет, что ['front' => []] → 'front', ['back' => []] → 'back'.
     * 2. Проверяет, что одновременное присутствие обеих сторон даёт null (неоднозначно).
     * 3. Проверяет, что пустой массив тоже даёт null.
     */
    public function test_detect_side(): void
    {
        $this->assertSame('front', $this->service->detectSide(['front' => []]));
        $this->assertSame('back', $this->service->detectSide(['back' => []]));
        $this->assertNull($this->service->detectSide(['front' => [], 'back' => []]));
        $this->assertNull($this->service->detectSide([]));
    }

    /**
     * Проверяет нормализацию значений адаптера (приватный normalizeAdapters()) через публичный
     * splitDetails(): приведение массивов adapter values с возможными пустыми/дублирующимися
     * элементами к чистому списку строк, по одному адаптеру на вариант.
     *
     * Шаги:
     * 1. Массив с пустой строкой и дублем схлопывается в 2 уникальных варианта ('A' и 'B').
     * 2. Один adapter внутри массива остаётся одним вариантом.
     * 3. Пустой массив даёт один вариант с пустым списком адаптеров.
     * 4. Массив из пустых/null-значений даёт тот же результат, что и пустой массив.
     */
    public function test_split_details_normalizes_adapter_values(): void
    {
        $duplicates = $this->service->splitDetails(['front' => ['adapter_type_front' => ['A', '', 'B', 'A']]]);
        $this->assertCount(2, $duplicates);
        $this->assertSame(['A'], $duplicates[0]['details']['front']['adapter_type_front']);
        $this->assertSame(['B'], $duplicates[1]['details']['front']['adapter_type_front']);

        $single = $this->service->splitDetails(['front' => ['adapter_type_front' => ['X']]]);
        $this->assertCount(1, $single);
        $this->assertSame(['X'], $single[0]['details']['front']['adapter_type_front']);

        $empty = $this->service->splitDetails(['front' => ['adapter_type_front' => []]]);
        $this->assertCount(1, $empty);
        $this->assertSame([], $empty[0]['details']['front']['adapter_type_front']);

        $emptyAndNull = $this->service->splitDetails(['front' => ['adapter_type_front' => ['', null]]]);
        $this->assertCount(1, $emptyAndNull);
        $this->assertSame([], $emptyAndNull[0]['details']['front']['adapter_type_front']);
    }

    /**
     * Проверяет splitDetails(): details с обеими сторонами разбивается на отдельные записи
     * по стороне, и каждая запись содержит только свой корневой ключ (front/back), не обе.
     *
     * Шаги:
     * 1. Зовёт splitDetails() с details, где заполнены и front, и back.
     * 2. Проверяет, что получилось ровно 2 части.
     * 3. Проверяет side и единственный корневой ключ details для каждой части.
     */
    public function test_split_details_separates_sides_and_keeps_single_root_key(): void
    {
        $parts = $this->service->splitDetails([
            'front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2],
            'back' => ['adapter_type_rear' => ['B1']],
        ]);

        $this->assertCount(2, $parts);
        $this->assertSame('front', $parts[0]['side']);
        $this->assertSame(['front'], array_keys($parts[0]['details']));
        $this->assertSame('back', $parts[1]['side']);
        $this->assertSame(['back'], array_keys($parts[1]['details']));
    }

    /**
     * Проверяет splitDetails(): если у стороны несколько адаптеров, каждый разворачивается в
     * отдельную запись (декартово произведение по адаптеру), а не остаётся одной строкой со
     * списком; прочие поля (count_wipers) при этом копируются в каждый вариант без изменений.
     *
     * Шаги:
     * 1. Зовёт splitDetails() с front, где adapter_type_front=['A1','A2'].
     * 2. Проверяет, что получилось ровно 2 части — по одной на адаптер.
     * 3. Проверяет, что count_wipers сохранился в обеих частях.
     */
    public function test_split_details_expands_multiple_adapters_into_separate_records(): void
    {
        $parts = $this->service->splitDetails([
            'front' => ['adapter_type_front' => ['A1', 'A2'], 'count_wipers' => 2],
        ]);

        $this->assertCount(2, $parts);
        $this->assertSame([['A1']], [$parts[0]['details']['front']['adapter_type_front']]);
        $this->assertSame([['A2']], [$parts[1]['details']['front']['adapter_type_front']]);
        // прочие поля сохраняются в каждом варианте
        $this->assertSame(2, $parts[0]['details']['front']['count_wipers']);
    }

    /**
     * Проверяет splitDetails(): пустая сторона (front без данных) не создаёт пустую запись —
     * в результате остаётся только реально заполненная сторона.
     *
     * Шаги:
     * 1. Зовёт splitDetails() с front=[] и заполненным back.
     * 2. Проверяет, что получилась ровно 1 часть — только 'back'.
     */
    public function test_split_details_skips_empty_side(): void
    {
        $parts = $this->service->splitDetails(['front' => [], 'back' => ['adapter_type_rear' => ['B1']]]);

        $this->assertCount(1, $parts);
        $this->assertSame('back', $parts[0]['side']);
    }

    /**
     * Проверяет splitSpecification(): комбинацию разворачивания по сторонам И по нескольким
     * адаптерам одновременно, плюс прикрепление part_specification_id к каждой части.
     *
     * Шаги:
     * 1. Зовёт splitSpecification() с front (2 адаптера) и back (1 адаптер в массиве).
     * 2. Проверяет, что получилось 3 части (2 варианта front + 1 back).
     * 3. Проверяет, что part_specification_id=123 проставлен во всех частях.
     * 4. Проверяет side и содержимое details для каждой из трёх частей.
     */
    public function test_split_specification_expands_sides_and_adapters(): void
    {
        $details = [
            'front' => ['adapter_type_front' => ['A1', 'A2'], 'count_wipers' => 2],
            'back' => ['adapter_type_rear' => ['B1']],
        ];

        $parts = $this->service->splitSpecification($details, 123);

        $this->assertCount(3, $parts);
        $this->assertSame([123, 123, 123], array_column($parts, 'part_specification_id'));
        $this->assertSame('front', $parts[0]['side']);
        $this->assertSame(['A1'], $parts[0]['details']['front']['adapter_type_front']);
        $this->assertSame('front', $parts[1]['side']);
        $this->assertSame(['A2'], $parts[1]['details']['front']['adapter_type_front']);
        $this->assertSame('back', $parts[2]['side']);
        $this->assertSame(['B1'], $parts[2]['details']['back']['adapter_type_rear']);
    }

    /**
     * Проверяет, что сервис пишет предупреждение через PSR logger, а не через Laravel facade.
     *
     * Шаги:
     * 1. Создать сервис с fake PSR logger.
     * 2. Передать спецификацию с несколькими adapter values.
     * 3. Проверить уровень, сообщение и structured context предупреждения.
     */
    public function test_split_specification_logs_multiple_vehicle_adapters_with_psr_logger(): void
    {
        $logger = new class extends AbstractLogger
        {
            /**
             * @var list<array{level: mixed, message: string, context: array<string, mixed>}>
             */
            public array $records = [];

            /**
             * Сохраняет log record для последующих assertions.
             */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };

        $service = new WiperSpecificationService($logger);

        $service->splitSpecification([
            'front' => ['adapter_type_front' => ['A1', 'A2']],
        ], 321);

        $this->assertCount(1, $logger->records);
        $this->assertSame('warning', $logger->records[0]['level']);
        $this->assertSame('В adapter_type_* найдено более одного значения для ТС', $logger->records[0]['message']);
        $this->assertSame(321, $logger->records[0]['context']['part_specification_id']);
        $this->assertSame('front', $logger->records[0]['context']['side']);
        $this->assertSame(2, $logger->records[0]['context']['adapter_count']);
        $this->assertSame(['A1', 'A2'], $logger->records[0]['context']['adapters']);
    }

    /**
     * Проверяет mergeForExport(): обратную операцию для экспорта — front/back-данные (уже
     * разделённые на импорте) снова собираются в одно дерево details под соответствующими
     * ключами, с корректной обработкой отсутствующей стороны.
     *
     * Шаги:
     * 1. Проверяет, что непустые front и back дают дерево с обоими ключами.
     * 2. Проверяет, что пустой back не создаёт ключ 'back' в результате.
     * 3. Проверяет, что оба пустых входа дают пустой результат.
     */
    public function test_merge_for_export(): void
    {
        $this->assertSame(
            ['front' => ['x' => 1], 'back' => ['y' => 2]],
            $this->service->mergeForExport(['x' => 1], ['y' => 2]),
        );
        $this->assertSame(['front' => ['x' => 1]], $this->service->mergeForExport(['x' => 1], []));
        $this->assertSame([], $this->service->mergeForExport([], []));
    }
}
