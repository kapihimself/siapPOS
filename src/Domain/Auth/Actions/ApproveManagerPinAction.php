<?php
declare(strict_types=1);

namespace Siappos\Domain\Auth\Actions;

use RuntimeException;
use Siappos\Domain\Auth\UserRepository;

final class ApproveManagerPinAction
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @return array<string, mixed> */
    public function execute(string $pin): array
    {
        $cleanPin = trim($pin);

        if (!preg_match('/^\d{4,6}$/', $cleanPin)) {
            throw new RuntimeException('PIN persetujuan tidak valid.');
        }

        $user = $this->users->findByPin($cleanPin);

        if (!is_array($user)) {
            throw new RuntimeException('PIN persetujuan tidak ditemukan.');
        }

        if (!in_array((string) $user['role'], ['manager', 'admin'], true)) {
            throw new RuntimeException('Persetujuan hanya bisa oleh Manager/Admin.');
        }

        return [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'role' => (string) $user['role'],
        ];
    }
}
