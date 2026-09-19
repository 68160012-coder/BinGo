<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

// ตรวจสอบ Method ต้องเป็น GET เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error("Method Not Allowed", 405);
}

try {
    $pdo = db();

    // --- ส่วนที่แก้: รับ year จาก GET parameter ---
    $yearParam = $_GET['year'] ?? null;
    $targetYear = null;

    if ($yearParam !== null) {
        // Validate ว่าเป็นตัวเลข 4 หลัก (พ.ศ.)
        if (!ctype_digit($yearParam) || strlen($yearParam) !== 4) {
            json_error("Invalid year format", 400);
        }
        $targetYear = (int) $yearParam;
    } else {
        // ถ้าไม่ระบุปี ให้หาปีล่าสุด
        $stmtMax = $pdo->prepare("SELECT MAX(year) FROM waste_stats");
        $stmtMax->execute();
        $targetYear = (int) $stmtMax->fetchColumn();
    }

    if ($targetYear === 0) {
        json_response([
            "year" => null,
            "top" => [],
            "bangsaen" => null,
            "available_years" => []
        ]);
    }

    // 2. Query Top 5 local_gov ที่สร้างขยะมากที่สุดในปีที่เลือก
    $stmtTop = $pdo->prepare("
        SELECT local_gov, generated_tpd 
        FROM waste_stats 
        WHERE year = :year AND generated_tpd IS NOT NULL
        ORDER BY generated_tpd DESC 
        LIMIT 5
    ");
    $stmtTop->execute([':year' => $targetYear]);
    $topData = $stmtTop->fetchAll();

    // จัดรูปแบบ top ให้เป็น array ของ object ตาม spec
    $top = array_map(function ($row) {
        return [
            'local_gov' => $row['local_gov'],
            'generated_tpd' => (float) $row['generated_tpd']
        ];
    }, $topData);

    // 3. Query ข้อมูลของเทศบาลเมืองแสนสุขในปีที่เลือก
    $stmtBangsaen = $pdo->prepare("
        SELECT generated_tpd, proper_tpd 
        FROM waste_stats 
        WHERE year = :year AND local_gov = :local_gov
        LIMIT 1
    ");
    $stmtBangsaen->execute([
        ':year' => $targetYear,
        ':local_gov' => 'เทศบาลเมืองแสนสุข'
    ]);
    $bangsaenRow = $stmtBangsaen->fetch();

    // คำนวณ proper_rate
    $bangsaen = null;
    if ($bangsaenRow) {
        $generated = $bangsaenRow['generated_tpd'];
        $proper = $bangsaenRow['proper_tpd'];
        
        $properRate = null;
        if ($generated !== null && (float)$generated > 0 && $proper !== null) {
            $properRate = round(((float)$proper / (float)$generated) * 100, 1);
        }

        $bangsaen = [
            'generated_tpd' => $generated !== null ? (float)$generated : null,
            'proper_tpd' => $proper !== null ? (float)$proper : null,
            'proper_rate' => $properRate
        ];
    }

    // --- (เพิ่ม) ดึงรายการปีทั้งหมดที่มีข้อมูล เพื่อส่งไปทำ Dropdown ---
    $stmtYears = $pdo->prepare("SELECT DISTINCT year FROM waste_stats ORDER BY year DESC");
    $stmtYears->execute();
    $availableYears = $stmtYears->fetchAll(PDO::FETCH_COLUMN);

    // คืนค่า JSON Response
    json_response([
        "year" => $targetYear,
        "top" => $top,
        "bangsaen" => $bangsaen,
        "available_years" => $availableYears
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("database error", 500);
}