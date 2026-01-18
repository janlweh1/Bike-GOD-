-- Apply these procedures to your BikeRental database without dropping it
USE BikeRental;
GO

-- Get average hourly rates by bike type
IF OBJECT_ID('dbo.sp_GetRatesByType', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRatesByType;
GO
CREATE PROCEDURE dbo.sp_GetRatesByType
AS
BEGIN
    SET NOCOUNT ON;
    SELECT bike_type, AVG(hourly_rate) AS rate
    FROM Bike
    GROUP BY bike_type;
END;
GO

-- Update hourly rate for all bikes of a given type
IF OBJECT_ID('dbo.sp_UpdateRateByType', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateRateByType;
GO
CREATE PROCEDURE dbo.sp_UpdateRateByType
    @BikeType NVARCHAR(50),
    @Rate DECIMAL(10,2)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Bike
    SET hourly_rate = @Rate
    WHERE bike_type = @BikeType;
END;
GO
