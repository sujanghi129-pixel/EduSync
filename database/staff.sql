CREATE TABLE Staff (
    staffId INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    role ENUM('Administrator', 'Teacher', 'Headteacher') NOT NULL,
    createdAt DATE NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT TRUE
);

INSERT INTO Staff (fullName, email, passwordHash, role, createdAt, isActive)
VALUES
  ('Sujan Ghimire',   'sujan@school.com',  SHA2('password1',256), 'Administrator', '2024-09-01', TRUE),
  ('Susma Pandey',     'susma@school.com',    SHA2('password2',256), 'Teacher',       '2024-09-01', TRUE),
  ('Laxman Giri',   'laxman@school.com',  SHA2('password3',256), 'Headteacher',   '2024-09-01', TRUE),
  ('Saimon Hasan',   'saimon@school.com',  SHA2('password4',256), 'Teacher',       '2024-09-02', TRUE),
  ('Dibya Roshni Shau',    'dibya@school.com',    SHA2('password5',256), 'Teacher',       '2024-09-02', FALSE);
