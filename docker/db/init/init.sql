-- TABELE

CREATE TABLE users (
                       id SERIAL PRIMARY KEY,
                       firstname VARCHAR(100) NOT NULL,
                       lastname VARCHAR(100) NOT NULL,
                       email VARCHAR(150) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       balance INTEGER NOT NULL DEFAULT 1000,
                       role VARCHAR(20) NOT NULL DEFAULT 'user',
                       enabled BOOLEAN DEFAULT TRUE,
                       created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE earnings (
                          user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
                          last_claimed TIMESTAMP NULL
);

CREATE TABLE upgrades (
                           id SERIAL PRIMARY KEY,
                           title VARCHAR(100) NOT NULL,
                           description TEXT NOT NULL,
                           base_cost INTEGER NOT NULL,
                           max_level INTEGER NOT NULL
);

CREATE TABLE user_upgrades (
                               user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                               upgrade_id INTEGER NOT NULL REFERENCES upgrades(id) ON DELETE CASCADE,
                               level INTEGER NOT NULL DEFAULT 0,
                               PRIMARY KEY (user_id, upgrade_id)
);

CREATE TABLE roulette_games (
                                id SERIAL PRIMARY KEY,
                                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                total_bet INTEGER NOT NULL DEFAULT 0,
                                payout INTEGER NOT NULL DEFAULT 0,
                                result_number INTEGER NOT NULL,
                                result_color VARCHAR(10) NOT NULL,
                                created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE login_attempts (
                                id SERIAL PRIMARY KEY,
                                email VARCHAR(150),
                                ip_address VARCHAR(45) NOT NULL,
                                success BOOLEAN NOT NULL DEFAULT FALSE,
                                attempted_at TIMESTAMP NOT NULL DEFAULT NOW(),
                                blocked_until TIMESTAMP NULL
);


-- FUNKCJE I TRIGGERS
-- Funkcja do ustawiania created_at przy wstawianiu nowego użytkownika
CREATE OR REPLACE FUNCTION set_user_created_at() RETURNS trigger AS $$
BEGIN
    IF NEW.created_at IS NULL THEN
        NEW.created_at = NOW();
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Funkcja do tworzenia rekordu earnings dla nowego użytkownika
CREATE OR REPLACE FUNCTION create_user_earnings() RETURNS trigger AS $$
BEGIN
    INSERT INTO earnings (user_id, last_claimed) VALUES (NEW.id, NULL);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger wywołujący funkcję przed wstawieniem rekordu do tabeli users
CREATE TRIGGER trg_users_created_at
BEFORE INSERT ON users
FOR EACH ROW
EXECUTE FUNCTION set_user_created_at();

-- Trigger wywołujący tworzenie rekordu earnings po utworzeniu użytkownika
CREATE TRIGGER trg_users_earnings
AFTER INSERT ON users
FOR EACH ROW
EXECUTE FUNCTION create_user_earnings();

-- Funkcja obliczająca łączny koszt ulepszeń użytkownika
CREATE OR REPLACE FUNCTION total_upgrades_cost(p_user_id INT) RETURNS INT AS $$
    SELECT COALESCE(SUM(u.base_cost * (uu.level * (uu.level + 1) / 2)), 0)
    FROM user_upgrades uu
    JOIN upgrades u ON u.id = uu.upgrade_id
    WHERE uu.user_id = p_user_id;
$$ LANGUAGE SQL;

-- WIDOKI
-- COALESCE użyte, aby uniknąć wartości NULL w wynikach agregacji
CREATE VIEW v_user_game_stats AS
SELECT
    user_id,
    COUNT(*) AS total_games,
    COALESCE(SUM(total_bet), 0) AS total_bet,
    COALESCE(SUM(payout), 0) AS total_payout,
    COALESCE(SUM(payout - total_bet), 0) AS total_net,
    COALESCE(SUM(CASE WHEN (payout - total_bet) > 0 THEN 1 ELSE 0 END), 0) AS wins,
    COALESCE(SUM(CASE WHEN (payout - total_bet) < 0 THEN 1 ELSE 0 END), 0) AS losses,
    COALESCE(SUM(CASE WHEN result_color = 'green' THEN 1 ELSE 0 END), 0) AS green,
    COALESCE(SUM(CASE WHEN result_color = 'red' THEN 1 ELSE 0 END), 0) AS red,
    COALESCE(SUM(CASE WHEN result_color = 'black' THEN 1 ELSE 0 END), 0) AS black,
    COALESCE(MAX(CASE WHEN (payout - total_bet) > 0 THEN (payout - total_bet) ELSE NULL END), 0) AS highest_win,
    COALESCE(MIN(CASE WHEN (payout - total_bet) < 0 THEN (payout - total_bet) ELSE NULL END), 0) AS highest_loss
FROM roulette_games
GROUP BY user_id;

CREATE VIEW v_login_attempt_stats AS
SELECT
    email,
    ip_address,
    COUNT(*) FILTER (WHERE success = FALSE AND attempted_at > NOW() - INTERVAL '1 hour') AS failed_last_hour,
    MAX(attempted_at) AS last_attempt_at,
    MAX(blocked_until) AS blocked_until
FROM login_attempts
GROUP BY email, ip_address;

CREATE VIEW v_login_attempts_recent AS
SELECT id, email, ip_address, success, attempted_at, blocked_until
FROM login_attempts
ORDER BY attempted_at DESC;





-- POCZĄTKOWE DANE
INSERT INTO users (firstname, lastname, email, password, balance, role, enabled)
VALUES (
    'Test',
    'User',
    'test.user@example.com',
    '$2y$10$.0Coc4MVIUF5p3MM3y4nje1HvGQ/sBEAWzByCrMafjX5CfErG5Rsy',
    300,
    'user',
    TRUE
);
INSERT INTO users (firstname, lastname, email, password, balance, role, enabled)
VALUES (
    'Admin',
    'User',
    'admin@example.com',
    '$2y$10$.0Coc4MVIUF5p3MM3y4nje1HvGQ/sBEAWzByCrMafjX5CfErG5Rsy',
    500000,
    'admin',
    TRUE
);

INSERT INTO upgrades (id, title, description, base_cost, max_level) VALUES
    (1, 'Additional 7', '2x 7 chances', 20, 5),
    (2, 'Black Multiplier', '+0.2x multiplier', 100, 5),
    (3, 'Red Multiplier', '+0.2x multiplier', 100, 5),
    (4, 'Green Multiplier', '+1 multiplier', 100, 5),
    (5, 'Lucky Green', '2x green chance', 1, 50),
    (6, 'Refund', '1% refund chance', 250, 5),
    (7, 'More Money2', '+0.1x more money', 500, 10);


