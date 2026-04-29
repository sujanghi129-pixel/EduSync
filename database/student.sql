CREATE TABLE tblStudent (
    StudentId     INT          NOT NULL AUTO_INCREMENT,
    FullName      VARCHAR(100) NOT NULL,
    DateOfBirth   DATE         NOT NULL,
    Age           INT          NOT NULL CHECK (Age >= 4 AND Age <= 18),
    IsEnrolled    BOOLEAN      NOT NULL DEFAULT TRUE,
    GradeID       INT,
    ClassID       INT,

    CONSTRAINT PK_tblStudent     PRIMARY KEY (StudentId),
    CONSTRAINT FK_Student_Grade  FOREIGN KEY (GradeID) REFERENCES tblGrade(GradeID),
    CONSTRAINT FK_Student_Class  FOREIGN KEY (ClassID) REFERENCES tblClass(ClassID)
);

