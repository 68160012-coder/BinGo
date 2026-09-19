"""Logic หลักของ Bangsaen Waste Watch"""

VALID_WASTE_TYPES = {"general", "recyclable", "hazardous", "organic"}


def proper_disposal_rate(generated_tpd, proper_tpd):
    """คืนร้อยละของขยะที่กำจัดถูกต้อง ปัดทศนิยม 1 ตำแหน่ง

    - generated_tpd ต้อง > 0 มิฉะนั้น ValueError
    - proper_tpd ต้อง >= 0 และ <= generated_tpd มิฉะนั้น ValueError
    - ไม่รับ bool และไม่รับ string
    """
    # ตรวจสอบชนิดข้อมูล (reject bool ก่อนเพราะ bool เป็น subclass ของ int)
    if isinstance(generated_tpd, bool) or isinstance(proper_tpd, bool):
        raise ValueError("generated_tpd และ proper_tpd ต้องเป็นตัวเลข")
    if not isinstance(generated_tpd, (int, float)) or not isinstance(proper_tpd, (int, float)):
        raise ValueError("generated_tpd และ proper_tpd ต้องเป็นตัวเลข")

    # ตรวจสอบขอบเขตค่า
    if generated_tpd <= 0:
        raise ValueError("generated_tpd ต้องมากกว่า 0")
    if proper_tpd < 0:
        raise ValueError("proper_tpd ต้องไม่น้อยกว่า 0")
    if proper_tpd > generated_tpd:
        raise ValueError("proper_tpd ต้องไม่มากกว่า generated_tpd")

    rate = (proper_tpd / generated_tpd) * 100
    return round(rate, 1)


def validate_report(data):
    """ตรวจข้อมูลแจ้งจุดขยะจาก form แล้วคืน dict ที่สะอาด

    - location: str ตัดช่องว่างหัวท้าย ยาว 3–100 ตัวอักษร
    - waste_type: หนึ่งใน VALID_WASTE_TYPES
    - amount_kg: int 1–1000 (รับ string ตัวเลขได้ เช่น "20" -> 20)
    - detail: str ไม่บังคับ ยาวไม่เกิน 500 ตัวอักษร ค่าเริ่มต้น ""
    - ผิดข้อใด -> ValueError พร้อมข้อความบอกชื่อฟิลด์
    """
    if not isinstance(data, dict):
        raise ValueError("data ต้องเป็น dict")

    # --- location ---
    location = data.get("location")
    if not isinstance(location, str):
        raise ValueError("location ต้องเป็น string")
    location = location.strip()
    if not (3 <= len(location) <= 100):
        raise ValueError("location ต้องมีความยาว 3–100 ตัวอักษร")

    # --- waste_type ---
    waste_type = data.get("waste_type")
    if not isinstance(waste_type, str):
        raise ValueError("waste_type ต้องเป็น string")
    if waste_type not in VALID_WASTE_TYPES:
        raise ValueError(f"waste_type ต้องเป็นหนึ่งใน {VALID_WASTE_TYPES}")

    # --- amount_kg ---
    amount_kg = data.get("amount_kg")
    if isinstance(amount_kg, bool):
        raise ValueError("amount_kg ต้องเป็น int")
    if isinstance(amount_kg, str):
        if not amount_kg.strip().isdigit():
            raise ValueError("amount_kg ต้องเป็นตัวเลข")
        amount_kg = int(amount_kg.strip())
    if not isinstance(amount_kg, int):
        raise ValueError("amount_kg ต้องเป็น int")
    if not (1 <= amount_kg <= 1000):
        raise ValueError("amount_kg ต้องอยู่ในช่วง 1–1000")

    # --- detail ---
    detail = data.get("detail")
    if detail is None:
        detail = ""
    if not isinstance(detail, str):
        raise ValueError("detail ต้องเป็น string")
    detail = detail.strip()
    if len(detail) > 500:
        raise ValueError("detail ต้องไม่เกิน 500 ตัวอักษร")

    return {
        "location": location,
        "waste_type": waste_type,
        "amount_kg": amount_kg,
        "detail": detail,
    }