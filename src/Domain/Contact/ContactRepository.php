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

    public function find(int $id, int $businessId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contacts WHERE id = :id AND business_id = :business_id');
        $stmt->execute([':id' => $id, ':business_id' => $businessId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getLedger(int $contactId, int $businessId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.transaction_number, t.type, t.status, t.payment_status, t.total_cents, t.created_at,
                   COALESCE(SUM(tp.amount_cents), 0) as paid_cents
            FROM transactions t
            LEFT JOIN transaction_payments tp ON t.id = tp.transaction_id
            WHERE t.contact_id = :contact_id AND t.business_id = :business_id
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([
            ':contact_id' => $contactId,
            ':business_id' => $businessId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
