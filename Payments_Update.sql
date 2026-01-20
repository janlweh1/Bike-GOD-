USE BikeRental;
GO

IF OBJECT_ID('dbo.Payments', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Payments (
        Payment_ID INT IDENTITY(1,1) PRIMARY KEY,
        transaction_id NVARCHAR(50) NOT NULL,
        rental_id INT NOT NULL,
        member_id INT NULL,
        payment_method NVARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status NVARCHAR(20) NOT NULL,
        payment_date DATETIME NOT NULL,
        notes NVARCHAR(500) NULL,
        CreatedAt DATETIME NOT NULL DEFAULT GETDATE()
    );
    -- Optional FKs (comment out if schema differs)
    -- ALTER TABLE dbo.Payments ADD CONSTRAINT FK_Payments_Rentals FOREIGN KEY (rental_id) REFERENCES dbo.Rentals(Rental_ID);
    -- ALTER TABLE dbo.Payments ADD CONSTRAINT FK_Payments_Member FOREIGN KEY (member_id) REFERENCES dbo.Member(Member_ID);
END
GO
