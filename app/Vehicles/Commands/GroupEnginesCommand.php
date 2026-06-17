<?php

declare(strict_types=1);

namespace App\Vehicles\Commands;

use App\Vehicles\Imports\EnginesCodeImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;

/**
 * @deprecated
 */
class GroupEnginesCommand extends Command
{
    protected $signature = 'engines:group';

    protected $description = 'Группировка двигателей на основе кросс-строк из Excel';

    /**
     * Разбиваем строку: Берем строку (например, A, B, C), чистим от пробелов и получаем список кодов.
     * Ищем, где они лежат: Проверяем каждый код из этого списка: не лежит ли он уже в какой-то из наших коробок?
     *
     * Выбираем сценарий:
     * 1. Никто нигде не лежит: Все коды из строки новые. Берем новую группу, присваиваем следующий номер (ID группы) и кидаем туда A, B и C.
     * 2. Один (или несколько) уже в одной группе. Пример: A уже лежит в Группе №5. Значит, B и C тоже должны быть там. Просто докладываем их в Группу №5.
     * 3. Коды из разных групп. Пример: A лежит в Группе №1, а B — в Группе №2. Но в текущей строке они написаны вместе. Значит, это одна и та же группа и мы их объединяем в одну.
     *
     * @return void
     *
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function handle()
    {
        $filePath = storage_path('vehicles/enginescodes.xlsx');
        $data = Excel::toCollection(new EnginesCodeImport, $filePath)->first();
        $groups = [];
        $codeToGroupId = [];
        $nextGroupId = 1;

        foreach ($data as $row) {
            $rawLine = $row[0];
            if (empty($rawLine)) {
                continue;
            }

            $codes = collect(explode(';', (string) $rawLine))
                ->map(fn ($c) => trim(strtoupper($c)))
                ->filter()
                ->unique();

            // Проверяем, в каких существующих группах уже есть эти коды
            $foundGroupIds = $codes->map(fn ($c) => $codeToGroupId[$c] ?? null)
                ->filter()
                ->unique();

            if ($foundGroupIds->isEmpty()) {
                $currentGroupId = $nextGroupId++;
                $groups[$currentGroupId] = $codes->toArray();
                foreach ($codes as $c) {
                    $codeToGroupId[$c] = $currentGroupId;
                }
            } elseif ($foundGroupIds->count() === 1) {
                $currentGroupId = $foundGroupIds->first();
                $newCodes = $codes->diff($groups[$currentGroupId]);
                foreach ($newCodes as $c) {
                    $groups[$currentGroupId][] = $c;
                    $codeToGroupId[$c] = $currentGroupId;
                }
            } else {
                $targetGroupId = $foundGroupIds->min();

                foreach ($foundGroupIds as $oldGroupId) {
                    if ($oldGroupId === $targetGroupId) {
                        continue;
                    }

                    foreach ($groups[$oldGroupId] as $c) {
                        if (! in_array($c, $groups[$targetGroupId])) {
                            $groups[$targetGroupId][] = $c;
                        }
                        $codeToGroupId[$c] = $targetGroupId;
                    }
                    unset($groups[$oldGroupId]);
                }

                foreach ($codes as $c) {
                    if (! in_array($c, $groups[$targetGroupId])) {
                        $groups[$targetGroupId][] = $c;
                    }
                    $codeToGroupId[$c] = $targetGroupId;
                }
            }
        }

        $exportData = [];
        foreach ($groups as $id => $engineCodes) {
            $exportData[] = [
                'group_id' => $id,
                'engine_codes' => implode('; ', $engineCodes),
            ];
        }

        $outputFile = 'grouped_engines_'.time().'.xlsx';
        Excel::store(new class($exportData) implements FromCollection
        {
            public function __construct(public $data) {}

            public function collection()
            {
                return collect($this->data);
            }
        }, $outputFile, 'public');

        $this->info("Готово! Файл сохранен в storage/app/public/$outputFile");
    }
}
