USE BikeRental;
GO

IF OBJECT_ID('dbo.PricingSettings', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.PricingSettings (
        Settings_ID INT PRIMARY KEY IDENTITY(1,1),
        LateFeePerDay DECIMAL(10,2) NOT NULL DEFAULT 0,
        DamageFeeMin DECIMAL(10,2) NOT NULL DEFAULT 0,
        SecurityDeposit DECIMAL(10,2) NOT NULL DEFAULT 0,
        TaxInclusive BIT NOT NULL DEFAULT 1,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE()
    );
    INSERT INTO dbo.PricingSettings (LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive)
    VALUES (200, 1000, 2000, 1);
END
GO

IF OBJECT_ID('dbo.sp_GetPricingSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetPricingSettings;
GO
CREATE PROCEDURE dbo.sp_GetPricingSettings
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 Settings_ID, LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive, UpdatedAt
    FROM dbo.PricingSettings
    ORDER BY Settings_ID DESC;
END;
GO

IF OBJECT_ID('dbo.sp_UpdatePricingSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdatePricingSettings;
GO
CREATE PROCEDURE dbo.sp_UpdatePricingSettings
    @LateFeePerDay DECIMAL(10,2),
    @DamageFeeMin DECIMAL(10,2),
    @SecurityDeposit DECIMAL(10,2),
    @TaxInclusive BIT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = Settings_ID FROM dbo.PricingSettings ORDER BY Settings_ID DESC;
    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.PricingSettings (LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive)
        VALUES (@LateFeePerDay, @DamageFeeMin, @SecurityDeposit, @TaxInclusive);
    END
    ELSE
    BEGIN
        UPDATE dbo.PricingSettings
        SET LateFeePerDay = @LateFeePerDay,
            DamageFeeMin = @DamageFeeMin,
            SecurityDeposit = @SecurityDeposit,
            TaxInclusive = @TaxInclusive,
            UpdatedAt = GETDATE()
        WHERE Settings_ID = @Id;
    END
END;
GO
