<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Support\MessageAlertType;
use Illuminate\Http\Request;

/**
 * Admin alias for client application messages (رسائل التطبيق للعميل).
 *
 * @see MessageAlertController
 */
class CustomerApplicationMessageController extends Controller
{
    use Responser;

    public function __construct(
        protected MessageAlertController $messageAlerts
    ) {}

    public function overview()
    {
        return $this->messageAlerts->types();
    }

    public function all(Request $request)
    {
        $request->merge(['type' => MessageAlertType::CLIENT]);

        return $this->messageAlerts->all($request);
    }

    public function createForm(Request $request)
    {
        return $this->messageAlerts->create($request, MessageAlertType::CLIENT);
    }

    public function index(Request $request)
    {
        return $this->messageAlerts->index($request, MessageAlertType::CLIENT);
    }

    public function store(Request $request)
    {
        return $this->messageAlerts->store($request, MessageAlertType::CLIENT);
    }

    public function show(Request $request, int $id)
    {
        $request->merge(['type' => MessageAlertType::CLIENT]);

        return $this->messageAlerts->show($request, $id);
    }

    public function update(Request $request, int $id)
    {
        $request->merge(['type' => MessageAlertType::CLIENT]);

        return $this->messageAlerts->update($request, $id);
    }

    public function destroy(Request $request, int $id)
    {
        $request->merge(['type' => MessageAlertType::CLIENT]);

        return $this->messageAlerts->destroy($request, $id);
    }
}
