<?php

namespace App\Http\Resources\Concerns;

trait WithContractDocumentationDeadline
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withDocumentationDeadline(array $data): array
    {
        $data['time_to_documentation_contract'] = $this->resource->documentationDeadlineAt()
            ?->format('Y-m-d H:i:s');

        return $data;
    }
}
