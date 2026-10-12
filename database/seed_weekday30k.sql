INSERT INTO promotions (
    code,
    name,
    discount_type,
    discount_value,
    discount_percent,
    start_date,
    end_date,
    min_amount,
    usage_limit,
    status
) VALUES (
    'WEEKDAY30K',
    'Giảm 30.000đ ngày thường',
    'fixed',
    30000,
    0,
    '2026-04-27 00:00:00',
    '2026-07-31 23:59:59',
    150000,
    0,
    1
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    discount_type = VALUES(discount_type),
    discount_value = VALUES(discount_value),
    discount_percent = VALUES(discount_percent),
    start_date = VALUES(start_date),
    end_date = VALUES(end_date),
    min_amount = VALUES(min_amount),
    usage_limit = VALUES(usage_limit),
    status = VALUES(status);
