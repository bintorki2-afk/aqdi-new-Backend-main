<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Paperwork;
use Illuminate\Http\Request;

class PersistPaperworkAction
{
    public function execute(Request $request, ?Paperwork $paperwork = null): Paperwork
    {
        $validated = $request->validated();

        if ($request->hasFile('icon')) {
            if ($paperwork?->icon) {
                $this->deleteIconFile($paperwork);
            }
            $validated['icon'] = fileUploader($request->file('icon'), 'paperworks');
        }

        if ($paperwork) {
            $paperwork->update($validated);

            return $paperwork->fresh();
        }

        return Paperwork::query()->create($validated);
    }

    public function deleteIconFile(Paperwork $paperwork): void
    {
        if ($paperwork->icon) {
            deleteFile($paperwork->icon);
        }
    }
}
