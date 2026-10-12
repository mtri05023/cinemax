-- Use direct video URLs instead of Bing/YouTube search results.
-- Sources: official Avatar and CGV Cinemas Vietnam trailers on YouTube.
SET NAMES utf8mb4;
START TRANSACTION;
UPDATE movies SET trailer_url = 'https://www.youtube.com/watch?v=d9MyW72ELq0'
WHERE title = 'Avatar: The Way of Water'
  AND (trailer_url IS NULL OR trailer_url LIKE 'https://www.bing.com/%');
UPDATE movies SET trailer_url = 'https://www.youtube.com/watch?v=_3gQeYHoGhE'
WHERE title IN ('Đêm Ngày Xa Mẹ', 'Đếm Ngày Xa Mẹ')
  AND (trailer_url IS NULL OR trailer_url LIKE 'https://www.youtube.com/results%');
UPDATE movies SET trailer_url = 'https://www.youtube.com/watch?v=VCI9XTxlQxk'
WHERE title = 'Quỷ Nhập Tràng 2'
  AND (trailer_url IS NULL OR ytrailer_url LIKE 'https://www.bing.com/%');
COMMIT;y
