<?php
declare(strict_types=1);

const VALID_WASTE_TYPES = ['general', 'recyclable', 'hazardous', 'organic'];

/**
 * ตรวจข้อมูลแจ้งจุดขยะจาก form แล้วคืน array ที่สะอาด
 *
 * @param array $data ข้อมูลจาก form
 * @return array ข้อมูลที่ผ่านการ validate และทำความสะอาดแล้ว
 * @throws InvalidArgumentException เมื่อข้อมูลไม่ถูกต้อง
 */
function validate_report(array $data): array
{
    // --- location ---
    if (!array_key_exists('location', $data) || !is_string($data['location'])) {
        throw new InvalidArgumentException("location ต้องเป็น string");
    }
    $location = trim($data['location']);
    $locLen = mb_strlen($location, 'UTF-8');
    if ($locLen < 3 || $locLen > 100) {
        throw new InvalidArgumentException("location ต้องมีความยาว 3–100 ตัวอักษร");
    }

    // --- waste_type ---
    if (!array_key_exists('waste_type', $data) || !is_string($data['waste_type'])) {
        throw new InvalidArgumentException("waste_type ต้องเป็น string");
    }
    $wasteType = $data['waste_type'];
    if (!in_array($wasteType, VALID_WASTE_TYPES, true)) {
        throw new InvalidArgumentException("waste_type ต้องเป็นหนึ่งใน " . json_encode(VALID_WASTE_TYPES));
    }

    // --- amount_kg ---
    if (!array_key_exists('amount_kg', $data)) {
        throw new InvalidArgumentException("amount_kg ต้องเป็น int");
    }
    $amountKg = $data['amount_kg'];

    // ปฏิเสธ bool และ float ทันที
    if (is_bool($amountKg) || is_float($amountKg)) {
        throw new InvalidArgumentException("amount_kg ต้องเป็น int");
    }

    // รับ string ที่เป็นจำนวนเต็มล้วน
    if (is_string($amountKg)) {
        $trimmed = trim($amountKg);
        if ($trimmed === '' || !ctype_digit($trimmed)) {
            throw new InvalidArgumentException("amount_kg ต้องเป็นตัวเลข");
        }
        $amountKg = (int) $trimmed;
    }

    // ต้องเป็น int เท่านั้น (หลังแปลง string แล้ว)
    if (!is_int($amountKg)) {
        throw new InvalidArgumentException("amount_kg ต้องเป็น int");
    }

    if ($amountKg < 1 || $amountKg > 1000) {
        throw new InvalidArgumentException("amount_kg ต้องอยู่ในช่วง 1–1000");
    }

    // --- detail ---
    $detail = $data['detail'] ?? '';
    if ($detail === null) {
        $detail = '';
    }
    if (!is_string($detail)) {
        throw new InvalidArgumentException("detail ต้องเป็น string");
    }
    $detail = trim($detail);
    if (mb_strlen($detail, 'UTF-8') > 500) {
        throw new InvalidArgumentException("detail ต้องไม่เกิน 500 ตัวอักษร");
    }

    return [
        'location'   => $location,
        'waste_type' => $wasteType,
        'amount_kg'  => $amountKg,
        'detail'     => $detail,
    ];
}