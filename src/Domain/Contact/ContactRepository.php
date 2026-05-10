<?php
declare(strict_types=1);

namespace Siappos\Domain\Contact;

use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $businessId, ?string $type = null): array
    {
        $sql = 'SELECT * FROM contacts WHERE business_id = :business_id';
        $params = [':business_id' => $businessId];

        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params[':type'] = $type;
        }

        $sql .= ' ORDER BY name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function create(int $businessId, string $type, string $name, ?string $email, ?string $phone, ?string $address): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contacts (business_id, type, name, email, phone, address, created_at, updated_at)
             VALUES (:business_id, :type, :name, :email, :phone, :address, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':business_id' => $businessId,
            ':type' => $type,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
        ]);
    }
}
