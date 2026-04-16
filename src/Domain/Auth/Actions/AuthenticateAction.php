<?php
declare(strict_types=1);

namespace Siappos\Domain\Auth\Actions;

use Siappos\Domain\Auth\LoginData;
use Siappos\Domain\Auth\UserRepository;

final class AuthenticateAction
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @return array<string, mixed>|null */
    public function execute(LoginData $data): ?array
    {
        $user = $this->users->findByUsername($data->username);

        if (!is_array($user)) {
            return null;
        }

        if (!password_verify($data->pin, (string) $user['pin_hash'])) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'role' => (string) $user['role'],
        ];
    }
}
