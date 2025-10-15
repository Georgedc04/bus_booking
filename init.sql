-- ======================================================
-- DATABASE: Online Bus Ticket Booking System
-- AUTHOR: George Kasmiro Quiriko
-- PURPOSE: DBMS Major Project (Bus Reservation System)
-- ======================================================

DROP DATABASE IF EXISTS bus_booking;
CREATE DATABASE bus_booking;
USE bus_booking;

-- ======================================================
-- TABLE: USERS
-- ======================================================
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ======================================================
-- TABLE: OPERATORS
-- ======================================================
CREATE TABLE operators (
  operator_id INT AUTO_INCREMENT PRIMARY KEY,
  operator_name VARCHAR(100) NOT NULL,
  contact_email VARCHAR(150),
  contact_phone VARCHAR(20),
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- TABLE: BUSES
-- ======================================================
CREATE TABLE buses (
  bus_id INT AUTO_INCREMENT PRIMARY KEY,
  operator_id INT NOT NULL,
  bus_number VARCHAR(50) UNIQUE NOT NULL,
  bus_type ENUM('AC','Non-AC','Sleeper','Semi-Sleeper') DEFAULT 'Non-AC',
  total_seats INT NOT NULL CHECK(total_seats > 0),
  fare DECIMAL(10,2) NOT NULL CHECK(fare > 0),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE
);

-- ======================================================
-- TABLE: ROUTES
-- ======================================================
CREATE TABLE routes (
  route_id INT AUTO_INCREMENT PRIMARY KEY,
  source VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  distance_km DECIMAL(10,2),
  estimated_time VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- TABLE: SCHEDULES
-- ======================================================
CREATE TABLE schedules (
  schedule_id INT AUTO_INCREMENT PRIMARY KEY,
  bus_id INT NOT NULL,
  route_id INT NOT NULL,
  departure_time DATETIME NOT NULL,
  arrival_time DATETIME NOT NULL,
  available_seats INT NOT NULL CHECK(available_seats >= 0),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bus_id) REFERENCES buses(bus_id) ON DELETE CASCADE,
  FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE
);

-- ======================================================
-- TABLE: BOOKINGS
-- ======================================================
CREATE TABLE bookings (
  booking_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  schedule_id INT NOT NULL,
  seats_booked INT NOT NULL CHECK(seats_booked > 0),
  booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('Booked','Cancelled','Completed') DEFAULT 'Booked',
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE
);

-- ======================================================
-- TABLE: PAYMENTS
-- ======================================================
CREATE TABLE payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  payment_mode ENUM('Credit Card','Debit Card','UPI','Net Banking','Wallet') NOT NULL,
  payment_status ENUM('Pending','Success','Failed') DEFAULT 'Pending',
  payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- ======================================================
-- TABLE: FEEDBACK
-- ======================================================
CREATE TABLE feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  bus_id INT,
  rating INT CHECK(rating BETWEEN 1 AND 5),
  comments TEXT,
  feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (bus_id) REFERENCES buses(bus_id) ON DELETE SET NULL
);

-- ======================================================
-- TABLE: AUDIT LOGS
-- ======================================================
CREATE TABLE audit_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(255),
  user_id INT,
  log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ======================================================
-- STORED PROCEDURE: ADD BOOKING
-- ======================================================
DELIMITER //
CREATE PROCEDURE add_booking(
    IN p_user_id INT,
    IN p_schedule_id INT,
    IN p_seats INT
)
BEGIN
    DECLARE available INT;
    DECLARE seat_price DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);

    SELECT available_seats INTO available FROM schedules WHERE schedule_id = p_schedule_id;
    SELECT fare INTO seat_price 
    FROM buses b
    JOIN schedules s ON s.bus_id = b.bus_id
    WHERE s.schedule_id = p_schedule_id;

    IF available >= p_seats THEN
        SET total = seat_price * p_seats;

        INSERT INTO bookings (user_id, schedule_id, seats_booked, total_amount)
        VALUES (p_user_id, p_schedule_id, p_seats, total);

        UPDATE schedules SET available_seats = available_seats - p_seats WHERE schedule_id = p_schedule_id;

        INSERT INTO audit_logs (action, user_id)
        VALUES (CONCAT('Booking made for Schedule ', p_schedule_id), p_user_id);
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Not enough seats available';
    END IF;
END//
DELIMITER ;

-- ======================================================
-- TRIGGERS
-- ======================================================
DELIMITER //
CREATE TRIGGER trg_user_delete
AFTER DELETE ON users
FOR EACH ROW
BEGIN
  INSERT INTO audit_logs (action, user_id)
  VALUES (CONCAT('User ', OLD.full_name, ' deleted'), NULL);
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER trg_cancel_booking
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
  IF NEW.status = 'Cancelled' THEN
    UPDATE schedules
    SET available_seats = available_seats + OLD.seats_booked
    WHERE schedule_id = OLD.schedule_id;

    INSERT INTO audit_logs (action, user_id)
    VALUES (CONCAT('Booking ', OLD.booking_id, ' cancelled'), OLD.user_id);
  END IF;
