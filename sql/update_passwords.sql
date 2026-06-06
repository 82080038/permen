-- Update test user passwords to 'password'
UPDATE users SET password_hash = '$2y$10$Oad4ERJn3ANxJzyw5uoYNugAs623F6EVoBttmvuFU9SSos7feKeSm' WHERE no_hp = '081234567890';
UPDATE users SET password_hash = '$2y$10$Oad4ERJn3ANxJzyw5uoYNugAs623F6EVoBttmvuFU9SSos7feKeSm' WHERE no_hp = '081987654321';
