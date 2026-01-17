CREATE TABLE users (
                       id SERIAL PRIMARY KEY,
                       firstname VARCHAR(100) NOT NULL,
                       lastname VARCHAR(100) NOT NULL,
                       email VARCHAR(150) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       role VARCHAR(20) NOT NULL DEFAULT 'user',
                       bio TEXT,
                       enabled BOOLEAN DEFAULT TRUE
);

INSERT INTO users (firstname, lastname, email, password, role, bio, enabled)
VALUES (
    'Test',
    'User',
    'test.user@example.com',
    '$2y$10$HMSh7s6x2P1nRMBKtgwYoe2z4OXbNURQhRpVjZwt6EXOVliuTIYIS',
    'user',
    'Użytkownik testowy do logowania.',
    TRUE
);
INSERT INTO users (firstname, lastname, email, password, role, bio, enabled)
VALUES (
    'Admin',
    'User',
    'admin@example.com',
    '$2y$10$HMSh7s6x2P1nRMBKtgwYoe2z4OXbNURQhRpVjZwt6EXOVliuTIYIS',
    'admin',
    'Konto administratora.',
    TRUE
);

