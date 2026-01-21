-- Migration: add planned return time to Rentals to compute accurate durations
USE BikeRental;
GO

IF COL_LENGTH('Rentals', 'return_time') IS NULL
BEGIN
    ALTER TABLE Rentals ADD return_time TIME NULL;
END
GO
