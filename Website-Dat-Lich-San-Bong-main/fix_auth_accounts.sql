USE football_7_booking;

UPDATE users
SET password = '$2y$10$iZp.c2ItCEVEwo6EaY6XYOkIU4JuDcaF0jVaZqv82oHi3GbEjg0wC',
    role = 'admin'
WHERE email = 'admin@example.com';

UPDATE users
SET password = '$2y$10$WBdWI24RRjfQiOVXpeRVIOyBxj0DMGTMQAPg9Jru.r.jk0bouwS9e',
    role = 'customer'
WHERE email = 'khach@example.com';
