<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\UseCases;

use App\Models\User;
use App\Modules\Auth\Application\DTOs\RegisterUserInput;
use App\Modules\Auth\Domain\Events\UserRegistered;
use App\Modules\Auth\Domain\Factories\TenantOwnerFactory;
use App\Modules\Tenant\Domain\Events\TenantCreated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;

final class RegisterUser
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
        private readonly TenantOwnerFactory $factory,
    ) {}

    public function execute(RegisterUserInput $input): User
    {
        return $this->db->transaction(function () use ($input): User {
            $created = $this->factory->create($input);

            $this->events->dispatch(new TenantCreated($created->tenant));
            $this->events->dispatch(new UserRegistered($created->user, $created->tenant));

            return $created->user;
        });
    }
}
