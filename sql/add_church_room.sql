-- ============================================
-- SQL Script: Add Church Room with Equipment
-- CDM Media Request System
-- ============================================

-- 1. Insert the Church room
INSERT INTO `rooms` (`name`, `is_active`, `notes`)
VALUES ('Church', 1, 'Main church sanctuary');

-- Get the Church room ID (for use in subsequent inserts)
SET @church_room_id = LAST_INSERT_ID();

-- 2. Insert new equipment items (skip existing ones like Wireless Microphone, Projector)
INSERT INTO `equipment` (`name`, `category`, `is_active`) VALUES
-- Microphones
('Choir Mic (Wired)', 'Audio', 1),
('Stage Mic', 'Audio', 1),
('Commentator Mic', 'Audio', 1),
('Lector Mic', 'Audio', 1),
('Altar Mic', 'Audio', 1),
('Ambo Mic', 'Audio', 1),
-- Instruments
('Piano', 'Instruments', 1),
('Organ', 'Instruments', 1),
('Drums', 'Instruments', 1),
('Amplifier', 'Instruments', 1),
-- Audio System
('Audio System', 'Audio', 1),
('Projection System', 'Video', 1);

-- 3. Link equipment to Church room

-- Cordless/Wireless Mics (2x) - use existing "Wireless Microphone"
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 2, 'Cordless microphones'
FROM `equipment` WHERE `name` = 'Wireless Microphone';

-- Projection System
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, 'Church projection system'
FROM `equipment` WHERE `name` = 'Projection System';

-- Audio System
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, 'Main church audio system'
FROM `equipment` WHERE `name` = 'Audio System';

-- Choir Mics Wired (16x)
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 16, 'Wired choir microphones'
FROM `equipment` WHERE `name` = 'Choir Mic (Wired)';

-- Stage Mics (2x)
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 2, 'Stage microphones'
FROM `equipment` WHERE `name` = 'Stage Mic';

-- Commentator Mic
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Commentator Mic';

-- Lector Mic
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Lector Mic';

-- Altar Mic
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Altar Mic';

-- Ambo Mic
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Ambo Mic';

-- Piano
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Piano';

-- Organ
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Organ';

-- Drums
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 1, NULL
FROM `equipment` WHERE `name` = 'Drums';

-- Amplifiers (3x)
INSERT INTO `room_equipment` (`room_id`, `equipment_id`, `quantity`, `notes`)
SELECT @church_room_id, id, 3, 'Guitar/instrument amplifiers'
FROM `equipment` WHERE `name` = 'Amplifier';

-- ============================================
-- Verification Query
-- ============================================
-- Run this to verify the Church room and its equipment:
SELECT r.name AS room_name, e.name AS equipment_name, e.category, re.quantity, re.notes
FROM rooms r
JOIN room_equipment re ON r.id = re.room_id
JOIN equipment e ON re.equipment_id = e.id
WHERE r.name = 'Church'
ORDER BY e.category, e.name;
