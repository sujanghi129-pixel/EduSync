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

INSERT INTO tblStudent(1,"Rajan Acharya","2010-05-15",14,TRUE,1,1);
INSERT INTO tblStudent(2,"Sita Thapa","2012-08-20",12,TRUE,2,2);
INSERT INTO tblStudent(3,"Hari Sharma","2009-11-30",15,TRUE,3,3);
INSERT INTO tblStudent(4,"Gita Rai","2011-03-10",13,TRUE,4,4);
INSERT INTO tblStudent(5,"Bikash Karki","2013-01-25",11,TRUE,5,5);
