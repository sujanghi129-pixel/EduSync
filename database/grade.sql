CREATE TABLE Grade (
    gradeID INT PRIMARY KEY AUTO_INCREMENT,
    gradeName VARCHAR(50) NOT NULL UNIQUE,
    gradeCode CHAR(3) NOT NULL UNIQUE,
    minAge INT NOT NULL CHECK (minAge >= 4),
    maxAge INT NOT NULL CHECK (maxAge <= 18),
    isActive BOOLEAN NOT NULL DEFAULT TRUE,
    createdAt DATE NOT NULL DEFAULT CURRENT_DATE
);

INSERT INTO Grade (gradeName, gradeCode, minAge, maxAge, isActive)
VALUES 
('Year 1', 'Y01', 5, 6, TRUE),
('Year 2', 'Y02', 6, 7, TRUE),
('Year 3', 'Y03', 7, 8, TRUE),
('Year 4', 'Y04', 8, 9, TRUE),
('Year 5', 'Y05', 9, 10, TRUE);