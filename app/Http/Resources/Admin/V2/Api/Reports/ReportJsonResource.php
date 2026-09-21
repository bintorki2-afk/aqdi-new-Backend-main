<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class ReportJsonResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Merge period tabs / date bounds into the payload before resolve().
     *
     * @param  array{periods?: list<array<string, mixed>>, period?: string, date_from?: string|null, date_to?: string|null}  $meta
     */
    public function withPeriod(array $meta): static
    {
        $this->resource = array_merge($meta, is_array($this->resource) ? $this->resource : []);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function periodFields(): array
    {
        if (! is_array($this->resource) || ! array_key_exists('period', $this->resource)) {
            return [];
        }

        return [
            'periods' => $this->collectResolve(
                ReportPeriodTabResource::class,
                $this->resource['periods'] ?? []
            ),
            'period' => $this->resource['period'] ?? null,
            'date_from' => $this->resource['date_from'] ?? null,
            'date_to' => $this->resource['date_to'] ?? null,
        ];
    }

    /**
     * @param  class-string<JsonResource>  $resource
     * @return list<array<string, mixed>>
     */
    protected function collectResolve(string $resource, mixed $items): array
    {
        $list = is_array($items) ? array_values($items) : [];

        return $resource::collection($list)->resolve();
    }

    /**
     * @param  class-string<JsonResource>  $resource
     * @return array<string, mixed>|null
     */
    protected function itemResolve(string $resource, mixed $item): ?array
    {
        if ($item === null) {
            return null;
        }

        return (new $resource($item))->resolve();
    }

    /**
     * @param  class-string<JsonResource>  $resource
     * @return array<string, mixed>|null
     */
    protected function resolveItem(mixed $item, string $resource): ?array
    {
        if ($item instanceof JsonResource) {
            return $item->resolve();
        }

        return $this->itemResolve($resource, $item);
    }

    /**
     * @param  class-string<JsonResource>  $resource
     * @return list<array<string, mixed>>
     */
    protected function resolveList(mixed $items, string $resource): array
    {
        if ($items instanceof ResourceCollection) {
            return $items->resolve();
        }

        return $this->collectResolve($resource, $items);
    }
}
