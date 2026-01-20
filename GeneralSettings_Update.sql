USE BikeRental;
GO

-- Create GeneralSettings table if it doesn't exist
IF OBJECT_ID('dbo.GeneralSettings', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.GeneralSettings (
        Settings_ID INT IDENTITY(1,1) PRIMARY KEY,
        SystemName NVARCHAR(200) NOT NULL DEFAULT N'BikeRental Management System',
        Language NVARCHAR(20) NOT NULL DEFAULT N'en',
        Timezone NVARCHAR(50) NOT NULL DEFAULT N'asia/manila',
        DateFormat NVARCHAR(20) NOT NULL DEFAULT N'dd/mm/yyyy',
        Currency NVARCHAR(10) NOT NULL DEFAULT N'php',
        RentalMinPeriod INT NOT NULL DEFAULT 1,
        RentalMaxDays INT NOT NULL DEFAULT 30,
        AutoLate BIT NOT NULL DEFAULT 1,
        RequireDeposit BIT NOT NULL DEFAULT 1,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE(),
        UpdatedBy INT NULL
    );
    INSERT INTO dbo.GeneralSettings (SystemName, Language, Timezone, DateFormat, Currency)
    VALUES (N'BikeRental Management System', N'en', N'asia/manila', N'dd/mm/yyyy', N'php');
END
GO

-- Get General Settings
IF OBJECT_ID('dbo.sp_GetGeneralSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetGeneralSettings;
GO
CREATE PROCEDURE dbo.sp_GetGeneralSettings
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 
        Settings_ID,
        SystemName,
        Language,
        Timezone,
        DateFormat,
        Currency,
        RentalMinPeriod,
        RentalMaxDays,
        AutoLate,
        RequireDeposit,
        UpdatedAt,
        UpdatedBy
    FROM dbo.GeneralSettings
    ORDER BY Settings_ID DESC;
END;
GO

-- Update General Settings
IF OBJECT_ID('dbo.sp_UpdateGeneralSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateGeneralSettings;
GO
CREATE PROCEDURE dbo.sp_UpdateGeneralSettings
    @SystemName NVARCHAR(200),
    @Language NVARCHAR(20),
    @Timezone NVARCHAR(50),
    @DateFormat NVARCHAR(20),
    @Currency NVARCHAR(10),
    @RentalMinPeriod INT,
    @RentalMaxDays INT,
    @AutoLate BIT,
    @RequireDeposit BIT,
    @UpdatedBy INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = Settings_ID FROM dbo.GeneralSettings ORDER BY Settings_ID DESC;
    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.GeneralSettings (
            SystemName, Language, Timezone, DateFormat, Currency,
            RentalMinPeriod, RentalMaxDays, AutoLate, RequireDeposit,
            UpdatedAt, UpdatedBy
        )
        VALUES (
            @SystemName, @Language, @Timezone, @DateFormat, @Currency,
            @RentalMinPeriod, @RentalMaxDays, @AutoLate, @RequireDeposit,
            GETDATE(), @UpdatedBy
        );
    END
    ELSE
    BEGIN
        UPDATE dbo.GeneralSettings
        SET SystemName = @SystemName,
            Language = @Language,
            Timezone = @Timezone,
            DateFormat = @DateFormat,
            Currency = @Currency,
            RentalMinPeriod = @RentalMinPeriod,
            RentalMaxDays = @RentalMaxDays,
            AutoLate = @AutoLate,
            RequireDeposit = @RequireDeposit,
            UpdatedAt = GETDATE(),
            UpdatedBy = @UpdatedBy
        WHERE Settings_ID = @Id;
    END
END;
GO
