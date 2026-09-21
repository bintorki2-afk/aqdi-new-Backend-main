<?php

namespace App\Modules\Contracts\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait AdminSearchableContract
{
    /**
     * Admin list search across `contracts` columns plus linked user / status / payments / employee.
     */
    public function scopeAdminSearch(Builder $query, ?string $term): Builder
    {
        $term = is_string($term) ? trim($term) : '';
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';
        $table = $query->getModel()->getTable();

        return $query->where(function (Builder $q) use ($like, $term, $table) {
            foreach (static::adminSearchableContractColumns() as $column) {
                $q->orWhere("{$table}.{$column}", 'like', $like);
            }

            if (ctype_digit($term)) {
                $q->orWhere("{$table}.{$q->getModel()->getKeyName()}", (int) $term);
            }

            $q->orWhereHas('user', function (Builder $uq) use ($like) {
                static::applyLikeOnColumns($uq, 'users', [
                    'fname',
                    'lname',
                    'mobile',
                    'email',
                ], $like);

                if (static::tableHasColumns('users', ['fname', 'lname'])) {
                    $uq->orWhereRaw(
                        "CONCAT(COALESCE(fname, ''), ' ', COALESCE(lname, '')) LIKE ?",
                        [$like]
                    );
                }
            });

            $q->orWhereHas('contractStatus', function (Builder $sq) use ($like) {
                static::applyLikeOnColumns($sq, 'contract_statuses', [
                    'name',
                    'description',
                    'color',
                ], $like);
            });

            $q->orWhereHas('contractPayments', function (Builder $pq) use ($like) {
                static::applyLikeOnColumns($pq, 'payments', [
                    'amount',
                    'status',
                    'payment_method',
                    'contract_uuid',
                    'tran_currency',
                    'name',
                    'name_payment',
                ], $like);
            });

            $q->orWhereHas('receivedContract.employee', function (Builder $eq) use ($like) {
                static::applyLikeOnColumns($eq, 'employees', [
                    'name',
                    'phone',
                    'email',
                ], $like);
            });
        });
    }

    /**
     * @param  list<string>  $columns
     */
    protected static function applyLikeOnColumns(
        Builder $query,
        string $table,
        array $columns,
        string $like
    ): void {
        $existing = static::filterExistingColumns($table, $columns);

        foreach ($existing as $index => $column) {
            if ($index === 0) {
                $query->where("{$table}.{$column}", 'like', $like);
            } else {
                $query->orWhere("{$table}.{$column}", 'like', $like);
            }
        }
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    protected static function filterExistingColumns(string $table, array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column) => static::tableHasColumns($table, [$column])
        ));
    }

    /**
     * @param  list<string>  $columns
     */
    protected static function tableHasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return $columns !== [];
    }

    /**
     * @return list<string>
     */
    public static function adminSearchableContractColumns(): array
    {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $instance = new static;
        $table = $instance->getTable();
        $all = Schema::getColumnListing($table);

        $exclude = [
            'id',
            'password',
            'remember_token',
        ];

        $columns = array_values(array_filter(
            $all,
            fn (string $col) => ! in_array($col, $exclude, true)
        ));

        return $columns;
    }
}
