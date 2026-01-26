-- TABELE

CREATE TABLE users (
                       id SERIAL PRIMARY KEY,
                       firstname VARCHAR(100) NOT NULL,
                       lastname VARCHAR(100) NOT NULL,
                       email VARCHAR(150) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       balance INTEGER NOT NULL DEFAULT 1000,
                       role VARCHAR(20) NOT NULL DEFAULT 'user',
                       bio TEXT,
                       enabled BOOLEAN DEFAULT TRUE
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

-- POCZĄTKOWE DANE
INSERT INTO users (firstname, lastname, email, password, balance, role, bio, enabled)
VALUES (
    'Test',
    'User',
    'test.user@example.com',
    '$2y$10$HMSh7s6x2P1nRMBKtgwYoe2z4OXbNURQhRpVjZwt6EXOVliuTIYIS',
    1000,
    'user',
    'Użytkownik testowy do logowania.',
    TRUE
);
INSERT INTO users (firstname, lastname, email, password, balance, role, bio, enabled)
VALUES (
    'Admin',
    'User',
    'admin@example.com',
    '$2y$10$HMSh7s6x2P1nRMBKtgwYoe2z4OXbNURQhRpVjZwt6EXOVliuTIYIS',
    500000,
    'admin',
    'Konto administratora.',
    TRUE
);

INSERT INTO upgrades (id, title, description, base_cost, max_level) VALUES
    (1, 'Additional 7', '2x 7 chances', 20, 5),
    (2, 'Black Multiplier', '+0.2x multiplier', 100, 5),
    (3, 'Red Multiplier', '+0.2x multiplier', 100, 5),
    (4, 'Green Multiplier', '2x multiplier', 100, 5),
    (5, 'Lucky Green', '2x green chance', 75, 4),
    (6, 'Refund', '1% refund chance', 250, 5),
    (7, 'More Money', '+0.1x more money', 500, 10);