END//
DELIMITER ;

-- ======================================================
-- FUNCTION
-- ======================================================
DELIMITER //
CREATE FUNCTION total_revenue()
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
  DECLARE total DECIMAL(15,2);
  SELECT IFNULL(SUM(total_amount),0) INTO total FROM bookings WHERE status='Booked';
  RETURN total;
END//
DELIMITER ;

-- ======================================================
-- VIEW
-- ======================================================
CREATE VIEW view_booking_summary AS
SELECT 
  b.booking_id,
  u.full_name AS customer,
  r.source, r.destination,
  s.departure_time, s.arrival_time,
  b.seats_booked, b.total_amount, b.status
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN schedules s ON b.schedule_id = s.schedule_id
JOIN routes r ON s.route_id = r.route_id;

-- ======================================================
-- SAMPLE DATA INSERTS
-- ======================================================

-- ✅ Admin password is now hashed for login.php compatibility
INSERT INTO users (full_name, email, phone, password, is_admin) VALUES
('Admin', 'admin@bus.com', '9999999999', '$2y$10$KzM8Yj4ZoqNgF4XK.DY9DuXAi7QqZpA1EhnUuqAzRLoV2FmjL/BlS', 1),
('George', 'george@mail.com', '9876543210', '$2y$10$Eyzr1tR52RyCjKX8sajyT.N17y4Q9IBcNdtRZBp6bWhw5iFJ.1B7m', 0);

INSERT INTO operators (operator_name, contact_email, contact_phone, address) VALUES
('GreenLine Travels', 'info@greenline.com', '9123456789', 'Dar es Salaam, Tanzania'),
('Royal Safari Ltd', 'contact@royalsafari.co.tz', '9876543211', 'Arusha, Tanzania');

-- 🗺️ ROUTES
INSERT INTO routes (source, destination, distance_km, estimated_time) VALUES
('Dar es Salaam', 'Dodoma', 465, '7 hrs'),
('Dar es Salaam', 'Mwanza', 1140, '17 hrs'),
('Dar es Salaam', 'Arusha', 650, '10 hrs'),
('Dodoma', 'Arusha', 420, '6 hrs'),
('Mwanza', 'Kigoma', 720, '11 hrs'),
('Mbeya', 'Iringa', 325, '5 hrs'),
('Arusha', 'Moshi', 85, '2 hrs'),
('Moshi', 'Tanga', 340, '6 hrs'),
('Tanga', 'Dar es Salaam', 350, '7 hrs'),
('Morogoro', 'Iringa', 320, '5 hrs'),
('Morogoro', 'Dodoma', 260, '4 hrs'),
('Kigoma', 'Tabora', 280, '5 hrs'),
('Tabora', 'Dodoma', 480, '8 hrs'),
('Iringa', 'Mbeya', 325, '5 hrs'),
('Dar es Salaam', 'Morogoro', 195, '3 hrs');

-- 🚌 BUSES
INSERT INTO buses (operator_id, bus_number, bus_type, total_seats, fare) VALUES
(1, 'T101', 'AC', 45, 35000),
(1, 'T102', 'Non-AC', 40, 28000),
(2, 'T103', 'Sleeper', 50, 45000),
(2, 'T104', 'Semi-Sleeper', 42, 40000),
(1, 'T105', 'AC', 36, 30000),
(1, 'T106', 'Sleeper', 48, 42000),
(2, 'T107', 'Non-AC', 44, 38000),
(2, 'T108', 'AC', 46, 45000),
(1, 'T109', 'Non-AC', 40, 25000),
(1, 'T110', 'AC', 38, 27000);

-- 📅 SCHEDULES
INSERT INTO schedules (bus_id, route_id, departure_time, arrival_time, available_seats) VALUES
(1, 1, '2025-10-16 07:00:00', '2025-10-16 14:00:00', 45),
(2, 2, '2025-10-16 06:30:00', '2025-10-16 20:30:00', 40),
(3, 3, '2025-10-17 08:00:00', '2025-10-17 16:00:00', 50),
(4, 4, '2025-10-17 09:00:00', '2025-10-17 15:00:00', 42),
(5, 5, '2025-10-17 05:30:00', '2025-10-17 13:30:00', 36),
(6, 6, '2025-10-18 06:00:00', '2025-10-18 11:00:00', 48),
(7, 7, '2025-10-18 08:00:00', '2025-10-18 09:45:00', 44),
(8, 8, '2025-10-18 09:00:00', '2025-10-18 15:00:00', 46),
(9, 9, '2025-10-19 07:30:00', '2025-10-19 14:30:00', 40),
(10, 10, '2025-10-19 06:00:00', '2025-10-19 12:00:00', 38);

-- Test booking
CALL add_booking(2, 1, 2);

-- ======================================================
-- DEMO QUERIES
-- ======================================================
SELECT * FROM view_booking_summary;
SELECT total_revenue() AS total_income;
SELECT * FROM audit_logs ORDER BY log_time DESC;
