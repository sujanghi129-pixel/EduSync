CREATE TABLE Grade (
    gradeID INT NOT NULL AUTO_INCREMENT,
    gradeName VARCHAR(20) NOT NULL,
    PRIMARY KEY (gradeID)
);

INSERT INTO Grade (gradeName) VALUES
('Year 1'),
('Year 2'),
('Year 3'),
('Year 4'),
('Year 5');


CREATE TABLE Staff (
    staffId INT NOT NULL AUTO_INCREMENT,
    fullName VARCHAR(100) NOT NULL,
    PRIMARY KEY (staffId)
);

INSERT INTO Staff (fullName) VALUES
('saimon'),
('luxman'),
('susan'),
('dibia');

CREATE TABLE Class (
    classID INT NOT NULL AUTO_INCREMENT,
    className VARCHAR(50) NOT NULL,
    classCode VARCHAR(10) NOT NULL UNIQUE,
    gradeID INT NOT NULL,
    staffID INT NOT NULL,
    capacity INT NOT NULL CHECK (capacity > 0),
    isActive BOOLEAN NOT NULL DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (classID),
    CONSTRAINT fk_class_staff
        FOREIGN KEY (staffID) REFERENCES Staff(staffId),
    CONSTRAINT fk_class_grade
        FOREIGN KEY (gradeID) REFERENCES Grade(gradeID)
);

INSERT INTO Class (className, classCode, gradeID, staffID, capacity, isActive)
VALUES
('Year 1 - A', 'Y1A', 1, 1, 30, TRUE),
('Year 2 - A', 'Y2A', 2, 2, 28, TRUE),
('Year 3 - A', 'Y3A', 3, 3, 32, TRUE),
('Year 4 - A', 'Y4A', 4, 4, 25, TRUE),
('Year 5 - A', 'Y5A', 5, 1, 20, FALSE);