<?php

namespace Tests\Unit\Contracts;

use App\Models\Contract;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContractOwnershipLookupTest extends TestCase
{
    public function test_require_api_user_id_aborts_when_guest(): void
    {
        $this->assertGuest();

        try {
            Contract::requireApiUserId();
            $this->fail('Expected unauthenticated abort.');
        } catch (HttpException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }

    public function test_require_api_user_id_returns_authenticated_user_id(): void
    {
        $user = new User();
        $user->id = 42;
        $this->actingAs($user);

        $this->assertSame(42, Contract::requireApiUserId());
    }

    public function test_find_for_staff_aborts_when_guest(): void
    {
        $this->assertGuest();

        try {
            Contract::findForStaffOrFail(1);
            $this->fail('Expected unauthenticated abort.');
        } catch (HttpException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }
}
