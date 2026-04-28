CREATE TABLE Staff (
    staffId INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    role ENUM('Administrator', 'Teacher', 'Headteacher') NOT NULL,
    createdAt DATE NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT TRUE
);
