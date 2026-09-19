import csv
from waste_logic import proper_disposal_rate

# 1) พิมพ์ 5 อปท.ที่ generated_tpd สูงสุดในปีล่าสุด
with open('../data/waste_chonburi.csv', 'r') as file:
    reader = csv.DictReader(file)
    max_generated_tpd = 0
    top_5_generated_tpd = []
    row_count = 0

    for row in reader:
        if row['generated_tpd'] and row['generated_tpd'] != 'NaN':
            generated_tpd = float(row['generated_tpd'])
            if generated_tpd > max_generated_tpd:
                max_generated_tpd = generated_tpd
                top_5_generated_tpd = [row]
            elif generated_tpd == max_generated_tpd:
                top_5_generated_tpd.append(row)

        row_count += 1

    print("5 อปท.ที่ generated_tpd สูงสุด:")
    for i, row in enumerate(top_5_generated_tpd, start=1):
        print(f"{i}. {row['year']} - {row['generated_tpd']}")

# 2) พิมพ์อัตรากำจัดถูกต้องของ "เทศบาลเมืองแสนสุข" ทุกปี
with open('../data/waste_chonburi.csv', 'r') as file:
    reader = csv.DictReader(file)
    proper_disposal_rate_data = []

    for row in reader:
        if row['district'] == 'เทศบาลเมืองแสนสุข':
            proper_disposal_rate_data.append(row)

    print("\nอัตรากำจัดถูกต้องของ 'เทศบาลเมืองแสนสุข':")
    for row in proper_disposal_rate_data:
        print(f"{row['year']}: {row['proper_disposal_rate']}")

print(f"\nTotal rows skipped: {row_count}")