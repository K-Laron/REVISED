<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use RuntimeException;

class IdGenerator
{
    public static function next(string $sequenceKey): string
    {
        $year = (int) date('Y');
        $ownsTransaction = !Database::connect()->inTransaction();

        try {
            if ($ownsTransaction) {
                Database::beginTransaction();
            }

            $sequence = Database::fetch(
                'SELECT * FROM id_sequences WHERE sequence_key = :sequence_key AND current_year = :current_year FOR UPDATE',
                ['sequence_key' => $sequenceKey, 'current_year' => $year]
            );

            if ($sequence === false) {
                $fallback = Database::fetch(
                    'SELECT prefix FROM id_sequences WHERE sequence_key = :sequence_key ORDER BY current_year DESC LIMIT 1',
                    ['sequence_key' => $sequenceKey]
                );

                if ($fallback === false) {
                    throw new RuntimeException(sprintf('Sequence [%s] is not configured.', $sequenceKey));
                }

                Database::execute(
                    'INSERT INTO id_sequences (sequence_key, prefix, current_year, last_number) VALUES (:sequence_key, :prefix, :current_year, 0)',
                    ['sequence_key' => $sequenceKey, 'prefix' => $fallback['prefix'], 'current_year' => $year]
                );

                $sequence = [
                    'prefix' => $fallback['prefix'],
                    'last_number' => 0,
                ];
            }

            $nextNumber = ((int) $sequence['last_number']) + 1;

            Database::execute(
                'UPDATE id_sequences SET last_number = :last_number WHERE sequence_key = :sequence_key AND current_year = :current_year',
                ['last_number' => $nextNumber, 'sequence_key' => $sequenceKey, 'current_year' => $year]
            );

            if ($ownsTransaction) {
                Database::commit();
            }

            return sprintf('%s-%d-%04d', $sequence['prefix'], $year, $nextNumber);
        } catch (\Throwable $exception) {
            if ($ownsTransaction) {
                Database::rollBack();
            }
            throw $exception;
        }
    }
}
