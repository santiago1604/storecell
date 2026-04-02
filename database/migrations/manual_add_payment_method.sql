-- Ejecutar este SQL en la base de datos de tu hosting
-- Agrega la columna payment_method a la tabla cash_movements

-- Nota: La columna `note` no existe en la base remota segun el error 1054.
-- Usamos ADD COLUMN simple al final de la tabla.
ALTER TABLE `cash_movements`
	ADD COLUMN `payment_method` VARCHAR(20) NULL AFTER `description`;

-- Índice opcional para futuras búsquedas por método de pago
CREATE INDEX `idx_cash_movements_payment_method`
	ON `cash_movements`(`payment_method`);
