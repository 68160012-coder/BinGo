<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/validate.php';

$method = $_SERVER['REQUEST_METHOD'];

// ตรวจสอบ Method ต้องเป็น GET หรือ POST เท่านั้น
if ($method !== 'GET' && $method !== 'POST') {
    json_error("Method Not Allowed", 405);
}

try {
    $pdo = db();

    if ($method === 'GET') {
        // --- ส่วนที่เพิ่ม: รับและตรวจสอบพารามิเตอร์ type ---
        $type = $_GET['type'] ?? null;
        $allowedTypes = ['general', 'recyclable', 'hazardous', 'organic'];
        
        if ($type !== null && !in_array($type, $allowedTypes, true)) {
            json_error("Invalid waste_type filter", 422);
        }

        // สร้าง query แบบ dynamic แต่ปลอดภัยด้วย prepared statement
        $sql = "SELECT id, location, waste_type, amount_kg, detail, status, 
                       has_nearby_bin, bin_full, created_at
                FROM reports";
        $params = [];
        
        if ($type !== null) {
            $sql .= " WHERE waste_type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        // -----------------------------------------------

        $reports = $stmt->fetchAll();

        // แปลงชนิดข้อมูลให้ตรงกับ JSON format ที่คาดหวัง
        foreach ($reports as &$row) {
            $row['id'] = (int) $row['id'];
            $row['amount_kg'] = $row['amount_kg'] !== null ? (int) $row['amount_kg'] : null;
            $row['has_nearby_bin'] = (bool) $row['has_nearby_bin'];
            $row['bin_full'] = (bool) $row['bin_full'];
        }
        unset($row);

        json_response($reports);

    } elseif ($method === 'POST') {
        // อ่าน JSON body
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            json_error("Invalid JSON", 400);
        }

        // Validate ข้อมูล
        try {
            $cleanData = validate_report($data);
        } catch (InvalidArgumentException $e) {
            json_error($e->getMessage(), 422);
        }

        // INSERT ลงฐานข้อมูลด้วย prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO reports (location, waste_type, amount_kg, detail)
            VALUES (:location, :waste_type, :amount_kg, :detail)
        ");
        $stmt->execute([
            ':location'   => $cleanData['location'],
            ':waste_type' => $cleanData['waste_type'],
            ':amount_kg'  => $cleanData['amount_kg'],
            ':detail'     => $cleanData['detail'],
        ]);

        $newId = (int) $pdo->lastInsertId();

        // คืนค่าข้อมูลที่บันทึกพร้อม id ใหม่
        json_response([
            'id'         => $newId,
            'location'   => $cleanData['location'],
            'waste_type' => $cleanData['waste_type'],
            'amount_kg'  => $cleanData['amount_kg'],
            'detail'     => $cleanData['detail'],
            'status'     => 'new',
        ], 201);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("database error", 500);
}