USE BikeRental;
GO

-- Create BusinessInfo table if it doesn't exist
IF OBJECT_ID('dbo.BusinessInfo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.BusinessInfo (
        BusinessInfo_ID INT IDENTITY(1,1) PRIMARY KEY,
        BusinessName NVARCHAR(200) NOT NULL DEFAULT '',
        Address NVARCHAR(500) NOT NULL DEFAULT '',
        Phone NVARCHAR(50) NOT NULL DEFAULT '',
        Email NVARCHAR(150) NOT NULL DEFAULT '',
        Website NVARCHAR(200) NOT NULL DEFAULT '',
        TIN NVARCHAR(50) NOT NULL DEFAULT '',
        WeekdaysOpen TIME NULL,
        WeekdaysClose TIME NULL,
        SaturdayOpen TIME NULL,
        SaturdayClose TIME NULL,
        SundayOpen TIME NULL,
        SundayClose TIME NULL,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE(),
        UpdatedBy INT NULL
    );
    INSERT INTO dbo.BusinessInfo (BusinessName, Address, Phone, Email, Website, TIN)
    VALUES (N'BikeRental Inc.', N'123 Bike Street, Manila, Metro Manila, Philippines 1000', N'+63 912 345 6789', N'info@bikerental.com', N'www.bikerental.com', N'');
END
GO

-- Get Business Info
IF OBJECT_ID('dbo.sp_GetBusinessInfo', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetBusinessInfo;
GO
CREATE PROCEDURE dbo.sp_GetBusinessInfo
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 
        BusinessInfo_ID,
        BusinessName,
        Address,
        Phone,
        Email,
        Website,
        TIN,
        WeekdaysOpen,
        WeekdaysClose,
        SaturdayOpen,
        SaturdayClose,
        SundayOpen,
        SundayClose,
        UpdatedAt,
        UpdatedBy
    FROM dbo.BusinessInfo
    ORDER BY BusinessInfo_ID DESC;
END;
GO

-- Upsert Business Info
IF OBJECT_ID('dbo.sp_UpdateBusinessInfo', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateBusinessInfo;
GO
CREATE PROCEDURE dbo.sp_UpdateBusinessInfo
    @BusinessName NVARCHAR(200),
    @Address NVARCHAR(500),
    @Phone NVARCHAR(50),
    @Email NVARCHAR(150),
    @Website NVARCHAR(200),
    @TIN NVARCHAR(50),
    @WeekdaysOpen NVARCHAR(10) = NULL, -- 'HH:MM'
    @WeekdaysClose NVARCHAR(10) = NULL,
    @SaturdayOpen NVARCHAR(10) = NULL,
    @SaturdayClose NVARCHAR(10) = NULL,
    @SundayOpen NVARCHAR(10) = NULL,
    @SundayClose NVARCHAR(10) = NULL,
    @UpdatedBy INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = BusinessInfo_ID FROM dbo.BusinessInfo ORDER BY BusinessInfo_ID DESC;

    DECLARE 
        @wOpen TIME = TRY_CONVERT(TIME, @WeekdaysOpen),
        @wClose TIME = TRY_CONVERT(TIME, @WeekdaysClose),
        @sOpen TIME = TRY_CONVERT(TIME, @SaturdayOpen),
        @sClose TIME = TRY_CONVERT(TIME, @SaturdayClose),
        @suOpen TIME = TRY_CONVERT(TIME, @SundayOpen),
        @suClose TIME = TRY_CONVERT(TIME, @SundayClose);

    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.BusinessInfo (
            BusinessName, Address, Phone, Email, Website, TIN,
            WeekdaysOpen, WeekdaysClose, SaturdayOpen, SaturdayClose,
            SundayOpen, SundayClose, UpdatedAt, UpdatedBy
        )
        VALUES (
            @BusinessName, @Address, @Phone, @Email, @Website, @TIN,
            @wOpen, @wClose, @sOpen, @sClose,
            @suOpen, @suClose, GETDATE(), @UpdatedBy
        );
    END
    ELSE
    BEGIN
        UPDATE dbo.BusinessInfo
        SET BusinessName = @BusinessName,
            Address = @Address,
            Phone = @Phone,
            Email = @Email,
            Website = @Website,
            TIN = @TIN,
            WeekdaysOpen = @wOpen,
            WeekdaysClose = @wClose,
            SaturdayOpen = @sOpen,
            SaturdayClose = @sClose,
            SundayOpen = @suOpen,
            SundayClose = @suClose,
            UpdatedAt = GETDATE(),
            UpdatedBy = @UpdatedBy
        WHERE BusinessInfo_ID = @Id;
    END
END;
GO
