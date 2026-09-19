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

    // 1. หาปีล่าสุดที่มีข้อมูล
    $stmtYear = $pdo->prepare("SELECT MAX(year) AS latest_year FROM waste_stats");
    $stmtYear->execute();
    $latestYear = (int) $stmtYear->fetchColumn();

    if ($latestYear === 0) {
        json_response([
            "year" => null,
            "top" => [],
            "bangsaen" => null
        ]);
    }

    // 2. Query Top 5 local_gov ที่สร้างขยะมากที่สุดในปีล่าสุด
    $stmtTop = $pdo->prepare("
        SELECT local_gov, generated_tpd 
        FROM waste_stats 
        WHERE year = :year AND generated_tpd IS NOT NULL
        ORDER BY generated_tpd DESC 
        LIMIT 5
    ");
    $stmtTop->execute([':year' => $latestYear]);
    $topData = $stmtTop->fetchAll();

    // จัดรูปแบบ top ให้เป็น array ของ object ตาม spec
    $top = array_map(function ($row) {
        return [
            'local_gov' => $row['local_gov'],
            'generated_tpd' => (float) $row['generated_tpd']
        ];
    }, $topData);

    // 3. Query ข้อมูลของเทศบาลเมืองแสนสุขในปีล่าสุด
    $stmtBangsaen = $pdo->prepare("
        SELECT generated_tpd, proper_tpd 
        FROM waste_stats 
        WHERE year = :year AND local_gov = :local_gov
        LIMIT 1
    ");
    $stmtBangsaen->execute([
        ':year' => $latestYear,
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

    // คืนค่า JSON Response
    json_response([ 
        "year" => $latestYear,
        "top" => $top,
        "bangsaen" => $bangsaen
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("database error", 500);
}